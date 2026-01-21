<?php
namespace AFCGlide\Listings;

/**
 * Front-end Logic & WhatsApp Integration
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Public {

    public static function init() {
        // Hook the button into the footer of all pages
        add_action( 'wp_footer', [ __CLASS__, 'render_whatsapp_button' ] );
        add_action( 'wp_footer', [ __CLASS__, 'render_agent_login_link' ] );
        add_action( 'template_redirect', [ __CLASS__, 'track_listing_views' ] );
        // Add the button styling
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_public_styles' ] );
    }

    public static function enqueue_public_styles() {
        // Assets now handled by afcglide-master.php for efficiency
        
        // WhatsApp Button Inline Style (Keep this here as it's dynamic/specific)
        $custom_css = "
            .afcglide-whatsapp-float {
                position: fixed;
                bottom: 30px;
                right: 30px;
                background-color: #25d366;
                color: #fff;
                border-radius: 50px;
                text-align: center;
                font-size: 16px;
                font-weight: 600;
                box-shadow: 0 4px 15px rgba(0,0,0,0.2);
                z-index: 9999;
                display: flex;
                align-items: center;
                padding: 12px 20px;
                text-decoration: none;
                transition: all 0.3s ease;
            }
            .afcglide-whatsapp-float:hover {
                transform: translateY(-5px);
                background-color: #128c7e;
                color: #fff;
            }
            .afcglide-whatsapp-icon {
                margin-right: 10px;
                font-size: 20px;
            }
        ";
        wp_add_inline_style( 'afcglide-public-styles', $custom_css );
    }

    public static function render_whatsapp_button() {
        // Only show on Single Listing pages
        if ( ! is_singular( 'afcglide_listing' ) ) {
            return;
        }

        // 1. Get the GLOBAL Agent set in our "No Compromise" Dashboard
        $agent_id = get_option('afc_global_agent_id');
        
        // Fallback: If no global agent is set, use the post author
        if ( ! $agent_id ) {
            $agent_id = get_the_author_meta( 'ID' );
        }

        // 2. Get Data using our consistent Keys
        $phone      = get_user_meta( $agent_id, 'agent_phone', true ); // Corrected Key
        $agent_name = get_userdata( $agent_id )->display_name ?? 'Agent';
        $prop_title = get_the_title();

        // 3. Clean the phone number (Remove spaces/dashes)
        $clean_phone = preg_replace('/[^0-9]/', '', $phone);

        if ( empty( $clean_phone ) ) {
            return; 
        }

        // 4. Create the pre-filled message for Costa Rica market
        $message = rawurlencode( "Pura Vida! I'm interested in " . $prop_title . ". Is this still available?" );
        $wa_url = "https://wa.me/" . $clean_phone . "?text=" . $message;

        // 5. Output the Premium Floating Button
        ?>
        <a href="<?php echo esc_url( $wa_url ); ?>" class="afcglide-whatsapp-float" target="_blank" rel="nofollow">
            <span class="afcglide-whatsapp-icon">💬</span>
            <?php echo esc_html__( 'WhatsApp Agent', 'afcglide' ); ?>
        </a>
        <?php
    }

    public static function render_agent_login_link() {
        if ( is_user_logged_in() ) return;
        ?>
        <div style="position: fixed; bottom: 15px; left: 20px; z-index: 9999;">
            <a href="<?php echo wp_login_url(); ?>" style="color: rgba(0,0,0,0.3); font-size: 10px; font-weight: 800; text-decoration: none; text-transform: uppercase; letter-spacing: 1.5px; transition: 0.3s;" onmouseover="this.style.color='rgba(0,0,0,0.8)'" onmouseout="this.style.color='rgba(0,0,0,0.3)'">🚀 Agent Access</a>
        </div>
        <?php
    }

    /**
     * Track Unique Network Hits
     */
    public static function track_listing_views() {
        if ( ! is_singular( 'afcglide_listing' ) ) return;
        
        // Don't track admins/brokers to keep data pure
        if ( current_user_can('manage_options') ) return;

        $post_id = get_the_ID();
        $views = intval( get_post_meta( $post_id, '_listing_views_count', true ) );
        
        // Simple Cookie Gate (24h)
        $cookie_name = 'afc_viewed_' . $post_id;
        if ( ! isset( $_COOKIE[$cookie_name] ) ) {
            setcookie( $cookie_name, '1', time() + 86400, COOKIEPATH, COOKIE_DOMAIN );
            update_post_meta( $post_id, '_listing_views_count', $views + 1 );
        }
    }
}