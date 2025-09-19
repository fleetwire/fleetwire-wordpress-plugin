<?php
/**
 * @wordpress-plugin
 * Plugin Name: Fleetwire Fleet Management Plugin
 * Description: Fleet management plugin for WordPress. Turn your website into a complete online rental store by connecting your Fleetwire.io account to WordPress.
 * Author: Fleetwire, LLC
 * Version: 1.0.20
 * Author URI: https://fleetwire.io
 * Copyright: 2025 Fleetwire, LLC
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

define( 'FLEETWIRE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'FLEETWIRE_TEXT_DOMAIN', 'fleetwire');
define( 'FLEETWIRE_VERSION', '1.0.20' );

// Constants for configuration
const FLEETWIRE_MENU_POSITION = 99;
const FLEETWIRE_SCRIPT_VERSION = '1.0.8';

//
// Admin
//

/**
 * Include admin settings page
 * 
 * @since 1.0.20
 * @return void
 */
function fleetwire_admin() {
	include FLEETWIRE_PLUGIN_DIR . 'admin/fleetwire_admin.php';
}


/**
 * Process company name form submission
 * 
 * @since 1.0.20
 * @return void
 */
function fleetwire_company_name_form_process() {
    // Check user capabilities
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [
            'message' => __( 'Insufficient permissions.', 'fleetwire' )
        ] );
    }
    
    // Verify nonce
    if ( ! wp_verify_nonce( 
        $_POST['fw_nonce'] ?? '', 
        'fleetwire_company_name_form_process' 
    ) ) {
        wp_send_json_error( [
            'message' => __( 'Security check failed.', 'fleetwire' )
        ] );
    }
    
    // Validate and sanitize input
    $company = sanitize_text_field( $_POST['fleetwire_company_name'] ?? '' );
    
    if ( empty( $company ) ) {
        wp_send_json_error( [
            'message' => __( 'Company ID is required.', 'fleetwire' )
        ] );
    }
    
    // Validate UUID format (basic check)
    if ( ! preg_match( '/^[a-f0-9\-]{36}$/i', $company ) ) {
        wp_send_json_error( [
            'message' => __( 'Invalid Company ID format.', 'fleetwire' )
        ] );
    }
    
    // Update option
    $updated = update_option( 'fleetwire_company_name', $company );
    
    if ( $updated ) {
        wp_send_json_success( [
            'message' => __( 'Options saved.', 'fleetwire' )
        ] );
    } else {
        wp_send_json_error( [
            'message' => __( 'No changes made.', 'fleetwire' )
        ] );
    }
}


/**
 * Add Fleetwire options page
 * 
 * @since 1.0.20
 * @return void
 */
function fleetwire_add_options_page() {
	add_options_page( 
		__( 'Fleetwire', 'fleetwire' ), 
		__( 'Fleetwire', 'fleetwire' ), 
		'manage_options', 
		'Fleetwire', 
		'fleetwire_admin' 
	);
}

/**
 * Add Fleetwire to the main admin menu
 * 
 * @since 1.0.20
 * @return void
 */
function fleetwire_add_main_menu_page() {
	add_menu_page(
		__( 'Fleetwire', 'fleetwire' ),                       // Page title
		__( 'Fleetwire', 'fleetwire' ),                       // Menu title
		'manage_options',                  // Capability
		'fleetwire',                       // Menu slug
		'fleetwire_admin',                 // Callback function
		plugin_dir_url(__FILE__) . 'assets/favicon-16x16.png', // Icon URL
		FLEETWIRE_MENU_POSITION                                // Position
	);
}

/**
 * Enqueue admin styles and scripts
 * 
 * @since 1.0.20
 * @param string $hook Current admin page hook
 * @return void
 */
function fleetwire_enqueue_admin_assets( $hook ) {
	// Load only on Fleetwire pages
	if ( $hook !== 'settings_page_Fleetwire' && $hook !== 'toplevel_page_fleetwire') {
		return;
	}
	
	// Enqueue styles
	wp_enqueue_style( 
		'fleetwire-admin', 
		plugins_url( 'assets/fleetwire-admins.css', __FILE__ ),
		[],
		FLEETWIRE_VERSION
	);
	
	// Enqueue scripts
	wp_enqueue_script(
		'fleetwire-admin',
		plugins_url('assets/fleetwire-admins.js', __FILE__),
		['jquery'],
		FLEETWIRE_VERSION,
		true
	);
	
	// Localize script for AJAX
	wp_localize_script('fleetwire-admin', 'fleetwireAdmin', [
		'ajaxUrl' => admin_url('admin-ajax.php'),
		'nonce' => wp_create_nonce('fleetwire_company_name_form_process'),
		'strings' => [
			'error' => __('An error occurred. Please try again.', 'fleetwire'),
			'success' => __('Options saved.', 'fleetwire')
		]
	]);
}


