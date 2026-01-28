<?php
namespace AFCGlide\Admin;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Settings {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ], 30 );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_settings_page() {
        add_submenu_page(
            'afcglide-dashboard',          
            'Core Settings',      
            '⚙️ Control Center',               
            'manage_options',              
            'afcglide-settings',           
            [ __CLASS__, 'render_settings_html' ]
        );
    }

    public static function register_settings() {
        $settings = [
            C::OPT_SYSTEM_LABEL,
            C::OPT_WA_COLOR,
            'afc_quality_gatekeeper',
            C::OPT_LOCKDOWN, 
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
            echo '<div class="notice notice-success is-dismissible" style="border-left: 6px solid #10b981; border-radius:12px; margin-top:20px;"><p><strong>Configuration Synced:</strong> Synergy backbone is now operating on updated parameters.</p></div>';
        }
        ?>
        <div class="afc-settings-wrap">
            <div class="afc-settings-header">
                <h1>Executive Command Center / v5.0 GOLD</h1>
                <div class="afc-status-badge">SYSTEM STATUS: ONLINE</div>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( 'afcglide_settings_group' ); ?>

                <div class="afc-settings-card">
                    <h2>🏷️ Identity & Synergy Branding</h2>
                    <div class="afc-settings-row">
                        <div>
                            <span class="afc-label">System White-Label</span>
                            <span class="afc-description">Global naming convention for the platform.</span>
                        </div>
                        <input type="text" name="<?php echo C::OPT_SYSTEM_LABEL; ?>" value="<?php echo esc_attr( get_option(C::OPT_SYSTEM_LABEL, 'Synergy') ); ?>">
                    </div>

                    <div class="afc-settings-row">
                        <div>
                            <span class="afc-label">WhatsApp Accent Color</span>
                            <span class="afc-description">Primary interaction color for communication nodes.</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:20px;">
                            <input type="color" name="<?php echo C::OPT_WA_COLOR; ?>" value="<?php echo esc_attr( get_option(C::OPT_WA_COLOR, '#25D366') ); ?>" style="width: 80px; height: 50px; padding: 4px;">
                            <code><?php echo esc_html( get_option(C::OPT_WA_COLOR, '#25D366') ); ?></code>
                        </div>
                    </div>
                </div>

                <div class="afc-settings-card" style="border-top: 5px solid #10b981;">
                    <h2>🛡️ Infrastructure & Shielding</h2>
                    <div class="afc-settings-row">
                        <div>
                            <span class="afc-label">Global Network Lockdown</span>
                            <span class="afc-description">Engage the Identity Shield for all visitors.</span>
                        </div>
                        <label class="afc-switch">
                            <input type="checkbox" name="<?php echo C::OPT_LOCKDOWN; ?>" value="1" <?php checked(1, get_option(C::OPT_LOCKDOWN, 1)); ?>>
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div class="afc-settings-row">
                        <div>
                            <span class="afc-label">Asset Image Gatekeeper</span>
                            <span class="afc-description">Enforce minimum resolution requirements.</span>
                        </div>
                        <div style="display:flex; align-items:center; gap:20px;">
                            <label class="afc-switch">
                                <input type="checkbox" name="afc_quality_gatekeeper" value="1" <?php checked(1, get_option('afc_quality_gatekeeper', 1)); ?>>
                                <span class="slider"></span>
                            </label>
                            <div style="position:relative; width: 150px;">
                                <input type="number" name="afc_min_image_width" value="<?php echo absint(get_option('afc_min_image_width', 1200)); ?>">
                                <span style="position:absolute; right:15px; top:15px; font-size:10px; font-weight:900; color:#94a3b8;">PX</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div style="text-align: right; padding-bottom: 50px;">
                    <?php submit_button( 'SYNC GLOBAL CONFIGURATION', 'afc-commit-btn', 'submit', false ); ?>
                </div>
            </form>
        </div>
        <?php
    }
}