<?php
/**
 * Site builder tests, run inside a real WordPress install with this plugin active:
 *
 *   php tests/site-builder.php /path/to/wordpress
 *
 * Uses fixture listings, then deletes every page and menu item it created and
 * restores the options it touched (builder state, front page, menu locations,
 * listings cache).
 */

if ( PHP_SAPI !== 'cli' ) {
    exit( 1 );
}

$wordpress_path = rtrim( $argv[1] ?? '', '/' );
if ( ! is_file( $wordpress_path . '/wp-load.php' ) ) {
    fwrite( STDERR, "Usage: php tests/site-builder.php /path/to/wordpress\n" );
    exit( 1 );
}

require $wordpress_path . '/wp-load.php';

use Fleetwire\Config;
use Fleetwire\SiteBuilder;

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

function fixture_listing( string $id ): array {
    return [
        'id'       => $id,
        'name'     => 'Builder Test ' . $id,
        'active'   => true,
        'approved' => true,
        'paused'   => false,
        'image'    => "https://img.test/$id.jpg",
        'images'   => [ "https://img.test/$id.jpg" ],
    ];
}

$admin = get_users( [ 'role' => 'administrator', 'number' => 1 ] );
wp_set_current_user( $admin[0]->ID );

$cache_key = 'fleetwire_listings_' . md5( Config::get_api_base_url() );
$backup = [
    'cache'         => get_transient( $cache_key ),
    'state'         => get_option( SiteBuilder::OPTION, null ),
    'show_on_front' => get_option( 'show_on_front' ),
    'page_on_front' => get_option( 'page_on_front' ),
    'locations'     => get_theme_mod( 'nav_menu_locations' ),
    'menus'         => wp_list_pluck( wp_get_nav_menus(), 'term_id' ),
    'navigation'    => wp_list_pluck( get_posts( [ 'post_type' => 'wp_navigation', 'post_status' => 'any', 'numberposts' => -1 ] ), 'post_content', 'ID' ),
    'menu_items'    => get_posts( [ 'post_type' => 'nav_menu_item', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ] ),
];

$fixtures = array_map( 'fixture_listing', array_map( function ( $n ) {
    return 'l_BT' . $n;
}, range( 1, 14 ) ) );
set_transient( $cache_key, $fixtures, HOUR_IN_SECONDS );
delete_option( SiteBuilder::OPTION );

$builder = \Fleetwire\Fleetwire::get_instance()->site_builder;
$created_ids = [];
$track = function ( array $summary ) use ( &$created_ids ) {
    foreach ( array_merge( $summary['pages'] ?? [], $summary['vehicles'] ?? [] ) as $page ) {
        // Only pages this run created: "existing" entries are the site's own pages and must survive.
        if ( $page['status'] === 'created' && preg_match( '/post=(\d+)/', $page['edit_url'], $m ) ) {
            $created_ids[ (int) $m[1] ] = true;
        }
    }
};

$fixture_pages = [];
$all_pages = [ 'home', 'fleet', 'faq', 'contact' ];

