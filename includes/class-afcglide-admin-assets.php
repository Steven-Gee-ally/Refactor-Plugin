<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Admin_Assets {

    public static function init() {
        $instance = new self();
        add_action( 'admin_enqueue_scripts', [ $instance, 'enqueue_admin_assets' ] );
    }

    /**
     * Enqueue admin scripts and styles
     */
    public function enqueue_admin_assets( $hook ) {
        global $post_type;

        // Load media uploader and color picker
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );

        /**
         * 1. Load the Master Admin CSS
         * Using AFCG_URL (corrected constant)
         */
        wp_enqueue_style(
            'afcglide-admin-style',
            AFCG_URL . 'assets/css/admin.css',
            [],
            AFCG_VERSION
        );

        /**
         * 2. Load the Admin JS
         * Only load script/localization if we are on a Listing screen
         */
        if ( 'afcglide_listing' === $post_type || 'post-new.php' === $hook || 'post.php' === $hook ) {
            wp_enqueue_script(
                'afcglide-admin-js',
                AFCG_URL . 'assets/js/afcglide-admin.js',
                [ 'jquery', 'wp-color-picker' ],
                AFCG_VERSION,
                true
            );

            wp_localize_script( 'afcglide-admin-js', 'afcglide_admin', [
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'afcglide_admin_nonce' ),
            ]);
        }
    }
}