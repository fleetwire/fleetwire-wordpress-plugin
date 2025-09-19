<?php
namespace Fleetwire;

/**
 * Fleetwire Autoloader Class
 * 
 * PSR-4 compliant autoloader for Fleetwire classes
 * 
 * @since 1.0.21
 * @package Fleetwire
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fleetwire Autoloader Class
 * 
 * @since 1.0.21
 */
class Autoloader {
    
    /**
     * Namespace prefix
     * 
     * @var string
     */
    private static string $prefix = 'Fleetwire\\';
    
    /**
     * Base directory for the namespace prefix
     * 
     * @var string
     */
    private static string $base_dir;
    
    /**
     * Whether the autoloader has been registered
     * 
     * @var bool
     */
    private static bool $registered = false;
    
    /**
     * Initialize the autoloader
     * 
     * @param string $base_dir Base directory for the namespace prefix
     *
     * @return void
     *@since 1.0.21
     */
    public static function init( string $base_dir ) {
        self::$base_dir = rtrim( $base_dir, '/' ) . '/';
        
        if ( ! self::$registered ) {
            spl_autoload_register( [ __CLASS__, 'load' ] );
            self::$registered = true;
        }
    }
    
    /**
     * Load a class file
     * 
     * @param string $class_name The fully qualified class name
     *
     * @return void
     *@since 1.0.21
     */
    public static function load( string $class_name ) {
        // Does the class use the namespace prefix?
        $len = strlen( self::$prefix );
        if ( strncmp( self::$prefix, $class_name, $len ) !== 0 ) {
            // No, move to the next registered autoloader
            return;
        }
        
        // Get the relative class name
        $relative_class = substr( $class_name, $len );
        
        // Replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = self::$base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
        
        // If the file exists, require it
        if ( file_exists( $file ) ) {
            require $file;
        }
    }
    
    /**
     * Check if a class exists
     * 
     * @param string $class_name The fully qualified class name
     *
     * @return bool
     *@since 1.0.21
     */
    public static function class_exists( string $class_name ) {
        // Does the class use the namespace prefix?
        $len = strlen( self::$prefix );
        if ( strncmp( self::$prefix, $class_name, $len ) !== 0 ) {
            return false;
        }
        
        // Get the relative class name
        $relative_class = substr( $class_name, $len );
        
        // Replace the namespace prefix with the base directory, replace namespace
        // separators with directory separators in the relative class name, append
        // with .php
        $file = self::$base_dir . str_replace( '\\', '/', $relative_class ) . '.php';
        
        return file_exists( $file );
    }
    
    /**
     * Get the base directory
     * 
     * @since 1.0.21
     * @return string
     */
    public static function get_base_dir() {
        return self::$base_dir;
    }
    
    /**
     * Get the namespace prefix
     * 
     * @since 1.0.21
     * @return string
     */
    public static function get_prefix() {
        return self::$prefix;
    }
    
    /**
     * Unregister the autoloader
     * 
     * @since 1.0.21
     * @return void
     */
    public static function unregister() {
        if ( self::$registered ) {
            spl_autoload_unregister( [ __CLASS__, 'load' ] );
            self::$registered = false;
        }
    }
}
