<?php
/**
 * Helper functions for AFCGlide Listings
 * Provides formatting for prices, badges, and template loading.
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Loads a template part, allowing themes to override plugin defaults.
 */
function afcglide_get_template_part( $slug ) {
    $theme_template = get_stylesheet_directory() . '/afcglide-listings/' . $slug . '.php';
    $plugin_template = AFCG_PLUGIN_DIR . 'templates/' . $slug . '.php';

    if ( file_exists( $theme_template ) ) {
        load_template( $theme_template, false );
    } elseif ( file_exists( $plugin_template ) ) {
        load_template( $plugin_template, false );
    }
}

/**
 * Get formatted price with currency symbol.
 * Used in the Single Listing and Grid templates.
 */
function afcg_get_price( $post_id ) {
    $price = get_post_meta( $post_id, \AFCGlide\Core\Constants::META_PRICE, true );
    if ( ! $price ) return __( 'Price on Request', 'afcglide' );

    // Default to $ if no currency is set in options
    $options = get_option( 'afcglide_options', [] );
    $currency = isset( $options['currency_symbol'] ) ? $options['currency_symbol'] : '$';
    
    return $currency . number_format( (float) $price );
}

/**
 * Get HTML badge for property status (For Sale, Sold, etc.).
 * Includes built-in translation support for your Spanish toggle later.
 */
function afcg_get_status_badge( $post_id ) {
    $status = get_post_meta( $post_id, \AFCGlide\Core\Constants::META_STATUS, true ) ?: 'for-sale';
    
    $labels = [
        'for-sale' => __( 'For Sale', 'afcglide' ),
        'sold'     => __( 'Sold', 'afcglide' ),
        'rented'   => __( 'Rented', 'afcglide' ),
        'pending'  => __( 'Pending', 'afcglide' )
    ];

    $label = isset( $labels[$status] ) ? $labels[$status] : $labels['for-sale'];
    
    return sprintf( 
        '<span class="afc-badge afc-badge-%s">%s</span>', 
        esc_attr( $status ), 
        esc_html( $label ) 
    );
}