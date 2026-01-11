<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Settings {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_admin_menu' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'wp_dashboard_setup', [ __CLASS__, 'add_broker_dashboard_widget' ] );
        
        // The "Surgical Strike" sidebar cleanup
        add_action( 'admin_menu', [ __CLASS__, 'apply_sidebar_lockdown' ], 999 );
    }

    public static function register_settings() {
        register_setting( 'afcglide_settings_group', 'afc_identity_lockdown' );
        register_setting( 'afcglide_settings_group', 'afc_listing_approval' );
        register_setting( 'afcglide_settings_group', 'afc_worker_mode' );
        // Global GPS defaults (Optional but good for production)
        register_setting( 'afcglide_settings_group', 'afc_default_lat' );
        register_setting( 'afcglide_settings_group', 'afc_default_long' );
    }

    public static function add_admin_menu() {
        add_menu_page(
            'AFCGlide Home',
            'AFCGlide Home',
            'read', 
            'afcglide-listings',
            [ __CLASS__, 'render_dashboard' ],
            'dashicons-admin-home',
            2
        );
    }

    public static function apply_sidebar_lockdown() {
        if ( ! current_user_can( 'manage_options' ) ) {
            remove_menu_page( 'elementor' );
            remove_menu_page( 'edit.php?post_type=elementor_library' );
            remove_menu_page( 'edit.php' );                
            remove_menu_page( 'edit-comments.php' );       
            remove_menu_page( 'tools.php' );               
            remove_menu_page( 'options-general.php' );     
            remove_menu_page( 'plugins.php' );             
        }
    }

    public static function render_dashboard() {
        $user = wp_get_current_user();
        $is_commander = current_user_can( 'manage_options' );
        ?>
        <div class="wrap" style="background: #f8fafc; padding: 20px; font-family: -apple-system,BlinkMacSystemFont,sans-serif;">
            
            <div style="margin-bottom: 30px;">
                <h1 style="font-weight: 800; color: #1e293b; font-size: 28px;">
                    AFCGlide <span style="color: #3b82f6;"><?php echo $is_commander ? 'Command Center' : 'Agent Portal'; ?></span>
                </h1>
                <p style="color: #64748b;">Welcome back, <?php echo esc_html($user->display_name); ?> | Status: <span style="color: #22c55e; font-weight: bold;">Online</span></p>
            </div>

            <?php if ( $is_commander ) : ?>
                <div style="background: #f0f9ff; border-radius: 20px; padding: 40px; border: 1px solid #e0f2fe; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); margin-bottom: 30px;">
                    <div style="max-width: 1000px;">
                        <h3 style="margin: 0 0 10px 0; font-size: 18px; color: #0c4a6e; font-weight: 800; text-transform: uppercase; letter-spacing: 0.05em;">🛡️ Global Command & Control</h3>
                        <p style="color: #334155; margin-bottom: 35px; font-size: 15px;">Broker Access Only: Configure office operational modes below.</p>

                        <form method="post" action="options.php">
                            <?php 
                            settings_fields( 'afcglide_settings_group' ); 
                            $lockdown = get_option( 'afc_identity_lockdown', 'no' );
                            $approval = get_option( 'afc_listing_approval', 'no' );
                            $worker   = get_option( 'afc_worker_mode', 'no' );
                            ?>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; margin-bottom: 30px;">
                                
                                <div style="background: white; padding: 25px; border-radius: 15px; border: 1px solid #bae6fd; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <span style="font-size: 24px;">👤</span>
                                        <input type="checkbox" name="afc_identity_lockdown" value="yes" <?php checked( $lockdown, 'yes' ); ?> style="width:22px; height:22px; cursor: pointer;">
                                    </div>
                                    <strong style="display: block; color: #000; font-size: 16px; margin-bottom: 8px;">Identity Lockdown</strong>
                                    <p style="font-size: 13px; color: #64748b; line-height:1.5; margin: 0;">Prevents agents from changing headshots/bios.</p>
                                </div>

                                <div style="background: white; padding: 25px; border-radius: 15px; border: 1px solid #bae6fd; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <span style="font-size: 24px;">📝</span>
                                        <input type="checkbox" name="afc_listing_approval" value="yes" <?php checked( $approval, 'yes' ); ?> style="width:22px; height:22px; cursor: pointer;">
                                    </div>
                                    <strong style="display: block; color: #000; font-size: 16px; margin-bottom: 8px;">Listing Approval</strong>
                                    <p style="font-size: 13px; color: #64748b; line-height:1.5; margin: 0;">Broker must approve listings before they go live.</p>
                                </div>

                                <div style="background: white; padding: 25px; border-radius: 15px; border: 1px solid #bae6fd; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                                        <span style="font-size: 24px;">🔑</span>
                                        <input type="checkbox" name="afc_worker_mode" value="yes" <?php checked( $worker, 'yes' ); ?> style="width:22px; height:22px; cursor: pointer;">
                                    </div>
                                    <strong style="display: block; color: #000; font-size: 16px; margin-bottom: 8px;">Worker Mode</strong>
                                    <p style="font-size: 13px; color: #64748b; line-height:1.5; margin: 0;">Grants specific staff global editing powers.</p>
                                </div>
                            </div>

                            <div style="background: #e0f2fe; padding: 2px 25px; border-radius: 12px; display: inline-block; border: 1px solid #bae6fd;">
                                <?php submit_button( 'Save Global Configuration' ); ?>
                            </div>
                        </form>
                    </div>
                </div>
            <?php else : ?>
                <div style="background: white; border-radius: 20px; padding: 40px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);">
                    <h3 style="color: #1e293b; margin-top: 0;">Welcome to your Dashboard</h3>
                    <p style="color: #64748b; font-size: 16px; line-height: 1.6;">Use the menu on the left to manage your listings and profile.</p>
                </div>
            <?php endif; ?>

        </div>

        <style>
            /* Pastel Blue Button Text Styling */
            .wrap #submit {
                background: transparent !important;
                border: none !important;
                color: #0369a1 !important; 
                box-shadow: none !important;
                text-shadow: none !important;
                font-weight: 700 !important;
                padding: 12px 0 !important;
                cursor: pointer;
                text-transform: uppercase;
                font-size: 13px !important;
                letter-spacing: 0.05em;
            }
        </style>
        <?php
    }
    /**
     * THE BROKER'S RADAR
     * Adds a high-visibility widget to the main WP Dashboard
     */
    public static function add_broker_dashboard_widget() {
        if ( current_user_can( 'manage_options' ) ) {
            wp_add_dashboard_widget(
                'afcglide_broker_radar',
                '🛡️ AFCGlide Office Radar',
                [ __CLASS__, 'render_radar_contents' ]
            );
        }
    }

    public static function render_radar_contents() {
        $pending_count = wp_count_posts( 'afcglide_listing' )->pending;
        $lockdown_active = get_option('afc_identity_lockdown', 'no') === 'yes';
        $lockdown_text = $lockdown_active ? 'ACTIVE' : 'OFF';
        
        ?>
        <div style="padding: 10px 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: #f0f9ff; padding: 15px; border-radius: 10px; border: 1px solid #e0f2fe;">
                <div>
                    <span style="display: block; font-size: 11px; text-transform: uppercase; color: #0369a1; font-weight: 800; letter-spacing: 1px;">Pending Approvals</span>
                    <span style="font-size: 28px; font-weight: 800; color: <?php echo $pending_count > 0 ? '#ef4444' : '#1e293b'; ?>;">
                        <?php echo $pending_count; ?>
                    </span>
                </div>
                <a href="edit.php?post_status=pending&post_type=afcglide_listing" class="button button-primary" style="background: #0369a1 !important; border: none !important; border-radius: 6px !important;">Review Now</a>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="display:block; font-size:10px; color:#64748b; font-weight:bold;">IDENTITY LOCK</span>
                    <span style="font-weight:bold; color: <?php echo $lockdown_active ? '#10b981' : '#64748b'; ?>; display: flex; align-items: center;">
                        <span style="
                            display: inline-block; 
                            width: 8px; 
                            height: 8px; 
                            border-radius: 50%; 
                            background: <?php echo $lockdown_active ? '#10b981' : '#94a3b8'; ?>; 
                            margin-right: 6px;
                            <?php echo $lockdown_active ? 'box-shadow: 0 0 8px #10b981;' : ''; ?>
                        "></span>
                        <?php echo $lockdown_text; ?>
                    </span>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="display:block; font-size:10px; color:#64748b; font-weight:bold;">QUICK LINK</span>
                    <a href="admin.php?page=afcglide-listings" style="text-decoration:none; font-weight:bold; color:#3b82f6;">Command Center →</a>
                </div>
            </div>
        </div>
        <?php
    }
}