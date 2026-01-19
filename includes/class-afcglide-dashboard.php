<?php
namespace AFCGlide\Admin; 

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Dashboard {
    
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_welcome_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_protocol_execution' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_agent_creation' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_backbone_settings' ] );
        add_action( 'wp_ajax_afc_toggle_security', [ __CLASS__, 'ajax_toggle_security' ] );
    }

    public static function register_backbone_settings() {
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

    public static function register_welcome_page() {
        // Main Group - Shifted to position 5.9 (Above Listings at 6.0)
        add_menu_page('AFCGlide Hub', 'AFCGlide', 'read', 'afcglide-dashboard', [ __CLASS__, 'render_welcome_screen' ], 'dashicons-dashboard', 5.9);
        
        // Submenus
        add_submenu_page('afcglide-dashboard', 'Hub Overview', 'Hub Overview', 'read', 'afcglide-dashboard', [ __CLASS__, 'render_welcome_screen' ]);
        
        // ✨ PRO ACCESS: Direct Entry Link for Agents/Brokers
        add_submenu_page('afcglide-dashboard', '🛸 ADD NEW ASSET', '🛸 ADD NEW ASSET', 'read', 'post-new.php?post_type=afcglide_listing');
    }

    public static function handle_protocol_execution() {
        if ( isset($_POST['afc_execute_protocols']) && check_admin_referer('afc_protocols', 'afc_protocols_nonce') ) {
            update_option('afc_global_lockdown', isset($_POST['global_lockdown']) ? '1' : '0');
            update_option('afc_identity_shield', isset($_POST['identity_shield']) ? '1' : '0');
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
                
                // Store temporary success data for the "Access Guide"
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
        
        $args = ['post_type' => 'afcglide_listing', 'post_status' => 'publish'];
        if (!$is_broker) { $args['author'] = $current_user->ID; }
        $total_listings = count(get_posts(array_merge($args, ['posts_per_page' => -1, 'fields' => 'ids'])));
        
        $args_pending = ['post_type' => 'afcglide_listing', 'post_status' => 'pending'];
        if (!$is_broker) { $args_pending['author'] = $current_user->ID; }
        $pending_listings = count(get_posts(array_merge($args_pending, ['posts_per_page' => -1, 'fields' => 'ids'])));
        
        $total_value = self::calculate_portfolio_volume($is_broker ? null : $current_user->ID);
        $portfolio_display = $total_value > 0 ? '$' . number_format($total_value) : '$0';
        ?>

        <div class="afc-control-center">
            <div class="afc-top-bar">
                <div class="afc-top-bar-section">SYSTEM OPERATOR: <span><?php echo esc_html($display_name); ?></span></div>
                <div class="afc-top-bar-section" style="font-weight:900;">AFCGlide GLOBAL INFRASTRUCTURE</div>
                <div class="afc-top-bar-section">SYSTEM NODE: <span style="font-weight:900;">AFCG-PRO-v5.0</span></div>
            </div>

            <?php if (!$is_broker) : ?>
            <div class="afc-hero">
                <div>
                    <h1 style="margin:0; font-size:36px; font-weight:900; letter-spacing:-1.5px;">PROPERTY PRODUCTION: HQ</h1>
                    <p style="margin:10px 0 0; opacity:0.9; font-size:18px; font-weight:600;">Welcome back. Initialize your next global asset with one click.</p>
                </div>
                <a href="<?php echo admin_url('post-new.php?post_type=afcglide_listing'); ?>" class="afc-hero-btn">
                    <span>🚀 FAST SUBMIT ASSET</span>
                </a>
            </div>
            <?php endif; ?>

            <div class="afc-metrics-grid">
                <div class="afc-metric-card metric-blue">
                    <div class="metric-label"><?php echo $is_broker ? 'GLOBAL PORTFOLIO VOLUME' : 'MY PORTFOLIO VOLUME'; ?></div>
                    <div class="metric-value"><?php echo esc_html($portfolio_display); ?></div>
                </div>
                <div class="afc-metric-card metric-green">
                    <div class="metric-label"><?php echo $is_broker ? 'TOTAL ACTIVE UNITS' : 'MY ACTIVE UNITS'; ?></div>
                    <div class="metric-value"><?php echo esc_html($total_listings); ?></div>
                </div>
                <div class="afc-metric-card metric-red">
                    <div class="metric-label"><?php echo $is_broker ? 'PENDING AGENCY REVIEW' : 'MY PENDING APPROVAL'; ?></div>
                    <div class="metric-value"><?php echo esc_html($pending_listings); ?></div>
                </div>
            </div>

            <div class="afc-quick-actions">
                <a href="<?php echo admin_url('post-new.php?post_type=afcglide_listing'); ?>" class="afc-action-card action-add">
                    <span>➕</span><h3>Add Asset</h3>
                </a>
                <a href="<?php echo admin_url('edit.php?post_type=afcglide_listing' . (!$is_broker ? '&author='.$current_user->ID : '')); ?>" class="afc-action-card action-inventory">
                    <span>💼</span><h3>Inventory</h3>
                </a>
                <a href="<?php echo $is_broker ? admin_url('users.php') : admin_url('profile.php'); ?>" class="afc-action-card action-identity">
                    <span>👥</span><h3>Identity</h3>
                </a>
                <a href="#backbone-settings" class="afc-action-card action-config">
                    <span>⚙️</span><h3>Backbone</h3>
                </a>
            </div>

            <?php if ($is_broker) : ?>
            <div class="afc-section" style="background:#fef2f2 !important; border-color:#fecaca !important;">
                <div class="afc-section-header"><div class="afc-pulse"></div><h2 style="color:#991b1b !important;">💓 SYSTEM HEARTBEAT: GLOBAL ACTIVITY</h2></div>
                <div class="afc-activity-stream">
                    <?php self::render_activity_stream(); ?>
                </div>
            </div>

            <div class="afc-section" style="background:#eff6ff !important; border-color:#dbeafe !important;">
                <div class="afc-section-header"><h2 style="color:#1e40af !important;">👥 TEAM PERFORMANCE ROSTER</h2></div>
                <div style="margin: 0 -45px -45px;"><?php self::render_team_roster(); ?></div>
            </div>

            <div class="afc-section" style="background:#f5f3ff !important; border-color:#ddd6fe !important;">
                <div class="afc-section-header"><h2 style="color:#5b21b6 !important;">🚀 RAPID AGENT ONBOARDING</h2></div>
                
                <?php if ( isset($_GET['agent_added']) && $guide = get_transient('afc_last_created_agent') ) : ?>
                    <div style="background:#ecfdf5; border:2px dashed #10b981; padding:30px; border-radius:15px; margin-bottom:35px;">
                        <h3 style="color:#065f46; margin-top:0; font-size:16px;">✅ AGENT ACCESS GUIDE GENERATED</h3>
                        <p style="font-size:13px; color:#065f46; font-weight:700;">Copy and send this to your new client:</p>
                        <textarea readonly style="width:100%; height:100px; padding:15px; border-radius:10px; background:white; border:1px solid #10b981; font-family:monospace; font-size:12px;">Welcome to the AFCGlide Global Network!
Your secure portal is active.
Portal URL: <?php echo esc_url($guide['url']); ?>
Username: <?php echo esc_html($guide['user']); ?>
Password: <?php echo esc_html($guide['pass']); ?></textarea>
                    </div>
                <?php endif; ?>

                <form method="post" action="">
                    <?php wp_nonce_field('afc_rapid_agent', 'afc_rapid_agent_nonce'); ?>
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; align-items: end;">
                        <div>
                            <label style="display:block; font-size:10px; font-weight:800; color:#64748b; margin-bottom:8px; text-transform:uppercase;">Agent Username</label>
                            <input type="text" name="agent_user" required placeholder="agent_name" style="width:100%; padding:12px; border-radius:10px; border:1px solid #cbd5e1;">
                        </div>
                        <div>
                            <label style="display:block; font-size:10px; font-weight:800; color:#64748b; margin-bottom:8px; text-transform:uppercase;">Email Address</label>
                            <input type="email" name="agent_email" required placeholder="agent@email.com" style="width:100%; padding:12px; border-radius:10px; border:1px solid #cbd5e1;">
                        </div>
                        <div>
                            <label style="display:block; font-size:10px; font-weight:800; color:#64748b; margin-bottom:8px; text-transform:uppercase;">Set Password</label>
                            <input type="text" name="agent_pass" required placeholder="SecurePass123!" style="width:100%; padding:12px; border-radius:10px; border:1px solid #cbd5e1;">
                        </div>
                        <button type="submit" name="afc_rapid_add_agent" class="afc-execute-btn" style="background:#8b5cf6 !important; width:100%;">CREATE & GENERATE GUIDE</button>
                    </div>
                </form>
            </div>

            <div id="backbone-settings" class="afc-section" style="background:#fefce8 !important; border-color:#fef08a !important; border-left: 10px solid #ca8a04;">
                <div class="afc-section-header"><h2 style="color:#854d0e !important;">🛰️ INFRASTRUCTURE & BACKBONE SETTINGS</h2></div>
                <form method="post" action="options.php">
                    <?php settings_fields( 'afcglide_settings_group' ); ?>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 35px; margin-bottom: 35px;">
                        <div style="background: #f1f5f9; padding: 30px; border-radius: 20px;">
                            <h3 style="margin-top:0; font-size:13px; color:#2563eb; letter-spacing:1px;">👤 GLOBAL BRAND IDENTITY</h3>
                            <label style="display:block; font-size:10px; font-weight:800; color:#64748b; margin-bottom:8px; text-transform:uppercase;">OFFICE NAME</label>
                            <input type="text" name="afc_agent_name" value="<?php echo esc_attr(get_option('afc_agent_name')); ?>" style="width:100%; padding:12px; border-radius:10px; border:1px solid #cbd5e1; margin-bottom:20px;">
                            <label style="display:block; font-size:10px; font-weight:800; color:#64748b; margin-bottom:8px; text-transform:uppercase;">CONTACT PHONE</label>
                            <input type="text" name="afc_agent_phone_display" value="<?php echo esc_attr(get_option('afc_agent_phone_display')); ?>" style="width:100%; padding:12px; border-radius:10px; border:1px solid #cbd5e1;">
                        </div>
                        <div style="background: #ecfdf5; padding: 30px; border-radius: 20px;">
                            <h3 style="margin-top:0; font-size:13px; color:#059669; letter-spacing:1px;">🛡️ CORE SYSTEM SAFEGUARDS</h3>
                            <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
                                <span style="font-size:13px; font-weight:800; color:#064e3b;">High-Res Photo Gatekeeper</span>
                                <label class="afc-switch"><input type="checkbox" name="afc_quality_gatekeeper" value="1" <?php checked(1, get_option('afc_quality_gatekeeper', 1)); ?>><span class="switch-slider"></span></label>
                            </div>
                            <div style="display:flex; justify-content:space-between; align-items:center;">
                                <span style="font-size:13px; font-weight:800; color:#064e3b;">Global Floating WhatsApp</span>
                                <label class="afc-switch"><input type="checkbox" name="afc_whatsapp_global" value="1" <?php checked(1, get_option('afc_whatsapp_global', 0)); ?>><span class="switch-slider"></span></label>
                            </div>
                        </div>
                    </div>
                    <div style="text-align:right;"><button type="submit" class="afc-execute-btn" style="background:#bbf7d0 !important; color:#166534 !important; border:1px solid #86efac !important; box-shadow: 0 4px 12px rgba(22, 101, 52, 0.1);">COMMIT GLOBAL CONFIG</button></div>
                </form>
            </div>
            <?php endif; ?>

            <div class="afc-section" style="background: #fef2f2 !important; border: 1px solid #fecaca !important;">
                <div class="afc-section-header"><h2 style="color:#991b1b !important;">🔒 SECURITY PROTOCOLS & LOCKDOWN</h2></div>
                <form method="post" action="">
                    <?php wp_nonce_field('afc_protocols', 'afc_protocols_nonce'); ?>
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div style="display:flex; gap:60px;">
                            <div style="display:flex; align-items:center; gap:15px;">
                                <label class="afc-switch"><input type="checkbox" name="global_lockdown" value="1" <?php checked(get_option('afc_global_lockdown'), '1'); ?>><span class="switch-slider"></span></label>
                                <span style="font-size:14px; font-weight:900; color:#7f1d1d;">GLOBAL LOCKDOWN</span>
                            </div>
                            <div style="display:flex; align-items:center; gap:15px;">
                                <label class="afc-switch"><input type="checkbox" name="identity_shield" value="1" <?php checked(get_option('afc_identity_shield'), '1'); ?>><span class="switch-slider"></span></label>
                                <span style="font-size:14px; font-weight:900; color:#7f1d1d;">IDENTITY SHIELD</span>
                            </div>
                        </div>
                        <button type="submit" name="afc_execute_protocols" class="afc-execute-btn" style="background:#dc2626 !important;">EXECUTE PROTOCOLS</button>
                    </div>
                </form>
            </div>
        </div>
        <?php
    }

    public static function render_activity_stream() {
        $recent = get_posts(['post_type'=>'afcglide_listing','post_status'=>['publish','pending','draft'],'posts_per_page'=>8,'orderby'=>'modified']);
        if (empty($recent)) { echo '<p style="font-size:13px; color:#64748b; padding:20px; font-style:italic;">No recent activity detected.</p>'; return; }
        foreach ($recent as $post) {
            $author = get_the_author_meta('display_name', $post->post_author);
            $time = human_time_diff(get_the_modified_time('U', $post->ID), current_time('timestamp')).' ago';
            $status = strtoupper($post->post_status === 'publish' ? 'live' : $post->post_status);
            $bg = $status === 'LIVE' ? '#10b981' : ($status === 'PENDING' ? '#f59e0b' : '#64748b');
            echo '<div style="display:flex; justify-content:space-between; padding:20px; border-bottom:1px solid #f1f5f9; align-items:center; transition:0.2s;">';
            echo '<div style="font-size:14px;"><span style="color:white; background:'.$bg.'; font-weight:900; padding:4px 10px; border-radius:6px; font-size:10px; margin-right:15px; letter-spacing:1px;">'.$status.'</span><strong style="color:#1e293b;">'.esc_html($post->post_title).'</strong> <span style="color:#94a3b8; font-size:12px; margin-left:10px;">by '.$author.'</span></div>';
            echo '<div style="font-size:11px; color:#94a3b8; font-weight:800; text-transform:uppercase;">'.$time.'</div></div>';
        }
    }

    public static function render_team_roster() {
        $agents = get_users(['role__in'=>['listing_agent','managing_broker','administrator']]);
        echo '<table style="width:100%; border-collapse:collapse; font-size:14px; border-radius:0 0 24px 24px; overflow:hidden;">';
        echo '<thead style="background:#f1f5f9; text-align:left;"><tr><th style="padding:20px; color:#64748b; font-size:11px; letter-spacing:1.5px; font-weight:800;">AGENT OPERATOR</th><th style="padding:20px; color:#64748b; font-size:11px; letter-spacing:1.5px; font-weight:800;">ACTIVE UNITS</th><th style="padding:20px; color:#64748b; font-size:11px; letter-spacing:1.5px; font-weight:800;">ACCUMULATED VOLUME</th><th style="padding:20px; color:#64748b; font-size:11px; letter-spacing:1.5px; font-weight:800;">STATUS</th></tr></thead><tbody>';
        foreach ($agents as $user) {
            $count = count(get_posts(['post_type'=>'afcglide_listing','post_status'=>'publish','author'=>$user->ID,'fields'=>'ids','posts_per_page'=>-1]));
            $vol = self::calculate_portfolio_volume($user->ID);
            echo '<tr><td style="padding:20px; border-bottom:1px solid #f1f5f9; font-weight:800; color:#0f172a;">'.esc_html($user->display_name).'</td><td style="padding:20px; border-bottom:1px solid #f1f5f9;">'.$count.'</td><td style="padding:20px; border-bottom:1px solid #f1f5f9; color:#059669; font-weight:900;">$'.number_format($vol).'</td><td style="padding:20px; border-bottom:1px solid #f1f5f9;"><span style="font-size:10px; background:#ecfdf5; color:#065f46; padding:5px 12px; border-radius:20px; font-weight:900; letter-spacing:1px;">ACTIVE</span></td></tr>';
        }
        echo '</tbody></table>';
    }

    public static function calculate_portfolio_volume($author_id = null) {
        global $wpdb;
        $sql = "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM $wpdb->postmeta pm JOIN $wpdb->posts p ON pm.post_id = p.ID WHERE pm.meta_key = '_listing_price' AND p.post_status = 'publish'";
        if ($author_id) { $sql .= $wpdb->prepare(" AND p.post_author = %d", $author_id); }
        return (float) $wpdb->get_var($sql) ?: 0;
    }
}