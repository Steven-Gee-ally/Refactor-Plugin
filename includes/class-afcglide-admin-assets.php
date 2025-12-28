<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Admin_Assets {

    public static function init() {
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
    }

    public static function enqueue_admin_assets( $hook ) {
        global $post_type;

        // 1. Identify which page we are on
        $is_listing_page  = ( 'afcglide_listing' === $post_type );
        $is_settings_page = ( isset( $_GET['page'] ) && strpos( $_GET['page'], 'afcglide' ) !== false );
        $is_profile_page  = ( 'profile.php' === $hook || 'user-edit.php' === $hook );
        $is_home_page     = ( strpos( $hook, 'afcglide-home' ) !== false );

        // 2. 🛑 CLEAN EXIT: Check all AFCGlide pages at once
        if ( ! $is_listing_page && ! $is_settings_page && ! $is_profile_page && ! $is_home_page ) {
            return;
        }

        // 3. LOAD MEDIA ENGINE
        // Needed for Property Photos (Listings), Agent Photos (Profile), and Branding (Home)
        if ( $is_listing_page || $is_profile_page || $is_home_page ) {
            wp_enqueue_media();
            wp_enqueue_style( 'wp-color-picker' );
        }

        // 4. LOAD ADMIN CSS (Now fires on all 4 page types)
        if ( file_exists( AFCG_PATH . 'assets/css/admin.css' ) ) {
            wp_enqueue_style(
                'afcglide-admin-style',
                AFCG_URL . 'assets/css/admin.css',
                [],
                AFCG_VERSION
            );
        }

        // 5. LOAD ADMIN JS
        if ( $is_listing_page || $is_profile_page || $is_home_page ) {
            if ( file_exists( AFCG_PATH . 'assets/js/afcglide-admin.js' ) ) {
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
                    'strings'  => [
                        'confirm_delete' => __( 'Are you sure?', 'afcglide' ),
                        'error'          => __( 'Error!', 'afcglide' ),
                        'success'        => __( 'Success!', 'afcglide' )
                    ]
                ]);
            }
        }

        // 6. SETTINGS JS
        if ( $is_settings_page && file_exists( AFCG_PATH . 'assets/js/settings-upload.js' ) ) {
            wp_enqueue_script(
                'afcglide-settings-upload',
                AFCG_URL . 'assets/js/settings-upload.js',
                [ 'jquery' ],
                AFCG_VERSION,
                true
            );
        }
    }
}