/**
 * Legacy admin JS function - now handled by fleetwire_enqueue_admin_assets
 * 
 * @since 1.0.20
 * @deprecated Use fleetwire_enqueue_admin_assets instead
 * @return void
 */
function fleetwire_admin_js() {
	// This function is now deprecated and handled by fleetwire_enqueue_admin_assets
	// The JavaScript is now in a separate file: assets/fleetwire-admin.js
    _deprecated_function( __FUNCTION__, '1.0.20', 'fleetwire_enqueue_admin_assets' );
}

/**
 * Add a settings link to plugin actions
 * 
 * @since 1.0.20
 * @param array $links Plugin action links
 * @return array Modified links
 */
function fleetwire_admin_settings_link( $links ) {
	$settings_link = '<a href="options-general.php?page=Fleetwire">' . 
		esc_html__( 'Settings', 'fleetwire' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

/**
 * Display admin notices
 * 
 * @since 1.0.20
 * @return void
 */
function fleetwire_admin_notice() {
	global $hook_suffix;

	if ( in_array( $hook_suffix, array( 'jetpack_page_akismet-key-config', 'settings_page_akismet-key-config' ) ) ) {
		// This page manages the notices and puts them inline where they make sense.
		return;
	}

	if ( $hook_suffix === 'plugins.php' && ! trim( get_option( 'fleetwire_company_name' ) ) ) {
		include( 'admin/fleetwire_notice.php' );
	}
}

//
// Assets
//

/**
 * Load Fleetwire client script
 * 
 * @since 1.0.20
 * @return void
 */
function fleetwire_enqueue_client_script() {
	$company = trim( get_option( 'fleetwire_company_name' ) );

	if ( $company ) {
		$asset_url = "https://$company.fleetwire.io/tenant/v2/fleetwire.js";
		//    $asset_url = 'http://fleetwire.test/tenant/v2/fleetwire.js';
		wp_enqueue_script( 
			'fleetwire_client', 
			$asset_url, 
			[], 
			FLEETWIRE_SCRIPT_VERSION, 
			true 
		);
	}
}

/**
 * Insert client configuration script
 * 
 * @since 1.0.20
 * @return void
 */
function fleetwire_add_client_configuration() {
	$company = get_option( 'fleetwire_company_name' );
	if ( ! $company ) {
		return;
	}
	?>
    <script>window.fleetwireOptions = {
            company: '<?php echo esc_js( $company ); ?>',
            storeProvider: 'wordpress'
        };</script>
	<?php
}

//
// fleetwire codes
//

// Convert shortcode options to data-{key}={value}
function fleetwire_shortcode_options_to_data( $options ) {
    $sanitized = [];

    foreach ( $options as $key => $value ) {
        if ( ! empty( $value ) ) {
            $key   = sanitize_key( $key );
            $value = sanitize_text_field( $value );
            $sanitized[] = sprintf( 'data-%s="%s"', esc_attr( $key ), esc_attr( $value ) );
        }
    }

    return implode( ' ', $sanitized );
}

// fleetwire code for embedding a product
// [fleetwire_card id="l_PP4QD3PM"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_card_bb( $params ) {
	$options = shortcode_atts( array(
		'id'        => null,
		'showprice' => null,
	), $params );

	return '<div class="fleetwire-listing-card" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}


// fleetwire code for embedding a product button
// [fleetwire_button id="l_PP4QD3PM"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_button_bb( $params ) {
	$options = shortcode_atts( array(
		'id' => null
	), $params );

	return '<div class="fleetwire-product-button" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire code for embedding a product detail view
// [fleetwire_detail id="l_PP4QD3PM"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_detail_bb( $params ) {
	$options = shortcode_atts( array(
		'id' => null
	), $params );

	return '<div class="fleetwire-product-detail" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire shortcode for embedding a vehicle door count
// [fleetwire_listing_doors id="l_PP4QD3PM"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_vehicle_doors_bb( $params ) {
	$options = shortcode_atts( array(
		'id' => null
	), $params );

	return '<span class="fleetwire-listing-doors" ' . fleetwire_shortcode_options_to_data( $options ) . '></span>';
}

// fleetwire shortcode for embedding a vehicle seat count
// [fleetwire_listing_seats id="l_PP4QD3PM"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_vehicle_seats_bb( $params ) {
	$options = shortcode_atts( array(
		'id' => null
	), $params );

	return '<span class="fleetwire-listing-seats" ' . fleetwire_shortcode_options_to_data( $options ) . '></span>';
}


