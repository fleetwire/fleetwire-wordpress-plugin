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

//
// Admin
//

// Include admin settings page
function fleetwire_admin() {
	include FLEETWIRE_PLUGIN_DIR . 'admin/fleetwire_admin.php';
}


function fleetwire_company_name_form_process() {
	if ( ! isset( $_POST['fleetwire_company_name'], $_POST['fw_nonce'] ) ) {
		wp_send_json_error( 'Missing fields' );
	}

	if ( ! wp_verify_nonce( $_POST['fw_nonce'], 'fleetwire_company_name_form_process' ) ) {
		wp_send_json_error( 'Security check failed' );
	}

	$company = sanitize_text_field( $_POST['fleetwire_company_name'] );
	update_option( 'fleetwire_company_name', $company );
	wp_send_json_success( [ 'message' => 'Options saved.' ] );
}


function fleetwire_admin_actions() {
	add_options_page( "Fleetwire", "Fleetwire", 'manage_options', "Fleetwire", "fleetwire_admin" );
}

function fleetwire_add_admin_menu_page() {
	add_menu_page(
		'Fleetwire',                       // Page title
		'Fleetwire',                       // Menu title
		'manage_options',                  // Capability
		'fleetwire',                       // Menu slug
		'fleetwire_admin',                 // Callback function
		plugin_dir_url(__FILE__) . 'assets/favicon-16x16.png', // Icon URL
		99                                // Position
	);
}

function fleetwire_admin_resources( $hook ) {
	// Load only on ?page=Fleetwire
	if ( $hook !== 'settings_page_Fleetwire' && $hook !== 'toplevel_page_fleetwire') {
		return;
	}
	wp_enqueue_style( 'fleetwire-admin', plugins_url( 'assets/fleetwire-admin.css', __FILE__ ) );
}


function fleetwire_admin_js() { ?>
    <script type="text/javascript">
        jQuery(document).ready(function ($) {
            $('#fleetwire_form').on('submit', function (e) {
                e.preventDefault();
                const companyUuid = $('.fleetwire-company-uuid').val();

                $.ajax({
                    type: 'POST',
                    url: "<?php echo admin_url( 'admin-ajax.php' ); ?>",
                    data: {
                        'fleetwire_company_name': companyUuid,
                        'fw_nonce': "<?php echo wp_create_nonce( 'fleetwire_company_name_form_process' ) ?>",
                        'action': 'fleetwire_company_name_form_process'
                    }, success: function (result) {
                        console.dir(result);
                        $('.fleetwire-card .updated').removeClass('fleetwire-d-none').addClass('fleetwire-d-block')
                    },
                    error: function () {
                        alert("error");
                    }
                });
            });
        });
    </script> <?php
}

function fleetwire_admin_settings_link( $links ) {
	$settings_link = '<a href="options-general.php?page=Fleetwire">' . __( 'Settings' ) . '</a>';
	array_unshift( $links, $settings_link );

	return $links;
}

function fleetwire_admin_notice() {
	global $hook_suffix;

	if ( in_array( $hook_suffix, array( 'jetpack_page_akismet-key-config', 'settings_page_akismet-key-config' ) ) ) {
		// This page manages the notices and puts them inline where they make sense.
		return;
	}

	if ( $hook_suffix == 'plugins.php' && ! trim( get_option( 'fleetwire_company_name' ) ) ) {
		include( 'admin/fleetwire_notice.php' );
	}
}

//
// Assets
//

// Load client script
function add_fleetwire_js() {
	$company = trim( get_option( 'fleetwire_company_name' ) );

	if ( $company ) {
		$asset_url = "https://$company.fleetwire.io/tenant/v2/fleetwire.js";
		//    $asset_url = 'http://fleetwire.test/tenant/v2/fleetwire.js';
		wp_enqueue_script( 'fleetwire_v1', $asset_url, array(), '1.0.8', true );
	}
}

// Insert client configuration
function add_fleetwire_client_configuration_js() {
	?>
    <script>window.fleetwireOptions = {
            company: '<?php echo esc_js( get_option( 'fleetwire_company_name' ) ); ?>',
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

function fleetwire_register_admin_hooks() {
	add_action( 'admin_menu', 'fleetwire_admin_actions' );
	add_action( 'admin_notices', 'fleetwire_admin_notice' );
	add_action( 'admin_enqueue_scripts', 'fleetwire_admin_resources' );
	add_action( 'admin_footer', 'fleetwire_admin_js' );
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'fleetwire_admin_settings_link' );
	add_action( 'admin_menu', 'fleetwire_add_admin_menu_page' );
}

function fleetwire_register_frontend_hooks() {
	add_action( 'wp_enqueue_scripts', 'add_fleetwire_js' );
	add_action( 'wp_head', 'add_fleetwire_client_configuration_js' );
	add_action( 'wp_ajax_fleetwire_company_name_form_process', 'fleetwire_company_name_form_process' );
}

function fleetwire_initialize() {
	fleetwire_register_admin_hooks();
	fleetwire_register_frontend_hooks();
	fleetwire_register_shortcodes();
}

fleetwire_initialize();

?>
