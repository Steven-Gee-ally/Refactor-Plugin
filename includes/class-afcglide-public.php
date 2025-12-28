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
        // Add the button styling
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_public_styles' ] );
    }

    public static function enqueue_public_styles() {
        // We can add a small CSS block for the button here
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
        wp_add_inline_style( 'wp-block-library', $custom_css );
    }

    public static function render_whatsapp_button() {
        // Only show on Single Listing pages to keep it exclusive
        if ( ! is_singular( 'afcglide_listing' ) ) {
            return;
        }

        // 1. Get Agent Data (From the fields we just built!)
        $user_id = get_the_author_meta( 'ID' );
        $whatsapp = get_user_meta( $user_id, 'afcglide_whatsapp', true );
        $agent_name = get_the_author_meta( 'display_name', $user_id );
        $property_title = get_the_title();

        // 2. Clean the phone number (Remove spaces/dashes)
        $clean_phone = preg_replace('/[^0-9]/', '', $whatsapp);

        if ( empty( $clean_phone ) ) {
            return; // Don't show if no phone is set
        }

        // 3. Create the pre-filled message
        $message = rawurlencode( "Hi " . $agent_name . ", I'm interested in: " . $property_title );
        $wa_url = "https://wa.me/" . $clean_phone . "?text=" . $message;

        // 4. Output the Button
        ?>
        <a href="<?php echo esc_url( $wa_url ); ?>" class="afcglide-whatsapp-float" target="_blank">
            <span class="afcglide-whatsapp-icon">💬</span>
            <?php echo esc_html__( 'Chat with Agent', 'afcglide' ); ?>
        </a>
        <?php
    }
}