<?php
namespace AFCGlide\Admin;

// Import Constants for a unified backbone
use AFCGlide\Core\Constants;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Master Settings
 * VERSION 4.7.3: Synergy Bridge & Dynamic Styling
 */
class AFCGlide_Settings {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ], 30 );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_settings_page() {
        add_submenu_page(
            'afcglide-dashboard',          
            'Core Settings',      
            '⚙️ Core Settings',               
            'manage_options',              
            'afcglide-settings',           
            [ __CLASS__, 'render_settings_html' ]
        );
    }

    public static function register_settings() {
        // Updated to use the Brain (Constants) for database keys
        $settings = [
            Constants::OPT_SYSTEM_LABEL,
            Constants::OPT_WA_COLOR,
            'afc_quality_gatekeeper',
            Constants::OPT_LOCKDOWN, // Syncing the Lockdown toggle key
            'afc_load_global_styles',
            'afc_min_image_width',
            'afc_brokerage_address',
            'afc_license_number'
        ];

        foreach ( $settings as $setting ) {
            register_setting( 'afcglide_settings_group', $setting );
        }
    }

    public static function render_settings_html() {
        if ( isset($_GET['settings-updated']) ) {
            echo '<div class="notice notice-success is-dismissible" style="border-left: 6px solid #10b981; border-radius:12px; margin-top:20px;"><p><strong>Configuration Synced:</strong> Backbone settings are now live across the ecosystem.</p></div>';
        }
        ?>
        <style>
            #wpbody-content { background: #f8fafc !important; }
            .afc-settings-wrap { padding: 40px; font-family: 'Inter', -apple-system, sans-serif; max-width: 1000px; }
            
            .afc-settings-header { 
                display: flex; justify-content: space-between; align-items: center;
                background: white;
                padding: 25px 35px; border-radius: 20px; margin-bottom: 30px; 
                border: 1px solid #e2e8f0;
                box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            }
            .afc-settings-header h1 { font-size: 12px; font-weight: 900; letter-spacing: 2px; color: #6366f1; text-transform: uppercase; margin: 0; }
            
            .afc-settings-card { background: white; border-radius: 20px; padding: 40px; margin-bottom: 25px; border: 1px solid #e2e8f0; }
            .afc-settings-card h2 { font-size: 18px; font-weight: 800; color: #1e293b; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; }
            
            .afc-settings-row { display: grid; grid-template-columns: 350px 1fr; gap: 40px; align-items: center; padding: 25px 0; border-bottom: 1px solid #f1f5f9; }
            .afc-settings-row:last-child { border-bottom: none; }
            
            .afc-label { font-size: 14px; font-weight: 700; color: #334155; }
            .afc-description { font-size: 12px; color: #64748b; font-weight: 500; display: block; margin-top: 5px; line-height: 1.4; }
            
            input[type="text"], input[type="number"], input[type="color"] {
                width: 100%; padding: 12px 15px; border-radius: 10px; border: 1px solid #cbd5e1; font-weight: 600; color: #1e293b;
            }
            input[type="color"] { height: 45px; padding: 5px; cursor: pointer; }

            /* Modern Toggle Switch */
            .afc-switch { position: relative; display: inline-block; width: 50px; height: 26px; }
            .afc-switch input { opacity: 0; width: 0; height: 0; }
            .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #e2e8f0; transition: .4s; border-radius: 34px; }
            .slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
            input:checked + .slider { background-color: #10b981; }
            input:checked + .slider:before { transform: translateX(24px); }

            .afc-commit-btn { 
                background: #6366f1 !important; color: white !important; 
                padding: 15px 40px !important; border-radius: 12px !important; 
                font-weight: 800 !important; cursor: pointer !important;
                border: none !important; transition: 0.2s !important;
                text-transform: uppercase; letter-spacing: 1px;
            }
            .afc-commit-btn:hover { background: #4f46e5 !important; transform: translateY(-2px); }
        </style>

        <div class="afc-settings-wrap">
            <div class="afc-settings-header">
                <h1>Global Control Center / v4.7.3</h1>
                <div style="background: #ecfdf5; color: #10b981; padding: 6px 12px; border-radius: 8px; font-size: 10px; font-weight: 900;">NODE SECURE</div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( 'afcglide_settings_group' ); ?>

                <div class="afc-settings-card">
                    <h2>🏷️ Identity & Branding</h2>
                    <div class="afc-settings-row">
                        <div>
                            <span class="afc-label">System White-Label</span>
                            <span class="afc-description">Change the internal naming (e.g., Synergy) across the Terminal.</span>
                        </div>
                        <input type="text" name="<?php echo Constants::OPT_SYSTEM_LABEL; ?>" value="<?php echo esc_attr( get_option(Constants::OPT_SYSTEM_LABEL, 'Synergy') ); ?>">
                    </div>

                    <div class="afc-settings-row">
                        <div>
                            <span class="afc-label">WhatsApp Accent Color</span>
                            <span class="afc-description">Match the floating WhatsApp button to your brand palette.</span>
                        </div>
                        <input type="color" name="<?php echo Constants::OPT_WA_COLOR; ?>" value="<?php echo esc_attr( get_option(Constants::OPT_WA_COLOR, '#25D366') ); ?>" style="width: 100px;">
                    </div>
                </div>

                <div class="afc-settings-card" style="border-top: 4px solid #ef4444;">
                    <h2>🛡️ Quality & Security</h2>
                    <div class="afc-settings-row">
                        <div>
                            <span class="afc-label">Global Network Lockdown</span>
                            <span class="afc-description">Restrict dashboard access and listing updates for all non-admins.</span>
                        </div>
                        <label class="afc-switch">
                            <input type="checkbox" name="<?php echo Constants::OPT_LOCKDOWN; ?>" value="1" <?php checked(1, get_option(Constants::OPT_LOCKDOWN, 0)); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="afc-settings-row">
                        <div>
                            <span class="afc-label">Asset Image Gatekeeper</span>
                            <span class="afc-description">Prevent low-resolution imagery from entering the system.</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:15px;">
                            <label class="afc-switch">
                                <input type="checkbox" name="afc_quality_gatekeeper" value="1" <?php checked(1, get_option('afc_quality_gatekeeper', 1)); ?>>
                                <span class="slider"></span>
                            </label>
                            <div style="position:relative;">
                                <input type="number" name="afc_min_image_width" value="<?php echo absint(get_option('afc_min_image_width', 1200)); ?>" style="width:120px; padding-right:40px;">
                                <span style="position:absolute; right:12px; top:12px; font-size:10px; font-weight:800; color:#94a3b8;">PX</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="text-align: right; padding-top: 10px;">
                    <?php submit_button( 'SYNC CONFIGURATION', 'afc-commit-btn', 'submit', false ); ?>
                </div>
            </form>
        </div>
        <?php
    }
}