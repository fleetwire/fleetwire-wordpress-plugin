<?php
namespace Fleetwire;

/**
 * Fleetwire Site Builder Class
 *
 * "Build my rental site": creates draft pages (Home, Our Fleet, FAQ, Contact and
 * one page per chosen vehicle) from the Fleetwire patterns. It never publishes and
 * never overwrites; what it created is recorded so a second run only adds pages for
 * new vehicles.
 *
 * @since 1.1.0
 * @package Fleetwire
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fleetwire Site Builder Class
 *
 * @since 1.1.0
 */
class SiteBuilder {

    /**
     * Option holding what the builder created.
     *
     * @var string
     */
    const OPTION = 'fleetwire_site_builder';

    /**
     * Most vehicle pages one run creates.
     *
     * @var int
     */
    const MAX_VEHICLE_PAGES = 12;

    /**
     * Marks menu items the builder holds back until their page is published.
     *
     * @var string
     */
    const PENDING_MENU_ITEM_META = '_fleetwire_pending_menu_item';

    /**
     * @var Patterns
     */
    private $patterns;

    /**
     * Constructor
     *
     * @since 1.1.0
     * @param Patterns $patterns
     */
    public function __construct( Patterns $patterns ) {
        $this->patterns = $patterns;
        add_action( 'transition_post_status', [ $this, 'apply_pending_front_page' ], 10, 3 );
        add_action( 'transition_post_status', [ $this, 'publish_pending_menu_items' ], 10, 3 );
        add_action( 'wp_ajax_fleetwire_build_site', [ $this, 'ajax_build' ] );
        add_action( 'wp_ajax_fleetwire_publish_site', [ $this, 'ajax_publish' ] );
    }

