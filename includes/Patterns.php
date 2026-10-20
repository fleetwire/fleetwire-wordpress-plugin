<?php
namespace Fleetwire;

/**
 * Fleetwire Patterns Class
 *
 * Registers the "Fleetwire – Car Rental" block patterns: ready-made sections for a
 * rental website built from core blocks plus the Fleetwire blocks. Markup lives in
 * patterns/*.html with {{TOKENS}} filled from the company's cached listings.
 *
 * Pattern markup stores text alignment in the WordPress 6.3–6.9 attribute format
 * (heading "textAlign", paragraph "align"); 7.0 migrates it through core block
 * deprecations. Keep that format when editing the files.
 *
 * @since 1.1.0
 * @package Fleetwire
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fleetwire Patterns Class
 *
 * @since 1.1.0
 */
class Patterns {

    /**
     * Pattern category slug.
     *
     * @var string
     */
    const CATEGORY = 'fleetwire';

    /**
     * Constructor
     *
     * @since 1.1.0
     */
    public function __construct() {
        add_action( 'init', [ $this, 'register_patterns' ] );
        add_action( 'admin_init', [ Listings::class, 'warm' ] );
    }

    /**
     * Patterns that work for any company, without a chosen vehicle.
     *
     * @return array<string, array{title: string, description: string, keywords: string[]}>
     */
    private function fleet_patterns() {
        return [
            'hero-search'     => [
                'title'       => __( 'Rental hero with search', 'fleetwire' ),
                'description' => __( 'Full-width banner with a headline and trip date search.', 'fleetwire' ),
                'keywords'    => [ 'hero', 'banner', 'search', 'header' ],
            ],
            'fleet-grid'      => [
                'title'       => __( 'Fleet grid', 'fleetwire' ),
                'description' => __( 'Six of your vehicles as cards, with a link to the full fleet.', 'fleetwire' ),
                'keywords'    => [ 'fleet', 'vehicles', 'cars', 'grid' ],
            ],
            'how-it-works'    => [
                'title'       => __( 'How renting works', 'fleetwire' ),
                'description' => __( 'Three steps from choosing a car to pickup.', 'fleetwire' ),
                'keywords'    => [ 'steps', 'process', 'how it works' ],
            ],
            'why-book-direct' => [
                'title'       => __( 'Why book direct', 'fleetwire' ),
                'description' => __( 'Reasons to book on your site instead of a marketplace.', 'fleetwire' ),
                'keywords'    => [ 'benefits', 'direct booking', 'trust' ],
            ],
            'trust-bar'       => [
                'title'       => __( 'Why renters book with you', 'fleetwire' ),
                'description' => __( 'A band of four reasons to book: confirmation, cancellation, delivery and support.', 'fleetwire' ),
                'keywords'    => [ 'trust', 'benefits', 'features', 'reasons' ],
            ],
            'reviews'         => [
                'title'       => __( 'Renter reviews', 'fleetwire' ),
                'description' => __( 'Three review cards with sample quotes. Replace them with reviews from your renters.', 'fleetwire' ),
                'keywords'    => [ 'reviews', 'testimonials', 'quotes', 'ratings' ],
            ],
            'faq'             => [
                'title'       => __( 'Rental FAQ', 'fleetwire' ),
                'description' => __( 'Deposit, age, insurance, mileage, delivery and cancellation questions.', 'fleetwire' ),
                'keywords'    => [ 'faq', 'questions', 'policies' ],
            ],
            'requirements'    => [
                'title'       => __( 'Rental requirements', 'fleetwire' ),
                'description' => __( 'License, age, deposit and insurance at a glance.', 'fleetwire' ),
                'keywords'    => [ 'requirements', 'license', 'age', 'deposit' ],
            ],
            'cta-band'        => [
                'title'       => __( 'Book now banner', 'fleetwire' ),
                'description' => __( 'A dark call-to-action band linking to your fleet.', 'fleetwire' ),
                'keywords'    => [ 'cta', 'call to action', 'banner' ],
            ],
            'contact'         => [
                'title'       => __( 'Contact and hours', 'fleetwire' ),
                'description' => __( 'Address, phone, email and opening hours.', 'fleetwire' ),
                'keywords'    => [ 'contact', 'hours', 'address', 'location' ],
            ],
        ];
    }

    /**
     * Register the pattern category and every pattern whose data is available.
     *
     * @since 1.1.0
     * @return void
     */
    public function register_patterns() {
        if ( ! function_exists( 'register_block_pattern' ) ) {
            return;
        }

        register_block_pattern_category( self::CATEGORY, [
            'label'       => __( 'Fleetwire – Car Rental', 'fleetwire' ),
            'description' => __( 'Sections for a car rental website, powered by your Fleetwire fleet.', 'fleetwire' ),
        ] );

        $listings = Listings::bookable();
        $tokens = $this->tokens( $listings );

        foreach ( $this->fleet_patterns() as $slug => $pattern ) {
            $this->register( $slug, $pattern, $this->file_for( $slug, $tokens ), $tokens );
        }

        $this->register_vehicle_patterns( $listings, $tokens );
    }

