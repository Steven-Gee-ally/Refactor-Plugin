<?php
/**
 * Validator Helper
 * Validates user input and returns boolean results
 *
 * @package AFCGlide\Listings\Helpers
 */

namespace AFCGlide\Listings\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

class Validator {

    /**
     * Validate required fields are not empty
     */
    public static function required( $value ) {
        if ( is_array( $value ) ) {
            return ! empty( array_filter( $value ) );
        }
        return ! empty( trim( $value ) );
    }

    /**
     * Validate email format
     */
    public static function email( $email ) {
        return is_email( $email );
    }

    /**
     * Validate nonce
     */
    public static function nonce( $nonce, $action ) {
        return wp_verify_nonce( $nonce, $action );
    }

    /**
     * Validate file upload
     */
    public static function file_upload( $file_key ) {
        if ( ! isset( $_FILES[ $file_key ] ) ) {
            return false;
        }

        $file = $_FILES[ $file_key ];

        // Check if file was actually uploaded
        if ( empty( $file['name'] ) || $file['error'] === UPLOAD_ERR_NO_FILE ) {
            return false;
        }

        // Check for upload errors
        if ( $file['error'] !== UPLOAD_ERR_OK ) {
            return false;
        }

        return true;
    }

    /**
     * Validate file type (images only)
     */
    public static function file_type( $file_key, $allowed_types = null ) {
        if ( ! self::file_upload( $file_key ) ) {
            return false;
        }

        if ( $allowed_types === null ) {
            $allowed_types = [ 'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp' ];
        }

        $file_type = $_FILES[ $file_key ]['type'];
        return in_array( $file_type, $allowed_types, true );
    }

    /**
     * Validate file size
     */
    public static function file_size( $file_key, $max_size_mb = 5 ) {
        if ( ! self::file_upload( $file_key ) ) {
            return false;
        }

        $max_bytes = $max_size_mb * 1024 * 1024;
        return $_FILES[ $file_key ]['size'] <= $max_bytes;
    }

    /**
     * Validate multiple file uploads (for gallery)
     */
    public static function multiple_files( $file_key ) {
        if ( ! isset( $_FILES[ $file_key ] ) ) {
            return false;
        }

        $files = $_FILES[ $file_key ];
        
        if ( ! is_array( $files['name'] ) ) {
            return false;
        }

        // Check if at least one file was uploaded
        return ! empty( array_filter( $files['name'] ) );
    }

    /**
     * Validate user is logged in
     */
    public static function user_logged_in() {
        return is_user_logged_in();
    }

    /**
     * Validate user has capability
     */
    public static function user_can( $capability ) {
        return current_user_can( $capability );
    }

    /**
     * Validate string length
     */
    public static function min_length( $value, $min ) {
        return strlen( trim( $value ) ) >= $min;
    }

    /**
     * Validate string max length
     */
    public static function max_length( $value, $max ) {
        return strlen( trim( $value ) ) <= $max;
    }

    /**
     * Validate numeric value
     */
    public static function is_numeric( $value ) {
        return is_numeric( $value );
    }

    /**
     * Validate positive number
     */
    public static function is_positive( $value ) {
        return is_numeric( $value ) && $value > 0;
    }

    /**
     * Validate URL format
     */
    public static function url( $url ) {
        return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
    }

    /**
     * Validate phone number (basic)
     */
    public static function phone( $phone ) {
        // Remove common formatting characters
        $cleaned = preg_replace( '/[^0-9]/', '', $phone );
        // Check if 10-15 digits remain
        return strlen( $cleaned ) >= 10 && strlen( $cleaned ) <= 15;
    }

    /**
     * Validate against array of allowed values
     */
    public static function in_array( $value, $allowed ) {
        return in_array( $value, $allowed, true );
    }

    /**
     * Validate post exists
     */
    public static function post_exists( $post_id ) {
        return get_post( $post_id ) !== null;
    }

    /**
     * Validate taxonomy term exists
     */
    public static function term_exists( $term_id, $taxonomy ) {
        return term_exists( $term_id, $taxonomy ) !== null;
    }
    /**
     * Validate numeric value with decimal support
     * Specifically for Baths (2.5) or Prices (1200.50)
     */
    public static function is_decimal( $value ) {
        return is_numeric( $value ) && preg_match( '/^[0-9]+(\.[0-9]+)?$/', $value );
    }
}