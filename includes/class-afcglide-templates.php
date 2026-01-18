<?php
namespace AFCGlide\Listings;

defined( 'ABSPATH' ) || exit;

class AFCGlide_Templates {

    public static function init() {
        // This hook injects the form into the WordPress Admin "Add New" screen
        add_action( 'edit_form_after_title', [ __CLASS__, 'render_submission_form' ] );
    }

    public static function render_submission_form( $post ) {
        // Only run on our specific real estate post type
        if ( $post->post_type !== 'afcglide_listing' ) return;

        // THE MASTER PATH: This matches your terminal tree exactly
        $template_path = AFCG_PATH . 'templates/template-submit-listing.php';

        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            // DIAGNOSTIC BOX: If this appears, the path below is what we need to fix
            echo '<div style="background:#fff5f5; border:2px solid #feb2b2; padding:20px; border-radius:8px; color:#c53030;">';
            echo '<strong>🚨 Path Disconnect</strong><br>';
            echo 'Controller is looking here: <code style="background:#fff; padding:2px;">' . esc_html($template_path) . '</code>';
            echo '</div>';
        }

        // Standard "Clean Slate" to hide the default editor
        echo '<style>#postdivrich { display: none !important; }</style>';
    }
}