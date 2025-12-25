<?php
namespace AFCGlide\Listings;

defined( 'ABSPATH' ) || exit;

class AFCGlide_Templates {

    public static function init() {
        add_filter( 'single_template', [ __CLASS__, 'load_listing_template' ] );
    }

    public static function load_listing_template( $template ) {
        if ( is_singular( 'afcglide_listing' ) ) {
            // Look for the template in our plugin folder
            $plugin_template = AFCG_PATH . 'templates/single-listing.php';
            if ( file_exists( $plugin_template ) ) {
                return $plugin_template;
            }
        }
        return $template;
    }
}