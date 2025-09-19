<?php
namespace Fleetwire;

/**
 * Fleetwire Configuration Class
 * 
 * Manages all plugin configuration and constants
 * 
 * @since 1.0.21
 * @package Fleetwire
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fleetwire Configuration Class
 * 
 * @since 1.0.21
 */
class Config {
    
    /**
     * Plugin version
     * 
     * @var string
     */
    const VERSION = '1.0.21';
    
    /**
     * Plugin text domain
     * 
     * @var string
     */
    const TEXT_DOMAIN = 'fleetwire';
    
    /**
     * Admin menu position
     * 
     * @var int
     */
    const MENU_POSITION = 99;
    
    /**
     * Script version for cache busting
     * 
     * @var string
     */
    const SCRIPT_VERSION = '1.0.21';
    
    /**
     * Minimum WordPress version required
     * 
     * @var string
     */
    const MIN_WP_VERSION = '5.0';
    
    /**
     * Minimum PHP version required
     * 
     * @var string
     */
    const MIN_PHP_VERSION = '7.4';
    
    /**
     * Fleetwire API base URL
     * 
     * @var string
     */
    const API_BASE_URL = 'https://fleetwire.io';
    
    /**
     * Fleetwire script base URL pattern
     * 
     * @var string
     */
    const SCRIPT_URL_PATTERN = 'https://%s.fleetwire.io/tenant/v2/fleetwire.js';
    
    /**
     * Plugin directory path
     * 
     * @var string
     */
    private static string $plugin_dir;
    
    /**
     * Plugin directory URL
     * 
     * @var string
     */
    private static string $plugin_url;
    
    /**
     * Initialize configuration
     * 
     * @param string $plugin_file Main plugin file path
     *
     * @return void
     *@since 1.0.21
     */
    public static function init( string $plugin_file ) {
        self::$plugin_dir = plugin_dir_path( $plugin_file );
        self::$plugin_url = plugin_dir_url( $plugin_file );
    }
    
    /**
     * Get plugin directory path
     * 
     * @since 1.0.21
     * @return string
     */
    public static function get_plugin_dir() {
        return self::$plugin_dir;
    }
    
    /**
     * Get plugin directory URL
     * 
     * @since 1.0.21
     * @return string
     */
    public static function get_plugin_url() {
        return self::$plugin_url;
    }
    
    /**
     * Get asset URL
     * 
     * @param string $asset_path Asset path relative to the plugin directory
     *
     * @return string
     *@since 1.0.21
     */
    public static function get_asset_url( string $asset_path ) {
        return self::$plugin_url . $asset_path;
    }
    
    /**
     * Get an asset path
     * 
     * @param string $asset_path Asset path relative to the plugin directory
     *
     * @return string
     *@since 1.0.21
     */
    public static function get_asset_path( string $asset_path ) {
        return self::$plugin_dir . $asset_path;
    }
    
    /**
     * Get company script URL
     * 
     * @param string $company_id Company ID
     *
     * @return string
     *@since 1.0.21
     */
    public static function get_company_script_url( string $company_id ) {
        return sprintf( self::SCRIPT_URL_PATTERN, $company_id );
    }
    
    /**
     * Get plugin option with default
     * 
     * @param string $option_name Option name
     * @param mixed $default Default value
     *
     * @return mixed
     *@since 1.0.21
     */
    public static function get_option( string $option_name, $default = null ) {
        return get_option( 'fleetwire_' . $option_name, $default );
    }
    
    /**
     * Update plugin option
     * 
     * @param string $option_name Option name
     * @param mixed $value Option value
     *
     * @return bool
     *@since 1.0.21
     */
    public static function update_option( string $option_name, $value ) {
        return update_option( 'fleetwire_' . $option_name, $value );
    }
    
    /**
     * Delete plugin option
     * 
     * @param string $option_name Option name
     *
     * @return bool
     *@since 1.0.21
     */
    public static function delete_option( string $option_name ) {
        return delete_option( 'fleetwire_' . $option_name );
    }
    
    /**
     * Check if plugin is properly configured
     * 
     * @since 1.0.21
     * @return bool
     */
    public static function is_configured() {
        $company_id = self::get_option( 'company_name' );
        return ! empty( $company_id );
    }
    
    /**
     * Get company ID
     * 
     * @since 1.0.21
     * @return string|null
     */
    public static function get_company_id() {
        return self::get_option( 'company_name' );
    }
    
    /**
     * Validate company ID format
     * 
     * @param string $company_id Company ID to validate
     *
     * @return bool
     *@since 1.0.21
     */
    public static function is_valid_company_id( string $company_id ) {
        if ( empty( $company_id ) ) {
            return false;
        }
        
        // Basic UUID format validation
        return preg_match( '/^[a-f0-9\-]{36}$/i', $company_id );
    }
    
    /**
     * Get all plugin options
     * 
     * @since 1.0.21
     * @return array
     */
    public static function get_all_options() {
        return [
            'company_name' => self::get_company_id(),
            'version' => self::VERSION,
            'configured' => self::is_configured(),
        ];
    }
}
