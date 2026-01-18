<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Shortcodes v4.0 - MASTER ENGINE
 * VERSION: Added jQuery/AJAX support for submission form
 */
final class AFCGlide_Shortcodes {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_shortcodes' ], 20 );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
    }

    /**
     * Enqueue frontend scripts when shortcode is detected
     */
    public static function enqueue_frontend_assets() {
        global $post;
        
        // Check if the submission form shortcode is present on the page
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'afcglide_submit_listing' ) ) {
            // Enqueue jQuery (WordPress comes with it)
            wp_enqueue_script( 'jquery' );
            
            // Enqueue submission form styles if you have them
            wp_enqueue_style( 'afc-submission-form', AFCG_URL . 'assets/css/admin-submission.css', [], '4.0' );
            
            // Optional: Enqueue submission form JS if you want to separate it
            // wp_enqueue_script( 'afc-submission-js', AFCG_URL . 'assets/js/afcglide-submission.js', ['jquery'], '4.0', true );
            
            // Localize script for AJAX (in case you move JS to external file later)
            wp_localize_script( 'jquery', 'afc_submission_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('afc_nonce'),
            ]);
        }
        
        // Enqueue grid styles when grid shortcode is present
        if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'afcglide_listings_grid' ) ) {
            wp_enqueue_style( 'afc-grid-styles', AFCG_URL . 'assets/css/afcglide-shortcodes.css', [], '4.0' );
        }
    }

    public static function register_shortcodes() {
        add_shortcode( 'afcglide_login', [ __CLASS__, 'render_login_form' ] );
        add_shortcode( 'afcglide_submit_listing', [ __CLASS__, 'render_submission_form' ] );
        add_shortcode( 'afcglide_listings_grid', [ __CLASS__, 'render_listing_grid' ] );
        
        // Agent Scoreboard Handshake
        if ( class_exists('\AFCGlide\Reporting\AFCGlide_Scoreboard') ) {
            add_shortcode( 'afc_scoreboard', [ '\AFCGlide\Reporting\AFCGlide_Scoreboard', 'render_scoreboard' ] );
        }
    }

    /**
     * 1. SUBMISSION FORM (Professional Agent Portal)
     */
    public static function render_submission_form() {
        if ( ! is_user_logged_in() ) {
            return '<div class="afc-auth-notice" style="padding:40px; text-align:center; background:#f8fafc; border-radius:12px; border:2px dashed #cbd5e1;">
                        <div style="font-size:48px; margin-bottom:15px;">🔒</div>
                        <h3 style="margin:0 0 10px 0; color:#1e293b; font-weight:800;">Access Denied</h3>
                        <p style="color:#64748b; margin:0;">Please log in to the Command Center to submit listings.</p>
                    </div>';
        }

        // Force enqueue jQuery if not already loaded (fallback)
        wp_enqueue_script('jquery');

        ob_start();
        $template_path = AFCG_PATH . 'templates/template-submit-listing.php';
        
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<div class="afc-error" style="padding:20px; color:#ef4444; border:2px solid #fca5a5; border-radius:8px; background:#fef2f2;">
                    <strong>⚠️ Template Error:</strong> template-submit-listing.php not found in /templates folder.
                    <br><small>Expected path: ' . esc_html($template_path) . '</small>
                  </div>';
        }
        
        return ob_get_clean();
    }

    /**
     * 2. LISTINGS GRID (The High-End Asset Wall)
     */
    public static function render_listing_grid( $atts ) {
        $atts = shortcode_atts( [
            'posts_per_page' => 9, 
            'columns' => 3,
            'status' => 'publish'
        ], $atts );
        
        $query = new \WP_Query( [
            'post_type'      => 'afcglide_listing', 
            'posts_per_page' => (int) $atts['posts_per_page'], 
            'post_status'    => sanitize_text_field( $atts['status'] ),
            'orderby'        => 'date',
            'order'          => 'DESC'
        ] );

        if ( ! $query->have_posts() ) {
            return '<div class="afc-no-results" style="padding:60px 20px; text-align:center; background:#f8fafc; border-radius:12px;">
                        <div style="font-size:64px; margin-bottom:20px; opacity:0.3;">🏠</div>
                        <p style="color:#64748b; font-size:16px; margin:0;">No luxury assets currently listed.</p>
                    </div>';
        }

        ob_start();
        echo '<div class="afc-grid-wrapper" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 30px; padding: 20px 0;">';
        
        while ( $query->have_posts() ) { 
            $query->the_post(); 
            self::render_listing_card(); 
        }
        
        echo '</div>';
        
        // Pagination (if needed)
        if ( $query->max_num_pages > 1 ) {
            echo '<div class="afc-pagination" style="margin-top:40px; text-align:center;">';
            echo paginate_links([
                'total' => $query->max_num_pages,
                'prev_text' => '← Previous',
                'next_text' => 'Next →',
            ]);
            echo '</div>';
        }
        
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * 3. LISTING CARD ROUTER (Decoupled Design)
     */
    public static function render_listing_card() {
        $template_path = AFCG_PATH . 'templates/listing-card.php';
        
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<div style="color:#dc2626; border:2px solid #fca5a5; padding:15px; border-radius:8px; background:#fef2f2;">
                    <strong>⚠️ Missing Card Template:</strong> /templates/listing-card.php
                  </div>';
        }
    }

    /**
     * 4. LOGIN FORM
     */
    public static function render_login_form() {
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            return '<div class="afc-success-box" style="padding:30px; background:linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border:2px solid #86efac; color:#166534; border-radius:12px; text-align:center;">
                        <div style="font-size:48px; margin-bottom:15px;">✅</div>
                        <h3 style="margin:0 0 8px 0; font-weight:800;">Identity Verified</h3>
                        <p style="margin:0; opacity:0.8;">Welcome back, ' . esc_html($user->display_name) . '. You are logged into the Command Center.</p>
                    </div>';
        }
        
        return wp_login_form( ['echo' => false] );
    }
}