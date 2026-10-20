<?php
/**
 * @wordpress-plugin
 * Plugin Name: Fleetwire – Car Rental Booking & Website Builder
 * Description: Build a car rental website in minutes: one-click starter pages, car rental blocks and patterns, live availability search and online booking powered by your Fleetwire account.
 * Author: Fleetwire, LLC
 * Version: 1.1.0
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: fleetwire-fleet-management
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
