<?php
/**
 * Block tests, run inside a real WordPress install with this plugin active:
 *
 *   php tests/blocks.php /path/to/wordpress
 *
 * Covers registration, block ↔ shortcode markup parity, attribute escaping, the
 * editor's listings REST route and listing normalization. The REST success case
 * needs the configured Fleetwire API to be reachable; it is skipped otherwise.
 */

if ( PHP_SAPI !== 'cli' ) {
    exit( 1 );
}

$wordpress_path = rtrim( $argv[1] ?? '', '/' );
if ( ! is_file( $wordpress_path . '/wp-load.php' ) ) {
    fwrite( STDERR, "Usage: php tests/blocks.php /path/to/wordpress\n" );
    exit( 1 );
}

require $wordpress_path . '/wp-load.php';

$failures = 0;
$passes = 0;

function check( $condition, string $message ) {
    global $failures, $passes;
    if ( $condition ) {
        $passes++;
        return;
    }
    $failures++;
    fwrite( STDERR, "FAIL: $message\n" );
}

function render_fleetwire_block( string $name, array $attributes = [] ): string {
    return render_block( [
        'blockName'    => 'fleetwire/' . $name,
        'attrs'        => $attributes,
        'innerBlocks'  => [],
        'innerHTML'    => '',
        'innerContent' => [],
    ] );
}

/**
 * Inner markup of a rendered block, without the block wrapper div.
 */
function inner( string $html ): string {
    return preg_replace( '#^<div [^>]*>(.*)</div>$#s', '$1', trim( $html ) );
}

// Registration
$registry = WP_Block_Type_Registry::get_instance();
foreach ( [ 'listings', 'search', 'listing-card', 'book-button', 'listing-gallery', 'listing-details', 'listing-calendar' ] as $slug ) {
    $type = $registry->get_registered( 'fleetwire/' . $slug );
    check( $type !== null, "fleetwire/$slug is registered" );
    check( $type && $type->category === 'fleetwire', "fleetwire/$slug is in the Fleetwire category" );
    check( $type && in_array( 'fleetwire-blocks-editor', $type->editor_script_handles, true ), "fleetwire/$slug loads the editor script" );
}
$categories = wp_list_pluck( get_block_categories( get_post( 0 ) ?: new WP_Post( (object) [] ) ), 'slug' );
check( in_array( 'fleetwire', $categories, true ), 'Fleetwire block category exists' );

// Parity with shortcodes
check( inner( render_fleetwire_block( 'listings', [ 'limit' => 3, 'showPrice' => false ] ) ) === do_shortcode( '[fleetwire_list limit="3" showprice="false"]' ), 'listings block matches [fleetwire_list limit showprice]' );
check( inner( render_fleetwire_block( 'listings', [ 'limit' => 0, 'perPage' => 4 ] ) ) === do_shortcode( '[fleetwire_list per="4"]' ), 'listings block with paging matches [fleetwire_list per]' );
check( inner( render_fleetwire_block( 'search' ) ) === do_shortcode( '[fleetwire_search]' ), 'search block matches [fleetwire_search]' );
check( inner( render_fleetwire_block( 'listing-card', [ 'listingId' => 'l_ABC' ] ) ) === do_shortcode( '[fleetwire_listing_card id="l_ABC"]' ), 'card block matches [fleetwire_listing_card]' );
check( inner( render_fleetwire_block( 'listing-gallery', [ 'listingId' => 'l_ABC' ] ) ) === do_shortcode( '[fleetwire_listing_image_gallery id="l_ABC"]' ), 'gallery block matches the gallery shortcode' );
check( inner( render_fleetwire_block( 'listing-calendar', [ 'listingId' => 'l_ABC', 'months' => 2, 'buttonText' => 'Reserve' ] ) ) === do_shortcode( '[fleetwire_listing_calendar id="l_ABC" months="2" button-text="Reserve"]' ), 'calendar block matches [fleetwire_listing_calendar]' );
check( inner( render_fleetwire_block( 'book-button', [ 'listingId' => 'l_ABC', 'label' => 'Reserve', 'showPrice' => true ] ) ) === do_shortcode( '[fleetwire_button id="l_ABC" custom-title="Reserve" show-price="true"]' ), 'button block matches [fleetwire_button]' );

