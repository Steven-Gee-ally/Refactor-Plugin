<?php
namespace AFCGlide\Listings;

defined( 'ABSPATH' ) || exit;

/**
 * AFCGlide Templates Controller
 * Takes control of Astra and forces the Luxury Mansion layout
 */
class AFCGlide_Templates {

    public static function init() {
        // 1. Force Astra to drop sidebars before the page renders
        add_action( 'wp', [ __CLASS__, 'lock_astra_settings' ] );

        // 2. Redirect WordPress to use our custom luxury template file
        add_filter( 'template_include', [ __CLASS__, 'load_listing_template' ], 999 );
    }

    /**
     * BULLETPROOF CONTROL: Overrides Astra internal settings
     */
    public static function lock_astra_settings() {
        if ( ! is_singular( 'afcglide_listing' ) ) return;

        // Force 'No Sidebar'
        add_filter( 'astra_page_layout', function() { return 'no-sidebar'; }, 1000 );
        
        // Force 'Full Width' container
        add_filter( 'astra_get_content_layout', function() { return 'content-layout-full-width'; }, 1000 );

        // Disable the default theme title (prevents double titles)
        add_filter( 'astra_the_title_enabled', '__return_false', 1000 );
    }

    /**
     * THE HIJACK: Points to your single-afcglide_listing.php
     */
    public static function load_listing_template( $template ) {
        if ( is_singular( 'afcglide_listing' ) ) {
            // Try hyphen version first (WordPress standard)
            $hyphen_template = AFCG_PATH . 'templates/single-afcglide-listing.php';
            if ( file_exists( $hyphen_template ) ) {
                return $hyphen_template;
            }
            
            // Fallback to underscore version
            $underscore_template = AFCG_PATH . 'templates/single-afcglide_listing.php';
            if ( file_exists( $underscore_template ) ) {
                return $underscore_template;
            }
        }
        return $template;
    }
} // <--- Added this final closing brace to close the class