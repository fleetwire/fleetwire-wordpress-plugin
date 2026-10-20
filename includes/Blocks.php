<?php
namespace Fleetwire;

/**
 * Fleetwire Blocks Class
 *
 * Registers the Fleetwire blocks. Each block renders on the server through the
 * matching shortcode class, so blocks and shortcodes always produce the same
 * markup for fleetwire.js to hydrate on the live site.
 *
 * @since 1.1.0
 * @package Fleetwire
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fleetwire Blocks Class
 *
 * @since 1.1.0
 */
class Blocks {

    /**
     * Shortcode registry used to render blocks.
     *
     * @var Shortcodes
     */
    private $shortcodes;

    /**
     * Constructor
     *
     * @since 1.1.0
     * @param Shortcodes $shortcodes
     */
    public function __construct( Shortcodes $shortcodes ) {
        $this->shortcodes = $shortcodes;
        $this->init_hooks();
    }

    /**
     * Initialize hooks
     *
     * @since 1.1.0
     * @return void
     */
    private function init_hooks() {
        add_action( 'init', [ $this, 'register_blocks' ] );
        add_action( 'rest_api_init', [ $this, 'register_rest_routes' ] );
        add_filter( 'block_categories_all', [ $this, 'register_category' ] );
    }

    /**
     * Block slug => render callback.
     *
     * @return array<string, callable>
     */
    private function renderers() {
        return [
            'listings'        => [ $this, 'render_listings' ],
            'search'          => [ $this, 'render_search' ],
            'listing-card'    => [ $this, 'render_listing_card' ],
            'book-button'     => [ $this, 'render_book_button' ],
            'listing-gallery' => [ $this, 'render_listing_gallery' ],
            'listing-details' => [ $this, 'render_listing_details' ],
        ];
    }