    /**
     * Vehicle spotlight and featured three, filled with real listing IDs. Left out
     * when the listings cache is empty rather than shipped with broken IDs.
     *
     * @param array $listings Bookable listings.
     * @param array $tokens
     * @return void
     */
    private function register_vehicle_patterns( array $listings, array $tokens ) {
        $listings = array_values( array_filter( $listings, function ( $listing ) {
            return $this->is_safe_listing_id( $listing['id'] ?? '' );
        } ) );

        if ( empty( $listings ) ) {
            return;
        }

        $by_photos = $listings;
        usort( $by_photos, function ( $a, $b ) {
            return count( $b['images'] ?? [] ) <=> count( $a['images'] ?? [] );
        } );

        $this->register( 'vehicle-spotlight', [
            /* translators: %s: vehicle name */
            'title'       => sprintf( __( 'Vehicle spotlight: %s', 'fleetwire' ), $by_photos[0]['name'] ),
            'description' => __( 'Photo gallery beside the vehicle details and a book button.', 'fleetwire' ),
            'keywords'    => [ 'vehicle', 'spotlight', 'gallery', 'details' ],
        ], 'vehicle-spotlight', $tokens + [ '{{LISTING_1}}' => $by_photos[0]['id'] ] );

        if ( count( $listings ) >= 3 ) {
            $this->register( 'featured-three', [
                'title'       => __( 'Three featured vehicles', 'fleetwire' ),
                'description' => __( 'Three of your vehicles side by side.', 'fleetwire' ),
                'keywords'    => [ 'featured', 'vehicles', 'cards' ],
            ], 'featured-three', $tokens + [
                '{{LISTING_1}}' => $listings[0]['id'],
                '{{LISTING_2}}' => $listings[1]['id'],
                '{{LISTING_3}}' => $listings[2]['id'],
            ] );
        }
    }

    /**
     * @param string $slug
     * @param array  $pattern
     * @param string $file
     * @param array  $tokens
     * @return void
     */
    private function register( string $slug, array $pattern, string $file, array $tokens ) {
        $content = $this->content( $file, $tokens );
        if ( $content === '' ) {
            return;
        }

        register_block_pattern( 'fleetwire/' . $slug, [
            'title'         => $pattern['title'],
            'description'   => $pattern['description'],
            'keywords'      => $pattern['keywords'],
            'categories'    => [ self::CATEGORY ],
            'viewportWidth' => 1280,
            'content'       => $content,
        ] );
    }

    /**
     * Tokens shared by every pattern: the fleet page link and a hero photo.
     *
     * @since 1.1.0
     * @param array       $listings  Bookable listings.
     * @param string|null $fleet_url Override for the "See all vehicles" link.
     * @return array<string, string>
     */
    public function tokens( array $listings, ?string $fleet_url = null ) {
        return [
            '{{FLEET_URL}}'  => $fleet_url ?? $this->fleet_url(),
            '{{HERO_IMAGE}}' => $this->hero_image( $listings ),
        ];
    }

    /**
     * Pattern file for a slug: the hero needs its gradient variant when there is no photo.
     *
     * @since 1.1.0
     * @param string $slug
     * @param array  $tokens
     * @return string
     */
    public function file_for( string $slug, array $tokens ) {
        return $slug === 'hero-search' && empty( $tokens['{{HERO_IMAGE}}'] ) ? 'hero-search-gradient' : $slug;
    }

    /**
     * Pattern markup with tokens replaced.
     *
     * @since 1.1.0
     * @param string $file   Pattern file name without extension.
     * @param array  $tokens Token => value.
     * @return string
     */
    public function content( string $file, array $tokens ) {
        $path = Config::get_plugin_dir() . 'patterns/' . $file . '.html';
        if ( ! is_readable( $path ) ) {
            return '';
        }

        $content = strtr( (string) file_get_contents( $path ), $tokens );

        // An unreplaced token means missing data; never register a half-filled pattern.
        return str_contains( $content, '{{' ) ? '' : $content;
    }

    /**
     * A vehicle photo for the hero: the first bookable listing with one.
     *
     * Only plain https URLs are used, because the value goes into both the block's
     * JSON attributes and its HTML.
     *
     * @param array $listings
     * @return string Empty when there is no usable photo.
     */
    private function hero_image( array $listings ) {
        foreach ( $listings as $listing ) {
            foreach ( (array) ( $listing['images'] ?? [] ) as $url ) {
                if ( is_string( $url ) && preg_match( '#^https://[^\s"\'<>&{}\\\\]+$#', $url ) ) {
                    return $url;
                }
            }
        }

        return '';
    }

    /**
     * Link for "See all vehicles": a page with the slug "fleet" when there is one.
     *
     * @return string
     */
    private function fleet_url() {
        $page = get_page_by_path( 'fleet' );

        return $page ? esc_url( wp_make_link_relative( get_permalink( $page ) ) ) : '#';
    }

    /**
     * @param mixed $id
     * @return bool
     */
    private function is_safe_listing_id( $id ) {
        return is_string( $id ) && preg_match( '/^[A-Za-z0-9_-]+$/', $id ) === 1;
    }
}
