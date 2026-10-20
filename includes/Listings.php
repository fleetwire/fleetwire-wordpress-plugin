<?php
namespace Fleetwire;

/**
 * Fleetwire Listings Class
 *
 * Loads the connected company's bookable listings for the admin listing picker
 * and the block editor, cached briefly so editing a page does not hit the API
 * on every block.
 *
 * @since 1.1.0
 * @package Fleetwire
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fleetwire Listings Class
 *
 * @since 1.1.0
 */
class Listings {

    /**
     * Cache lifetime for the listings list.
     *
     * @var int
     */
    const CACHE_TTL = 12 * HOUR_IN_SECONDS;

    /**
     * How long to wait before retrying after the API failed, so a down API does
     * not slow every admin page load.
     *
     * @var int
     */
    const FAILURE_BACKOFF = 5 * MINUTE_IN_SECONDS;

    /**
     * Fetch the company's listings, normalized for pickers.
     *
     * @since 1.1.0
     * @param bool $refresh Skip the cache and refetch.
     * @return array|\WP_Error List of normalized listings.
     */
    public static function fetch( bool $refresh = false ) {
        $company_id = Config::get_api_company_id();
        $company_validation = Validator::validate_company_id( (string) $company_id );

        if ( ! $company_validation['valid'] ) {
            return new \WP_Error( 'fleetwire_not_configured', $company_validation['error'] );
        }

        $api_base_url = Config::get_api_base_url( $company_validation['value'] );
        $cache_key = self::cache_key( $api_base_url );

        if ( ! $refresh ) {
            $cached = get_transient( $cache_key );
            if ( is_array( $cached ) ) {
                return $cached;
            }
        }

        $response = wp_remote_get( add_query_arg( 'perPage', 100, $api_base_url . '/api/v1/products' ), [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'sslverify' => Config::should_verify_api_ssl(),
            'timeout' => 10,
        ] );

        if ( is_wp_error( $response ) ) {
            Logger::log_api_error( 'products', $response->get_error_message(), [
                'company_id' => $company_validation['value'],
            ] );
            set_transient( self::failure_key( $api_base_url ), 1, self::FAILURE_BACKOFF );

            return new \WP_Error( 'fleetwire_api_error', __( 'Could not load listings from Fleetwire.', 'fleetwire' ) );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code < 200 || $status_code >= 300 || ! is_array( $body ) ) {
            set_transient( self::failure_key( $api_base_url ), 1, self::FAILURE_BACKOFF );

            return new \WP_Error( 'fleetwire_api_error', __( 'Fleetwire returned an unexpected listings response.', 'fleetwire' ) );
        }

        $items = is_array( $body['data'] ?? null ) ? $body['data'] : [];
        $listings = array_values( array_filter( array_map( [ self::class, 'normalize' ], $items ) ) );

        set_transient( $cache_key, $listings, self::CACHE_TTL );

        return $listings;
    }

    /**
     * Cached listings only, without calling the API.
     *
     * @since 1.1.0
     * @return array
     */
    public static function cached() {
        $cached = get_transient( self::cache_key( Config::get_api_base_url() ) );

        return is_array( $cached ) ? $cached : [];
    }

    /**
     * Fill the cache when it is empty, unless the API failed recently.
     *
     * Runs on admin page loads so vehicle patterns are registered by the time the
     * block editor asks for them.
     *
     * @since 1.1.0
     * @return void
     */
    public static function warm() {
        if ( ! Config::is_configured() || ! current_user_can( 'edit_posts' ) ) {
            return;
        }

        $api_base_url = Config::get_api_base_url();
        if ( self::cached() || get_transient( self::failure_key( $api_base_url ) ) ) {
            return;
        }

        self::fetch();
    }

    /**
     * Cached listings a customer can book: active, approved and not paused.
     *
     * @since 1.1.0
     * @return array
     */
    public static function bookable() {
        return array_values( array_filter( self::cached(), function ( $listing ) {
            return ! empty( $listing['active'] ) && ! empty( $listing['approved'] ) && empty( $listing['paused'] );
        } ) );
    }

    /**
     * Normalize a Fleetwire listing for pickers and editor previews.
     *
     * @since 1.1.0
     * @param mixed $listing Listing payload.
     * @return array|null
     */
    public static function normalize( $listing ) {
        if ( ! is_array( $listing ) ) {
            return null;
        }

        $listing_id = sanitize_text_field( $listing['l_id'] ?? '' );
        if ( empty( $listing_id ) ) {
            return null;
        }

        $images = [];
        foreach ( (array) ( $listing['listing_images'] ?? [] ) as $image ) {
            $url = $image['optimized_path'] ?? $image['path'] ?? $image['url'] ?? '';
            if ( is_string( $url ) && $url !== '' && count( $images ) < 8 ) {
                $images[] = esc_url_raw( $url );
            }
        }

        $amount = $listing['pricing']['display_price'] ?? $listing['pricing']['amount'] ?? null;
        $period = $listing['pricing']['display_period'] ?? $listing['pricing']['price_period'] ?? '';

        return [
            'id' => $listing_id,
            'name' => sanitize_text_field( $listing['name'] ?? $listing['title'] ?? $listing_id ),
            'active' => (bool) ( $listing['active'] ?? false ),
            'approved' => (bool) ( $listing['approved'] ?? false ),
            'paused' => (bool) ( $listing['paused'] ?? false ),
            // Fleetwire prices are in cents.
            'price' => is_numeric( $amount ) ? round( $amount / 100, 2 ) : null,
            'price_period' => is_string( $period ) ? strtolower( sanitize_text_field( $period ) ) : '',
            'seats' => isset( $listing['vehicle']['seats'] ) ? absint( $listing['vehicle']['seats'] ) : null,
            'doors' => isset( $listing['vehicle']['doors'] ) ? absint( $listing['vehicle']['doors'] ) : null,
            'description' => wp_html_excerpt( sanitize_textarea_field( (string) ( $listing['description'] ?? '' ) ), 300, '…' ),
            'image' => $images[0] ?? '',
            'images' => $images,
        ];
    }

    /**
     * Transient key, scoped to the API base so switching live/local does not mix fleets.
     *
     * @param string $api_base_url
     * @return string
     */
    private static function cache_key( string $api_base_url ) {
        return 'fleetwire_listings_' . md5( $api_base_url );
    }

    /**
     * @param string $api_base_url
     * @return string
     */
    private static function failure_key( string $api_base_url ) {
        return 'fleetwire_listings_failed_' . md5( $api_base_url );
    }
}
