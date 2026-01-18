<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Master Settings
 * VERSION 4.6.2: Finalized Global Phone & Branding Handshake
 */
class AFCGlide_Settings {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_settings_page() {
        // Register the page so the URL works, but don't create a separate top-level menu
        // This allows the "Backbone Settings" link in the Dashboard to function perfectly.
        add_submenu_page(
            null,                          // No parent = hidden from sidebar
            'AFCGlide Core Settings',      // Page Title
            'Core Settings',               // Menu Title
            'manage_options',              // Capability
            'afcglide-settings',           // Menu Slug
            [ __CLASS__, 'render_settings_html' ]
        );
    }

    public static function register_settings() {
        // Section 1: Identity & Branding
        register_setting( 'afcglide_settings_group', 'afc_agent_name' );
        register_setting( 'afcglide_settings_group', 'afc_agent_phone_display' ); 
        register_setting( 'afcglide_settings_group', 'afc_primary_color' );
        register_setting( 'afcglide_settings_group', 'afc_whatsapp_color' );
        
        // Section 2: Legal & Brokerage Info
        register_setting( 'afcglide_settings_group', 'afc_brokerage_address' );
        register_setting( 'afcglide_settings_group', 'afc_license_number' );
        
        // Section 3: Safeguards & Features
        register_setting( 'afcglide_settings_group', 'afc_quality_gatekeeper' );
        register_setting( 'afcglide_settings_group', 'afc_admin_lockdown' );
        register_setting( 'afcglide_settings_group', 'afc_whatsapp_global' );
    }

    public static function render_settings_html() {
        if ( isset($_GET['settings-updated']) ) {
            echo '<div class="notice notice-success is-dismissible" style="border-left-color:#10b981; border-radius:8px;"><p><strong>Configuration Synced:</strong> Backbone settings are now live across this entity.</p></div>';
        }
        ?>
        <div class="wrap afc-settings-wrap">
            <div class="afc-settings-header">
                <h1>Backbone Configuration</h1>
                <p>Configure this independent brokerage entity.</p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( 'afcglide_settings_group' ); ?>
                
                <div class="afc-settings-section">
                    <h3>Identity & Branding</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Broker/Agent Name</th>
                            <td><input type="text" name="afc_agent_name" value="<?php echo esc_attr( get_option('afc_agent_name') ); ?>" class="regular-text" placeholder="e.g. John Smith Realty"></td>
                        </tr>
                        <tr>
                            <th scope="row">Global Contact Phone</th>
                            <td>
                                <input type="text" name="afc_agent_phone_display" value="<?php echo esc_attr( get_option('afc_agent_phone_display') ); ?>" class="regular-text" placeholder="e.g. (555) 555-5555">
                                <p class="description">Used for the sitewide Global WhatsApp button.</p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Brand Primary Color</th>
                            <td>
                                <input type="color" name="afc_primary_color" value="<?php echo esc_attr( get_option('afc_primary_color', '#10b981') ); ?>">
                                <span class="description" style="margin-left:10px;">Dashboard highlights and action buttons.</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">WhatsApp Brand Color</th>
                            <td>
                                <input type="color" name="afc_whatsapp_color" value="<?php echo esc_attr( get_option('afc_whatsapp_color', '#25D366') ); ?>">
                                <span class="description" style="margin-left:10px;">Custom color for the floating contact button.</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="afc-settings-section">
                    <h3>🛡️ System Safeguards & Features</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Luxury Image Gatekeeper</th>
                            <td>
                                <label class="afc-switch">
                                    <input type="checkbox" name="afc_quality_gatekeeper" value="1" <?php checked(1, get_option('afc_quality_gatekeeper', 1)); ?>>
                                    <span class="slider round"></span>
                                </label>
                                <span class="description">Enforce 1200px minimum width for property photos.</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">SaaS Admin Lockdown</th>
                            <td>
                                <label class="afc-switch">
                                    <input type="checkbox" name="afc_admin_lockdown" value="1" <?php checked(1, get_option('afc_admin_lockdown', 0)); ?>>
                                    <span class="slider round"></span>
                                </label>
                                <span class="description">Hide all WP menus/notices for a pure white-label experience.</span>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Global Floating WhatsApp</th>
                            <td>
                                <label class="afc-switch">
                                    <input type="checkbox" name="afc_whatsapp_global" value="1" <?php checked(1, get_option('afc_whatsapp_global', 0)); ?>>
                                    <span class="slider round"></span>
                                </label>
                                <span class="description">Display a WhatsApp contact button sitewide.</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="afc-settings-section">
                    <h3>Legal & Compliance</h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row">Office Address</th>
                            <td><input type="text" name="afc_brokerage_address" value="<?php echo esc_attr( get_option('afc_brokerage_address') ); ?>" class="regular-text"></td>
                        </tr>
                        <tr>
                            <th scope="row">Brokerage License #</th>
                            <td><input type="text" name="afc_license_number" value="<?php echo esc_attr( get_option('afc_license_number') ); ?>" class="small-text"></td>
                        </tr>
                    </table>
                </div>

                <?php submit_button( 'Save Backbone Configuration' ); ?>
            </form>
        </div>

        <style>
            .afc-settings-wrap { max-width: 850px; margin-top: 20px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; }
            .afc-settings-header { margin-bottom: 30px; }
            .afc-settings-header h1 { font-size: 32px; font-weight: 900; color: #0f172a; margin: 0; }
            .afc-settings-header p { color: #64748b; font-size: 16px; margin-top: 5px; }
            .afc-settings-section { background: #fff; border: 1px solid #e2e8f0; padding: 30px; border-radius: 16px; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); }
            .afc-settings-section h3 { margin-top: 0; font-size: 18px; font-weight: 800; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px; margin-bottom: 20px; }
            .form-table th { font-weight: 600; color: #475569; width: 280px; padding: 20px 0; }
            .description { color: #94a3b8; font-style: italic; font-size: 13px; display: inline-block; vertical-align: middle; }
            .afc-switch { position: relative; display: inline-block; width: 44px; height: 24px; margin-right: 12px; vertical-align: middle; }
            .afc-switch input { opacity: 0; width: 0; height: 0; }
            .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 34px; }
            .slider:before { position: absolute; content: ""; height: 18px; width: 18px; left: 3px; bottom: 3px; background-color: white; transition: .4s; border-radius: 50%; }
            input:checked + .slider { background-color: #10b981; }
            input:checked + .slider:before { transform: translateX(20px); }
            .button-primary { background: #0f172a !important; border: none !important; padding: 12px 30px !important; height: auto !important; border-radius: 10px !important; font-weight: 700 !important; font-size: 14px !important; box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2) !important; transition: all 0.2s !important; }
            .button-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(15, 23, 42, 0.3) !important; }
        </style>
        <?php
    }
}