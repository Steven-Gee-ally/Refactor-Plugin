<?php
/**
 * Single Listing Template Loader
 *
 * @package AFCGlide_Listings
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Templates {

    const TEMPLATE_FILE = 'single-afcglide_listing.php';

    public static function init() {
        add_filter( 'template_include', [ __CLASS__, 'load_single_template' ], 99 );
    }

    public static function load_single_template( $template ) {
        if ( is_singular( 'afcglide_listing' ) ) { // ✅ Singular CPT
            
            // Check for theme override first
            $theme_file = get_stylesheet_directory() . '/afcglide-listings/' . self::TEMPLATE_FILE;
            
            // Check plugin default
            $plugin_file = AFCG_PLUGIN_DIR . 'templates/' . self::TEMPLATE_FILE;

            if ( file_exists( $theme_file ) ) {
                return $theme_file;
            } elseif ( file_exists( $plugin_file ) ) {
                return $plugin_file;
            }
        }
        return $template;
    }
}
?>