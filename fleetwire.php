<?php
  /**
   * @wordpress-plugin
   * Plugin Name: Fleetwire Fleet Management Plugin
   * Description: Fleet management plugin for WordPress. Turn your website into a complete online rental store by connecting your Fleetwire.io account to WordPress.
   * Author: Fleetwire, LLC
   * Version: 1.0.0
   * Author URI: https://fleetwire.io
   * Copyright: 2021 Fleetwire, LLC
   */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
  die;
}

//
// Admin
//

// Include admin settings page
function fleetwire_admin() {
  include('admin/fleetwire_admin.php');
}

function fleetwire_admin_actions() {
  add_options_page("Fleetwire", "Fleetwire", 'administrator', "Fleetwire", "fleetwire_admin");
}

function fleetwire_admin_resources($hook) {
  // Load only on ?page=mypluginname
  if($hook != 'settings_page_Fleetwire') {
    return;
  }
  wp_enqueue_style( 'fleetwire-admin', plugins_url('assets/fleetwire-admin.css', __FILE__) );
}

function fleetwire_admin_settings_link($links) {
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

  if ( $hook_suffix == 'plugins.php' && trim(get_option('fleetwire_company_name')) == false ) {
    include('admin/fleetwire_notice.php');
  }
}

//
// Assets
//

// Load client script
function add_fleetwire_js() {
//  $asset_url = 'https://' . get_option('fleetwire_company_name') . '.assets.fleetwire.io/v2/fleetwire.js';
  $asset_url = 'http://141f-68-101-211-11.ngrok.io/tenant/fleetwire.js';
  wp_enqueue_script('fleetwire_v1', $asset_url, array(), '1.0.6', true);
}

// Insert client configuration
function add_fleetwire_client_configuration_js() {
  ?>
  <script>var fleetwireOptions = { company: '<?php echo get_option('fleetwire_company_name'); ?>', storeProvider: 'wordpress' };</script>
  <?php
}

//
// BBcodes
//

// Convert shortcode options to data-{key}={value}
function fleetwire_shortcode_options_to_data($options) {
  $data = implode(' ', array_map(
    function ($v, $k) {
      if (! empty($v)) {
        return sprintf("data-%s=\"%s\"", $k, $v);
      }
    },
    $options,
    array_keys($options)
  ));

  return $data;
}

// BBcode for embedding a product
// [fleetwire_card id="ipad"]
function fleetwire_card_bb($params) {
  $options = shortcode_atts(array(
    'id' => NULL,
      'showprice'       => NULL,
  ), $params);

  return '<div class="fleetwire-listing-card" ' . fleetwire_shortcode_options_to_data($options) . '></div>';
}


// BBcode for embedding a product button
// [fleetwire_button id="ipad"]
function fleetwire_button_bb($params) {
  $options = shortcode_atts(array(
    'id' => NULL
  ), $params);

  return '<div class="fleetwire-product-button" ' . fleetwire_shortcode_options_to_data($options) . '></div>';
}

// BBcode for embedding a product detail view
// [fleetwire_detail id="ipad"]
function fleetwire_detail_bb($params) {
  $options = shortcode_atts(array(
    'id' => NULL
  ), $params);

  return '<div class="fleetwire-product-detail" ' . fleetwire_shortcode_options_to_data($options) . '></div>';
}

// BBcode for embedding a product list
// [fleetwire_list]
// [fleetwire_list tags="tablets"]
// [fleetwire_list categories="apple"]
function fleetwire_list_bb($params) {
  $options = shortcode_atts(array(
    'tags'        => NULL,
    'categories'  => NULL,
    'per'         => NULL,
    'limit'       => NULL,
    'show-search' => NULL,
    'search-key'  => NULL
  ), $params);

  return '<div class="fleetwire-listing-list" ' . fleetwire_shortcode_options_to_data($options) . '></div>';
}

// BBcode for embedding a product search
// [fleetwire_search]
// [fleetwire_search search-key="only-tablets"]
function fleetwire_search_bb($params) {
  $options = shortcode_atts(array(
    'search-key'  => NULL
  ), $params);

  return '<div class="fleetwire-product-search" ' . fleetwire_shortcode_options_to_data($options) . '></div>';
}

// BBcode for datepicker
// [fleetwire_datepicker]
function fleetwire_datepicker_bb($params) {
  $options = shortcode_atts(array(), $params);

  return '<div class="fleetwire-datepicker" ' . fleetwire_shortcode_options_to_data($options) . '></div>';
}
add_shortcode('fleetwire_datepicker', 'fleetwire_datepicker_bb');