// fleetwire shortcode for embedding a vehicles feature list
// [fleetwire_listing_features id="l_PP4QD3PM"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_vehicle_features_bb( $params ) {
	$options = shortcode_atts( array(
		'id' => null
	), $params );

	return '<span class="fleetwire-listing-features" ' . fleetwire_shortcode_options_to_data( $options ) . '></span>';
}

// fleetwire shortcode for embedding a vehicle review
// [fleetwire_listing_reviews id="l_PP4QD3PM"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_vehicle_reviews_bb( $params ) {
	$options = shortcode_atts( array(
		'id'         => null,
		'showsource' => null,
	), $params );

	return '<span class="fleetwire-listing-reviews" ' . fleetwire_shortcode_options_to_data( $options ) . '></span>';
}

// fleetwire shortcode for embedding a vehicles image gallery
// [fleetwire_listing_image_gallery id="l_PP4QD3PM"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_vehicle_image_gallery_bb( $params ) {
	$options = shortcode_atts( array(
		'id' => null,
	), $params );

	return '<span class="fleetwire-listing-image-gallery" ' . fleetwire_shortcode_options_to_data( $options ) . '></span>';
}

// fleetwire code for embedding a product list
// [fleetwire_list]
// [fleetwire_list tags="tablets"]
// [fleetwire_list categories="apple"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_list_bb( $params ) {

	$options = shortcode_atts( array(
		'tags'        => null,
		'categories'  => null,
		'per'         => null,
		'limit'       => null,
		'show-search' => null,
		'showprice'   => null,
		'search-key'  => null
	), $params );

	return '<div class="fleetwire-listing-list" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire code for embedding a product search
// [fleetwire_search]
// [fleetwire_search search-key="only-tablets"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_search_bb( $params ) {
	$options = shortcode_atts( array(
		'search-key' => null
	), $params );

	return '<div class="fleetwire-product-search" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire code for embedding an availability checker
// [fleetwire_availability]
// [fleetwire_availability data-show-location-picker="false"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_availability( $params ) {
	$options = shortcode_atts( array(
		'data-show-location-picker' => null
	), $params );

	return '<div class="fleetwire-search" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire code for datepicker
// [fleetwire_datepicker]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_datepicker_bb( $params ) {
	$options = shortcode_atts( array(), $params );

	return '<div class="fleetwire-datepicker" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire code for embedding a cart button
// [fleetwire_cart_button]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_cart_button_bb( $params ) {
	$options = shortcode_atts( array(
		'href' => null
	), $params );

	return '<div class="fleetwire-cart-button" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire code for embedding the embeddable cart
// [fleetwire_embeddable_cart]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_embeddable_cart_bb( ) {
	return '<div class="fleetwire-embeddable-cart"></div>';
}

// fleetwire code for embedding the embeddable cart lines
// [fleetwire_embeddable_cart_lines]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_embeddable_cart_lines_bb( $params ) {
	$options = shortcode_atts( array(
		'compact' => null
	), $params );

	return '<div class="fleetwire-embeddable-cart-lines" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire code for embedding the embeddable cart sidebar
// [fleetwire_embeddable_cart_sidebar]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_embeddable_cart_sidebar_bb( $params ) {
	$options = shortcode_atts( array(
		'continue-shopping' => null,
		'datepicker'        => null
	), $params );

	return '<div class="fleetwire-embeddable-cart-sidebar" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire code for embedding a sidebar with datepicker and categories
// [fleetwire_sidebar]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_sidebar_bb( $params ) {
	$options = shortcode_atts( array(
		'search-key' => null
	), $params );

	return '<div class="fleetwire-sidebar" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire code for embedding sorting select
// [fleetwire_sort]
// [fleetwire_sort search-key="only-tablets"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_sort_bb( $params ) {
	$options = shortcode_atts( array(
		'search-key' => null
	), $params );

	return '<div class="fleetwire-sort" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire code for embedding a bar with search and sorting select
