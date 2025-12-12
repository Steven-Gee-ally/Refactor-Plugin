<?php
/**
 * Public Assets Loader
 *
 * @package AFCGlide_Listings
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Public {

    public static function init() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_public_assets' ] );
    }

    public static function enqueue_public_assets() {

        /// 1. CSS
        // UPDATED: Matches your actual file name 'afcglide-styles.css'
        if ( file_exists( AFCG_PLUGIN_DIR . 'assets/css/afcglide-styles.css' ) ) {
            wp_enqueue_style(
                'afcglide-public-css',
                AFCG_PLUGIN_URL . 'assets/css/afcglide-styles.css',
                [],
                AFCG_VERSION
            );
        }

        // 2. JS (The Standalone File)
        if ( file_exists( AFCG_PLUGIN_DIR . 'assets/js/afcglide-public.js' ) ) {
            wp_enqueue_script(
                'afcglide-public-js',
                AFCG_PLUGIN_URL . 'assets/js/afcglide-public.js', // ✅ Hyphenated Name
                [ 'jquery' ],
                AFCG_VERSION,
                true
            );

            // 3. The Bridge (Data)
            wp_localize_script(
                'afcglide-public-js',
                'afcglide_ajax_object', 
                [
                    'ajax_url' => admin_url( 'admin-ajax.php' ),
                    'nonce'    => wp_create_nonce( 'afcglide_ajax_nonce' ),
                    'strings'  => [
                        'loading' => __( 'Loading...', 'afcglide' ),
                    ],
                ]
            );
        }
    }
}
?>