// BBcode for embedding a cart button
// [fleetwire_cart_button]
function fleetwire_cart_button_bb($params) {
  $options = shortcode_atts(array(
    'href'  => NULL
  ), $params);

  return '<div class="fleetwire-cart-button" ' . fleetwire_shortcode_options_to_data($options) . '></div>';
}

// BBcode for embedding the embeddable cart
// [fleetwire_embeddable_cart]
function fleetwire_embeddable_cart_bb($params) {
  return '<div class="fleetwire-embeddable-cart"></div>';
}

// BBcode for embedding the embeddable cart lines
// [fleetwire_embeddable_cart_lines]
function fleetwire_embeddable_cart_lines_bb($params) {
  $options = shortcode_atts(array(
    'compact'  => NULL
  ), $params);

  return '<div class="fleetwire-embeddable-cart-lines" ' . fleetwire_shortcode_options_to_data($options) . '></div>';
}

// BBcode for embedding the embeddable cart sidebar
// [fleetwire_embeddable_cart_sidebar]
function fleetwire_embeddable_cart_sidebar_bb($params) {
  $options = shortcode_atts(array(
    'continue-shopping'  => NULL,
    'datepicker' => NULL
  ), $params);

  return '<div class="fleetwire-embeddable-cart-sidebar" ' . fleetwire_shortcode_options_to_data($options) . '></div>';
}

// BBcode for embedding a sidebar with datepicker and categories
// [fleetwire_sidebar]
function fleetwire_sidebar_bb($params) {
  return '<div class="fleetwire-sidebar"></div>';
}

// BBcode for embedding a sorting select
// [fleetwire_sort]
// [fleetwire_sort search-key="only-tablets"]
function fleetwire_sort_bb($params) {
  $options = shortcode_atts(array(
    'search-key'  => NULL
  ), $params);

  return '<div class="fleetwire-sort" ' . fleetwire_shortcode_options_to_data($options) . '></div>';
}

// BBcode for embedding a bar with search and sorting select
// [fleetwire_bar]
// [fleetwire_bar search-key="only-tablets"]
function fleetwire_bar_bb($params) {
  $options = shortcode_atts(array(
    'search-key'  => NULL,
  ), $params);

  return '<div class="fleetwire-bar" ' . fleetwire_shortcode_options_to_data($options) . '></div>';
}

// BBcode for embedding a category list
// [fleetwire_categories]
// [fleetwire_categories search-key="only-tablets"]
function fleetwire_categories_bb($params) {
  $options = shortcode_atts(array(
    'search-key'  => NULL
  ), $params);

  return '<div class="fleetwire-categories" ' . fleetwire_shortcode_options_to_data($options) . '></div>';
}

function fleetwire_initialize() {
  add_action('admin_menu', 'fleetwire_admin_actions');
  add_action('admin_notices', 'fleetwire_admin_notice');
  add_action('admin_enqueue_scripts', 'fleetwire_admin_resources');

  $plugin = plugin_basename( __FILE__ );
  add_filter("plugin_action_links_$plugin", 'fleetwire_admin_settings_link' );

  add_action('wp_enqueue_scripts', 'add_fleetwire_js');
  add_action('wp_head', 'add_fleetwire_client_configuration_js');


  add_shortcode('fleetwire_listing_card', 'fleetwire_card_bb');
  add_shortcode('fleetwire_button', 'fleetwire_button_bb');
  add_shortcode('fleetwire_product', 'fleetwire_button_bb');
  add_shortcode('fleetwire_detail', 'fleetwire_detail_bb');
  add_shortcode('fleetwire_list', 'fleetwire_list_bb');
  add_shortcode('fleetwire_search', 'fleetwire_search_bb');
  add_shortcode('fleetwire_cart_button', 'fleetwire_cart_button_bb');
  add_shortcode('fleetwire_embeddable_cart', 'fleetwire_embeddable_cart_bb');
  add_shortcode('fleetwire_embeddable_cart_sidebar', 'fleetwire_embeddable_cart_sidebar_bb');
  add_shortcode('fleetwire_embeddable_cart_lines', 'fleetwire_embeddable_cart_lines_bb');
  add_shortcode('fleetwire_sidebar', 'fleetwire_sidebar_bb');
  add_shortcode('fleetwire_sort', 'fleetwire_sort_bb');
  add_shortcode('fleetwire_bar', 'fleetwire_bar_bb');
  add_shortcode('fleetwire_categories', 'fleetwire_categories_bb');
}
fleetwire_initialize();

?>
