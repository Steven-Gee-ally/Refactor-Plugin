<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Command Center v4.3 - Synergy Terminal Build
 * 🖥️ Optimized for MacBook Pro 19" | 🚀 No-Compromise Redirects
 */
class AFCGlide_Admin_Menu {

   public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_menus' ], 5 );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        
        // 🛡️ Load the external Protection Class if it exists
        $protection_file = plugin_dir_path( __FILE__ ) . 'class-afcglide-agent-protection.php';
        if ( file_exists( $protection_file ) ) {
            require_once $protection_file;
        }

        self::enforce_identity_shield();
        self::enforce_global_lockdown();
    }

    public static function register_settings() {
        register_setting('afc_glide_settings', 'afc_lockdown_master');
        register_setting('afc_glide_settings', 'afc_agent_id_lock');
    }

    public static function register_menus() {
        // 1. The Main Sidebar Parent
        add_menu_page('AFCGlide', '🚀 AFCGlide', 'read', 'afcglide-home', [ __CLASS__, 'render_ui' ], 'dashicons-dashboard', 1);
        
        // 2. The WORLD-CLASS REDIRECT: Add New Asset
        // We replace post-new.php with our custom terminal slug
        $lockdown = get_option('afc_lockdown_master', 'no');
        
        if ( $lockdown !== 'yes' ) {
            add_submenu_page(
                'afcglide-home',
                'Launch New Asset',
                '➕ Launch Asset',
                'edit_posts',
                'afcglide-launch', // This is our clean room slug
                [ __CLASS__, 'render_submission_terminal' ]
            );
        }

        // 3. Hidden Settings page
        add_submenu_page('afcglide-home', 'Settings', 'Settings', 'manage_options', 'afcglide-settings', [ __CLASS__, 'render_settings_page' ]);
    }

    /**
     * THE CLEAN ROOM LOADER
     * This pulls in your template-submit-listing.php without Astra clutter
     */
    public static function render_submission_terminal() {
        // 🚀 ACTIVATE ANTI-GRAVITY STYLES FOR THIS PAGE
        self::inject_visionary_styles(); 

        $template_path = plugin_dir_path( __FILE__ ) . '../../templates/template-submit-listing.php';
        if ( file_exists( $template_path ) ) {
            include_once $template_path;
        } else {
            echo '<div class="notice notice-error"><p>Synergy Error: Submission Template not found.</p></div>';
        }
    }
    public static function render_ui() {
        $user = wp_get_current_user();
        
        // 📊 LIVE DATA HOOKS
        $published_count = wp_count_posts('afcglide_listing')->publish ?? 0;
        $pending_count   = wp_count_posts('afcglide_listing')->pending ?? 0;
        $total_volume    = self::calculate_portfolio_volume();

        self::inject_visionary_styles(); 
        ?>
        <div class="afc-vision-wrapper">
            
            <div class="afc-tier-1-bar">
                <div class="bar-left">
                    <span class="operator-label">OPERATOR:</span>
                    <span class="operator-name"><?php echo esc_html( strtoupper($user->display_name) ); ?></span>
                </div>
                <div class="bar-center">
                    <span class="system-title">AFCGlide GLOBAL INFRASTRUCTURE</span>
                </div>
                <div class="bar-right">
                    <span class="status-pulse"></span> SYSTEM ACTIVE
                </div>
            </div>

            <div class="afc-tier-grid afc-tier-2">
                <a href="<?php echo admin_url('admin.php?page=afcglide-launch'); ?>" class="afc-action-card">
                    <div class="card-icon">➕</div>
                    <div class="card-text"><h3>Launch Asset</h3><p>Synergy Submission</p></div>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=afcglide_listing'); ?>" class="afc-action-card">
                    <div class="card-icon">📋</div>
                    <div class="card-text"><h3>Inventory</h3><p>Manage Database</p></div>
                </a>
                <a href="<?php echo admin_url('profile.php'); ?>" class="afc-action-card">
                    <div class="card-icon">👤</div>
                    <div class="card-text"><h3>Agent Identity</h3><p>Credentials & Bio</p></div>
                </a>
                <a href="<?php echo admin_url('admin.php?page=afcglide-settings'); ?>" class="afc-action-card">
                    <div class="card-icon">⚙️</div>
                    <div class="card-text"><h3>Config</h3><p>System Defaults</p></div>
                </a>
            </div>

            <div class="afc-tier-grid afc-tier-3">
                <div class="afc-stat-card">
                    <span class="stat-label">PORTFOLIO VOLUME</span>
                    <span class="stat-value">$<?php echo number_format($total_volume); ?></span>
                </div>
                <div class="afc-stat-card">
                    <span class="stat-label">ACTIVE LISTINGS</span>
                    <span class="stat-value"><?php echo $published_count; ?></span>
                </div>
                <div class="afc-stat-card warning">
                    <span class="stat-label">PENDING APPROVAL</span>
                    <span class="stat-value"><?php echo $pending_count; ?></span>
                </div>
            </div>

            <div class="afc-tier-4-lockdown">
                <div class="lockdown-header">
                    <h3>🛡️ SYSTEM SECURITY & LOCKDOWN CONTROLS</h3>
                </div>
                <form method="post" action="options.php" class="lockdown-form">
                    <?php 
                        settings_fields( 'afc_glide_settings' ); 
                        $lockdown = get_option('afc_lockdown_master', 'no');
                        $id_shield = get_option('afc_agent_id_lock', 'no');
                    ?>
                    <div class="lockdown-grid">
                        <div class="lock-control">
                            <label class="afc-switch">
                                <input type="checkbox" name="afc_lockdown_master" value="yes" <?php checked($lockdown, 'yes'); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="lock-desc">
                                <strong>Global Lockdown</strong>
                                <p>Freeze all front-end listing updates.</p>
                            </div>
                        </div>
                        <div class="lock-control">
                            <label class="afc-switch">
                                <input type="checkbox" name="afc_agent_id_lock" value="yes" <?php checked($id_shield, 'yes'); ?>>
                                <span class="slider"></span>
                            </label>
                            <div class="lock-desc">
                                <strong>Identity Shield</strong>
                                <p>Restrict headshot and bio modifications.</p>
                            </div>
                        </div>
                        <div class="lock-control submit-area">
                            <?php submit_button('EXECUTE PROTOCOLS', 'primary', 'submit', false, ['id' => 'submit']); ?>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    private static function calculate_portfolio_volume() {
        global $wpdb;
        $query = "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM $wpdb->postmeta WHERE meta_key = '_listing_price'";
        return (float) $wpdb->get_var($query) ?: 0;
    }

    public static function render_settings_page() {
        echo '<div class="wrap"><h1>AFCGlide Settings</h1><p>Configuration panel initializing...</p></div>';
    }

  public static function inject_visionary_styles() {
        ?>
        <style>
            body { border: 10px solid red !important; }
            :root { --afc-blue: #2563eb; --afc-dark: #1e293b; --afc-border: #e2e8f0; --afc-ice: #f0f9ff; --afc-action-green: #22c55e; }
            #wpbody-content { background: #f8fafc !important; padding-bottom: 50px; }
            .afc-vision-wrapper { max-width: 1600px; margin: 20px auto; padding: 0 20px; font-family: 'Inter', sans-serif; }

            /* --- BULLETPROOF SURGICAL STRIKE: THE ASTRA SILENCER --- */
            /* This targets the BODY class to ensure Astra and WP notices are erased */
            body[class*="afcglide-launch"] .notice, 
            body[class*="afcglide-launch"] .astra-notice,
            body[class*="afcglide-launch"] #screen-meta-links,
            body[class*="afcglide-launch"] #adminmenuwrap, 
            body[class*="afcglide-launch"] .updated,
            body[class*="afcglide-launch"] .error,
            body[class*="afcglide-launch"] .update-nag,
            body[class*="afcglide-launch"] #wpadminbar,
            body[class*="afcglide-launch"] #astra_settings_meta_box {
                display: none !important;
            }

            body[class*="afcglide-launch"] #adminmenuback, 
body[class*="afcglide-launch"] #adminmenuwrap { 
    display: none !important; 
}
body[class*="afcglide-launch"] #wpcontent { 
    margin-left: 0 !important; 
}

            /* Force content to the very top edge */
            body[class*="afcglide-launch"] #wpbody-content { padding-top: 0 !important; }
            body[class*="afcglide-launch"] #wpcontent { margin-left: 0 !important; } /* Optional: Hides sidebar too if desired */

            .afc-tier-1-bar { 
                background: linear-gradient(135deg, #E0F2FE 0%, #DCFCE7 25%, #FEF9C3 50%, #FCE7F3 75%, #F3E8FF 100%) !important;
                padding: 25px 40px; border-radius: 15px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid rgba(255,255,255,0.8);
            }
            .operator-label { color: #64748b; font-size: 11px; font-weight: 800; }
            .operator-name { color: #1e293b; font-weight: 800; }
            .system-title { font-weight: 900; letter-spacing: 2px; font-size: 14px; color: #1e293b; }
            .status-pulse { height: 10px; width: 10px; background: #22c55e; border-radius: 50%; display: inline-block; margin-right: 8px; box-shadow: 0 0 10px #22c55e; }

            .afc-tier-grid { display: grid; gap: 20px; margin-bottom: 25px; }
            .afc-tier-2 { grid-template-columns: repeat(4, 1fr); }
            .afc-tier-3 { grid-template-columns: repeat(3, 1fr); }

            /* PASTEL IDENTITY */
            .afc-tier-2 .afc-action-card:nth-child(1) { background: rgba(224, 242, 254, 0.7) !important; }
            .afc-tier-2 .afc-action-card:nth-child(2) { background: rgba(220, 252, 231, 0.7) !important; }
            .afc-tier-2 .afc-action-card:nth-child(3) { background: rgba(254, 249, 195, 0.7) !important; }
            .afc-tier-2 .afc-action-card:nth-child(4) { background: rgba(243, 232, 255, 0.7) !important; }

            .afc-action-card, .afc-stat-card { 
                backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 30px; border-radius: 20px; text-decoration: none; display: flex; align-items: center; gap: 20px; border: 1px solid rgba(255,255,255,0.9); transition: 0.3s cubic-bezier(0.4, 0, 0.2, 1); box-shadow: 0 4px 15px rgba(0,0,0,0.03);
            }
            .afc-action-card:hover { transform: translateY(-5px); border-color: white; box-shadow: 0 20px 40px rgba(0,0,0,0.06); background-color: rgba(255,255,255,0.9) !important; }
            .afc-action-card .card-icon { font-size: 32px; background: rgba(255,255,255,0.5); padding: 15px; border-radius: 15px; }
            .afc-action-card h3 { margin: 0; color: var(--afc-dark); font-size: 18px; font-weight: 800; }
            .afc-action-card p { margin: 5px 0 0; color: #64748b; font-size: 13px; }

            .afc-stat-card { background: white !important; border-left: 6px solid var(--afc-blue); flex-direction: column; align-items: flex-start; }
            .afc-stat-card.warning { border-left-color: #ef4444; }
            .stat-label { font-size: 11px; font-weight: 800; color: #64748b; }
            .stat-value { font-size: 36px; font-weight: 900; color: var(--afc-dark); margin-top: 5px; }

            /* SECURITY ZONE */
            .afc-tier-4-lockdown { background: var(--afc-ice) !important; border-radius: 25px; border: 1px solid #bae6fd; margin-top: 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.02); }
            .lockdown-header { background: rgba(186, 230, 253, 0.3); padding: 20px 40px; border-bottom: 1px solid #bae6fd; border-radius: 25px 25px 0 0; }
            .lockdown-header h3 { margin:0; font-weight: 800; color: #0369a1; font-size: 13px; letter-spacing: 0.5px; }
            .lockdown-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 40px; padding: 40px; align-items: center; }
            
            .lock-control { display: flex; align-items: center; gap: 20px; }
            .afc-switch { position: relative; width: 60px; height: 32px; display: inline-block; }
            .afc-switch input { opacity: 0; width: 0; height: 0; }
            .slider { position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background: #cbd5e1; transition: .4s; border-radius: 34px; }
            .slider:before { position: absolute; content: ""; height: 24px; width: 24px; left: 4px; bottom: 4px; background: #fff; transition: .4s; border-radius: 50%; }
            input:checked + .slider { background: #ef4444; }
            input:checked + .slider:before { transform: translateX(28px); }

            #submit { background: var(--afc-action-green) !important; color: white !important; padding: 15px 40px !important; border-radius: 12px !important; font-weight: 800 !important; text-transform: uppercase; letter-spacing: 1px; border: none !important; cursor: pointer; box-shadow: 0 4px 14px rgba(34, 197, 94, 0.4); }
            #submit:hover { background: #16a34a !important; transform: scale(1.02); }
        </style>
        <?php
    }

    public static function enforce_identity_shield() {
        if ( get_option('afc_agent_id_lock') !== 'yes' ) return;
        add_action( 'admin_footer-profile.php', function() {
            ?>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const fields = ['first_name', 'last_name', 'nickname', 'display_name', 'description'];
                    fields.forEach(id => {
                        let el = document.getElementById(id);
                        if(el) { el.readOnly = true; el.style.backgroundColor = '#f1f5f9'; }
                    });
                    let submit = document.getElementById('submit');
                    if(submit) { submit.disabled = true; submit.value = "IDENTITY SHIELD ACTIVE"; }
                });
            </script>
            <?php
        });
    }

    public static function enforce_global_lockdown() {
        if ( get_option('afc_lockdown_master') !== 'yes' ) return;
        add_filter( 'map_meta_cap', function( $caps, $cap, $user_id, $args ) {
            $lock_caps = [ 'edit_post', 'delete_post', 'edit_afcglide_listing', 'delete_afcglide_listing' ];
            if ( in_array( $cap, $lock_caps ) ) {
                $post = get_post( $args[0] ?? 0 );
                if ( $post && $post->post_type === 'afcglide_listing' ) { return [ 'do_not_allow' ]; }
            }
            return $caps;
        }, 10, 4 );
    }   
}