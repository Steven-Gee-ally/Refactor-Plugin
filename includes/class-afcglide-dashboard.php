<?php
namespace AFCGlide\Admin; 

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Dashboard {
    
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_welcome_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_protocol_execution' ] );
        add_action( 'wp_ajax_afc_toggle_security', [ __CLASS__, 'ajax_toggle_security' ] );
    }

    public static function register_welcome_page() {
        add_menu_page('AFCGlide Dashboard', 'AFCGlide', 'manage_options', 'afcglide-dashboard', [ __CLASS__, 'render_welcome_screen' ], 'dashicons-dashboard', 2);
        add_submenu_page('afcglide-dashboard', 'Dashboard', 'Dashboard', 'manage_options', 'afcglide-dashboard', [ __CLASS__, 'render_welcome_screen' ]);
        add_submenu_page('afcglide-dashboard', 'Backbone Settings', 'Backbone Settings', 'manage_options', 'afcglide-settings', '__return_null');
    }

    public static function handle_protocol_execution() {
        if ( isset($_POST['afc_execute_protocols']) && check_admin_referer('afc_protocols', 'afc_protocols_nonce') ) {
            update_option('afc_global_lockdown', isset($_POST['global_lockdown']) ? '1' : '0');
            update_option('afc_identity_shield', isset($_POST['identity_shield']) ? '1' : '0');
            wp_redirect( admin_url('admin.php?page=afcglide-dashboard&protocols=executed') );
            exit;
        }
    }

    public static function render_welcome_screen() {
        $current_user = wp_get_current_user();
        $display_name = strtoupper($current_user->first_name ?: $current_user->display_name);
        $total_listings = wp_count_posts('afcglide_listing')->publish;
        $pending_listings = wp_count_posts('afcglide_listing')->pending;
        
        $args = ['post_type' => 'afcglide_listing', 'post_status' => 'publish', 'posts_per_page' => -1, 'fields' => 'ids'];
        $listing_ids = get_posts($args);
        $total_value = 0;
        foreach ($listing_ids as $id) {
            $price = get_post_meta($id, '_listing_price', true);
            if ($price) $total_value += floatval($price);
        }
        $portfolio_display = $total_value > 0 ? '$' . number_format($total_value) . ' EST' : '$0 EST';
        $global_lockdown = get_option('afc_global_lockdown', '0');
        $identity_shield = get_option('afc_identity_shield', '0');
        ?>
        <style>
            #wpbody-content { background: #f8fafc !important; padding: 0 !important; }
            #wpfooter { display: none !important; }
            .afc-control-center { padding: 40px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; max-width: 1600px; margin: 0 auto; }

            /* 🌈 RAINBOW TOP BAR [cite: 2026-01-18] */
            .afc-top-bar { 
                display: flex; justify-content: space-between; align-items: center;
                background: linear-gradient(90deg, #ff9a9e 0%, #fad0c4 25%, #a1c4fd 50%, #c2e9fb 75%, #d4fc79 100%) !important;
                padding: 22px 35px; border-radius: 15px; margin-bottom: 40px; box-shadow: 0 10px 20px rgba(0,0,0,0.05);
            }
            .afc-top-bar-section { font-size: 11px; font-weight: 900; letter-spacing: 2px; color: #1e293b; text-transform: uppercase; }

            /* 🎨 PASTEL ACTION CARDS [cite: 2026-01-18] */
            .afc-quick-actions { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
            .afc-action-card { 
                padding: 35px 25px; border-radius: 20px; text-decoration: none; 
                transition: all 0.3s; display: flex; flex-direction: column; align-items: center; text-align: center; gap: 15px;
                border-bottom: 6px solid;
            }
            .afc-action-card:hover { transform: translateY(-5px); box-shadow: 0 15px 30px rgba(0,0,0,0.08); }
            
            .action-add { background: #f0fdf4 !important; border-bottom-color: #10b981 !important; border-top: 1px solid #dcfce7; border-left: 1px solid #dcfce7; border-right: 1px solid #dcfce7; }
            .action-inventory { background: #fffbeb !important; border-bottom-color: #f59e0b !important; border-top: 1px solid #fef3c7; border-left: 1px solid #fef3c7; border-right: 1px solid #fef3c7; }
            .action-identity { background: #f5f3ff !important; border-bottom-color: #8b5cf6 !important; border-top: 1px solid #ede9fe; border-left: 1px solid #ede9fe; border-right: 1px solid #ede9fe; }
            .action-config { background: #f8fafc !important; border-bottom-color: #334155 !important; border-top: 1px solid #e2e8f0; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; }

            .afc-action-icon { font-size: 32px; }
            .afc-action-text h3 { margin: 0; font-size: 17px; font-weight: 800; color: #1e293b; }
            .afc-action-text p { margin: 5px 0 0; font-size: 12px; color: #64748b; font-weight: 600; }

            /* 🟢 MONEY SECTION (REFINED TYPOGRAPHY) [cite: 2026-01-18] */
            .afc-metrics-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 30px; margin-bottom: 40px; }
            .afc-metric-card { padding: 45px 35px; border-radius: 25px; border-left: 12px solid; background: white; }
            
            .metric-blue { background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%) !important; border-left-color: #22c55e !important; }
            .metric-green { background: linear-gradient(135deg, #f7fee7 0%, #ecfccb 100%) !important; border-left-color: #84cc16 !important; }
            .metric-red { background: linear-gradient(135deg, #f0fdfa 0%, #ccfbf1 100%) !important; border-left-color: #14b8a6 !important; }

            .metric-label { font-size: 11px; font-weight: 900; letter-spacing: 2.5px; color: #166534 !important; margin-bottom: 10px; }
            .metric-value { 
                font-size: 44px !important; 
                font-weight: 700 !important; 
                color: #064e3b !important; 
                letter-spacing: -1.5px !important; 
                line-height: 1 !important;
                margin-top: 5px !important;
            }

            /* 🔵 SECURITY VAULT (FULL WIDTH) [cite: 2026-01-18] */
            .afc-security-section {
                background: #f0f9ff !important; border: 1px solid #bae6fd !important;
                border-radius: 25px; padding: 45px; width: 100%; box-sizing: border-box;
            }
            .afc-security-header { margin-bottom: 35px; border-bottom: 1px solid #e0f2fe; padding-bottom: 20px; }
            .afc-security-header h2 { font-size: 18px; font-weight: 900; color: #0369a1 !important; margin: 0; letter-spacing: 1px; }
            
            .afc-security-controls { display: flex; justify-content: space-between; align-items: center; width: 100%; }
            .afc-security-toggles-group { display: flex; gap: 60px; }
            .afc-security-toggle { display: flex; align-items: center; gap: 15px; }
            .toggle-label { font-size: 15px; font-weight: 800; color: #0c4a6e; }
            
            .afc-switch { position: relative; display: inline-block; width: 50px; height: 28px; }
            .afc-switch input { opacity: 0; width: 0; height: 0; }
            .switch-slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #cbd5e1; transition: .4s; border-radius: 28px; }
            .switch-slider:before { position: absolute; content: ""; height: 20px; width: 20px; left: 4px; bottom: 4px; background-color: white; transition: .4s; border-radius: 50%; }
            input:checked + .switch-slider { background-color: #0ea5e9; }
            input:checked + .switch-slider:before { transform: translateX(22px); }

            .afc-execute-btn {
                background: #0ea5e9 !important; color: white !important; border: none;
                padding: 20px 50px; border-radius: 12px; font-size: 14px; font-weight: 900;
                letter-spacing: 1.5px; cursor: pointer; transition: all 0.3s;
                box-shadow: 0 4px 15px rgba(14, 165, 233, 0.3);
            }
            .afc-execute-btn:hover { background: #0284c7 !important; transform: translateY(-2px); }
        </style>

        <div class="afc-control-center">
            <div class="afc-top-bar">
                <div class="afc-top-bar-section">OPERATOR: <span style="font-weight:900;"><?php echo esc_html($display_name); ?></span></div>
                <div class="afc-top-bar-section">AFCGlide GLOBAL INFRASTRUCTURE</div>
                <div class="afc-top-bar-section">SYSTEM ACTIVE</div>
            </div>

            <div class="afc-quick-actions">
                <a href="<?php echo admin_url('post-new.php?post_type=afcglide_listing'); ?>" class="afc-action-card action-add">
                    <div class="afc-action-icon">➕</div>
                    <div class="afc-action-text"><h3>Add Listing</h3><p>Initialize New Asset</p></div>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=afcglide_listing'); ?>" class="afc-action-card action-inventory">
                    <div class="afc-action-icon">📋</div>
                    <div class="afc-action-text"><h3>Inventory</h3><p>Manage Database</p></div>
                </a>
                <a href="<?php echo admin_url('profile.php'); ?>" class="afc-action-card action-identity">
                    <div class="afc-action-icon">👤</div>
                    <div class="afc-action-text"><h3>Agent Identity</h3><p>Credentials & Bio</p></div>
                </a>
                <a href="<?php echo admin_url('admin.php?page=afcglide-settings'); ?>" class="afc-action-card action-config">
                    <div class="afc-action-icon">⚙️</div>
                    <div class="afc-action-text"><h3>Config</h3><p>System Defaults</p></div>
                </a>
            </div>

            <div class="afc-metrics-grid">
                <div class="afc-metric-card metric-blue">
                    <div class="metric-label">PORTFOLIO VOLUME</div>
                    <div class="metric-value"><?php echo esc_html($portfolio_display); ?></div>
                </div>
                <div class="afc-metric-card metric-green">
                    <div class="metric-label">ACTIVE LISTINGS</div>
                    <div class="metric-value"><?php echo esc_html($total_listings); ?></div>
                </div>
                <div class="afc-metric-card metric-red">
                    <div class="metric-label">PENDING APPROVAL</div>
                    <div class="metric-value"><?php echo esc_html($pending_listings); ?></div>
                </div>
            </div>

            <div class="afc-security-section">
                <div class="afc-security-header"><h2>SYSTEM SECURITY & LOCKDOWN CONTROLS</h2></div>
                <form method="post" action="">
                    <?php wp_nonce_field('afc_protocols', 'afc_protocols_nonce'); ?>
                    <div class="afc-security-controls">
                        <div class="afc-security-toggles-group">
                            <div class="afc-security-toggle">
                                <label class="afc-switch">
                                    <input type="checkbox" name="global_lockdown" value="1" <?php checked($global_lockdown, '1'); ?>>
                                    <span class="switch-slider"></span>
                                </label>
                                <span class="toggle-label">Global Lockdown</span>
                            </div>
                            <div class="afc-security-toggle">
                                <label class="afc-switch">
                                    <input type="checkbox" name="identity_shield" value="1" <?php checked($identity_shield, '1'); ?>>
                                    <span class="switch-slider"></span>
                                </label>
                                <span class="toggle-label">Identity Shield</span>
                            </div>
                        </div>
                        <button type="submit" name="afc_execute_protocols" class="afc-execute-btn">EXECUTE PROTOCOLS</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }
}