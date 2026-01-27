<?php
namespace AFCGlide\Listings;

// Import the Constants class to keep the code clean
use AFCGlide\Core\Constants;

/**
 * Synergy Public Interface
 * Handles front-end logic, gateways, and tracking with strict separation of styles.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Public {

    public static function init() {
        // Enqueue consolidated styles
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_public_assets' ] );

        // Render tactical footer elements
        add_action( 'wp_footer', [ __CLASS__, 'render_whatsapp_button' ] );
        add_action( 'wp_footer', [ __CLASS__, 'render_agent_login_link' ] );

        // Logic & Redirects
        add_action( 'template_redirect', [ __CLASS__, 'track_listing_views' ] );
        add_action( 'template_redirect', [ __CLASS__, 'maybe_render_set_password' ] );

        // Premium Handshake: Hook into user registration
        add_action( 'user_register', [ __CLASS__, 'send_onboarding_email' ] );
    }

    /**
     * Enqueue external stylesheet + Dynamic Variable Bridge
     */
    public static function enqueue_public_assets() {
        wp_enqueue_style( 
            'afcglide-public', 
            AFCG_URL . 'assets/css/afcglide-public.css', 
            [], 
            Constants::VERSION 
        );

        // Bridge the settings using the Constant
        $wa_color = get_option( Constants::OPT_WA_COLOR, '#25D366' );
        $dynamic_bridge = ":root { --afc-wa-dynamic: {$wa_color}; --afc-wa-pulse: {$wa_color}b3; }";
        wp_add_inline_style( 'afcglide-public', $dynamic_bridge );
    }

    /**
     * Synergy Terminal Gateway
     * Discreet entry for agents. Styles are now in assets/css/afcglide-public.css
     */
    public static function render_agent_login_link() {
        $system_label = Constants::get_option(Constants::OPT_SYSTEM_LABEL, 'Synergy');
        $hub_url      = admin_url( 'admin.php?page=' . Constants::MENU_DASHBOARD );

        if ( is_user_logged_in() ) {
            if ( ! ( current_user_can( 'create_afc_listings' ) || current_user_can( Constants::CAP_MANAGE ) ) ) {
                return;
            }
            $url   = $hub_url;
            $label = "◈ " . strtoupper($system_label) . " TERMINAL";
        } else {
            $url   = wp_login_url( $hub_url );
            $label = "◈ AGENT SECURE ACCESS";
        }

        printf(
            '<div class="afc-synergy-gateway"><a href="%s" class="afc-gateway-link">%s</a></div>',
            esc_url( $url ),
            esc_html( $label )
        );
    }

    /**
     * WhatsApp Floating Button
     * Logic-only. Styles moved to CSS file.
     */
    public static function render_whatsapp_button() {
        if ( ! is_singular( 'afcglide_listing' ) ) return;

        $agent_id = get_option('afc_global_agent_id') ?: get_the_author_meta( 'ID' );
        $phone = get_user_meta( $agent_id, 'agent_phone', true );
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);

        if ( empty( $clean_phone ) ) return; 

        $prop_title = get_the_title();
        $message = rawurlencode( "Pura Vida! I'm interested in " . $prop_title . ". Is this still available?" );
        $wa_url = "https://wa.me/" . $clean_phone . "?text=" . $message;

        printf(
            '<a href="%s" class="afcglide-whatsapp-float" target="_blank" rel="nofollow">
                <span class="afcglide-whatsapp-icon">💬</span> %s
            </a>',
            esc_url( $wa_url ),
            esc_html__( 'WhatsApp Agent', 'afcglide' )
        );
    }

    /**
     * Password Reset Gateway
     */
    public static function maybe_render_set_password() {
        if ( empty( $_GET['afc_reset_token'] ) ) return;

        $token = sanitize_text_field( wp_unslash( $_GET['afc_reset_token'] ) );
        $token_key = 'afc_reset_token_' . $token;
        $user_id = get_transient( $token_key );

        if ( ! $user_id ) wp_die( esc_html__( 'Invalid or expired link.', 'afcglide' ) );

        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && isset( $_POST['afc_set_password_nonce'] ) ) {
            if ( ! wp_verify_nonce( $_POST['afc_set_password_nonce'], 'afc_set_password_action' ) ) wp_die( 'Security check failed.', 403 );

            $pass = trim( $_POST['afc_new_password'] ?? '' );
            $pass2 = $_POST['afc_new_password_confirm'] ?? '';

            if ( empty( $pass ) || $pass !== $pass2 || strlen( $pass ) < 8 ) {
                $error = __( 'Passwords must match and be at least 8 characters.', 'afcglide' );
            } else {
                wp_set_password( $pass, $user_id );
                delete_transient( $token_key );
                
                $user = get_user_by( 'id', $user_id );
                $creds = ['user_login' => $user->user_login, 'user_password' => $pass, 'remember' => true];
                wp_signon( $creds, is_ssl() );
                wp_safe_redirect( admin_url( 'admin.php?page=' . Constants::MENU_DASHBOARD ) );
                exit;
            }
        }

        include_once plugin_dir_path( __FILE__ ) . '../templates/password-reset-form.php'; 
        exit;
    }

    /**
     * Track Unique Hits
     */
    public static function track_listing_views() {
        if ( ! is_singular( 'afcglide_listing' ) || current_user_can('manage_options') ) return;

        $post_id = get_the_ID();
        $cookie_name = 'afc_viewed_' . $post_id;
        
        if ( ! isset( $_COOKIE[$cookie_name] ) ) {
            setcookie( $cookie_name, '1', time() + 86400, COOKIEPATH, COOKIE_DOMAIN );
            $views = intval( get_post_meta( $post_id, '_listing_views_count', true ) );
            update_post_meta( $post_id, '_listing_views_count', $views + 1 );
        }
    }

    /**
     * Premium Onboarding: Automated Welcome Email
     */
    public static function send_onboarding_email( $user_id ) {
        $user = get_user_by( 'id', $user_id );
        
        $allowed_roles = ['listing_agent', 'managing_broker'];
        if ( ! array_intersect( $allowed_roles, (array) $user->roles ) ) return;

        $token = bin2hex( random_bytes( 20 ) );
        set_transient( 'afc_reset_token_' . $token, $user_id, DAY_IN_SECONDS );

        $system_label = Constants::get_option( Constants::OPT_SYSTEM_LABEL, 'Synergy' );
        $reset_url    = add_query_arg( 'afc_reset_token', $token, home_url() );
        
        $subject = "◈ Action Required: Initialize Your {$system_label} Terminal Access";
        
        $message = "
        <div style='font-family: sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;'>
            <div style='background: #1e293b; padding: 30px; text-align: center;'>
                <h1 style='color: #ffffff; margin: 0; font-size: 22px; letter-spacing: 1px;'>" . strtoupper($system_label) . " TERMINAL</h1>
            </div>
            <div style='padding: 40px; background: #ffffff;'>
                <p style='font-size: 16px; color: #334155;'>Pura Vida, <strong>{$user->display_name}</strong>,</p>
                <p style='font-size: 14px; line-height: 1.6; color: #64748b;'>
                    Welcome to the team. Your agent profile has been successfully provisioned within the <strong>{$system_label}</strong> ecosystem. 
                </p>
                <div style='text-align: center; margin: 40px 0;'>
                    <a href='{$reset_url}' style='background: #6366f1; color: #ffffff; padding: 15px 30px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 14px;'>ACTIVATE AGENT ACCESS</a>
                </div>
            </div>
            <div style='background: #f8fafc; padding: 20px; text-align: center; border-top: 1px solid #e2e8f0;'>
                <p style='font-size: 11px; color: #94a3b8; margin: 0;'>&copy; " . date('Y') . " {$system_label} Real Estate Engine.</p>
            </div>
        </div>";

        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . get_bloginfo('name') . ' <' . get_bloginfo('admin_email') . '>'
        ];

        wp_mail( $user->user_email, $subject, $message, $headers );
    }
}