try {
    // Taken slugs: the site already has published /fleet/ and /faq/ pages.
    foreach ( [ 'fleet' => 'Site Fleet', 'faq' => 'Site FAQ' ] as $slug => $title ) {
        if ( ! get_posts( [ 'post_type' => 'page', 'name' => $slug, 'post_status' => 'publish', 'numberposts' => 1 ] ) ) {
            $fixture_pages[] = wp_insert_post( [ 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug ] );
        }
    }
    $plan = $builder->plan();
    check( $plan['fleet']['taken_by'] && $plan['faq']['taken_by'], 'plan spots the taken /fleet/ and /faq/ slugs' );
    $defaults = $builder->default_page_keys();
    check( ! in_array( 'faq', $defaults, true ) && ! in_array( 'fleet', $defaults, true ), 'taken FAQ and Our Fleet are not created by default' );
    check( in_array( 'home', $defaults, true ), 'Home is created by default even when /home/ is taken' );

    $summary = $builder->build( [], false, true );
    $track( $summary );
    $statuses = array_column( $summary['pages'], 'status', 'title' );
    check( ( $statuses[ get_the_title( $plan['faq']['taken_by'] ) ] ?? '' ) === 'existing', 'the site FAQ is reused, not duplicated' );
    check( ! isset( $builder->state()['pages']['faq'] ) && ! isset( $builder->state()['pages']['fleet'] ), 'no FAQ or Our Fleet page is created' );
    $home = get_post( $builder->state()['pages']['home'] );
    check( str_contains( $home->post_content, 'href="' . esc_url( wp_make_link_relative( get_permalink( $plan['fleet']['taken_by'] ) ) ) . '"' ), '"See all vehicles" links to the site\'s existing fleet page' );
    if ( ! wp_is_block_theme() && get_registered_nav_menus() ) {
        $menu_id = get_nav_menu_locations()[ array_key_first( get_registered_nav_menus() ) ] ?? 0;
        check( in_array( $plan['faq']['taken_by']->ID, SiteBuilder::menu_page_ids( (int) $menu_id ), true ), 'the menu links the site\'s existing FAQ' );
    }

    // Start over for the full flow.
    foreach ( array_merge( $builder->state()['pages'], $builder->state()['vehicles'] ) as $page_id ) {
        wp_delete_post( (int) $page_id, true );
    }
    delete_option( SiteBuilder::OPTION );

    // First run.
    $summary = $builder->build( [ 'l_BT1', 'l_BT2', 'l_UNKNOWN', 'l_"><script>' ], true, true, $all_pages );
    check( ! is_wp_error( $summary ), 'first build succeeds' );
    $track( $summary );
    $state = $builder->state();

    check( count( $summary['pages'] ) === 4 && ! array_diff( array_column( $summary['pages'], 'status' ), [ 'created' ] ), 'creates Home, Our Fleet, FAQ and Contact' );
    check( count( $summary['vehicles'] ) === 2, 'creates one page per chosen bookable vehicle, ignoring unknown and unsafe ids' );
    check( isset( $state['vehicles']['l_BT1'], $state['vehicles']['l_BT2'] ) && count( $state['vehicles'] ) === 2, 'records the vehicle pages' );

    foreach ( array_merge( $state['pages'], $state['vehicles'] ) as $page_id ) {
        $page = get_post( $page_id );
        check( $page && $page->post_status === 'draft', "page $page_id is a draft" );
        check( ! str_contains( $page->post_content, '{{' ), "page $page_id has no unreplaced tokens" );
        $names = wp_list_pluck( parse_blocks( $page->post_content ), 'blockName' );
        check( count( array_filter( $names ) ) > 0 && ! in_array( 'core/freeform', $names, true ), "page $page_id is made of blocks" );
    }

    $home = get_post( $state['pages']['home'] );
    $fleet = get_post( $state['pages']['fleet'] );
    $fleet_path = wp_make_link_relative( get_permalink( $fleet ) );
    list( $template, $name ) = get_sample_permalink( $fleet->ID );
    $fleet_sample = wp_make_link_relative( str_replace( [ '%pagename%', '%postname%' ], $name, $template ) );
    check( str_contains( $home->post_content, 'href="' . esc_url( $fleet_sample ) . '"' ), 'Home links to the Our Fleet page' );
    check( str_contains( get_post( $state['vehicles']['l_BT1'] )->post_content, '"listingId":"l_BT1"' ), 'vehicle page shows its own vehicle' );
    check( str_contains( $fleet->post_content, '"limit":0' ), 'Our Fleet lists every vehicle' );

    check( $summary['front_page'] === 'pending' && $state['front_page_pending'], 'front page waits for Home to be published' );
    check( get_option( 'page_on_front' ) == $backup['page_on_front'], 'the live front page is untouched while Home is a draft' );
    check( is_string( $summary['menu'] ) && $summary['menu'] !== '', 'reports where the menu links went' );
    check( ! empty( $summary['home_preview_url'] ), 'returns a Home preview link' );

    if ( ! wp_is_block_theme() && get_registered_nav_menus() ) {
        $location = array_key_first( get_registered_nav_menus() );
        $menu_id = get_nav_menu_locations()[ $location ] ?? 0;
        $linked = SiteBuilder::menu_page_ids( (int) $menu_id );
        check( ! array_diff( [ $home->ID, $fleet->ID ], $linked ), 'core pages are in the theme menu' );

        // Front end: draft pages must not show in the menu.
        wp_set_current_user( 0 );
        $html = wp_nav_menu( [ 'menu' => $menu_id, 'echo' => false, 'fallback_cb' => false ] );
        wp_set_current_user( $admin[0]->ID );
        check( ! str_contains( (string) $html, 'page_id=' . $home->ID ) && ! str_contains( (string) $html, 'page_id=' . $fleet->ID ), 'draft pages are hidden from the live menu' );
    }

    // Publishing Home applies the front page.
    wp_update_post( [ 'ID' => $home->ID, 'post_status' => 'publish' ] );
    if ( ! wp_is_block_theme() && get_registered_nav_menus() ) {
        wp_set_current_user( 0 );
        $html = wp_nav_menu( [ 'menu' => $menu_id, 'echo' => false, 'fallback_cb' => false ] );
        wp_set_current_user( $admin[0]->ID );
        check( str_contains( (string) $html, get_permalink( $home ) ) && ! str_contains( (string) $html, 'page_id=' . $fleet->ID ), 'publishing Home adds only Home to the live menu' );
    }
    check( get_option( 'show_on_front' ) === 'page' && (int) get_option( 'page_on_front' ) === $home->ID, 'publishing Home makes it the front page' );
    check( ! $builder->state()['front_page_pending'], 'the pending front page is cleared' );

    if ( wp_is_block_theme() ) {
        $navigation = get_posts( [ 'post_type' => 'wp_navigation', 'post_status' => 'publish', 'numberposts' => 1, 'orderby' => 'date', 'order' => 'DESC' ] )[0] ?? null;
        check( $navigation && str_contains( $navigation->post_content, '"id":' . $fleet->ID ), 'core pages are in the navigation menu' );
        $html = do_blocks( $navigation->post_content );
        // Match by address, not label: the site's own navigation may already link a published "Our Fleet".
        check( str_contains( $html, wp_make_link_relative( get_permalink( $home ) ) ) && ! str_contains( $html, '"' . $fleet_sample . '"' ), 'navigation shows published Home but not the Our Fleet draft' );
    }

    // Second run: no duplicates, only new vehicles.
    $summary = $builder->build( [ 'l_BT2', 'l_BT3' ], false, true, $all_pages );
    $track( $summary );
    check( ! array_diff( array_column( $summary['pages'], 'status' ), [ 'kept' ] ), 'second run keeps the core pages' );
    $vehicle_statuses = array_column( $summary['vehicles'], 'status', 'title' );
    check( ( $vehicle_statuses['Builder Test l_BT2'] ?? '' ) === 'kept' && ( $vehicle_statuses['Builder Test l_BT3'] ?? '' ) === 'created', 'second run adds only the new vehicle' );
    check( count( get_posts( [ 'post_type' => 'page', 'post_status' => 'any', 'title' => 'Our Fleet', 'numberposts' => -1, 'post__in' => array_keys( $created_ids ) ] ) ) === 1, 'Our Fleet exists once' );

    // A trashed page comes back.
    wp_trash_post( $fleet->ID );
    $summary = $builder->build( [], false, false, $all_pages );
    $track( $summary );
    $fleet_status = array_column( $summary['pages'], 'status', 'title' )['Our Fleet'] ?? '';
    check( $fleet_status === 'created', 'a trashed core page is recreated' );

    // Cap.
    $many = array_map( function ( $listing ) {
        return $listing['id'];
    }, $fixtures );
    $summary = $builder->build( $many, false, false, $all_pages );
    $track( $summary );
    check( count( array_filter( $summary['vehicles'], function ( $page ) {
        return $page['status'] === 'created';
    } ) ) <= SiteBuilder::MAX_VEHICLE_PAGES, 'one run creates at most ' . SiteBuilder::MAX_VEHICLE_PAGES . ' vehicle pages' );

    // Publish all.
    check( $builder->draft_count() > 0, 'there are drafts to publish' );
    $published = $builder->publish_drafts();
    check( count( $published ) > 0 && $builder->draft_count() === 0, 'publish all publishes every builder draft' );
    foreach ( array_merge( $builder->state()['pages'], $builder->state()['vehicles'] ) as $page_id ) {
        check( get_post_status( $page_id ) === 'publish', "page $page_id is published" );
    }
    if ( ! wp_is_block_theme() && get_registered_nav_menus() ) {
        wp_set_current_user( 0 );
        $html = wp_nav_menu( [ 'menu' => $menu_id, 'echo' => false, 'fallback_cb' => false ] );
        wp_set_current_user( $admin[0]->ID );
        // Contact's menu item was held back as a draft in the first run (Our Fleet was trashed and rebuilt without the menu option).
        check( str_contains( (string) $html, get_permalink( $builder->state()['pages']['contact'] ) ), 'publishing all brings the held-back menu links live' );
    }
} finally {
    foreach ( $fixture_pages as $fixture_page ) {
        wp_delete_post( (int) $fixture_page, true );
    }
    $state = $builder->state();
    foreach ( array_merge( array_keys( $created_ids ), $state['pages'], $state['vehicles'] ) as $page_id ) {
        wp_delete_post( (int) $page_id, true );
    }

    // Exact: remove every menu item the run added (including links to the site's own reused pages), then any new menu.
    foreach ( get_posts( [ 'post_type' => 'nav_menu_item', 'post_status' => 'any', 'numberposts' => -1, 'fields' => 'ids' ] ) as $item_id ) {
        if ( ! in_array( $item_id, $backup['menu_items'], true ) ) {
            wp_delete_post( $item_id, true );
        }
    }
    foreach ( wp_get_nav_menus() as $menu ) {
        if ( ! in_array( $menu->term_id, $backup['menus'], true ) ) {
            wp_delete_nav_menu( $menu->term_id );
        }
    }
    foreach ( get_posts( [ 'post_type' => 'wp_navigation', 'post_status' => 'any', 'numberposts' => -1 ] ) as $navigation ) {
        if ( array_key_exists( $navigation->ID, $backup['navigation'] ) ) {
            wp_update_post( [ 'ID' => $navigation->ID, 'post_content' => $backup['navigation'][ $navigation->ID ] ] );
        } else {
            wp_delete_post( $navigation->ID, true );
        }
    }

    set_theme_mod( 'nav_menu_locations', $backup['locations'] );
    update_option( 'show_on_front', $backup['show_on_front'] );
    update_option( 'page_on_front', $backup['page_on_front'] );
    $backup['state'] === null ? delete_option( SiteBuilder::OPTION ) : update_option( SiteBuilder::OPTION, $backup['state'] );
    is_array( $backup['cache'] ) ? set_transient( $cache_key, $backup['cache'], 12 * HOUR_IN_SECONDS ) : delete_transient( $cache_key );
}

echo "$passes passed, $failures failed\n";
exit( $failures ? 1 : 0 );
