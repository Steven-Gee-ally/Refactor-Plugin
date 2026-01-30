<?php
namespace AFCGlide\Admin; 

use AFCGlide\Core\Constants as C;
use AFCGlide\Core\AFCGlide_Synergy_Engine as Engine;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Dashboard {
    
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_welcome_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_protocol_execution' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_agent_creation' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_backbone_settings' ] );
        add_action( 'wp_ajax_afc_toggle_security', [ __CLASS__, 'ajax_toggle_security' ] );
        add_action( 'admin_notices', [ __CLASS__, 'check_homepage_configuration' ] );
    }

    public static function register_backbone_settings() {
        register_setting( 'afcglide_settings_group', 'afc_agent_name' );
        register_setting( 'afcglide_settings_group', 'afc_agent_phone_display' ); 
        register_setting( 'afc_primary_color', 'afc_primary_color' );
        register_setting( 'afc_whatsapp_color', 'afc_whatsapp_color' );
        register_setting( 'afc_brokerage_address', 'afc_brokerage_address' );
        register_setting( 'afc_license_number', 'afc_license_number' );
        register_setting( 'afc_quality_gatekeeper', 'afc_quality_gatekeeper' );
        register_setting( 'afc_global_lockdown', 'afc_global_lockdown' );
        register_setting( 'afc_whatsapp_global', 'afc_whatsapp_global' );
    }

    public static function register_welcome_page() {
        $is_broker = current_user_can('manage_options');
        $system_label = get_option('afc_system_label', 'AFCGlide');
        
        add_menu_page($system_label . ' Hub', $system_label, 'read', 'afcglide-dashboard', [ __CLASS__, 'render_welcome_screen' ], 'dashicons-dashboard', 5.9);
        add_submenu_page('afcglide-dashboard', 'Hub Overview', '📊 Hub Overview', 'read', 'afcglide-dashboard', [ __CLASS__, 'render_welcome_screen' ]);
        
        add_submenu_page('afcglide-dashboard', 'Add New Asset', '🛸 Add New Asset', 'read', 'post-new.php?post_type=' . C::POST_TYPE);
        add_submenu_page('afcglide-dashboard', 'My Profile', '👤 My Profile', 'read', 'profile.php');
        add_submenu_page('afcglide-dashboard', 'System Manual', '📘 System Manual', 'read', 'afcglide-manual', [ __CLASS__, 'render_manual_page' ]);
    }

    public static function handle_protocol_execution() {
        if ( isset($_POST['afc_execute_protocols']) && check_admin_referer('afc_protocols', 'afc_protocols_nonce') ) {
            update_option('afc_global_lockdown', isset($_POST['global_lockdown']) ? '1' : '0');
            wp_redirect( admin_url('admin.php?page=afcglide-dashboard&protocols=executed') );
            exit;
        }
    }

    public static function handle_agent_creation() {
        if ( isset($_POST['afc_rapid_add_agent']) && check_admin_referer('afc_rapid_agent', 'afc_rapid_agent_nonce') ) {
            $user_login = sanitize_user($_POST['agent_user']);
            $user_email = sanitize_email($_POST['agent_email']);
            $user_pass  = $_POST['agent_pass'];

            if ( username_exists($user_login) || email_exists($user_email) ) {
                wp_redirect( admin_url('admin.php?page=afcglide-dashboard&agent_error=exists') );
                exit;
            }

            $user_id = wp_create_user($user_login, $user_pass, $user_email);
            if ( ! is_wp_error($user_id) ) {
                $user = new \WP_User($user_id);
                $user->set_role('listing_agent');
                update_user_meta($user_id, 'agent_phone', sanitize_text_field($_POST['agent_phone']));
                
                set_transient('afc_last_created_agent', [
                    'user' => $user_login,
                    'pass' => $user_pass,
                    'url'  => wp_login_url()
                ], 300);

                wp_redirect( admin_url('admin.php?page=afcglide-dashboard&agent_added=1') );
                exit;
            }
        }
    }

    public static function render_welcome_screen() {
        $current_user = wp_get_current_user();
        $is_broker = current_user_can('manage_options');
        $display_name = strtoupper($current_user->first_name ?: $current_user->display_name);
        $focus_mode = get_user_meta(get_current_user_id(), 'afc_focus_mode', true) === '1';
        
        // WORLD-CLASS: Fetch stats via Synergy Engine for high performance
        $stats = Engine::get_synergy_stats();
        ?>

        <div class="afc-control-center">
            
            <!-- 🏢 BRAND Identity -->
            <div class="afc-hub-brand">
                <div class="afc-main-logo">
                    <div class="afc-logo-icon-wrap">
                        <span class="dashicons dashicons-shield"></span>
                    </div>
                    <div class="afc-logo-text">
                        <strong><?php echo esc_html(get_option('afc_system_label', 'AFCGlide')); ?></strong>
                        <span>BROKER COMMAND HUB</span>
                    </div>
                </div>
            </div>

            <!-- 🌐 HUB TOP BAR -->
            <div class="afc-top-bar">
                <div class="afc-top-bar-section">SYSTEM OPERATOR: <span><?php echo esc_html($display_name); ?></span></div>
                <div class="afc-top-bar-section"><?php echo esc_html(get_option('afc_system_label', 'AFCGlide')); ?> GLOBAL HUB</div>
                
                <div class="afc-focus-wrap">
                    <span>EYE_FOCUS MODE</span>
                    <label class="afc-switch">
                        <input type="checkbox" id="afc-focus-toggle" <?php checked($focus_mode); ?>>
                        <span class="switch-slider"></span>
                    </label>
                </div>
            </div>

            <!-- 🗲 HERO SECTION -->
            <div class="afc-hero">
                <div>
                    <h1>PROPERTY PRODUCTION: HQ</h1>
                    <p>Synergy active. Initialize your next global asset now.</p>
                </div>
                <a href="<?php echo admin_url('post-new.php?post_type='.C::POST_TYPE); ?>" class="afc-hero-btn">
                    <span>🚀 FAST SUBMIT ASSET</span>
                </a>
            </div>

            <!-- 📊 UNIFIED SCOREBOARD -->
            <?php echo \AFCGlide\Reporting\AFCGlide_Scoreboard::render_scoreboard( $is_broker ? null : $current_user->ID ); ?>

            <?php if ( $is_broker ) : ?>
            <!-- 🏰 BROKER COMMAND MATRIX -->
            <div class="afc-broker-matrix">
                <!-- ⚙️ SYSTEM BACKBONE -->
                <div class="afc-matrix-card afc-header-green">
                    <h2><span class="dashicons dashicons-admin-generic"></span> SYSTEM BACKBONE</h2>
                    <div class="afc-backbone-grid">
                        <div class="afc-backbone-item">
                            <label>System White Label</label>
                            <input type="text" id="afc-system-label" value="<?php echo esc_attr(get_option('afc_system_label', 'AFCGlide')); ?>">
                        </div>
                        <div class="afc-backbone-item">
                            <label>WhatsApp Accent Color</label>
                            <input type="color" id="afc-whatsapp-color" value="<?php echo esc_attr(get_option('afc_whatsapp_color', '#25d366')); ?>">
                        </div>
                        <div class="afc-toggle-row">
                            <div class="afc-toggle-item">
                                <label class="afc-switch">
                                    <input type="checkbox" id="afc-lockdown-toggle" <?php checked(get_option('afc_global_lockdown'), '1'); ?>>
                                    <span class="switch-slider"></span>
                                </label>
                                <span>NETWORK LOCKDOWN</span>
                            </div>
                            <div class="afc-toggle-item">
                                <label class="afc-switch">
                                    <input type="checkbox" id="afc-gatekeeper-toggle" <?php checked(get_option('afc_quality_gatekeeper'), '1'); ?>>
                                    <span class="switch-slider"></span>
                                </label>
                                <span>IMAGE GATEKEEPER</span>
                            </div>
                        </div>
                        <div class="afc-matrix-footer">
                            <button id="afc-save-backbone" class="afc-vogue-btn">EXECUTE SYSTEM SYNC</button>
                        </div>
                    </div>
                </div>

                <!-- 👤 RAPID ONBOARDING -->
                <div class="afc-matrix-card afc-header-yellow">
                    <h2><span class="dashicons dashicons-admin-users"></span> RAPID ONBOARDING</h2>
                    <div class="afc-backbone-grid">
                        <div class="afc-backbone-item">
                            <label>Agent Username</label>
                            <input type="text" id="afc-new-user" placeholder="e.g. jdoe">
                        </div>
                        <div class="afc-backbone-item">
                            <label>Email Address</label>
                            <input type="email" id="afc-new-email" placeholder="agent@network.com">
                        </div>
                        <div class="afc-backbone-item" style="grid-column: span 2;">
                            <label>Agent Password</label>
                            <input type="text" id="afc-new-pass" value="<?php echo wp_generate_password(12, false); ?>">
                        </div>
                        <div class="afc-matrix-footer">
                            <button id="afc-recruit-btn" class="afc-vogue-btn" style="background:#3b82f6 !important;">RECRUIT AGENT</button>
                        </div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
                <!-- 💓 SYSTEM HEARTBEAT -->
                <div class="afc-section afc-header-red" style="padding: 25px;">
                    <div class="afc-section-header">
                        <div class="afc-pulse"></div>
                        <h2>SYSTEM HEARTBEAT</h2>
                    </div>
                    <?php self::render_activity_stream(); ?>
                </div>

                <!-- 👥 TEAM PERFORMANCE -->
                <div class="afc-section afc-header-orange" style="padding: 25px;">
                    <div class="afc-section-header">
                        <h2>TEAM PERFORMANCE ROSTER</h2>
                    </div>
                    <?php self::render_team_roster(); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 💼 UNIVERSAL INVENTORY -->
            <div class="afc-section" style="padding: 0; background: transparent !important; border: none !important; box-shadow: none !important;">
                <?php 
                $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
                $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
                $status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
                
                $inv_query = \AFCGlide\Admin\AFCGlide_Inventory::get_inventory_query([
                    'paged' => $paged,
                    's' => $search,
                    'status' => $status
                ]);
                
                $inv_stats = Engine::get_detailed_stats( $is_broker ? null : $current_user->ID );
                
                \AFCGlide\Admin\AFCGlide_Inventory::render_inventory_table( $inv_query, $inv_stats ); 
                ?>
            </div>
        </div>
<?php
    }

    public static function render_manual_page() {
        ?>
        <div class="wrap afc-system-manual">
            <style>
                .afc-manual-container { max-width: 900px; margin: 40px auto; background: white; padding: 60px; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); font-family: 'Inter', sans-serif; color: #1e293b; line-height: 1.6; }
                .afc-cover { text-align: center; margin-bottom: 60px; padding: 60px 20px; background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%); border-radius: 16px; border: 4px solid #bae6fd; }
                .afc-manual-h1 { font-size: 42px; color: #1e40af; margin: 0 0 15px 0; font-weight: 900; letter-spacing: -1px; }
                .afc-manual-h2 { font-size: 24px; color: #0f172a; margin: 50px 0 20px 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; font-weight: 800; }
                .afc-manual-h3 { font-size: 18px; color: #334155; margin: 30px 0 15px 0; font-weight: 700; }
                .afc-tips-box { background: #fef9c3; border-left: 5px solid #facc15; padding: 20px; border-radius: 8px; margin: 30px 0; font-size: 15px; }
                .afc-step-box { background: white; border: 1px solid #e2e8f0; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
                .afc-role-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
                .bg-broker { background: #dcfce7; color: #166534; }
                .bg-agent { background: #e0f2fe; color: #0369a1; }
                @media print { .afc-print-btn, #adminmenuwrap, #wpadminbar { display: none; } .afc-manual-container { margin: 0; padding: 0; box-shadow: none; } }
            </style>

            <button onclick="window.print()" style="float: right; background: white; border: 2px solid #e2e8f0; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">🖨️ Print to PDF</button>
            <div style="clear: both;"></div>

            <div class="afc-manual-container">
                <div class="afc-cover">
                    <h1 class="afc-manual-h1">THE REAL ESTATE MACHINE</h1>
                    <p style="font-size: 20px; color: #64748b; font-weight: 600;">System Operator Manual: S-Grade Edition</p>
                </div>

                <h2 class="afc-manual-h2">1. The Core Infrastructure</h2>
                <p>Congratulations. AFCGlide isn't just a plugin; it's a <strong>Real Estate Machine</strong> designed for high-volume asset broadcasting.</p>

                <h2 class="afc-manual-h2">2. Roles & Permissions</h2>
                <div class="afc-step-box"><span class="afc-role-badge bg-broker">MANAGING BROKER</span><p>Full control over the global inventory, agent onboarding, and security protocols.</p></div>
                <div class="afc-step-box"><span class="afc-role-badge bg-agent">LISTING AGENT</span><p>Focused strictly on production. Manage your portfolio and track your performance stats.</p></div>

                <h2 class="afc-manual-h2">3. The Submission Matrix</h2>
                <p>Our world-class submission form ensures no listing is ever subpar:</p>
                <ul>
                    <li><strong>Bilingual Sync:</strong> Every asset requires English and Spanish data for maximum reach.</li>
                    <li><strong>Quality Gatekeeper:</strong> System will reject images under 1200px to maintain luxury standards.</li>
                    <li><strong>GPS Precision:</strong> Coordinates are used for high-fidelity mapping.</li>
                </ul>

                <div class="afc-tips-box"><strong>💡 PRO TIP:</strong> Use the "Rapid Onboarding" tool to create an agent in 5 seconds and generate a custom Access Guide for them.</div>
            </div>
        </div>
        <?php
    }

    public static function render_activity_stream() {
        $recent = get_posts(['post_type'=>C::POST_TYPE,'post_status'=>['publish','pending','sold','draft'],'posts_per_page'=>8, 'orderby'=>'modified']);
        if (empty($recent)) { echo '<p style="padding:20px; font-style:italic; color:#64748b;">No recent activity.</p>'; return; }
        foreach ($recent as $post) {
            $author = get_the_author_meta('display_name', $post->post_author);
            $time = human_time_diff(get_the_modified_time('U', $post->ID), current_time('timestamp')).' ago';
            $status = strtoupper($post->post_status);
            echo "<div style='display:flex; justify-content:space-between; padding:15px; border-bottom:1px solid #f1f5f9; align-items:center;'>";
            echo "<div><span style='font-size:10px; font-weight:900; background:#eee; padding:3px 8px; border-radius:4px; margin-right:10px;'>$status</span><strong>{$post->post_title}</strong> <small>by $author</small></div>";
            echo "<div style='font-size:11px; color:#94a3b8;'>$time</div></div>";
        }
    }

    public static function render_team_roster() {
        $agents = get_users(['role__in'=>['listing_agent','managing_broker','administrator']]);
        echo '<table style="width:100%; border-collapse:collapse; font-size:14px;">';
        echo '<thead style="background:#f1f5f9;"><tr><th style="padding:15px; text-align:left;">AGENT</th><th style="padding:15px;">UNITS</th><th style="padding:15px;">VOLUME</th></tr></thead><tbody>';
        foreach ($agents as $user) {
            $count = count(get_posts(['post_type'=>C::POST_TYPE,'post_status'=>'publish','author'=>$user->ID,'fields'=>'ids','posts_per_page'=>-1]));
            $vol = self::calculate_portfolio_volume($user->ID);
            echo "<tr><td style='padding:15px; border-bottom:1px solid #f1f5f9; font-weight:700;'>{$user->display_name}</td><td style='padding:15px; border-bottom:1px solid #f1f5f9; text-align:center;'>$count</td><td style='padding:15px; border-bottom:1px solid #f1f5f9; text-align:center; color:#059669; font-weight:900;'>$".number_format($vol)."</td></tr>";
        }
        echo '</tbody></table>';
    }

    public static function calculate_portfolio_volume($author_id = null) {
        global $wpdb;
        $sql = "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM $wpdb->postmeta pm JOIN $wpdb->posts p ON pm.post_id = p.ID WHERE pm.meta_key = '_listing_price' AND p.post_status = 'publish'";
        if ($author_id) { $sql .= $wpdb->prepare(" AND p.post_author = %d", $author_id); }
        return (float) $wpdb->get_var($sql) ?: 0;
    }

    public static function check_homepage_configuration() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        if ( get_option( 'show_on_front' ) !== 'page' ) {
            echo '<div class="notice notice-warning is-dismissible"><p><strong>AFCGlide Alert:</strong> For best results, set a static Front Page in <a href="'.admin_url('options-reading.php').'">Settings > Reading</a>.</p></div>';
        }
    }
}