// Behavior
check( str_contains( render_fleetwire_block( 'listings', [ 'limit' => 6, 'showPrice' => true ] ), 'data-limit="6"' ), 'listings block emits the limit' );
check( ! str_contains( render_fleetwire_block( 'listings', [ 'limit' => 6, 'showPrice' => true ] ), 'showprice' ), 'prices on: no showprice attribute' );
check( ! str_contains( render_fleetwire_block( 'book-button', [ 'listingId' => 'l_ABC', 'showPrice' => false ] ), 'data-show-price' ), 'button without price omits data-show-price (fleetwire.js checks presence)' );
check( str_contains( render_fleetwire_block( 'listing-gallery', [ 'listingId' => 'l_ABC' ] ), 'fleetwire-listing-gallery' ), 'gallery block carries the gallery class' );
foreach ( [ 'listing-card', 'book-button', 'listing-gallery', 'listing-details', 'listing-calendar' ] as $slug ) {
    check( render_fleetwire_block( $slug ) === '', "$slug renders nothing until a vehicle is picked" );
}
$calendar = render_fleetwire_block( 'listing-calendar', [ 'listingId' => 'l_ABC' ] );
check( str_contains( $calendar, 'class="fleetwire-listing-calendar" data-id="l_ABC"' ), 'calendar block renders the calendar embed for the vehicle' );
check( ! str_contains( $calendar, 'data-months' ) && ! str_contains( $calendar, 'data-button-text' ), 'calendar block omits unset options' );
check( ! str_contains( render_fleetwire_block( 'listing-calendar', [ 'listingId' => 'l_ABC', 'months' => 6 ] ), 'data-months' ), 'calendar block ignores month counts the embed does not support' );
$details = render_fleetwire_block( 'listing-details', [ 'listingId' => 'l_ABC', 'showDescription' => true, 'descriptionLength' => 120 ] );
foreach ( [ 'fleetwire-listing-title', 'fleetwire-listing-price', 'fleetwire-listing-seats', 'fleetwire-listing-doors' ] as $class ) {
    check( str_contains( $details, $class . '" data-id="l_ABC"' ), "details block renders $class" );
}
check( str_contains( $details, 'data-length="120"' ), 'details block passes the description length' );
check( ! str_contains( render_fleetwire_block( 'listing-details', [ 'listingId' => 'l_ABC', 'showDescription' => false ] ), 'fleetwire-listing-description' ), 'details block can hide the description' );

// Escaping
$hostile = '"><script>alert(1)</script>';
foreach ( [
    'listing-card'    => [ 'listingId' => $hostile ],
    'book-button'     => [ 'listingId' => 'l_ABC', 'label' => $hostile ],
    'listing-gallery' => [ 'listingId' => $hostile ],
    'listing-details' => [ 'listingId' => $hostile, 'showDescription' => true ],
    'listing-calendar' => [ 'listingId' => $hostile, 'buttonText' => $hostile ],
] as $slug => $attributes ) {
    check( ! str_contains( render_fleetwire_block( $slug, $attributes ), '<script>' ), "$slug escapes hostile attributes" );
}

// REST route
wp_set_current_user( 0 );
$response = rest_do_request( new WP_REST_Request( 'GET', '/fleetwire/v1/listings' ) );
check( $response->get_status() === 401, 'listings route refuses anonymous users (got ' . $response->get_status() . ')' );

$subscriber = get_users( [ 'role' => 'subscriber', 'number' => 1 ] );
if ( $subscriber ) {
    wp_set_current_user( $subscriber[0]->ID );
    $response = rest_do_request( new WP_REST_Request( 'GET', '/fleetwire/v1/listings' ) );
    check( $response->get_status() === 403, 'listings route refuses users who cannot edit posts' );
}

$admin = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
wp_set_current_user( $admin[0]->ID );
$response = rest_do_request( new WP_REST_Request( 'GET', '/fleetwire/v1/listings' ) );
if ( $response->get_status() === 200 ) {
    $listings = $response->get_data();
    check( is_array( $listings ) && isset( $listings[0]['id'], $listings[0]['name'] ), 'listings route returns normalized listings' );
    check( is_array( get_transient( 'fleetwire_listings_' . md5( \Fleetwire\Config::get_api_base_url() ) ) ), 'listings are cached' );
} else {
    fwrite( STDERR, 'SKIP: listings route success case (API returned ' . $response->get_status() . ")\n" );
}

// Normalization
$normalized = \Fleetwire\Listings::normalize( [
    'l_id'           => 'l_ABC',
    'name'           => 'Model 3',
    'cover_photo'    => 'aff53443-efb3-40d7-bbf3-a27dcb8c0561',
    'pricing'        => [ 'amount' => 19900, 'price_period' => 'Day' ],
    'vehicle'        => [ 'seats' => 5, 'doors' => 4 ],
    'listing_images' => [ [ 'optimized_path' => 'https://img.test/1.webp' ], [ 'path' => 'https://img.test/2.jpg' ] ],
] );
check( $normalized['price'] === 199.0 && $normalized['price_period'] === 'day', 'price converts from cents' );
check( $normalized['image'] === 'https://img.test/1.webp', 'image comes from listing_images, not the cover_photo uuid' );
check( $normalized['images'] === [ 'https://img.test/1.webp', 'https://img.test/2.jpg' ], 'all image urls are kept' );
check( $normalized['seats'] === 5 && $normalized['doors'] === 4, 'seats and doors are kept' );
check( \Fleetwire\Listings::normalize( [ 'name' => 'no id' ] ) === null, 'listings without an id are dropped' );

echo "$passes passed, $failures failed\n";
exit( $failures ? 1 : 0 );
