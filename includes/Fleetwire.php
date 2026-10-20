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
    public Admin $admin;
    
    /**
     * Assets instance
     * 
     * @var Assets
     */
    public Assets $assets;
    
    /**
     * Shortcode instance
     * 
     * @var Shortcodes
     */
    public Shortcodes $shortcodes;

    /**
     * Blocks instance
     *
     * @var Blocks
     */
    public Blocks $blocks;
    
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
        // Dependencies are now loaded automatically via PSR-4 autoloading
        // No manual require_once statements needed
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
        $this->blocks = new Blocks( $this->shortcodes );
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
