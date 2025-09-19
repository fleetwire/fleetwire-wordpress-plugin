<?php
namespace Fleetwire;

/**
 * Fleetwire Logger Class
 * 
 * Handles logging and error reporting
 * 
 * @since 1.0.21
 * @package Fleetwire
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fleetwire Logger Class
 * 
 * @since 1.0.21
 */
class Logger {
    
    /**
     * Log levels
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO = 'info';
    const LEVEL_WARNING = 'warning';
    const LEVEL_ERROR = 'error';
    const LEVEL_CRITICAL = 'critical';
    
    /**
     * Log file path
     * 
     * @var string
     */
    private static string $log_file;
    
    /**
     * Initialize logger
     * 
     * @since 1.0.21
     * @return void
     */
    public static function init() {
        $upload_dir = wp_upload_dir();
        $log_dir = $upload_dir['basedir'] . '/fleetwire-logs';
        
        // Create a log directory if it doesn't exist
        if ( ! file_exists( $log_dir ) ) {
            wp_mkdir_p( $log_dir );
        }
        
        self::$log_file = $log_dir . '/fleetwire.log';
    }
    
    /**
     * Log a message
     * 
     * @param string $level Log level
     * @param string $message Log message
     * @param array $context Additional context
     *
     * @return void
     * @since 1.0.21
     */
    public static function log( string $level, string $message, array $context = [] ) {
        if ( ! self::should_log( $level ) ) {
            return;
        }
        
        $timestamp = current_time( 'Y-m-d H:i:s' );
        $context_str = ! empty( $context ) ? ' ' . wp_json_encode( $context ) : '';
        $log_entry = sprintf( '[%s] %s: %s%s' . PHP_EOL, $timestamp, strtoupper( $level ), $message, $context_str );
        
        // Write to a log file
        error_log( $log_entry, 3, self::$log_file );
        
        // Also log to WordPress debug log if enabled
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( sprintf( 'Fleetwire [%s]: %s', $level, $message ) );
        }
    }
    
    /**
     * Log debug message
     * 
     * @param string $message Debug message
     * @param array $context Additional context
     *
     * @return void
     * @since 1.0.21
     */
    public static function debug( string $message, array $context = [] ) {
        self::log( self::LEVEL_DEBUG, $message, $context );
    }
    
    /**
     * Log info message
     * 
     * @param string $message Info message
     * @param array $context Additional context
     *
     * @return void
     * @since 1.0.21
     */
    public static function info( string $message, array $context = [] ) {
        self::log( self::LEVEL_INFO, $message, $context );
    }
    
    /**
     * Log warning message
     * 
     * @param string $message Warning message
     * @param array $context Additional context
     *
     * @return void
     * @since 1.0.21
     */
    public static function warning( string $message, array $context = [] ) {
        self::log( self::LEVEL_WARNING, $message, $context );
    }
    
    /**
     * Log error message
     * 
     * @param string $message Error message
     * @param array $context Additional context
     *
     * @return void
     * @since 1.0.21
     */
    public static function error( string $message, array $context = [] ) {
        self::log( self::LEVEL_ERROR, $message, $context );
    }
    
    /**
     * Log critical message
     * 
     * @param string $message Critical message
     * @param array $context Additional context
     *
     * @return void
     * @since 1.0.21
     */
    public static function critical( string $message, array $context = [] ) {
        self::log( self::LEVEL_CRITICAL, $message, $context );
    }
    
    /**
     * Log AJAX error
     * 
     * @param string $action AJAX action
          * @param string $error Error message
     * @param array $context Additional context
     *
     * @return void
     * @since 1.0.21
     */
    public static function log_ajax_error( string $action, string $error, array $context = [] ) {
        $context['ajax_action'] = $action;
        $context['user_id'] = get_current_user_id();
        $context['ip'] = self::get_client_ip();
        
        self::error( sprintf( 'AJAX Error in %s: %s', $action, $error ), $context );
    }
    
    /**
     * Log API error
     * 
     * @param string $endpoint API endpoint
     * @param string $error Error message
     * @param array $context Additional context
     *
     * @return void
     *@since 1.0.21
     */
    public static function log_api_error( string $endpoint, string $error, array $context = [] ) {
        $context['api_endpoint'] = $endpoint;
        $context['company_id'] = Config::get_company_id();
        
        self::error( sprintf( 'API Error for %s: %s', $endpoint, $error ), $context );
    }
    
    /**
     * Check if we should log at this level
     * 
     * @param string $level Log level
     *
     * @return bool
     *@since 1.0.21
     */
    private static function should_log( string $level ) {
        // Only log errors and critical messages in production
        if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
            return in_array( $level, [ self::LEVEL_ERROR, self::LEVEL_CRITICAL ], true );
        }
        
        // Log everything in debug mode
        return true;
    }
    
    /**
     * Get client IP address
     * 
     * @since 1.0.21
     * @return string
     */
    private static function get_client_ip() {
        $ip_keys = [ 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR' ];
        
        foreach ( $ip_keys as $key ) {
            if ( array_key_exists( $key, $_SERVER ) === true ) {
                foreach ( explode( ',', $_SERVER[ $key ] ) as $ip ) {
                    $ip = trim( $ip );
                    
                    if ( filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) !== false ) {
                        return $ip;
                    }
                }
            }
        }
        
        return '0.0.0.0';
    }
    
    /**
     * Get a log file path
     * 
     * @since 1.0.21
     * @return string
     */
    public static function get_log_file() {
        return self::$log_file;
    }
    
    /**
     * Clear log file
     * 
     * @since 1.0.21
     * @return bool
     */
    public static function clear_log() {
        if ( file_exists( self::$log_file ) ) {
            return unlink( self::$log_file );
        }
        
        return true;
    }
    
    /**
     * Get log file size
     * 
     * @since 1.0.21
     * @return int
     */
    public static function get_log_size() {
        if ( file_exists( self::$log_file ) ) {
            return filesize( self::$log_file );
        }
        
        return 0;
    }
}
