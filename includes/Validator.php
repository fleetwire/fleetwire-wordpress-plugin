<?php
namespace Fleetwire;

/**
 * Fleetwire Validator Class
 * 
 * Handles validation and sanitization
 * 
 * @since 1.0.21
 * @package Fleetwire
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Fleetwire Validator Class
 * 
 * @since 1.0.21
 */
class Validator {
    
    /**
     * Validate and sanitize company ID
     * 
     * @since 1.0.21
     * @param mixed $company_id Company ID to validate
     * @return array Result with 'valid' boolean and 'value' string
     */
    public static function validate_company_id( $company_id ) {
        $result = [
            'valid' => false,
            'value' => '',
            'error' => ''
        ];
        
        if ( empty( $company_id ) ) {
            $result['error'] = __( 'Company ID is required.', 'fleetwire-fleet-management' );
            return $result;
        }
        
        $sanitized = sanitize_text_field( $company_id );
        
        if ( ! Config::is_valid_company_id( $sanitized ) ) {
            $result['error'] = __( 'Invalid Company ID format. Please check your Company ID.', 'fleetwire-fleet-management' );
            return $result;
        }
        
        $result['valid'] = true;
        $result['value'] = $sanitized;
        
        return $result;
    }
    
    /**
     * Validate shortcode attributes
     * 
     * @since 1.0.21
     * @param array $atts Shortcode attributes
     * @param array $allowed_attributes Allowed attribute names
     * @return array Sanitized attributes
     */
    public static function validate_shortcode_attributes( $atts, $allowed_attributes = [] ) {
        if ( ! is_array( $atts ) ) {
            return [];
        }
        
        $sanitized = [];
        
        foreach ( $atts as $key => $value ) {
            // Only process allowed attributes
            if ( ! empty( $allowed_attributes ) && ! in_array( $key, $allowed_attributes, true ) ) {
                continue;
            }
            
            // Sanitize key
            $clean_key = sanitize_key( $key );
            
            // Sanitize value based on type
            if ( is_array( $value ) ) {
                $sanitized[ $clean_key ] = array_map( 'sanitize_text_field', $value );
            } else {
                $sanitized[ $clean_key ] = sanitize_text_field( $value );
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Validate nonce
     * 
     * @since 1.0.21
     * @param string $nonce Nonce value
     * @param string $action Nonce action
     * @return bool
     */
    public static function validate_nonce( $nonce, $action ) {
        if ( empty( $nonce ) || empty( $action ) ) {
            return false;
        }
        
        return wp_verify_nonce( $nonce, $action );
    }
    
    /**
     * Validate user capability
     * 
     * @since 1.0.21
     * @param string $capability Required capability
     * @return bool
     */
    public static function validate_capability( $capability = 'manage_options' ) {
        return current_user_can( $capability );
    }
    
    /**
     * Validate AJAX request
     * 
     * @since 1.0.21
     * @param string $action AJAX action
     * @param string $nonce_field Nonce field name
     * @param string $capability Required capability
     * @return array Result with 'valid' boolean and 'error' string
     */
    public static function validate_ajax_request( $action, $nonce_field = 'fw_nonce', $capability = 'manage_options' ) {
        $result = [
            'valid' => false,
            'error' => ''
        ];
        
        // Check capability
        if ( ! self::validate_capability( $capability ) ) {
            $result['error'] = __( 'Insufficient permissions.', 'fleetwire-fleet-management' );
            return $result;
        }
        
        // Check nonce
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- this reads the nonce in order to verify it.
        $nonce = isset( $_POST[ $nonce_field ] ) ? sanitize_text_field( wp_unslash( $_POST[ $nonce_field ] ) ) : '';
        if ( ! self::validate_nonce( $nonce, $action ) ) {
            $result['error'] = __( 'Security check failed.', 'fleetwire-fleet-management' );
            return $result;
        }
        
        $result['valid'] = true;
        return $result;
    }
    
    /**
     * Sanitize HTML output
     * 
     * @since 1.0.21
     * @param string $html HTML to sanitize
     * @param array $allowed_html Allowed HTML tags
     * @return string Sanitized HTML
     */
    public static function sanitize_html( $html, $allowed_html = [] ) {
        if ( empty( $allowed_html ) ) {
            $allowed_html = [
                'div' => [
                    'class' => [],
                    'data-*' => []
                ],
                'span' => [
                    'class' => [],
                    'data-*' => []
                ]
            ];
        }
        
        return wp_kses( $html, $allowed_html );
    }
    
    /**
     * Validate URL
     * 
     * @since 1.0.21
     * @param string $url URL to validate
     * @return bool
     */
    public static function is_valid_url( $url ) {
        return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
    }
    
    /**
     * Validate email
     * 
     * @since 1.0.21
     * @param string $email Email to validate
     * @return bool
     */
    public static function is_valid_email( $email ) {
        return is_email( $email );
    }
    
    /**
     * Sanitize and validate integer
     * 
     * @since 1.0.21
     * @param mixed $value Value to validate
     * @param int $min Minimum value
     * @param int $max Maximum value
     * @return int|false Validated integer or false
     */
    public static function validate_integer( $value, $min = null, $max = null ) {
        $int_value = intval( $value );
        
        if ( $min !== null && $int_value < $min ) {
            return false;
        }
        
        if ( $max !== null && $int_value > $max ) {
            return false;
        }
        
        return $int_value;
    }
}
