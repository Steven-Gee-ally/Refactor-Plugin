<?php
/**
 * Helper functions for AFCGlide Listings
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

function afcglide_get_template_part( $slug ) {
    $theme_template = get_stylesheet_directory() . '/afcglide-listings/' . $slug . '.php';
    $plugin_template = AFCG_PLUGIN_DIR . 'templates/' . $slug . '.php';

    if ( file_exists( $theme_template ) ) {
        load_template( $theme_template, false );
    } elseif ( file_exists( $plugin_template ) ) {
        load_template( $plugin_template, false );
    }
}
?>