    /**
     * Register the editor assets and every block.
     *
     * @since 1.1.0
     * @return void
     */
    public function register_blocks() {
        wp_register_script(
            'fleetwire-blocks-editor',
            Config::get_asset_url( 'assets/blocks/editor.js' ),
            [ 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch' ],
            Config::asset_version( 'assets/blocks/editor.js' ),
            true
        );

        wp_add_inline_script(
            'fleetwire-blocks-editor',
            'window.fleetwireBlocks = ' . wp_json_encode( [
                'configured'  => Config::is_configured(),
                'settingsUrl' => admin_url( 'admin.php?page=fleetwire' ),
            ] ) . ';',
            'before'
        );

        wp_register_style(
            'fleetwire-blocks-editor',
            Config::get_asset_url( 'assets/blocks/editor.css' ),
            [],
            Config::asset_version( 'assets/blocks/editor.css' )
        );

        foreach ( $this->renderers() as $slug => $renderer ) {
            register_block_type( Config::get_plugin_dir() . 'blocks/' . $slug, [
                'render_callback' => $renderer,
            ] );
        }
    }

    /**
     * Add the "Fleetwire" block category.
     *
     * @since 1.1.0
     * @param array $categories
     * @return array
     */
    public function register_category( $categories ) {
        return array_merge( [
            [
                'slug'  => 'fleetwire',
                'title' => __( 'Fleetwire', 'fleetwire' ),
                'icon'  => null,
            ],
        ], $categories );
    }

    /**
     * Listings for the editor's vehicle picker.
     *
     * @since 1.1.0
     * @return void
     */
    public function register_rest_routes() {
        register_rest_route( 'fleetwire/v1', '/listings', [
            'methods'             => \WP_REST_Server::READABLE,
            'permission_callback' => function () {
                return current_user_can( 'edit_posts' );
            },
            'args'                => [
                'refresh' => [
                    'type'    => 'boolean',
                    'default' => false,
                ],
            ],
            'callback'            => function ( \WP_REST_Request $request ) {
                $listings = Listings::fetch( (bool) $request->get_param( 'refresh' ) );

                if ( is_wp_error( $listings ) ) {
                    $listings->add_data( [ 'status' => 502 ] );
                    return $listings;
                }

                return rest_ensure_response( $listings );
            },
        ] );
    }

    /**
     * [fleetwire_list]
     *
     * @param array $attributes
     * @return string
     */
    public function render_listings( $attributes ) {
        return $this->wrap( $this->shortcode( 'fleetwire_list', [
            'limit'     => $this->positive_int( $attributes['limit'] ?? 0 ),
            'per'       => $this->positive_int( $attributes['perPage'] ?? 0 ),
            'showprice' => ! empty( $attributes['showPrice'] ) ? null : 'false',
        ] ) );
    }

    /**
     * [fleetwire_search]
     *
     * @return string
     */
    public function render_search() {
        return $this->wrap( $this->shortcode( 'fleetwire_search', [] ) );
    }

    /**
     * [fleetwire_listing_card]
     *
     * @param array $attributes
     * @return string
     */
    public function render_listing_card( $attributes ) {
        if ( empty( $attributes['listingId'] ) ) {
            return '';
        }

        return $this->wrap( $this->shortcode( 'fleetwire_listing_card', [
            'id'        => $attributes['listingId'],
            'showprice' => ! empty( $attributes['showPrice'] ) ? null : 'false',
        ] ) );
    }

    /**
     * [fleetwire_button]
     *
     * @param array $attributes
     * @return string
     */
    public function render_book_button( $attributes ) {
        if ( empty( $attributes['listingId'] ) ) {
            return '';
        }

        return $this->wrap( $this->shortcode( 'fleetwire_button', [
            'id'           => $attributes['listingId'],
            'custom-title' => $attributes['label'] ?? '',
            // fleetwire.js checks for the attribute's presence, so only emit it when on.
            'show-price'   => ! empty( $attributes['showPrice'] ) ? 'true' : null,
        ] ) );
    }

    /**
     * [fleetwire_listing_image_gallery]
     *
     * @param array $attributes
     * @return string
     */
    public function render_listing_gallery( $attributes ) {
        if ( empty( $attributes['listingId'] ) ) {
            return '';
        }

        return $this->wrap( $this->shortcode( 'fleetwire_listing_image_gallery', [
            'id' => $attributes['listingId'],
        ] ) );
    }

    /**
     * Title, price, seats, doors and description of one listing.
     *
     * Seats and doors carry labels through data attributes their shortcodes do not
     * expose, so this block writes the same classes directly.
     *
     * @param array $attributes
     * @return string
     */
    public function render_listing_details( $attributes ) {
        if ( empty( $attributes['listingId'] ) ) {
            return '';
        }

        $id = sanitize_text_field( $attributes['listingId'] );
        $field = function ( $tag, $class, array $data = [] ) use ( $id ) {
            $data_attributes = sprintf( 'data-id="%s"', esc_attr( $id ) );
            foreach ( $data as $key => $value ) {
                $data_attributes .= sprintf( ' data-%s="%s"', esc_attr( $key ), esc_attr( $value ) );
            }

            return sprintf( '<%1$s class="%2$s" %3$s></%1$s>', $tag, esc_attr( $class ), $data_attributes );
        };

        $html = '<h3 class="fleetwire-details__title">' . $field( 'span', 'fleetwire-listing-title' ) . '</h3>';
        $html .= '<p class="fleetwire-details__price">' . $field( 'span', 'fleetwire-listing-price' ) . '</p>';
        $html .= '<p class="fleetwire-details__specs">'
            . $field( 'span', 'fleetwire-listing-seats', [ 'prepend-label' => ' ' . __( 'seats', 'fleetwire' ) ] )
            . ' · '
            . $field( 'span', 'fleetwire-listing-doors', [ 'prepend-label' => ' ' . __( 'doors', 'fleetwire' ) ] )
            . '</p>';

        if ( ! empty( $attributes['showDescription'] ) ) {
            $length = $this->positive_int( $attributes['descriptionLength'] ?? 0 );
            $html .= $field( 'div', 'fleetwire-listing-description', $length ? [ 'length' => $length ] : [] );
        }

        return $this->wrap( $html );
    }

    /**
     * Render a registered shortcode with the given attributes, dropping empty ones.
     *
     * @param string $tag
     * @param array  $attributes
     * @return string
     */
    private function shortcode( string $tag, array $attributes ) {
        $shortcode = $this->shortcodes->get_shortcode( $tag );
        if ( ! $shortcode ) {
            return '';
        }

        $attributes = array_filter( $attributes, function ( $value ) {
            return $value !== null && $value !== '' && $value !== 0;
        } );

        return $shortcode->handle( array_map( 'strval', $attributes ) );
    }

    /**
     * Wrap block output so alignment, margins and custom classes from the editor apply.
     *
     * @param string $html
     * @return string
     */
    private function wrap( string $html ) {
        return sprintf( '<div %s>%s</div>', get_block_wrapper_attributes(), $html );
    }

    /**
     * @param mixed $value
     * @return int
     */
    private function positive_int( $value ) {
        $value = (int) $value;

        return $value > 0 ? $value : 0;
    }
}
