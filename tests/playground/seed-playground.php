<?php
/**
 * Builds a "Fleetwire Playground" page on a local WordPress site that shows every
 * shortcode and option next to its live render, using the connected company's real
 * listing IDs. Re-runnable: the page is updated in place.
 *
 * Usage: php tests/playground/seed-playground.php /path/to/wordpress
 *
 * Point the plugin at a local Fleetwire checkout first (Fleetwire settings → API
 * environment "local"), or the page renders live fleetwire.io data.
 */

if ( PHP_SAPI !== 'cli' ) {
    exit( 1 );
}

$wordpress_path = rtrim( $argv[1] ?? '', '/' );
if ( ! is_file( $wordpress_path . '/wp-load.php' ) ) {
    fwrite( STDERR, "Usage: php seed-playground.php /path/to/wordpress\n" );
    exit( 1 );
}

require $wordpress_path . '/wp-load.php';

use Fleetwire\Config;

if ( ! class_exists( Config::class ) || ! Config::is_configured() ) {
    fwrite( STDERR, "Activate the Fleetwire plugin and save a Company ID first.\n" );
    exit( 1 );
}

$response = wp_remote_get( Config::get_api_base_url() . '/api/v1/products?perPage=50', [
    'headers'   => [ 'Accept' => 'application/json' ],
    'sslverify' => Config::should_verify_api_ssl(),
    'timeout'   => 15,
] );
$listings = json_decode( wp_remote_retrieve_body( $response ), true )['data'] ?? [];

if ( empty( $listings ) ) {
    fwrite( STDERR, 'No listings returned from ' . Config::get_api_base_url() . "\n" );
    exit( 1 );
}

usort( $listings, fn ( $a, $b ) => count( $b['listing_images'] ?? [] ) <=> count( $a['listing_images'] ?? [] ) );
$featured = $listings[0]['l_id'];
$second = $listings[1]['l_id'] ?? $featured;

/**
 * One playground section: heading, what it shows, the shortcode as text, then the shortcode itself.
 */
function fleetwire_playground_section( string $title, string $note, string $shortcode ): string {
    return sprintf(
        "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">%s</h3>\n<!-- /wp:heading -->\n\n" .
        "<!-- wp:paragraph -->\n<p>%s<br><code>%s</code></p>\n<!-- /wp:paragraph -->\n\n" .
        "<!-- wp:shortcode -->\n%s\n<!-- /wp:shortcode -->\n\n" .
        "<!-- wp:separator -->\n<hr class=\"wp-block-separator has-alpha-channel-opacity\"/>\n<!-- /wp:separator -->\n\n",
        esc_html( $title ),
        esc_html( $note ),
        str_replace( [ '[', ']' ], [ '&#91;', '&#93;' ], esc_html( $shortcode ) ),
        $shortcode
    );
}

function fleetwire_playground_group( string $title ): string {
    return sprintf( "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">%s</h2>\n<!-- /wp:heading -->\n\n", esc_html( $title ) );
}

$sections = [
    [ 'group' => 'Search & dates' ],
    [ 'Search', 'Date and location search widget.', '[fleetwire_search]' ],
    [ 'Availability', 'Same widget as search today (shares the fleetwire-search class).', '[fleetwire_availability]' ],
    [ 'Datepicker', 'Standalone trip dates picker; feeds the booking links below.', '[fleetwire_datepicker]' ],

    [ 'group' => 'Listing lists' ],
    [ 'List — whole fleet', 'Every bookable listing, in the host order.', '[fleetwire_list]' ],
    [ 'List — limit 3', 'Caps the list at 3, no pagination.', '[fleetwire_list limit="3"]' ],
    [ 'List — 2 per page', 'Pages through the fleet two at a time.', '[fleetwire_list per="2"]' ],
    [ 'List — no prices', 'Price hidden on every card.', '[fleetwire_list limit="3" showprice="false"]' ],

    [ 'group' => 'Single listing' ],
    [ 'Listing card', 'One card for a chosen listing.', "[fleetwire_listing_card id=\"$featured\"]" ],
    [ 'Listing card — no price', '', "[fleetwire_listing_card id=\"$second\" showprice=\"false\"]" ],
    [ 'Detail', 'Alias of the listing card.', "[fleetwire_detail id=\"$second\"]" ],
    [ 'Book button', 'Button into checkout.', "[fleetwire_button id=\"$featured\"]" ],
    [ 'Book button — custom title + price', '', "[fleetwire_button id=\"$featured\" custom-title=\"Reserve this car\" show-price=\"true\"]" ],
    [ 'Gallery', 'Every photo with thumbnails (listing with the most photos).', "[fleetwire_listing_image_gallery id=\"$featured\"]" ],
    [ 'Image', 'First photo only.', "[fleetwire_listing_image id=\"$featured\"]" ],
    [ 'Title', '', "[fleetwire_listing_title id=\"$featured\"]" ],
    [ 'Title — with labels', 'Label order is inverted on purpose (see composeLabeledText).', "[fleetwire_listing_title id=\"$featured\" append-label=\"Vehicle: \"]" ],
    [ 'Price', '', "[fleetwire_listing_price id=\"$featured\" append-label=\"From \" prepend-label=\" / day\"]" ],
    [ 'Seats', '', "[fleetwire_listing_seats id=\"$featured\"]" ],
    [ 'Doors', '', "[fleetwire_listing_doors id=\"$featured\"]" ],
    [ 'Description', 'Full text.', "[fleetwire_listing_description id=\"$featured\"]" ],
    [ 'Description — 120 characters', '', "[fleetwire_listing_description id=\"$featured\" length=\"120\"]" ],

    [ 'group' => 'Retired (render nothing on purpose)' ],
    [ 'Features', 'Kept registered so old pages do not print raw shortcode text.', "[fleetwire_listing_features id=\"$featured\"]" ],
    [ 'Cart button', '', '[fleetwire_cart_button]' ],
];

$content = "<!-- wp:paragraph -->\n<p>Every Fleetwire shortcode and option with its live render. Regenerate with <code>php tests/playground/seed-playground.php " . esc_html( $wordpress_path ) . "</code>. Data from <code>" . esc_html( Config::get_api_base_url() ) . "</code>.</p>\n<!-- /wp:paragraph -->\n\n";

foreach ( $sections as $section ) {
    $content .= isset( $section['group'] )
        ? fleetwire_playground_group( $section['group'] )
        : fleetwire_playground_section( ...$section );
}

$existing = get_page_by_path( 'fleetwire-playground', OBJECT, 'page' );
$page_id = wp_insert_post( [
    'ID'           => $existing ? $existing->ID : 0,
    'post_type'    => 'page',
    'post_status'  => 'publish',
    'post_title'   => 'Fleetwire Playground',
    'post_name'    => 'fleetwire-playground',
    'post_content' => $content,
], true );

if ( is_wp_error( $page_id ) ) {
    fwrite( STDERR, $page_id->get_error_message() . "\n" );
    exit( 1 );
}

echo get_permalink( $page_id ), "\n";
