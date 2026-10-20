<?php
namespace Fleetwire;

/**
 * Fleetwire Configuration Class
 * 
 * Manages all plugin configurations and constants
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
    const VERSION = '1.0.24';
    
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
    const SCRIPT_VERSION = '1.0.24';
    
    /**
     * Minimum WordPress version required
     * 
     * @var string
     */
    const MIN_WP_VERSION = '6.3';
    
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
        $local_base_url = self::get_local_asset_base_url();
        if ( $local_base_url ) {
            return $local_base_url . '/tenant/v2/fleetwire.js';
        }

        return sprintf( self::SCRIPT_URL_PATTERN, $company_id );
    }

    /**
     * Base URL of a local Fleetwire checkout serving the embed assets and API.
     *
     * Only set when a local WordPress site (.test, localhost, …) has the API
     * environment switched to "local"; live sites always use fleetwire.io.
     *
     * @since 1.1.0
     * @return string|null
     */
    public static function get_local_asset_base_url() {
        if ( ! self::is_local_wordpress_environment() || self::get_api_environment() !== 'local' ) {
            return null;
        }

        return untrailingslashit( self::get_api_base_url() );
    }

    /**
     * Get company store URL
     *
     * @param string $company_id Company ID
     *
     * @return string
     * @since 1.0.21
     */
    public static function get_company_store_url( string $company_id ) {
        return sprintf( 'https://%s.fleetwire.io', $company_id );
    }

    /**
     * Determine whether this WordPress install is running on a local hostname.
     *
     * @return bool
     * @since 1.0.21
     */
    public static function is_local_wordpress_environment() {
        $host = wp_parse_url( home_url(), PHP_URL_HOST );

        if ( empty( $host ) ) {
            return false;
        }

        $host = strtolower( trim( $host, '[]' ) );

        return in_array( $host, [ 'localhost', '127.0.0.1', '::1' ], true )
            || self::string_ends_with( $host, '.test' )
            || self::string_ends_with( $host, '.local' )
            || self::string_ends_with( $host, '.localhost' );
    }

    /**
     * Get the Fleetwire API environment target.
     *
     * @return string
     * @since 1.0.21
     */
    public static function get_api_environment() {
        if ( ! self::is_local_wordpress_environment() ) {
            return 'live';
        }

        $environment = self::get_option( 'api_environment', '' );

        if ( in_array( $environment, [ 'live', 'local' ], true ) ) {
            return $environment;
        }

        return 'live';
    }

    /**
     * Get the company ID to use for the selected Fleetwire API target.
     *
     * @return string|null
     * @since 1.0.21
     */
    public static function get_api_company_id() {
        if ( self::is_local_wordpress_environment() && self::get_api_environment() === 'local' ) {
            $local_company_id = self::get_option( 'local_company_id', '' );

            if ( ! empty( $local_company_id ) ) {
                return $local_company_id;
            }
        }

        return self::get_company_id();
    }

    /**
     * Get the Fleetwire API base URL for this WordPress site.
     *
     * @param string|null $company_id Company ID
     *
     * @return string
     * @since 1.0.21
     */
    public static function get_api_base_url( ?string $company_id = null ) {
        $company_id = $company_id ?: self::get_api_company_id();

        if ( self::is_local_wordpress_environment() && self::get_api_environment() === 'local' ) {
            $configured_url = trim( (string) self::get_option( 'api_base_url', '' ) );

            if ( empty( $configured_url ) ) {
                return $company_id ? self::get_company_store_url( $company_id ) : self::API_BASE_URL;
            }

            $configured_url = untrailingslashit( $configured_url );
            $configured_host = wp_parse_url( $configured_url, PHP_URL_HOST );

            if (
                $company_id &&
                $configured_host &&
                ! self::string_starts_with( strtolower( $configured_host ), strtolower( $company_id ) . '.' ) &&
                self::string_ends_with( strtolower( $configured_host ), '.test' )
            ) {
                $scheme = wp_parse_url( $configured_url, PHP_URL_SCHEME ) ?: 'https';
                $port = wp_parse_url( $configured_url, PHP_URL_PORT );
                $port = $port ? ':' . $port : '';

                return $scheme . '://' . $company_id . '.' . $configured_host . $port;
            }

            return $configured_url;
        }

        return $company_id ? self::get_company_store_url( $company_id ) : self::API_BASE_URL;
    }

    /**
     * Get the Fleetwire company verification URL for the selected API target.
     *
     * @param string|null $company_id Company ID
     *
     * @return string
     * @since 1.0.21
     */
    public static function get_company_api_url( ?string $company_id = null ) {
        $company_id = $company_id ?: self::get_api_company_id();
        $api_base_url = self::get_api_base_url( $company_id );

        if ( self::is_local_wordpress_environment() && self::get_api_environment() === 'local' ) {
            $base_host = wp_parse_url( $api_base_url, PHP_URL_HOST );
            if ( $base_host && $company_id && self::string_starts_with( strtolower( $base_host ), strtolower( $company_id ) . '.' ) ) {
                return $api_base_url . '/api/v1/company';
            }
        }

        return $api_base_url . '/api/v1/company/' . rawurlencode( (string) $company_id );
    }

    /**
     * Get the Fleetwire WordPress integration dashboard API URL.
     *
     * @return string
     * @since 1.0.21
     */
    public static function get_dashboard_api_url() {
        $api_base_url = self::get_api_base_url();

        if ( self::is_local_wordpress_environment() && self::get_api_environment() === 'local' ) {
            $company_id = self::get_api_company_id();
            $host = wp_parse_url( $api_base_url, PHP_URL_HOST );

            if ( $company_id && $host && self::string_starts_with( strtolower( $host ), strtolower( $company_id ) . '.' ) ) {
                $scheme = wp_parse_url( $api_base_url, PHP_URL_SCHEME ) ?: 'https';
                $port = wp_parse_url( $api_base_url, PHP_URL_PORT );
                $port = $port ? ':' . $port : '';
                $host = substr( $host, strlen( $company_id ) + 1 );
                $api_base_url = $scheme . '://' . $host . $port;
            }
        }

        return untrailingslashit( $api_base_url ) . '/api/wp/v1/dashboard-summary';
    }

    /**
     * Determine whether WordPress should verify SSL certificates for Fleetwire API requests.
     *
     * @return bool
     * @since 1.0.21
     */
    public static function should_verify_api_ssl() {
        return ! ( self::is_local_wordpress_environment() && self::get_api_environment() === 'local' );
    }

    /**
     * Get the scoped Fleetwire WordPress integration token.
     *
     * @return string
     * @since 1.0.21
     */
    public static function get_integration_token() {
        return (string) self::get_option( 'integration_token', '' );
    }

    /**
     * PHP 7.4 compatible string prefix check.
     *
     * @param string $haystack String to search.
     * @param string $needle Prefix to find.
     *
     * @return bool
     * @since 1.0.22
     */
    private static function string_starts_with( string $haystack, string $needle ) {
        return $needle === '' || strpos( $haystack, $needle ) === 0;
    }

    /**
     * PHP 7.4 compatible string suffix check.
     *
     * @param string $haystack String to search.
     * @param string $needle Suffix to find.
     *
     * @return bool
     * @since 1.0.22
     */
    private static function string_ends_with( string $haystack, string $needle ) {
        if ( $needle === '' ) {
            return true;
        }

        return substr( $haystack, -strlen( $needle ) ) === $needle;
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
     * Check if the plugin is properly configured
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
     * @since 1.0.21
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
            'local_company_id' => self::get_option( 'local_company_id', '' ),
            'api_environment' => self::get_api_environment(),
            'api_base_url' => self::get_api_base_url(),
            'has_integration_token' => ! empty( self::get_integration_token() ),
            'version' => self::VERSION,
            'configured' => self::is_configured(),
        ];
    }
}
