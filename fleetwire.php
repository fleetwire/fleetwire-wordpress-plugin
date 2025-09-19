<?php
/**
 * @wordpress-plugin
 * Plugin Name: Fleetwire Fleet Management Plugin
 * Description: Fleet management plugin for WordPress. Turn your website into a complete online rental store by connecting your Fleetwire.io account to WordPress.
 * Author: Fleetwire, LLC
 * Version: 1.0.21
 * Author URI: https://fleetwire.io
 * Copyright: 2025 Fleetwire, LLC
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

// Load configuration and main plugin class
require_once plugin_dir_path( __FILE__ ) . 'includes/class-fleetwire-config.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-fleetwire-logger.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-fleetwire.php';

// Initialize configuration
\Fleetwire\Config::init( __FILE__ );

// Initialize logger
\Fleetwire\Logger::init();

// Initialize the plugin
function fleetwire_init() {
	return \Fleetwire\Fleetwire::get_instance();
}

// Start the plugin
fleetwire_init();

// Legacy functions are now handled by the Fleetwire class system
// All functionality has been moved to:
// - Fleetwire_Admin class (admin functionality)
// - Fleetwire_Assets class (script/style enqueuing)
// - Fleetwire_Shortcodes class (shortcode handling)

// All shortcode functionality has been moved to the Fleetwire_Shortcodes class
// All admin functionality has been moved to the Fleetwire_Admin class  
// All asset management has been moved to the Fleetwire_Assets class
// The main Fleetwire class orchestrates everything
