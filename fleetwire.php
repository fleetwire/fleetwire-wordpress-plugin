<?php
/**
 * @wordpress-plugin
 * Plugin Name: Fleetwire Fleet Management Plugin
 * Description: Fleet management plugin for WordPress. Turn your website into a complete online rental store by connecting your Fleetwire.io account to WordPress.
 * Author: Fleetwire, LLC
 * Version: 1.0.22
 * Author URI: https://fleetwire.io
 * Copyright: 2026 Fleetwire, LLC
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load and initialize autoloader
require_once plugin_dir_path( __FILE__ ) . 'includes/Autoloader.php';
\Fleetwire\Autoloader::init( plugin_dir_path( __FILE__ ) . 'includes' );

// Initialize configuration
\Fleetwire\Config::init( __FILE__ );

// Initialize logger
\Fleetwire\Logger::init();

/**
 * Initialize the Fleetwire plugin singleton.
 */
function fleetwire_init() {
	return \Fleetwire\Fleetwire::get_instance();
}

// Start the plugin
fleetwire_init();
