<?php
namespace AFCGlide\Listings;

/**
 * Admin Settings Page & Dashboard Hub
 * Version 1.3.0 - Portfolio Stats & Financial Snapshot Added
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Settings {

   public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_pages' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_scripts' ] );

        $options = get_option( 'afcglide_options' );
        $lockdown_active = isset( $options['lockdown_mode'] ) && $options['lockdown_mode'] == '1';

        if ( $lockdown_active ) {
            add_action( 'admin_head-profile.php', [ __CLASS__, 'lockdown_profile_styles' ] );
            add_action( 'admin_head', [ __CLASS__, 'remove_add_media_button' ] );
        }
        
        add_action( 'show_user_profile', [ __CLASS__, 'render_custom_user_fields' ] );
        add_action( 'edit_user_profile', [ __CLASS__, 'render_custom_user_fields' ] );
        add_action( 'personal_options_update', [ __CLASS__, 'save_custom_user_fields' ] );
        add_action( 'edit_user_profile_update', [ __CLASS__, 'save_custom_user_fields' ] );
    }

    public static function remove_add_media_button() {
        remove_action( 'media_buttons', 'media_buttons' );
    }

    public static function add_settings_pages() {
        add_submenu_page('afcglide-home', __('Dashboard', 'afcglide'), __('Dashboard', 'afcglide'), 'manage_options', 'afcglide-home', [__CLASS__, 'render_dashboard']);
        add_submenu_page('afcglide-home', __('Settings', 'afcglide'), __('Settings', 'afcglide'), 'manage_options', 'afcglide-settings', [__CLASS__, 'render_page']);
    }

    public static function render_dashboard() {
        // 1. Handle Sync Logic with Timestamp
        if ( isset($_POST['afc_save_agent_choice']) ) {
            update_option('afc_global_agent_id', sanitize_text_field($_POST['afc_agent_id']));
            update_option('afc_last_agent_sync', current_time('M j, Y - g:i a')); 
            echo '<div class="notice notice-success is-dismissible" style="border:none; background:#f0fdf4; color:#166534; border-left:4px solid #4ade80;"><p><strong>Success:</strong> Global Agent synchronization complete.</p></div>';
        }

        $agents = get_users(['role__in' => ['administrator', 'author', 'editor']]);
        $current_agent_id = get_option('afc_global_agent_id', '');
        $current_agent = get_userdata($current_agent_id);
        $add_listing_url = admin_url('post-new.php?post_type=afcglide_listing');
        
        // 2. Financial & Inventory Stats Calculation
        $listings_query = new \WP_Query(['post_type' => 'afcglide_listing', 'post_status' => 'publish', 'posts_per_page' => -1]);
        $listing_count = $listings_query->found_posts;
        $total_value = 0;

        if ( $listings_query->have_posts() ) {
            while ( $listings_query->have_posts() ) {
                $listings_query->the_post();
                $price = get_post_meta( get_the_ID(), '_listing_price', true );
                // Strip non-numeric characters so we can sum the total volume
                $total_value += (float) preg_replace('/[^0-9.]/', '', $price);
            }
            wp_reset_postdata();
        }
        ?>
        
        <div class="wrap afcglide-dashboard" style="max-width: 1100px; margin: 40px auto; font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
            
            <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 40px;">
                <div>
                    <h1 style="font-size: 32px; font-weight: 300; color: #1e293b; margin: 0;">AFCGlide <span style="font-weight: 700;">Home</span></h1>
                    <p style="color: #94a3b8; font-size: 16px; margin-top: 5px;">Luxury Real Estate Management Engine v3</p>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; background: #f0fdf4; padding: 8px 16px; border-radius: 20px; border: 1px solid #dcfce7;">
                    <span style="height: 8px; width: 8px; background: #22c55e; border-radius: 50%; display: inline-block; box-shadow: 0 0 8px #22c55e;"></span>
                    <span style="font-size: 12px; font-weight: 700; color: #166534; text-transform: uppercase; letter-spacing: 0.5px;">System Active</span>
                </div>
            </div>

            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px; padding: 25px; border-radius: 16px; background:#f0fdf4; border: 1px solid #dcfce7; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="height: 50px; width: 50px; background: #fff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">✨</div>
                    <div>
                        <p style="margin: 0; font-size: 11px; text-transform: uppercase; font-weight: 700; color:#166534; letter-spacing: 0.5px;">Primary Global Agent</p>
                        <h3 style="margin: 0; font-size: 20px; font-weight: 700; color:#1e293b;">
                            <?php echo $current_agent ? esc_html($current_agent->display_name) : 'Connection Awaiting...'; ?>
                        </h3>
                        <?php $last_sync = get_option('afc_last_agent_sync', 'Never'); ?>
                        <p style="margin: 3px 0 0 0; font-size: 11px; color: #15803d; opacity: 0.7;">Last Sync: <?php echo esc_html($last_sync); ?></p>
                    </div>
                </div>

                <form method="post" style="display: flex; gap: 12px; align-items: center;">
                    <select name="afc_agent_id" style="padding: 10px 15px; border-radius: 10px; border: 1px solid #bbf7d0; background: #fff; color: #475569; min-width: 200px;">
                        <option value="">Choose Agent Profile...</option>
                        <?php foreach ( $agents as $agent ) : ?>
                            <option value="<?php echo $agent->ID; ?>" <?php selected($current_agent_id, $agent->ID); ?>>
                                <?php echo esc_html( $agent->display_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="afc_save_agent_choice" style="background:#10b981; color:white; border:none; padding:11px 25px; border-radius:10px; font-weight:700; cursor:pointer;">Sync</button>
                </form>
            </div>

            <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px;">
                <a href="<?php echo esc_url($add_listing_url); ?>" style="text-decoration: none; padding: 25px; border-radius: 16px; background: #f5f3ff; border: 1px solid #ede9fe; transition: transform 0.2s; display: block;">
                    <h4 style="margin: 0 0 10px 0; font-size: 18px; color: #5b21b6;">Add Listing</h4>
                    <p style="margin: 0; font-size: 13px; color: #7c3aed; opacity: 0.8;">Launch a new high-end listing.</p>
                </a>

                <a href="<?php echo admin_url('edit.php?post_type=afcglide_listing'); ?>" style="text-decoration: none; padding: 25px; border-radius: 16px; background: #f0f9ff; border: 1px solid #e0f2fe; transition: transform 0.2s; display: block; position: relative;">
                    <div style="position: absolute; top: 15px; right: 15px; background: #0ea5e9; color: white; font-size: 10px; font-weight: 700; padding: 4px 8px; border-radius: 10px;"><?php echo $listing_count; ?> LIVE</div>
                    <h4 style="margin: 0 0 10px 0; font-size: 18px; color: #075985;">Inventory</h4>
                    <p style="margin: 0; font-size: 13px; color: #0369a1; opacity: 0.8;">Manage property details.</p>
                </a>

                <a href="<?php echo admin_url('profile.php'); ?>" style="text-decoration: none; padding: 25px; border-radius: 16px; background: #fefce8; border: 1px solid #fef08a; transition: transform 0.2s; display: block;">
                    <h4 style="margin: 0 0 10px 0; font-size: 18px; color: #854d0e;">Agent Identity</h4>
                    <p style="margin: 0; font-size: 13px; color: #a16207; opacity: 0.8;">Manage headshots and profile.</p>
                </a>

                <a href="<?php echo admin_url('admin.php?page=afcglide-settings'); ?>" style="text-decoration: none; padding: 25px; border-radius: 16px; background: #f8fafc; border: 1px solid #e2e8f0; transition: transform 0.2s; display: block;">
                    <h4 style="margin: 0 0 10px 0; font-size: 18px; color: #1e293b;">Config</h4>
                    <p style="margin: 0; font-size: 13px; color: #475569; opacity: 0.8;">API keys and branding.</p>
                </a>
            </div>

            <div style="margin-top: 25px; padding: 25px; border-radius: 16px; background: #f8fafc; border: 1px solid #e2e8f0; display: flex; justify-content: space-around; align-items: center; text-align: center;">
                <div>
                    <p style="margin: 0; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px;">Portfolio Volume</p>
                    <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: 700; color: #1e293b;">$<?php echo number_format($total_value); ?></p>
                </div>
                <div style="height: 40px; border-left: 2px solid #e2e8f0;"></div>
                <div>
                    <p style="margin: 0; font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px;">Active Listings</p>
                    <p style="margin: 5px 0 0 0; font-size: 28px; font-weight: 700; color: #1e293b;"><?php echo $listing_count; ?></p>
                </div>
            </div>

            <div style="margin-top: 35px; background: #fff; padding: 30px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                <h2 style="font-size: 22px; font-weight: 700; color: #1e293b; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 24px;">🔒</span> System Lockdown Controls
                </h2>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                    <div style="padding: 20px; border-radius: 15px; background: #fef2f2; border: 1px solid #fee2e2; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #991b1b; font-size: 16px;">Delete Protection</h3>
                            <p style="font-size: 12px; color: #b91c1c; margin: 0;">Require confirmation to delete.</p>
                        </div>
                        <input type="checkbox" class="lockdown-toggle" data-type="lockdown" <?php checked( get_option('afc_lockdown_enabled'), 'yes' ); ?> style="width:20px; height:20px; cursor:pointer;">
                    </div>

                    <div style="padding: 20px; border-radius: 15px; background: #f0fdf4; border: 1px solid #dcfce7; display: flex; justify-content: space-between; align-items: center;">
                        <div>
                            <h3 style="margin: 0 0 5px 0; color: #166534; font-size: 16px;">Ghost Mode</h3>
                            <p style="font-size: 12px; color: #15803d; margin: 0;">Hide system menus from agents.</p>
                        </div>
                        <input type="checkbox" class="lockdown-toggle" data-type="ghost" <?php checked( get_option('afc_ghost_mode'), 'yes' ); ?> style="width:20px; height:20px; cursor:pointer;">
                    </div>
                </div>
            </div>

            <script>
            jQuery(document).ready(function($) {
                $('.lockdown-toggle').on('change', function() {
                    var status = $(this).is(':checked') ? 'yes' : 'no';
                    var type = $(this).data('type');
                    $.post(ajaxurl, {
                        action: 'afc_toggle_lockdown_ajax',
                        status: status,
                        type: type,
                        nonce: '<?php echo wp_create_nonce("afc_lockdown_nonce"); ?>'
                    });
                });
            });
            </script>
        </div>
        <?php
    }

    public static function render_page() {
        ?>
        <div class="wrap afcglide-dashboard" style="max-width: 1100px; margin: 40px auto;">
            <div style="margin-bottom: 30px;">
                <h1 style="font-size: 32px; font-weight: 300; color: #1e293b; margin: 0;">AFCGlide <span style="font-weight: 700;">Settings</span></h1>
            </div>

            <form action="options.php" method="post">
                <?php settings_fields( 'afcglide_settings_group' ); ?>
                <div class="afcglide-card" style="background:#fff; border-radius:16px; padding:30px; border:1px solid #e2e8f0; margin-bottom:25px;">
                    <div style="font-size: 18px; font-weight: 700; color: #10b981; margin-bottom: 20px;">🏢 Agency Branding</div>
                    <div style="margin-bottom: 25px;"><?php self::render_field_logo(); ?></div>
                    <div><?php self::render_field_company_name(); ?></div>
                </div>

                <div class="afcglide-card" style="background:#fff; border-radius:16px; padding:30px; border:1px solid #e2e8f0; margin-bottom:25px;">
                    <div style="font-size: 18px; font-weight: 700; color: #10b981; margin-bottom: 20px;">⚙️ Technical Configuration</div>
                    <?php self::render_field_maps_key(); ?>
                    <hr style="border:0; border-top:1px solid #eee; margin:20px 0;">
                    <label style="display: flex; align-items: center; gap: 12px; cursor: pointer;">
                        <?php 
                        $options = get_option( 'afcglide_options' );
                        $val = isset( $options['lockdown_mode'] ) ? $options['lockdown_mode'] : '0';
                        ?>
                        <input type="checkbox" name="afcglide_options[lockdown_mode]" value="1" <?php checked($val, '1'); ?> style="width:20px; height:20px;">
                        <div>
                            <span style="font-weight:700; color:#1e293b;">Enable Agent Lockdown Mode</span>
                            <p style="margin:0; font-size:12px; color:#64748b;">Hides WordPress leftovers and forces professional profile branding.</p>
                        </div>
                    </label>
                </div>
                <input type="submit" name="submit" class="button button-primary" value="Save All Changes" style="background:#10b981; border:none; padding:14px 40px; height:auto; border-radius:12px; font-weight:700;">
            </form>
        </div>
        <?php
    }

    public static function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'afcglide' ) === false && $hook !== 'post.php' && $hook !== 'post-new.php' ) return;
        wp_enqueue_style( 'afcglide-admin-styles', AFCG_URL . 'assets/css/admin.css', [], time() );
        wp_enqueue_media();
        wp_enqueue_script( 'afcglide-settings-upload', AFCG_URL . 'assets/js/settings-upload.js', [ 'jquery' ], AFCG_VERSION, true );
    }

    public static function register_settings() { register_setting( 'afcglide_settings_group', 'afcglide_options' ); }

    public static function render_field_logo() {
        $options = get_option( 'afcglide_options' );
        $logo_id = isset( $options['company_logo'] ) ? $options['company_logo'] : '';
        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        ?>
        <div class="afcglide-logo-upload">
            <input type="hidden" id="afcglide_logo_id" name="afcglide_options[company_logo]" value="<?php echo esc_attr( $logo_id ); ?>">
            <div class="afcglide-logo-preview" style="margin-bottom:10px;"><?php if ( $logo_url ): ?><img src="<?php echo esc_url( $logo_url ); ?>" style="max-width: 300px;"><?php endif; ?></div>
            <button type="button" class="button button-secondary afcglide-upload-logo-btn">Upload Logo</button>
        </div>
        <?php
    }

    public static function render_field_company_name() {
        $options = get_option( 'afcglide_options' );
        $val = isset( $options['company_name'] ) ? $options['company_name'] : '';
        echo '<input type="text" name="afcglide_options[company_name]" value="' . esc_attr( $val ) . '" class="regular-text" placeholder="Company Name">';
    }

    public static function render_field_maps_key() {
        $options = get_option( 'afcglide_options' );
        $val = isset( $options['google_maps_api_key'] ) ? $options['google_maps_api_key'] : '';
        echo '<input type="text" name="afcglide_options[google_maps_api_key]" value="' . esc_attr( $val ) . '" class="regular-text" placeholder="Maps API Key">';
    }

    public static function lockdown_profile_styles() {
        echo '<style>
            .user-rich-editing-wrap, .user-admin-color-wrap, 
            .user-comment-shortcuts-wrap, .user-admin-bar-front-wrap, 
            .user-language-wrap, .user-syntax-highlighting-wrap,
            .user-description-wrap, .user-url-wrap, #application-passwords-section,
            .user-profile-picture, .show-admin-bar { display: none !important; }
            .wrap h1 { color: #10b981 !important; font-weight: 700 !important; }
        </style>';
    }

    public static function render_custom_user_fields( $user ) {
        $phone = get_user_meta( $user->ID, 'agent_phone', true );
        $photo_id = get_user_meta( $user->ID, 'agent_photo', true );
        $photo_url = $photo_id ? wp_get_attachment_url( $photo_id ) : '';
        ?>
        <div class="afcglide-agent-branding-wrap" style="margin-top: 30px; background:#fff; padding:30px; border-radius:16px; border:1px solid #e2e8f0; max-width: 1040px;">
            <h2 style="color: #10b981; font-weight: 700; margin-top:0;">🛡️ Agent Identity Branding</h2>
            <table class="form-table">
                <tr><th>Phone</th><td><input type="text" name="agent_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text"></td></tr>
                <tr><th>Headshot</th><td>
                    <div style="width:100px; height:100px; border-radius:12px; background:#f8fafc; overflow:hidden; border:2px solid #10b981; margin-bottom:10px;">
                        <?php if($photo_url): ?><img src="<?php echo esc_url($photo_url); ?>" style="width:100%; height:100%; object-fit:cover;"><?php endif; ?>
                    </div>
                    <input type="hidden" name="agent_photo" id="agent_photo_id" value="<?php echo esc_attr($photo_id); ?>">
                    <button type="button" class="button afcglide-upload-photo-btn">Select Headshot</button>
                </td></tr>
            </table>
        </div>
        <?php
    }

    public static function save_custom_user_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;
        if ( isset( $_POST['agent_phone'] ) ) update_user_meta( $user_id, 'agent_phone', sanitize_text_field( $_POST['agent_phone'] ) );
        if ( isset( $_POST['agent_photo'] ) ) update_user_meta( $user_id, 'agent_photo', sanitize_text_field( $_POST['agent_photo'] ) );
    }
}

    