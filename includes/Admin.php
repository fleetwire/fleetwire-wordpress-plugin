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
        add_action( 'wp_ajax_fleetwire_verify_company_connection', [ $this, 'verify_company_connection' ] );
        add_action( 'wp_ajax_fleetwire_fetch_listings', [ $this, 'fetch_listings' ] );
        add_action( 'wp_ajax_fleetwire_fetch_dashboard_summary', [ $this, 'fetch_dashboard_summary' ] );
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
    public function enqueue_admin_assets( string $hook ) {
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
        $company_id = Config::get_company_id();

        wp_localize_script('fleetwire-admin', 'fleetwireAdmin', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('fleetwire_company_name_form_process'),
            'verifyNonce' => wp_create_nonce('fleetwire_verify_company_connection'),
            'listingsNonce' => wp_create_nonce('fleetwire_fetch_listings'),
            'dashboardNonce' => wp_create_nonce('fleetwire_fetch_dashboard_summary'),
            'companyId' => $company_id,
            'apiCompanyId' => Config::get_api_company_id(),
            'storeUrl' => $company_id ? Config::get_company_store_url( $company_id ) : '',
            'isLocalWordPress' => Config::is_local_wordpress_environment(),
            'apiEnvironment' => Config::get_api_environment(),
            'apiBaseUrl' => Config::get_api_base_url(),
            'hasIntegrationToken' => ! empty( Config::get_integration_token() ),
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
    public function add_settings_link( array $links ) {
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
        $api_environment = sanitize_text_field( wp_unslash( $_POST['fleetwire_api_environment'] ?? 'live' ) );
        $local_company_id = sanitize_text_field( wp_unslash( $_POST['fleetwire_local_company_id'] ?? '' ) );
        $api_base_url = sanitize_text_field( wp_unslash( $_POST['fleetwire_api_base_url'] ?? '' ) );
        $integration_token = sanitize_text_field( wp_unslash( $_POST['fleetwire_integration_token'] ?? '' ) );

        if ( ! Config::is_local_wordpress_environment() ) {
            $api_environment = 'live';
            $api_base_url = '';
        } elseif ( ! in_array( $api_environment, [ 'live', 'local' ], true ) ) {
            $api_environment = 'live';
        }

        if ( $api_environment === 'local' && empty( $api_base_url ) ) {
            wp_send_json_error( [
                'message' => __( 'Local Fleetwire API URL is required when Local is selected.', 'fleetwire' )
            ] );
        }

        if ( $api_environment === 'local' && ! empty( $local_company_id ) && ! Config::is_valid_company_id( $local_company_id ) ) {
            wp_send_json_error( [
                'message' => __( 'Invalid Local Company ID format. Please check your local Fleetwire Company ID.', 'fleetwire' )
            ] );
        }

        if ( ! empty( $api_base_url ) && ! Validator::is_valid_url( $api_base_url ) ) {
            wp_send_json_error( [
                'message' => __( 'Invalid Fleetwire API Base URL.', 'fleetwire' )
            ] );
        }

        $updated_company = Config::update_option( 'company_name', $company_validation['value'] );
        $updated_local_company = Config::update_option( 'local_company_id', $local_company_id );
        $updated_environment = Config::update_option( 'api_environment', $api_environment );
        $updated_api = Config::update_option( 'api_base_url', untrailingslashit( $api_base_url ) );
        $updated_token = Config::update_option( 'integration_token', $integration_token );

        Logger::info( 'Fleetwire WordPress options saved successfully', [
            'company_id' => $company_validation['value'],
            'local_company_id' => $local_company_id,
            'api_environment' => $api_environment,
            'changed' => $updated_company || $updated_local_company || $updated_environment || $updated_api || $updated_token,
            'user_id' => get_current_user_id()
        ] );

        wp_send_json_success( [
            'message' => __( 'Options saved.', 'fleetwire' ),
            'companyId' => $company_validation['value'],
            'apiCompanyId' => Config::get_api_company_id(),
            'storeUrl' => Config::get_company_store_url( $company_validation['value'] ),
            'apiEnvironment' => Config::get_api_environment(),
            'apiBaseUrl' => Config::get_api_base_url(),
            'hasIntegrationToken' => ! empty( $integration_token ),
        ] );
    }

    /**
     * Verify Fleetwire company connection.
     *
     * @since 1.0.21
     * @return void
     */
    public function verify_company_connection() {
        $validation = Validator::validate_ajax_request( 'fleetwire_verify_company_connection', 'fw_nonce' );
        if ( ! $validation['valid'] ) {
            Logger::log_ajax_error( 'fleetwire_verify_company_connection', $validation['error'] );
            wp_send_json_error( [ 'message' => $validation['error'] ] );
        }

        $company_id = Config::get_api_company_id();
        $company_validation = Validator::validate_company_id( $company_id );

        if ( ! $company_validation['valid'] ) {
            wp_send_json_error( [
                'message' => $company_validation['error'],
                'status' => 'invalid'
            ] );
        }

        $company_id = $company_validation['value'];
        $api_base_url = Config::get_api_base_url( $company_id );
        $store_url = Config::get_company_store_url( $company_id );
        $response = wp_remote_get( Config::get_company_api_url( $company_id ), [
            'headers' => [
                'Accept' => 'application/json',
            ],
            'sslverify' => Config::should_verify_api_ssl(),
            'timeout' => 8,
        ] );

        if ( is_wp_error( $response ) ) {
            Logger::log_api_error( 'company_connection', $response->get_error_message(), [
                'company_id' => $company_id,
            ] );
            wp_send_json_error( [
                'message' => __( 'Could not reach Fleetwire. Check your site connection and try again.', 'fleetwire' ),
                'status' => 'unreachable'
            ] );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code >= 200 && $status_code < 300 && ! empty( $body['company'] ) ) {
            wp_send_json_success( [
                'message' => __( 'Connected to Fleetwire.', 'fleetwire' ),
                'status' => 'connected',
                'companyId' => $company_id,
                'companyName' => $body['company']['name'] ?? '',
                'storeUrl' => $store_url,
                'apiBaseUrl' => $api_base_url,
            ] );
        }

        wp_send_json_error( [
            'message' => __( 'Fleetwire could not find that Company ID.', 'fleetwire' ),
            'status' => 'invalid'
        ] );
    }

    /**
     * Fetch public Fleetwire listings for the shortcode picker.
     *
     * @since 1.0.21
     * @return void
     */
    public function fetch_listings() {
        $validation = Validator::validate_ajax_request( 'fleetwire_fetch_listings', 'fw_nonce' );
        if ( ! $validation['valid'] ) {
            Logger::log_ajax_error( 'fleetwire_fetch_listings', $validation['error'] );
            wp_send_json_error( [ 'message' => $validation['error'] ] );
        }

        $listings = Listings::fetch( true );

        if ( is_wp_error( $listings ) ) {
            wp_send_json_error( [
                'message' => $listings->get_error_message(),
            ] );
        }

        wp_send_json_success( [
            'message' => __( 'Listings loaded.', 'fleetwire' ),
            'listings' => $listings,
        ] );
    }

    /**
     * Fetch private Fleetwire dashboard data for the WordPress admin page.
     *
     * @since 1.0.21
     * @return void
     */
    public function fetch_dashboard_summary() {
        $validation = Validator::validate_ajax_request( 'fleetwire_fetch_dashboard_summary', 'fw_nonce' );
        if ( ! $validation['valid'] ) {
            Logger::log_ajax_error( 'fleetwire_fetch_dashboard_summary', $validation['error'] );
            wp_send_json_error( [ 'message' => $validation['error'] ] );
        }

        $company_id = Config::get_api_company_id();
        $company_validation = Validator::validate_company_id( $company_id );

        if ( ! $company_validation['valid'] ) {
            wp_send_json_error( [
                'message' => $company_validation['error'],
            ] );
        }

        $integration_token = Config::get_integration_token();

        if ( empty( $integration_token ) ) {
            wp_send_json_error( [
                'message' => __( 'Add a Fleetwire WordPress integration token before loading host dashboard data.', 'fleetwire' ),
            ] );
        }

        $response = wp_remote_get( Config::get_dashboard_api_url(), [
            'headers' => [
                'Accept' => 'application/json',
                'Authorization' => 'Bearer ' . $integration_token,
            ],
            'sslverify' => Config::should_verify_api_ssl(),
            'timeout' => 10,
        ] );

        if ( is_wp_error( $response ) ) {
            Logger::log_api_error( 'wordpress_dashboard_summary', $response->get_error_message(), [
                'company_id' => $company_validation['value'],
            ] );
            wp_send_json_error( [
                'message' => __( 'Could not load host dashboard data from Fleetwire.', 'fleetwire' ),
            ] );
        }

        $status_code = wp_remote_retrieve_response_code( $response );
        $body = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( $status_code === 401 ) {
            wp_send_json_error( [
                'message' => __( 'Fleetwire rejected the WordPress integration token.', 'fleetwire' ),
            ] );
        }

        if ( $status_code < 200 || $status_code >= 300 || ! is_array( $body ) ) {
            wp_send_json_error( [
                'message' => __( 'Fleetwire returned an unexpected dashboard response.', 'fleetwire' ),
            ] );
        }

        wp_send_json_success( [
            'message' => __( 'Dashboard loaded.', 'fleetwire' ),
            'dashboard' => $body,
        ] );
    }
}