    /**
     * AJAX: "Publish all draft pages" from the plugin admin page.
     *
     * @since 1.1.0
     * @return void
     */
    public function ajax_publish() {
        $validation = Validator::validate_ajax_request( 'fleetwire_publish_site', 'fw_nonce' );
        if ( ! $validation['valid'] ) {
            Logger::log_ajax_error( 'fleetwire_publish_site', $validation['error'] );
            wp_send_json_error( [ 'message' => $validation['error'] ] );
        }

        if ( ! current_user_can( 'publish_pages' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'fleetwire' ) ] );
        }

        wp_send_json_success( [ 'published' => $this->publish_drafts() ] );
    }

    /**
     * AJAX: "Build my rental site" from the plugin admin page.
     *
     * @since 1.1.0
     * @return void
     */
    public function ajax_build() {
        $validation = Validator::validate_ajax_request( 'fleetwire_build_site', 'fw_nonce' );
        if ( ! $validation['valid'] ) {
            Logger::log_ajax_error( 'fleetwire_build_site', $validation['error'] );
            wp_send_json_error( [ 'message' => $validation['error'] ] );
        }

        if ( ! current_user_can( 'edit_pages' ) ) {
            wp_send_json_error( [ 'message' => __( 'Insufficient permissions.', 'fleetwire' ) ] );
        }

        $listing_ids = array_map( 'sanitize_text_field', array_map( 'wp_unslash', (array) ( $_POST['listings'] ?? [] ) ) );
        $page_keys = array_values( array_intersect(
            array_map( 'sanitize_key', array_map( 'wp_unslash', (array) ( $_POST['pages'] ?? [] ) ) ),
            array_keys( $this->core_pages() )
        ) );
        $summary = $this->build(
            $listing_ids,
            ! empty( $_POST['front_page'] ) && $_POST['front_page'] !== 'false',
            ! empty( $_POST['menu'] ) && $_POST['menu'] !== 'false',
            $page_keys
        );

        if ( is_wp_error( $summary ) ) {
            wp_send_json_error( [ 'message' => $summary->get_error_message() ] );
        }

        wp_send_json_success( $summary );
    }

    /**
     * Pages every site gets, in creation order. Our Fleet comes first so the other
     * pages can link to it.
     *
     * @return array<string, array{title: string, slug: string, sections: string[]}>
     */
    private function core_pages() {
        return [
            'fleet'   => [
                'title'    => __( 'Our Fleet', 'fleetwire' ),
                'slug'     => 'fleet',
                'sections' => [ 'fleet-page' ],
            ],
            'home'    => [
                'title'    => __( 'Home', 'fleetwire' ),
                'slug'     => 'home',
                'sections' => [ 'hero-search', 'fleet-grid', 'how-it-works', 'why-book-direct', 'cta-band' ],
            ],
            'faq'     => [
                'title'    => __( 'FAQ', 'fleetwire' ),
                'slug'     => 'faq',
                'sections' => [ 'faq', 'requirements' ],
            ],
            'contact' => [
                'title'    => __( 'Contact', 'fleetwire' ),
                'slug'     => 'contact',
                'sections' => [ 'contact' ],
            ],
        ];
    }

    /**
     * What earlier runs created.
     *
     * @since 1.1.0
     * @return array{pages: array<string, int>, vehicles: array<string, int>, front_page_pending: bool}
     */
    public function state() {
        $state = get_option( self::OPTION, [] );
        $state = is_array( $state ) ? $state : [];

        return [
            'pages'              => array_map( 'intval', (array) ( $state['pages'] ?? [] ) ),
            'vehicles'           => array_map( 'intval', (array) ( $state['vehicles'] ?? [] ) ),
            'front_page_pending' => ! empty( $state['front_page_pending'] ),
        ];
    }

    /**
     * A page the builder created that still exists (not deleted or trashed).
     *
     * @since 1.1.0
     * @param int $page_id
     * @return \WP_Post|null
     */
    public function existing_page( int $page_id ) {
        $page = $page_id ? get_post( $page_id ) : null;

        return $page && $page->post_type === 'page' && $page->post_status !== 'trash' ? $page : null;
    }

    /**
     * Per core page: the builder's own page if it made one, and the site's published
     * page already using the slug, if any.
     *
     * @since 1.1.0
     * @return array<string, array{title: string, slug: string, ours: \WP_Post|null, taken_by: \WP_Post|null}>
     */
    public function plan() {
        $state = $this->state();
        $plan = [];

        foreach ( $this->core_pages() as $key => $definition ) {
            $ours = $this->existing_page( $state['pages'][ $key ] ?? 0 );
            $plan[ $key ] = [
                'title'    => $definition['title'],
                'slug'     => $definition['slug'],
                'ours'     => $ours,
                'taken_by' => $this->page_with_slug( $definition['slug'], $ours ? $ours->ID : 0 ),
            ];
        }

        return $plan;
    }

    /**
     * Core pages created by default: all free ones, plus Home even when /home/ is
     * taken (the new homepage's slug does not matter and the current one stays live
     * until it is published). Taken Our Fleet, FAQ and Contact slugs mean the site
     * already has that page, so it is reused instead of duplicated as /faq-2/.
     *
     * @since 1.1.0
     * @return string[]
     */
    public function default_page_keys() {
        return array_keys( array_filter( $this->plan(), function ( $page, $key ) {
            return ! $page['taken_by'] || $key === 'home';
        }, ARRAY_FILTER_USE_BOTH ) );
    }

    /**
     * Create the draft pages.
     *
     * @since 1.1.0
     * @param string[]      $listing_ids    Vehicles that get their own page.
     * @param bool          $set_front_page Use Home as the front page once it is published.
     * @param bool          $add_to_menu    Add the core pages to the site navigation.
     * @param string[]|null $page_keys      Core pages to create (home, fleet, faq, contact); null for the defaults.
     * @return array|\WP_Error Summary of what was created, kept or reused.
     */
    public function build( array $listing_ids, bool $set_front_page, bool $add_to_menu, ?array $page_keys = null ) {
        $state = $this->state();
        $listings = [];
        foreach ( Listings::bookable() as $listing ) {
            $listings[ $listing['id'] ] = $listing;
        }

        $summary = [ 'pages' => [], 'vehicles' => [], 'front_page' => null, 'menu' => null ];

        // Core pages: keep ours, create the chosen missing ones, and fall back to the
        // site's own page where the host chose not to create a duplicate.
        $page_keys = $page_keys ?? $this->default_page_keys();
        $plan = $this->plan();
        $fleet_url = null;
        $menu_pages = [];
        foreach ( $this->core_pages() as $key => $definition ) {
            $page = $plan[ $key ]['ours'];

            if ( $page ) {
                $summary['pages'][] = $this->describe( $page, 'kept' );
            } elseif ( in_array( $key, $page_keys, true ) ) {
                $tokens = $this->patterns->tokens( array_values( $listings ), $fleet_url );
                $page = $this->create_page( $definition['title'], $definition['slug'], $definition['sections'], $tokens );
                if ( is_wp_error( $page ) ) {
                    return $page;
                }
                $state['pages'][ $key ] = $page->ID;
                $summary['pages'][] = $this->describe( $page, 'created' );
            } elseif ( $plan[ $key ]['taken_by'] ) {
                $page = $plan[ $key ]['taken_by'];
                $summary['pages'][] = $this->describe( $page, 'existing' );
            }

            if ( $page ) {
                $menu_pages[ $key ] = $page;
                if ( $key === 'fleet' ) {
                    $fleet_url = $this->page_url( $page );
                }
            }
        }

        // Vehicle pages: one per chosen bookable listing that has none yet.
        $listing_ids = array_slice( array_values( array_unique( array_filter( $listing_ids, 'is_string' ) ) ), 0, self::MAX_VEHICLE_PAGES );
        foreach ( $listing_ids as $listing_id ) {
            if ( ! isset( $listings[ $listing_id ] ) || ! preg_match( '/^[A-Za-z0-9_-]+$/', $listing_id ) ) {
                continue;
            }

            $page = $this->existing_page( $state['vehicles'][ $listing_id ] ?? 0 );
            if ( $page ) {
                $summary['vehicles'][] = $this->describe( $page, 'kept' );
                continue;
            }

            $tokens = $this->patterns->tokens( array_values( $listings ), $fleet_url ) + [ '{{LISTING_1}}' => $listing_id ];
            $page = $this->create_page(
                $listings[ $listing_id ]['name'],
                sanitize_title( $listings[ $listing_id ]['name'] ),
                [ 'vehicle-spotlight', 'requirements', 'cta-band' ],
                $tokens
            );
            if ( is_wp_error( $page ) ) {
                return $page;
            }

            $state['vehicles'][ $listing_id ] = $page->ID;
            $summary['vehicles'][] = $this->describe( $page, 'created' );
        }

        $home = $this->existing_page( $state['pages']['home'] ?? 0 );

        if ( $set_front_page && $home ) {
            if ( $home->post_status === 'publish' ) {
                $this->use_as_front_page( $home->ID );
                $summary['front_page'] = 'set';
                $state['front_page_pending'] = false;
            } else {
                // Pointing the live front page at a draft would break the site; wait for publish.
                $state['front_page_pending'] = true;
                $summary['front_page'] = 'pending';
            }
        }

        if ( $add_to_menu ) {
            $ordered = array_filter( array_map( function ( $key ) use ( $menu_pages ) {
                return $menu_pages[ $key ] ?? null;
            }, [ 'home', 'fleet', 'faq', 'contact' ] ) );
            $summary['menu'] = $this->add_to_navigation( $ordered );
        }

        update_option( self::OPTION, $state, false );

        $summary['home_preview_url'] = $home ? get_preview_post_link( $home ) : '';

        return $summary;
    }

    /**
     * Publish every page the builder created that is still a draft. Publishing goes
     * through wp_update_post, so the pending front page and held-back menu items
     * follow their pages.
     *
     * @since 1.1.0
     * @return array[] The pages that were published.
     */
    public function publish_drafts() {
        $state = $this->state();
        $published = [];

        // Our Fleet and vehicle pages first, Home last, so its links resolve as soon as it is live.
        $ids = array_merge(
            array_values( array_diff_key( $state['pages'], [ 'home' => 0 ] ) ),
            array_values( $state['vehicles'] ),
            isset( $state['pages']['home'] ) ? [ $state['pages']['home'] ] : []
        );

        foreach ( $ids as $page_id ) {
            $page = $this->existing_page( (int) $page_id );
            if ( ! $page || $page->post_status !== 'draft' || ! current_user_can( 'publish_post', $page->ID ) ) {
                continue;
            }

            $result = wp_update_post( [ 'ID' => $page->ID, 'post_status' => 'publish' ], true );
            if ( ! is_wp_error( $result ) ) {
                $published[] = $this->describe( get_post( $page->ID ), 'published' );
            }
        }

        return $published;
    }

    /**
     * Builder pages that are still drafts.
     *
     * @since 1.1.0
     * @return int
     */
    public function draft_count() {
        $state = $this->state();

        return count( array_filter( array_merge( $state['pages'], $state['vehicles'] ), function ( $page_id ) {
            $page = $this->existing_page( (int) $page_id );
            return $page && $page->post_status === 'draft';
        } ) );
    }

    /**
     * A published page the site already has at this slug.
     *
     * @param string $slug
     * @param int    $exclude_id The builder's own page.
     * @return \WP_Post|null
     */
    private function page_with_slug( string $slug, int $exclude_id ) {
        $pages = get_posts( [
            'post_type'    => 'page',
            'name'         => $slug,
            'post_status'  => [ 'publish', 'private', 'future' ],
            'numberposts'  => 1,
            'post__not_in' => $exclude_id ? [ $exclude_id ] : [],
        ] );

        return $pages[0] ?? null;
    }

    /**
     * Set Home as the front page when it gets published, if the host asked for that.
     *
     * @since 1.1.0
     * @param string   $new_status
     * @param string   $old_status
     * @param \WP_Post $post
     * @return void
     */
    public function apply_pending_front_page( $new_status, $old_status, $post ) {
        if ( $new_status !== 'publish' || ! $post instanceof \WP_Post ) {
            return;
        }

        $state = $this->state();
        if ( ! $state['front_page_pending'] || (int) ( $state['pages']['home'] ?? 0 ) !== $post->ID ) {
            return;
        }

        $this->use_as_front_page( $post->ID );
        $state['front_page_pending'] = false;
        update_option( self::OPTION, $state, false );
    }

    /**
     * Publish the builder's held-back menu items for a page once the page goes live.
     *
     * @since 1.1.0
     * @param string   $new_status
     * @param string   $old_status
     * @param \WP_Post $post
     * @return void
     */
    public function publish_pending_menu_items( $new_status, $old_status, $post ) {
        if ( $new_status !== 'publish' || ! $post instanceof \WP_Post || $post->post_type !== 'page' ) {
            return;
        }

        $items = get_posts( [
            'post_type'   => 'nav_menu_item',
            'post_status' => 'draft',
            'numberposts' => -1,
            'fields'      => 'ids',
            'meta_query'  => [
                [ 'key' => self::PENDING_MENU_ITEM_META, 'value' => '1' ],
                [ 'key' => '_menu_item_object_id', 'value' => (string) $post->ID ],
            ],
        ] );

        foreach ( $items as $item_id ) {
            delete_post_meta( $item_id, self::PENDING_MENU_ITEM_META );
            wp_update_post( [ 'ID' => $item_id, 'post_status' => 'publish' ] );
        }
    }

    /**
     * @param int $page_id
     * @return void
     */
    private function use_as_front_page( int $page_id ) {
        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $page_id );
    }

    /**
     * @param string   $title
     * @param string   $slug
     * @param string[] $sections Pattern files, in order.
     * @param array    $tokens
     * @return \WP_Post|\WP_Error
     */
    private function create_page( string $title, string $slug, array $sections, array $tokens ) {
        $blocks = [];
        foreach ( $sections as $section ) {
            $markup = $this->patterns->content( $this->patterns->file_for( $section, $tokens ), $tokens );
            if ( $markup === '' ) {
                /* translators: %s: pattern name */
                return new \WP_Error( 'fleetwire_pattern_missing', sprintf( __( 'Could not build the "%s" section.', 'fleetwire' ), $section ) );
            }
            $blocks[] = trim( $markup );
        }

        $page_id = wp_insert_post( [
            'post_type'    => 'page',
            'post_status'  => 'draft',
            'post_title'   => $title,
            'post_name'    => $slug,
            'post_content' => implode( "\n\n", $blocks ),
        ], true );

        return is_wp_error( $page_id ) ? $page_id : get_post( $page_id );
    }

    /**
     * Site-relative URL the page will have once published (drafts have no pretty permalink yet).
     *
     * @param \WP_Post $page
     * @return string
     */
    private function page_url( \WP_Post $page ) {
        if ( $page->post_status === 'publish' ) {
            return esc_url( wp_make_link_relative( get_permalink( $page ) ) );
        }

        if ( ! function_exists( 'get_sample_permalink' ) ) {
            require_once ABSPATH . 'wp-admin/includes/post.php';
        }
        list( $template, $name ) = get_sample_permalink( $page->ID );

        return esc_url( wp_make_link_relative( str_replace( [ '%pagename%', '%postname%' ], $name, $template ) ) );
    }

    /**
     * @param \WP_Post $page
     * @param string   $status created|kept
     * @return array
     */
    private function describe( \WP_Post $page, string $status ) {
        return [
            'title'       => get_the_title( $page ),
            'status'      => $status,
            'post_status' => $page->post_status,
            'edit_url'    => get_edit_post_link( $page->ID, 'raw' ),
            'preview_url' => $page->post_status === 'publish' ? get_permalink( $page ) : get_preview_post_link( $page ),
        ];
    }

    /**
     * Add pages to the site navigation. Links to unpublished pages are hidden by
     * WordPress, so the menu fills in as the host publishes.
     *
     * @param \WP_Post[] $pages
     * @return string What happened, for the summary.
     */
    private function add_to_navigation( array $pages ) {
        if ( empty( $pages ) ) {
            return '';
        }

        return function_exists( 'wp_is_block_theme' ) && wp_is_block_theme()
            ? $this->add_to_block_navigation( $pages )
            : $this->add_to_classic_menu( $pages );
    }

    /**
     * Classic themes: append to the menu in the theme's first menu location, or
     * create one there when the location is empty.
     *
     * @param \WP_Post[] $pages
     * @return string
     */
    private function add_to_classic_menu( array $pages ) {
        $locations = get_registered_nav_menus();
        if ( empty( $locations ) ) {
            return __( 'Your theme has no menu locations, so the pages were not added to a menu.', 'fleetwire' );
        }

        $location = array_key_first( $locations );
        $assigned = get_nav_menu_locations();
        $menu = ! empty( $assigned[ $location ] ) ? wp_get_nav_menu_object( $assigned[ $location ] ) : false;

        if ( ! $menu ) {
            $name = __( 'Rental site', 'fleetwire' );
            $menu = wp_get_nav_menu_object( $name );
            $menu_id = $menu ? $menu->term_id : wp_create_nav_menu( $name );
            if ( is_wp_error( $menu_id ) ) {
                return $menu_id->get_error_message();
            }
            $assigned[ $location ] = $menu_id;
            set_theme_mod( 'nav_menu_locations', $assigned );
            $menu = wp_get_nav_menu_object( $menu_id );
        }

        $linked = self::menu_page_ids( $menu->term_id );
        foreach ( $pages as $page ) {
            if ( in_array( $page->ID, $linked, true ) ) {
                continue;
            }
            // Classic menus show links to drafts (they would 404 for visitors), so the
            // item stays a draft until its page is published; see publish_pending_menu_items().
            $published = $page->post_status === 'publish';
            $item_id = wp_update_nav_menu_item( $menu->term_id, 0, [
                'menu-item-object-id' => $page->ID,
                'menu-item-object'    => 'page',
                'menu-item-type'      => 'post_type',
                'menu-item-status'    => $published ? 'publish' : 'draft',
            ] );
            if ( ! $published && ! is_wp_error( $item_id ) ) {
                update_post_meta( $item_id, self::PENDING_MENU_ITEM_META, 1 );
            }
        }

        /* translators: 1: menu name, 2: theme menu location */
        return sprintf( __( 'Added to the "%1$s" menu (%2$s). Links appear as you publish each page.', 'fleetwire' ), $menu->name, $locations[ $location ] );
    }

    /**
     * Pages a classic menu links to, including held-back draft items (which
     * wp_get_nav_menu_items() never returns).
     *
     * @since 1.1.0
     * @param int $menu_id
     * @return int[]
     */
    public static function menu_page_ids( int $menu_id ) {
        $item_ids = get_posts( [
            'post_type'   => 'nav_menu_item',
            'post_status' => [ 'publish', 'draft' ],
            'numberposts' => -1,
            'fields'      => 'ids',
            'tax_query'   => [
                [ 'taxonomy' => 'nav_menu', 'field' => 'term_id', 'terms' => $menu_id ],
            ],
        ] );

        return array_map( function ( $item_id ) {
            return (int) get_post_meta( $item_id, '_menu_item_object_id', true );
        }, $item_ids );
    }

    /**
     * Block themes: append navigation links to the most recent navigation menu, the
     * one the Navigation block falls back to, or create it.
     *
     * @param \WP_Post[] $pages
     * @return string
     */
    private function add_to_block_navigation( array $pages ) {
        $navigation = get_posts( [
            'post_type'   => 'wp_navigation',
            'post_status' => 'publish',
            'numberposts' => 1,
            'orderby'     => 'date',
            'order'       => 'DESC',
        ] );
        $navigation = $navigation[0] ?? null;
        $content = $navigation ? $navigation->post_content : '';

        $links = '';
        foreach ( $pages as $page ) {
            if ( preg_match( '/"id":' . $page->ID . '[,}]/', $content ) ) {
                continue;
            }
            $links .= "\n" . '<!-- wp:navigation-link ' . wp_json_encode( [
                'label' => get_the_title( $page ),
                'type'  => 'page',
                'id'    => $page->ID,
                'url'   => $this->page_url( $page ),
                'kind'  => 'post-type',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ) . ' /-->';
        }

        if ( $navigation ) {
            if ( $links !== '' ) {
                wp_update_post( [ 'ID' => $navigation->ID, 'post_content' => trim( $content . $links ) ] );
            }
            $name = $navigation->post_title;
        } else {
            $name = __( 'Navigation', 'fleetwire' );
            wp_insert_post( [
                'post_type'    => 'wp_navigation',
                'post_status'  => 'publish',
                'post_title'   => $name,
                'post_content' => trim( $links ),
            ] );
        }

        /* translators: %s: navigation menu name */
        return sprintf( __( 'Added to the "%s" navigation menu. Links appear as you publish each page.', 'fleetwire' ), $name );
    }
}
