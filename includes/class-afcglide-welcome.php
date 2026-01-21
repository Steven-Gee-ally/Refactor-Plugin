<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Welcome Experience
 * First-time onboarding for agents
 */
class AFCGlide_Welcome {

    public static function init() {
        add_action( 'admin_notices', [ __CLASS__, 'show_welcome_banner' ] );
        add_action( 'wp_ajax_afc_dismiss_welcome', [ __CLASS__, 'dismiss_welcome' ] );
    }

    public static function show_welcome_banner() {
        $user = wp_get_current_user();
        
        // Only show for agents (not brokers)
        if ( ! in_array( 'listing_agent', $user->roles ) || current_user_can('manage_options') ) {
            return;
        }

        // Check if already dismissed
        if ( get_user_meta( $user->ID, 'afc_welcome_dismissed', true ) ) {
            return;
        }

        // Only show on the dashboard
        $screen = get_current_screen();
        if ( $screen->id !== 'toplevel_page_afcglide-dashboard' ) {
            return;
        }

        // Check if profile is complete
        $has_listings = get_posts([
            'post_type' => 'afcglide_listing',
            'author' => $user->ID,
            'posts_per_page' => 1,
            'post_status' => 'any'
        ]);

        if ( !empty($has_listings) ) {
            return; // They've already started
        }

        ?>
        <div class="notice notice-info afc-welcome-banner" style="position: relative; padding: 0; border: none; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border-left: 5px solid #0ea5e9; border-radius: 12px; margin: 20px 0;">
            <button type="button" class="notice-dismiss" onclick="afcDismissWelcome()" style="top: 10px; right: 10px;"></button>
            <div style="padding: 40px 50px;">
                <div style="display: flex; align-items: center; gap: 30px;">
                    <div style="font-size: 64px;">👋</div>
                    <div style="flex: 1;">
                        <h2 style="margin: 0 0 12px 0; font-size: 24px; font-weight: 800; color: #1e40af;">Welcome to AFCGlide, <?php echo esc_html($user->display_name); ?>!</h2>
                        <p style="margin: 0 0 20px 0; font-size: 16px; color: #1e293b; line-height: 1.6;">Let's get you started with your luxury listing portfolio. Follow these quick steps:</p>
                        
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 25px;">
                            <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #bae6fd;">
                                <div style="font-size: 32px; margin-bottom: 8px;">👤</div>
                                <h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 800; color: #0c4a6e;">1. Complete Profile</h4>
                                <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">Add your photo and contact info</p>
                            </div>
                            <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #bae6fd;">
                                <div style="font-size: 32px; margin-bottom: 8px;">🏡</div>
                                <h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 800; color: #0c4a6e;">2. Create Listing</h4>
                                <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">Upload your first property</p>
                            </div>
                            <div style="background: white; padding: 20px; border-radius: 10px; border: 2px solid #bae6fd;">
                                <div style="font-size: 32px; margin-bottom: 8px;">📊</div>
                                <h4 style="margin: 0 0 8px 0; font-size: 14px; font-weight: 800; color: #0c4a6e;">3. Track Stats</h4>
                                <p style="margin: 0; font-size: 13px; color: #64748b; line-height: 1.5;">Monitor performance & views</p>
                            </div>
                        </div>

                        <a href="<?php echo admin_url('profile.php'); ?>" style="display: inline-block; background: #0ea5e9; color: white; padding: 14px 28px; border-radius: 10px; text-decoration: none; font-weight: 700; font-size: 15px; box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);">
                            Get Started →
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <script>
        function afcDismissWelcome() {
            jQuery.post(ajaxurl, {
                action: 'afc_dismiss_welcome',
                nonce: '<?php echo wp_create_nonce('afc_welcome_nonce'); ?>'
            });
            jQuery('.afc-welcome-banner').fadeOut();
        }
        </script>
        <?php
    }

    public static function dismiss_welcome() {
        check_ajax_referer('afc_welcome_nonce', 'nonce');
        update_user_meta( get_current_user_id(), 'afc_welcome_dismissed', true );
        wp_send_json_success();
    }
}