// [fleetwire_bar]
// [fleetwire_bar search-key="only-tablets"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_bar_bb( $params ) {
	$options = shortcode_atts( array(
		'search-key' => null,
	), $params );

	return '<div class="fleetwire-bar" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire code for embedding a category list
// [fleetwire_categories]
// [fleetwire_categories search-key="only-tablets"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_categories_bb( $params ) {
	$options = shortcode_atts( array(
		'search-key' => null
	), $params );

	return '<div class="fleetwire-categories" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

// fleetwire code for embedding a listing description
// [fleetwire_listing_description]
// [fleetwire_listing_description id="l_PP4QD3PM"]
// [fleetwire_listing_description id="l_PP4QD3PM" length="100"]
/** @used-by fleetwire_register_shortcodes */
function fleetwire_description_bb( $params ) {
	$options = shortcode_atts( array(
		'id'     => null,
		'length' => null,
	), $params );

	return '<div class="fleetwire-listing-description" ' . fleetwire_shortcode_options_to_data( $options ) . '></div>';
}

function fleetwire_register_shortcodes() {
	$shortcodes = [
		'fleetwire_listing_card'            => 'fleetwire_card_bb',
		'fleetwire_button'                  => 'fleetwire_button_bb',
		'fleetwire_product'                 => 'fleetwire_button_bb',
		'fleetwire_detail'                  => 'fleetwire_detail_bb',
		'fleetwire_list'                    => 'fleetwire_list_bb',
		'fleetwire_search'                  => 'fleetwire_search_bb',
		'fleetwire_availability'            => 'fleetwire_availability',
		'fleetwire_cart_button'             => 'fleetwire_cart_button_bb',
		'fleetwire_embeddable_cart'         => 'fleetwire_embeddable_cart_bb',
		'fleetwire_embeddable_cart_sidebar' => 'fleetwire_embeddable_cart_sidebar_bb',
		'fleetwire_embeddable_cart_lines'   => 'fleetwire_embeddable_cart_lines_bb',
		'fleetwire_sidebar'                 => 'fleetwire_sidebar_bb',
		'fleetwire_sort'                    => 'fleetwire_sort_bb',
		'fleetwire_bar'                     => 'fleetwire_bar_bb',
		'fleetwire_categories'              => 'fleetwire_categories_bb',
		'fleetwire_listing_description'     => 'fleetwire_description_bb',
		'fleetwire_listing_doors'           => 'fleetwire_vehicle_doors_bb',
		'fleetwire_listing_seats'           => 'fleetwire_vehicle_seats_bb',
		'fleetwire_listing_reviews'         => 'fleetwire_vehicle_reviews_bb',
		'fleetwire_listing_image_gallery'   => 'fleetwire_vehicle_image_gallery_bb',
		'fleetwire_listing_features'        => 'fleetwire_vehicle_features_bb',
		'fleetwire_datepicker'              => 'fleetwire_datepicker_bb',
	];

	foreach ( $shortcodes as $tag => $handler ) {
		add_shortcode( $tag, $handler );
	}
}

/**
 * Register admin hooks
 * 
 * @since 1.0.20
 * @return void
 */
function fleetwire_register_admin_hooks() {
	add_action( 'admin_menu', 'fleetwire_add_options_page' );
	add_action( 'admin_notices', 'fleetwire_admin_notice' );
	add_action( 'admin_enqueue_scripts', 'fleetwire_enqueue_admin_assets' );
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'fleetwire_admin_settings_link' );
	add_action( 'admin_menu', 'fleetwire_add_main_menu_page' );
}

/**
 * Register frontend hooks
 * 
 * @since 1.0.20
 * @return void
 */
function fleetwire_register_frontend_hooks() {
	add_action( 'wp_enqueue_scripts', 'fleetwire_enqueue_client_script' );
	add_action( 'wp_head', 'fleetwire_add_client_configuration' );
	add_action( 'wp_ajax_fleetwire_company_name_form_process', 'fleetwire_company_name_form_process' );
}

/**
 * Initialize the Fleetwire plugin
 * 
 * @since 1.0.20
 * @return void
 */
function fleetwire_initialize() {
	fleetwire_register_admin_hooks();
	fleetwire_register_frontend_hooks();
	fleetwire_register_shortcodes();
}

fleetwire_initialize();

?>
