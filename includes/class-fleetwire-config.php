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
    private static $plugin_dir;
    
    /**
     * Plugin directory URL
     * 
     * @var string
     */
    private static $plugin_url;
    
    /**
     * Initialize configuration
     * 
     * @since 1.0.21
     * @param string $plugin_file Main plugin file path
     * @return void
     */
    public static function init( $plugin_file ) {
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
     * @since 1.0.21
     * @param string $asset_path Asset path relative to plugin directory
     * @return string
     */
    public static function get_asset_url( $asset_path ) {
        return self::$plugin_url . $asset_path;
    }
    
    /**
     * Get asset path
     * 
     * @since 1.0.21
     * @param string $asset_path Asset path relative to plugin directory
     * @return string
     */
    public static function get_asset_path( $asset_path ) {
        return self::$plugin_dir . $asset_path;
    }
    
    /**
     * Get company script URL
     * 
     * @since 1.0.21
     * @param string $company_id Company ID
     * @return string
     */
    public static function get_company_script_url( $company_id ) {
        return sprintf( self::SCRIPT_URL_PATTERN, $company_id );
    }
    
    /**
     * Get plugin option with default
     * 
     * @since 1.0.21
     * @param string $option_name Option name
     * @param mixed $default Default value
     * @return mixed
     */
    public static function get_option( $option_name, $default = null ) {
        return get_option( 'fleetwire_' . $option_name, $default );
    }
    
    /**
     * Update plugin option
     * 
     * @since 1.0.21
     * @param string $option_name Option name
     * @param mixed $value Option value
     * @return bool
     */
    public static function update_option( $option_name, $value ) {
        return update_option( 'fleetwire_' . $option_name, $value );
    }
    
    /**
     * Delete plugin option
     * 
     * @since 1.0.21
     * @param string $option_name Option name
     * @return bool
     */
    public static function delete_option( $option_name ) {
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
     * @since 1.0.21
     * @param string $company_id Company ID to validate
     * @return bool
     */
    public static function is_valid_company_id( $company_id ) {
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
