<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Master Settings
 * VERSION 4.7.0: Command Center UI Synchronization [cite: 2026-01-18]
 */
class AFCGlide_Settings {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_settings_page() {
        add_submenu_page(
            null,                          
            'AFCGlide Core Settings',      
            'Core Settings',               
            'manage_options',              
            'afcglide-settings',           
            [ __CLASS__, 'render_settings_html' ]
        );
    }

    public static function register_settings() {
        register_setting( 'afcglide_settings_group', 'afc_agent_name' );
        register_setting( 'afcglide_settings_group', 'afc_agent_phone_display' ); 
        register_setting( 'afcglide_settings_group', 'afc_primary_color' );
        register_setting( 'afcglide_settings_group', 'afc_whatsapp_color' );
        register_setting( 'afcglide_settings_group', 'afc_brokerage_address' );
        register_setting( 'afcglide_settings_group', 'afc_license_number' );
        register_setting( 'afcglide_settings_group', 'afc_quality_gatekeeper' );
        register_setting( 'afcglide_settings_group', 'afc_admin_lockdown' );
        register_setting( 'afcglide_settings_group', 'afc_whatsapp_global' );
    }

    public static function render_settings_html() {
        if ( isset($_GET['settings-updated']) ) {
            echo '<div class="notice notice-success is-dismissible" style="border-left: 6px solid #10b981; border-radius:12px; margin-top:20px;"><p><strong>Configuration Synced:</strong> Backbone settings are now live across this entity.</p></div>';
        }
        ?>
        <style>
            #wpbody-content { background: #f8fafc !important; padding: 0 !important; }
            #wpfooter { display: none !important; }
            .afc-settings-wrap { padding: 40px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 1200px; margin: 0 auto; }

            /* 🌈 RAINBOW TOP BAR [cite: 2026-01-18] */
            .afc-settings-header { 
                display: flex; justify-content: space-between; align-items: center;
                background: linear-gradient(90deg, #ff9a9e 0%, #fad0c4 25%, #a1c4fd 50%, #c2e9fb 75%, #d4fc79 100%) !important;
                padding: 22px 35px; border-radius: 15px; margin-bottom: 40px; box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            }
            .afc-settings-header h1 { font-size: 11px; font-weight: 900; letter-spacing: 2px; color: #1e293b; text-transform: uppercase; margin: 0; }

            /* 📦 CONFIGURATION CARDS [cite: 2026-01-18] */
            .afc-settings-card { 
                background: white; border-radius: 25px; padding: 40px; margin-bottom: 30px;
                border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            }
            .afc-settings-card h2 { font-size: 18px; font-weight: 800; color: #1e293b; margin-top: 0; margin-bottom: 30px; border-bottom: 1px solid rgba(0,0,0,0.05); padding-bottom: 15px; }

            /* 🎨 SECTION TINTS TO MATCH DASHBOARD [cite: 2026-01-18] */
            .section-identity { border-top: 8px solid #8b5cf6; background: #f5f3ff !important; } 
            .section-safeguards { border-top: 8px solid #10b981; background: #f0fdf4 !important; } 
            .section-legal { border-top: 8px solid #334155; background: #f8fafc !important; } 

            .afc-settings-row { 
                display: grid; grid-template-columns: 300px 1fr; gap: 20px; align-items: center;
                padding: 20px 0; border-bottom: 1px solid rgba(0,0,0,0.03);
            }
            .afc-settings-row:last-child { border-bottom: none; }
            .afc-label { font-size: 14px; font-weight: 700; color: #334155; }
            .afc-description { font-size: 12px; color: #64748b; font-style: italic; display: block; margin-top: 4px; line-height: 1.4; }

            /* INPUTS & CONTROLS */
            input[type="text"], input[type="tel"] { width: 100%; max-width: 450px; padding: 12px 15px; border-radius: 10px; border: 1px solid #cbd5e1; font-size: 14px; }
            input[type="color"] { border: none; width: 50px; height: 50px; cursor: pointer; background: none; }

            /* TOGGLES [cite: 2026-01-18] */
            .afc-switch { position: relative; display: inline-block; width: 46px; height: 24px; }
            .afc-switch input { opacity: 0; width: 0; height: 0; }
            .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 24px; }
            .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
            input:checked + .slider { background-color: #10b981; }
            input:checked + .slider:before { transform: translateX(22px); }

            .afc-commit-btn {
                background: #1e293b !important; color: white !important; border: none !important;
                padding: 20px 50px !important; border-radius: 12px !important; font-size: 13px !important;
                font-weight: 900 !important; letter-spacing: 1.5px !important; cursor: pointer;
                transition: 0.3s; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            }
            .afc-commit-btn:hover { transform: translateY(-2px); background: #0f172a !important; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
        </style>

        <div class="afc-settings-wrap">
            <div class="afc-settings-header">
                <h1>BACKBONE CONFIGURATION: SYSTEM CONTROL</h1>
                <div style="font-size: 10px; font-weight: 900; color: #1e293b;">NODE: ACTIVE</div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( 'afcglide_settings_group' ); ?>

                <div class="afc-settings-card section-identity">
                    <h2>👤 Identity & Branding</h2>
                    <div class="afc-settings-row">
                        <div class="afc-label">Broker/Agent Name</div>
                        <input type="text" name="afc_agent_name" value="<?php echo esc_attr( get_option('afc_agent_name') ); ?>" placeholder="e.g. John Smith Realty">
                    </div>
                    <div class="afc-settings-row">
                        <div class="afc-label">
                            Global Contact Phone
                            <span class="afc-description">Used for the sitewide Global WhatsApp button.</span>
                        </div>
                        <input type="text" name="afc_agent_phone_display" value="<?php echo esc_attr( get_option('afc_agent_phone_display') ); ?>" placeholder="e.g. (555) 555-5555">
                    </div>
                    <div class="afc-settings-row">
                        <div class="afc-label">
                            Brand Colors
                            <span class="afc-description">Left: Dashboard Accent | Right: WhatsApp Icon</span>
                        </div>
                        <div style="display:flex; gap:15px; align-items:center;">
                            <input type="color" name="afc_primary_color" value="<?php echo esc_attr( get_option('afc_primary_color', '#10b981') ); ?>">
                            <input type="color" name="afc_whatsapp_color" value="<?php echo esc_attr( get_option('afc_whatsapp_color', '#25D366') ); ?>">
                        </div>
                    </div>
                </div>

                <div class="afc-settings-card section-safeguards">
                    <h2>🛡️ System Safeguards & Features</h2>
                    <div class="afc-settings-row">
                        <div class="afc-label">
                            Luxury Image Gatekeeper
                            <span class="afc-description">Enforce 1200px minimum width for property photos.</span>
                        </div>
                        <label class="afc-switch">
                            <input type="checkbox" name="afc_quality_gatekeeper" value="1" <?php checked(1, get_option('afc_quality_gatekeeper', 1)); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="afc-settings-row">
                        <div class="afc-label">
                            SaaS Admin Lockdown
                            <span class="afc-description">Hide WP menus/notices for a pure white-label experience.</span>
                        </div>
                        <label class="afc-switch">
                            <input type="checkbox" name="afc_admin_lockdown" value="1" <?php checked(1, get_option('afc_admin_lockdown', 0)); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                    <div class="afc-settings-row">
                        <div class="afc-label">
                            Global Floating WhatsApp
                            <span class="afc-description">Display a WhatsApp contact button sitewide.</span>
                        </div>
                        <label class="afc-switch">
                            <input type="checkbox" name="afc_whatsapp_global" value="1" <?php checked(1, get_option('afc_whatsapp_global', 0)); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>

                <div class="afc-settings-card section-legal">
                    <h2>⚖️ Legal & Compliance</h2>
                    <div class="afc-settings-row">
                        <div class="afc-label">Office Address</div>
                        <input type="text" name="afc_brokerage_address" value="<?php echo esc_attr( get_option('afc_brokerage_address') ); ?>">
                    </div>
                    <div class="afc-settings-row">
                        <div class="afc-label">Brokerage License #</div>
                        <input type="text" name="afc_license_number" value="<?php echo esc_attr( get_option('afc_license_number') ); ?>" style="max-width:200px;">
                    </div>
                </div>

                <div style="margin-top: 40px; text-align: right;">
                    <?php submit_button( 'COMMIT CONFIGURATION', 'afc-commit-btn', 'submit', false ); ?>
                </div>
            </form>
        </div>
        <?php
    }
}