<?php
/**
 * AFCGlide Public Assets & Front-End Bridge
 * Handles the "Luxury" UI delivery for visitors.
 *
 * @package AFCGlide\Listings
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Public {

    /**
     * Initialize the class
     */
    public static function init() {
        $instance = new self();
        add_action( 'wp_enqueue_scripts', [ $instance, 'enqueue_public_assets' ] );
    }

    /**
     * Enqueue Luxury Styles and Front-End Scripts
     */
    public function enqueue_public_assets() {
        
        /**
         * 1. GLOBAL FOUNDATION (afcglide-styles.css)
         * This contains your :root variables, fonts, and Glassmorphism logic.
         * Using time() for versioning ensures you see CSS changes INSTANTLY.
         */
        wp_enqueue_style(
            'afcglide-main-styles',
            AFCG_URL . 'assets/css/afcglide-styles.css',
            [],
            time() 
        );

        /**
         * 2. SHORTCODE COMPONENTS (shortcodes.css)
         * We list 'afcglide-main-styles' as a dependency to ensure 
         * variables like --afc-glass are already loaded.
         */
        wp_enqueue_style(
            'afcglide-shortcode-styles',
            AFCG_URL . 'assets/css/shortcodes.css',
            ['afcglide-main-styles'], 
            time()
        );

        /**
         * 3. PUBLIC JAVASCRIPT
         * Handles sliders, photo stack clicks, and AJAX.
         */
        if ( file_exists( AFCG_PATH . 'assets/js/afcglide-public.js' ) ) {
            wp_enqueue_script(
                'afcglide-public-js',
                AFCG_URL . 'assets/js/afcglide-public.js',
                ['jquery'],
                time(),
                true
            );

            // The "Handshake" - passing data to JS
            wp_localize_script( 'afcglide-public-js', 'afcglide_data', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'afcglide_public_nonce' ),
                'is_live'  => true
            ]);
        }
    }
}
