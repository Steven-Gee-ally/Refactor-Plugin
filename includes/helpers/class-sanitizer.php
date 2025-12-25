<?php
/**
 * Sanitizer Helper - Full Version with Real Estate Optimizations
 */

namespace AFCGlide\Listings\Helpers;

if ( ! defined( 'ABSPATH' ) ) exit;

class Sanitizer {

    // --- NEW REAL ESTATE LOGIC (The stuff we need for the sync) ---

    public static function price( $value ) {
        return preg_replace( '/[^0-9.]/', '', $value );
    }

    public static function decimal( $value ) {
        return filter_var( $value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
    }

    public static function json_string( $value ) {
        if ( is_array( $value ) ) return json_encode( $value );
        return ( is_string( $value ) && is_array( json_decode( $value, true ) ) ) ? $value : '';
    }

    // --- YOUR ORIGINAL LOGIC (Keeping these so nothing breaks) ---

    public static function text( $value ) { return sanitize_text_field( $value ); }
    public static function email( $value ) { return sanitize_email( $value ); }
    public static function textarea( $value ) { return sanitize_textarea_field( $value ); }
    public static function html( $value ) { return wp_kses_post( $value ); }
    public static function url( $value ) { return esc_url_raw( $value ); }
    public static function int( $value ) { return absint( $value ); }
    public static function phone( $value ) { return preg_replace( '/[^0-9\s\-\(\)\+]/', '', $value ); }
    public static function key( $value ) { return sanitize_key( $value ); }
    public static function hex_color( $value ) { return sanitize_hex_color( $value ); }
    public static function boolean( $value ) { return filter_var( $value, FILTER_VALIDATE_BOOLEAN ); }
    
    public static function array_recursive( $array, $sanitize_function = 'sanitize_text_field' ) {
        if ( ! is_array( $array ) ) return call_user_func( $sanitize_function, $array );
        foreach ( $array as $key => $value ) {
            $array[ $key ] = is_array( $value ) ? self::array_recursive( $value, $sanitize_function ) : call_user_func( $sanitize_function, $value );
        }
        return $array;
    }
}