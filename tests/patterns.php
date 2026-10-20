<?php
/**
 * Pattern tests, run inside a real WordPress install with this plugin active:
 *
 *   php tests/patterns.php /path/to/wordpress
 *
 * Swaps the listings cache for fixtures, re-registers the patterns and checks what
 * gets registered. The cache is restored afterwards. Block validity of the markup is
 * checked in the editor (wp.blocks.parse → isValid); see includes/Patterns.php.
 */

if ( PHP_SAPI !== 'cli' ) {
    exit( 1 );
}

$wordpress_path = rtrim( $argv[1] ?? '', '/' );
if ( ! is_file( $wordpress_path . '/wp-load.php' ) ) {
    fwrite( STDERR, "Usage: php tests/patterns.php /path/to/wordpress\n" );
    exit( 1 );
}

require $wordpress_path . '/wp-load.php';

use Fleetwire\Config;
use Fleetwire\Patterns;

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

$cache_key = 'fleetwire_listings_' . md5( Config::get_api_base_url() );
$original_cache = get_transient( $cache_key );

function listing( string $id, array $overrides = [] ): array {
    return array_merge( [
        'id'       => $id,
        'name'     => 'Vehicle ' . $id,
        'active'   => true,
        'approved' => true,
        'paused'   => false,
        'image'    => "https://img.test/$id.jpg",
        'images'   => [ "https://img.test/$id.jpg" ],
    ], $overrides );
}

/**
 * Re-register patterns against the given cache and return name => content.
 */
function register_with_cache( $listings ): array {
    global $cache_key;
    $registry = WP_Block_Patterns_Registry::get_instance();
    foreach ( $registry->get_all_registered() as $pattern ) {
        if ( str_starts_with( $pattern['name'], 'fleetwire/' ) ) {
            $registry->unregister( $pattern['name'] );
        }
    }

    if ( $listings === null ) {
        delete_transient( $cache_key );
    } else {
        set_transient( $cache_key, $listings, HOUR_IN_SECONDS );
    }

    ( new Patterns() )->register_patterns();

    $registered = [];
    foreach ( $registry->get_all_registered() as $pattern ) {
        if ( str_starts_with( $pattern['name'], 'fleetwire/' ) ) {
            $registered[ $pattern['name'] ] = $pattern;
        }
    }

    return $registered;
}

$fleet_patterns = [ 'hero-search', 'fleet-grid', 'how-it-works', 'why-book-direct', 'trust-bar', 'reviews', 'faq', 'requirements', 'cta-band', 'contact' ];

try {
    // No cache: fleet-wide patterns only, gradient hero.
    $patterns = register_with_cache( null );
    foreach ( $fleet_patterns as $slug ) {
        check( isset( $patterns[ "fleetwire/$slug" ] ), "$slug registers without listings" );
    }
    check( ! isset( $patterns['fleetwire/vehicle-spotlight'] ) && ! isset( $patterns['fleetwire/featured-three'] ), 'vehicle patterns are left out without listings' );
    check( str_contains( $patterns['fleetwire/hero-search']['content'], 'linear-gradient' ), 'hero falls back to a gradient without a fleet photo' );
    check( WP_Block_Pattern_Categories_Registry::get_instance()->is_registered( 'fleetwire' ), 'Fleetwire pattern category is registered' );

    foreach ( $patterns as $name => $pattern ) {
        check( ! str_contains( $pattern['content'], '{{' ), "$name has no unreplaced tokens" );
        check( $pattern['categories'] === [ 'fleetwire' ], "$name is in the Fleetwire category" );
    }

    // Full cache: vehicle patterns use real ids, hero uses a fleet photo.
    $patterns = register_with_cache( [
        listing( 'l_PAUSED', [ 'paused' => true ] ),
        listing( 'l_ONE' ),
        listing( 'l_TWO', [ 'images' => [ 'https://img.test/a.jpg', 'https://img.test/b.jpg' ] ] ),
        listing( 'l_THREE' ),
    ] );
    check( isset( $patterns['fleetwire/vehicle-spotlight'], $patterns['fleetwire/featured-three'] ), 'vehicle patterns register with three bookable listings' );
    check( str_contains( $patterns['fleetwire/vehicle-spotlight']['content'], '"listingId":"l_TWO"' ), 'spotlight uses the listing with the most photos' );
    check( str_contains( $patterns['fleetwire/vehicle-spotlight']['title'], 'Vehicle l_TWO' ), 'spotlight title names the vehicle' );
    $featured = $patterns['fleetwire/featured-three']['content'];
    check( str_contains( $featured, 'l_ONE' ) && str_contains( $featured, 'l_TWO' ) && str_contains( $featured, 'l_THREE' ), 'featured three uses the first three bookable listings' );
    check( ! str_contains( $featured . $patterns['fleetwire/vehicle-spotlight']['content'], 'l_PAUSED' ), 'paused listings never appear' );
    check( str_contains( $patterns['fleetwire/hero-search']['content'], 'src="https://img.test/l_ONE.jpg"' ), 'hero uses the first bookable fleet photo' );

    // Fewer than three: spotlight only.
    $patterns = register_with_cache( [ listing( 'l_ONE' ) ] );
    check( isset( $patterns['fleetwire/vehicle-spotlight'] ) && ! isset( $patterns['fleetwire/featured-three'] ), 'featured three needs three listings' );

    // Hostile data is refused.
    $patterns = register_with_cache( [
        listing( 'l_"><script>', [ 'images' => [ 'https://img.test/a.jpg?x=1&y="><script>' ] ] ),
    ] );
    check( ! isset( $patterns['fleetwire/vehicle-spotlight'] ), 'unsafe listing ids are skipped' );
    check( str_contains( $patterns['fleetwire/hero-search']['content'], 'linear-gradient' ), 'unsafe image urls fall back to the gradient' );
    foreach ( $patterns as $name => $pattern ) {
        check( ! str_contains( $pattern['content'], '<script>' ), "$name contains no injected markup" );
    }
} finally {
    if ( is_array( $original_cache ) ) {
        set_transient( $cache_key, $original_cache, HOUR_IN_SECONDS * 12 );
    } else {
        delete_transient( $cache_key );
    }
}

echo "$passes passed, $failures failed\n";
exit( $failures ? 1 : 0 );
