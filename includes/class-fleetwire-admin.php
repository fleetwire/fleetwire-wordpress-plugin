<?php
namespace Fleetwire;

/**
 * Fleetwire Admin Class
 * 
 * Handles all admin-related functionality
 * 
 * @since 1.0.21
 * @package Fleetwire
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fleetwire Admin Class
 * 
 * @since 1.0.21
 */
class Admin {
    
    /**
     * Constructor
     * 
     * @since 1.0.21
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize admin hooks
     * 
     * @since 1.0.21
     * @return void
     */
    private function init_hooks() {
        add_action( 'admin_menu', [ $this, 'add_options_page' ] );
        add_action( 'admin_menu', [ $this, 'add_main_menu_page' ] );
        add_action( 'admin_notices', [ $this, 'display_admin_notices' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
        add_filter( 'plugin_action_links_' . plugin_basename( Config::get_asset_path( 'fleetwire.php' ) ), [ $this, 'add_settings_link' ] );
        add_action( 'wp_ajax_fleetwire_company_name_form_process', [ $this, 'process_company_name_form' ] );
    }
    
    /**
     * Add Fleetwire options page
     * 
     * @since 1.0.21
     * @return void
     */
    public function add_options_page() {
        add_options_page( 
            __( 'Fleetwire', 'fleetwire' ), 
            __( 'Fleetwire', 'fleetwire' ), 
            'manage_options', 
            'Fleetwire', 
            [ $this, 'render_admin_page' ]
        );
    }
    
    /**
     * Add Fleetwire to the main admin menu
     * 
     * @since 1.0.21
     * @return void
     */
    public function add_main_menu_page() {
        add_menu_page(
            __( 'Fleetwire', 'fleetwire' ),                       // Page title
            __( 'Fleetwire', 'fleetwire' ),                       // Menu title
            'manage_options',                  // Capability
            'fleetwire',                       // Menu slug
            [ $this, 'render_admin_page' ],                 // Callback function
            Config::get_asset_url( 'assets/favicon-16x16.png' ), // Icon URL
            Config::MENU_POSITION                                // Position
        );
    }
    
    /**
     * Render admin page
     * 
     * @since 1.0.21
     * @return void
     */
    public function render_admin_page() {
        include Config::get_asset_path( 'admin/fleetwire_admin.php' );
    }
    
    /**
     * Enqueue admin styles and scripts
     * 
     * @since 1.0.21
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets( $hook ) {
        // Load only on Fleetwire pages
        if ( $hook !== 'settings_page_Fleetwire' && $hook !== 'toplevel_page_fleetwire') {
            return;
        }
        
        // Enqueue styles
        wp_enqueue_style( 
            'fleetwire-admin', 
            Config::get_asset_url( 'assets/fleetwire-admin.css' ),
            [],
            Config::VERSION
        );
        
        // Enqueue scripts
        wp_enqueue_script(
            'fleetwire-admin',
            Config::get_asset_url( 'assets/fleetwire-admin.js' ),
            ['jquery'],
            Config::VERSION,
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
     * Add a settings link to plugin actions
     * 
     * @since 1.0.21
     * @param array $links Plugin action links
     * @return array Modified links
     */
    public function add_settings_link( $links ) {
        $settings_link = '<a href="options-general.php?page=Fleetwire">' . 
            esc_html__( 'Settings', 'fleetwire' ) . '</a>';
        array_unshift( $links, $settings_link );

        return $links;
    }
    
    /**
     * Display admin notices
     * 
     * @since 1.0.21
     * @return void
     */
    public function display_admin_notices() {
        global $hook_suffix;

        if ( in_array( $hook_suffix, array( 'jetpack_page_akismet-key-config', 'settings_page_akismet-key-config' ) ) ) {
            // This page manages the notices and puts them inline where they make sense.
            return;
        }

        if ( $hook_suffix === 'plugins.php' && ! Config::is_configured() ) {
            include( Config::get_asset_path( 'admin/fleetwire_notice.php' ) );
        }
    }
    
    /**
     * Process company name form submission
     * 
     * @since 1.0.21
     * @return void
     */
    public function process_company_name_form() {
        // Validate AJAX request
        $validation = Validator::validate_ajax_request( 'fleetwire_company_name_form_process' );
        if ( ! $validation['valid'] ) {
            Logger::log_ajax_error( 'fleetwire_company_name_form_process', $validation['error'] );
            wp_send_json_error( [ 'message' => $validation['error'] ] );
        }
        
        // Validate company ID
        $company_id = $_POST['fleetwire_company_name'] ?? '';
        $company_validation = Validator::validate_company_id( $company_id );
        
        if ( ! $company_validation['valid'] ) {
            Logger::log_ajax_error( 'fleetwire_company_name_form_process', $company_validation['error'], [
                'company_id' => $company_id
            ] );
            wp_send_json_error( [ 'message' => $company_validation['error'] ] );
        }
        
        // Update option
        $updated = Config::update_option( 'company_name', $company_validation['value'] );
        
        if ( $updated ) {
            Logger::info( 'Company ID updated successfully', [
                'company_id' => $company_validation['value'],
                'user_id' => get_current_user_id()
            ] );
            wp_send_json_success( [
                'message' => __( 'Options saved.', 'fleetwire' )
            ] );
        } else {
            Logger::warning( 'No changes made to company ID', [
                'company_id' => $company_validation['value']
            ] );
            wp_send_json_error( [
                'message' => __( 'No changes made.', 'fleetwire' )
            ] );
        }
    }
}
