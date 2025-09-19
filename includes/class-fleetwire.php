<?php
namespace Fleetwire;

/**
 * Main Fleetwire Plugin Class
 * 
 * Orchestrates all plugin functionality
 * 
 * @since 1.0.21
 * @package Fleetwire
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Main Fleetwire Plugin Class
 * 
 * @since 1.0.21
 */
class Fleetwire {
    
    /**
     * Plugin instance
     * 
     * @var Fleetwire
     */
    private static $instance = null;
    
    /**
     * Admin instance
     * 
     * @var Admin
     */
    public $admin;
    
    /**
     * Assets instance
     * 
     * @var Assets
     */
    public $assets;
    
    /**
     * Shortcode instance
     * 
     * @var Shortcodes
     */
    public $shortcodes;
    
    /**
     * Get plugin instance
     * 
     * @since 1.0.21
     * @return Fleetwire
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     * 
     * @since 1.0.21
     */
    private function __construct() {
        $this->init();
    }
    
    /**
     * Initialize the plugin
     * 
     * @since 1.0.21
     * @return void
     */
    private function init() {
        // Load required files
        $this->load_dependencies();
        
        // Initialize components
        $this->init_components();
        
        // Register hooks
        $this->register_hooks();
    }
    
    /**
     * Load plugin dependencies
     * 
     * @since 1.0.21
     * @return void
     */
    private function load_dependencies() {
        require_once Config::get_asset_path( 'includes/class-fleetwire-admin.php' );
        require_once Config::get_asset_path( 'includes/class-fleetwire-assets.php' );
        require_once Config::get_asset_path( 'includes/class-fleetwire-shortcodes.php' );
        require_once Config::get_asset_path( 'includes/class-fleetwire-validator.php' );
    }
    
    /**
     * Initialize plugin components
     * 
     * @since 1.0.21
     * @return void
     */
    private function init_components() {
        $this->admin = new Admin();
        $this->assets = new Assets();
        $this->shortcodes = new Shortcodes();
    }
    
    /**
     * Register plugin hooks
     * 
     * @since 1.0.21
     * @return void
     */
    private function register_hooks() {
        add_action( 'init', [ $this, 'register_shortcodes' ] );
    }
    
    /**
     * Register shortcodes
     * 
     * @since 1.0.21
     * @return void
     */
    public function register_shortcodes() {
        $this->shortcodes->register_shortcodes();
    }
    
    /**
     * Get plugin version
     * 
     * @since 1.0.21
     * @return string
     */
    public function get_version() {
        return Config::VERSION;
    }
    
    /**
     * Get plugin directory path
     * 
     * @since 1.0.21
     * @return string
     */
    public function get_plugin_dir() {
        return Config::get_plugin_dir();
    }
    
    /**
     * Get plugin directory URL
     * 
     * @since 1.0.21
     * @return string
     */
    public function get_plugin_url() {
        return Config::get_plugin_url();
    }
}
