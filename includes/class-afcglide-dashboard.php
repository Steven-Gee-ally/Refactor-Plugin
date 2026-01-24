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
        add_action( 'admin_notices', [ __CLASS__, 'check_homepage_configuration' ] );
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
        $is_broker = current_user_can('manage_options');
        $system_label = get_option('afc_system_label', 'AFCGlide');
        $capability = 'create_afc_listings';

        // Main AFCGlide Menu (Agents + Brokers)
        add_menu_page( $system_label . ' Hub', $system_label, $capability, 'afcglide-dashboard', [ __CLASS__, 'render_welcome_screen' ], 'dashicons-dashboard', 5.9 );

        // Hub Overview
        add_submenu_page( 'afcglide-dashboard', 'Hub Overview', '📊 Hub Overview', $capability, 'afcglide-dashboard', [ __CLASS__, 'render_welcome_screen' ] );

        // My Portfolio (Agents) / Global Inventory (Brokers)
        if ( $is_broker ) {
            add_submenu_page( 'afcglide-dashboard', 'Global Inventory', '💼 Global Inventory', $capability, 'afcglide-inventory', '' );
        } else {
            add_submenu_page( 'afcglide-dashboard', 'My Portfolio', '💼 My Portfolio', $capability, 'afcglide-inventory', '' );
        }

        // Add New Asset
        add_submenu_page( 'afcglide-dashboard', 'Add New Asset', '🛸 Add New Asset', $capability, 'post-new.php?post_type=afcglide_listing' );

        // My Profile
        add_submenu_page( 'afcglide-dashboard', 'My Profile', '👤 My Profile', $capability, 'profile.php' );

        // System Manual
        add_submenu_page( 'afcglide-dashboard', 'System Manual', '📘 System Manual', $capability, 'afcglide-manual', [ __CLASS__, 'render_manual_page' ] );
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
        // Protect this screen: only users with the agent capability (or admins) may access
        if ( ! current_user_can( 'create_afc_listings' ) ) {
            if ( ! is_user_logged_in() ) {
                wp_redirect( wp_login_url() );
                exit;
            }
            wp_die( __( 'Unauthorized access', 'afcglide' ), 403 );
        }

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
                <div class="afc-top-bar-section" style="font-weight:900; text-transform:uppercase;"><?php echo esc_html(get_option('afc_system_label', 'AFCGlide')); ?> GLOBAL INFRASTRUCTURE</div>
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

            <!-- 🚀 THE REAL ESTATE MACHINE: MODERN SCOREBOARD -->
            <?php echo \AFCGlide\Reporting\AFCGlide_Scoreboard::render_scoreboard( $is_broker ? null : $current_user->ID ); ?>

            <?php if (!$is_broker) : 
                // Get actionable data for agents
                $drafts = get_posts(['post_type' => 'afcglide_listing', 'post_status' => 'draft', 'author' => $current_user->ID, 'posts_per_page' => -1]);
                $recent_listings = get_posts(['post_type' => 'afcglide_listing', 'post_status' => 'publish', 'author' => $current_user->ID, 'posts_per_page' => 5, 'orderby' => 'date', 'order' => 'DESC']);
                $has_profile_photo = get_user_meta($current_user->ID, '_agent_photo_id', true);
                $total_views = 0;
                foreach ($recent_listings as $listing) {
                    $total_views += intval(get_post_meta($listing->ID, '_listing_views_count', true));
                }
            ?>
            
            <!-- Agent Insight Cards -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 25px; margin-bottom: 35px;">
                
                <!-- Quick Start Card -->
                <div style="background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); padding: 30px; border-radius: 16px; border: 2px solid #93c5fd;">
                    <div style="font-size: 24px; margin-bottom: 12px;">🎯</div>
                    <h3 style="margin: 0 0 15px 0; font-size: 16px; font-weight: 800; color: #1e40af;">Quick Start</h3>
                    <ul style="margin: 0; padding: 0; list-style: none; font-size: 14px; color: #1e293b;">
                        <?php if (!$has_profile_photo) : ?>
                        <li style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                            <span style="color: #f59e0b;">⚠️</span>
                            <a href="<?php echo admin_url('profile.php'); ?>" style="color: #1e40af; text-decoration: none; font-weight: 600;">Complete Your Profile</a>
                        </li>
                        <?php endif; ?>
                        <?php if (empty($recent_listings)) : ?>
                        <li style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                            <span style="color: #059669;">✓</span>
                            <a href="<?php echo admin_url('post-new.php?post_type=afcglide_listing'); ?>" style="color: #1e40af; text-decoration: none; font-weight: 600;">Upload Your First Listing</a>
                        </li>
                        <?php else : ?>
                        <li style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                            <span style="color: #059669;">✓</span>
                            <a href="<?php echo admin_url('admin.php?page=afcglide-inventory'); ?>" style="color: #1e40af; text-decoration: none; font-weight: 600;">Review Active Listings</a>
                        </li>
                        <?php endif; ?>
                        <li style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                            <span style="color: #059669;">✓</span>
                            <a href="<?php echo admin_url('post-new.php?post_type=afcglide_listing'); ?>" style="color: #1e40af; text-decoration: none; font-weight: 600;">Create New Listing</a>
                        </li>
                    </ul>
                </div>

                <!-- Performance Card -->
                <div style="background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); padding: 30px; border-radius: 16px; border: 2px solid #86efac;">
                    <div style="font-size: 24px; margin-bottom: 12px;">📈</div>
                    <h3 style="margin: 0 0 15px 0; font-size: 16px; font-weight: 800; color: #166534;">Your Performance</h3>
                    <div style="font-size: 14px; color: #1e293b; line-height: 1.8;">
                        <div style="margin-bottom: 12px;">
                            <span style="font-weight: 700; color: #059669;"><?php echo count($recent_listings); ?></span> Active Listings
                        </div>
                        <div style="margin-bottom: 12px;">
                            <span style="font-weight: 700; color: #059669;"><?php echo number_format($total_views); ?></span> Total Views
                        </div>
                        <div>
                            <span style="font-weight: 700; color: #f59e0b;"><?php echo $pending_listings; ?></span> Pending Sale<?php echo $pending_listings != 1 ? 's' : ''; ?>
                        </div>
                    </div>
                </div>

                <!-- Need Attention Card -->
                <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); padding: 30px; border-radius: 16px; border: 2px solid #fbbf24;">
                    <div style="font-size: 24px; margin-bottom: 12px;">⚡</div>
                    <h3 style="margin: 0 0 15px 0; font-size: 16px; font-weight: 800; color: #92400e;">Need Attention</h3>
                    <?php if (!empty($drafts)) : ?>
                        <div style="font-size: 14px; color: #1e293b; margin-bottom: 12px;">
                            <span style="font-weight: 700; color: #f59e0b;"><?php echo count($drafts); ?></span> Draft<?php echo count($drafts) != 1 ? 's' : ''; ?> Need Completion
                        </div>
                        <a href="<?php echo admin_url('admin.php?page=afcglide-inventory'); ?>" style="display: inline-block; background: #f59e0b; color: white; padding: 8px 16px; border-radius: 8px; text-decoration: none; font-size: 13px; font-weight: 700;">View Drafts →</a>
                    <?php else : ?>
                        <div style="font-size: 14px; color: #64748b;">
                            <span style="color: #059669;">✓</span> All listings up to date!
                        </div>
                    <?php endif; ?>
                </div>

            </div>
            <?php endif; ?>

            <?php if ($is_broker) : ?>
            <div class="afc-quick-actions">
                <a href="<?php echo admin_url('post-new.php?post_type=afcglide_listing'); ?>" class="afc-action-card action-add">
                    <span>➕</span><h3>Add Asset</h3>
                </a>
                <a href="<?php echo admin_url('admin.php?page=afcglide-inventory'); ?>" class="afc-action-card action-inventory">
                    <span>💼</span><h3>Inventory</h3>
                </a>
                <a href="<?php echo admin_url('profile.php'); ?>" class="afc-action-card action-identity">
                    <span>👤</span><h3>Profile</h3>
                </a>
                <a href="#backbone-settings" class="afc-action-card action-config">
                    <span>⚙️</span><h3>Backbone</h3>
                </a>
            </div>
            <?php endif; ?>

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
        $recent = get_posts(['post_type'=>'afcglide_listing','post_status'=>['publish','pending','sold','draft'],'posts_per_page'=>8, 'orderby'=>'modified']);
        if (empty($recent)) { echo '<p style="font-size:13px; color:#64748b; padding:20px; font-style:italic;">No recent activity detected.</p>'; return; }
        foreach ($recent as $post) {
            $author = get_the_author_meta('display_name', $post->post_author);
            $time = human_time_diff(get_the_modified_time('U', $post->ID), current_time('timestamp')).' ago';
            $status = strtoupper($post->post_status === 'publish' ? 'live' : ($post->post_status === 'sold' ? 'closed' : $post->post_status));
            $bg = $status === 'LIVE' ? '#10b981' : ($status === 'CLOSED' ? '#ef4444' : ($status === 'PENDING' ? '#f59e0b' : '#64748b'));
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

    public static function render_manual_page() {
        ?>
        <div class="wrap afc-system-manual">
            <!-- Manual Content --> 
            <!-- (Content omitted for brevity, use existing) -->
             <?php // re-insert existing manual code if needed, but for appending new function:
             // I will replace only the end of the class to append the new function
             ?>
            <style>
                .afc-manual-container { max-width: 900px; margin: 40px auto; background: white; padding: 60px; border-radius: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.05); font-family: 'Inter', sans-serif; color: #1e293b; line-height: 1.6; }
                .afc-cover { text-align: center; margin-bottom: 60px; padding: 60px 20px; background: linear-gradient(135deg, #e0f2fe 0%, #dbeafe 100%); border-radius: 16px; border: 4px solid #bae6fd; }
                .afc-manual-h1 { font-size: 42px; color: #1e40af; margin: 0 0 15px 0; font-weight: 900; letter-spacing: -1px; }
                .afc-manual-h2 { font-size: 24px; color: #0f172a; margin: 50px 0 20px 0; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; font-weight: 800; }
                .afc-manual-h3 { font-size: 18px; color: #334155; margin: 30px 0 15px 0; font-weight: 700; }
                .afc-tips-box { background: #fef9c3; border-left: 5px solid #facc15; padding: 20px; border-radius: 8px; margin: 30px 0; font-size: 15px; }
                .afc-step-box { background: white; border: 1px solid #e2e8f0; padding: 25px; border-radius: 12px; margin-bottom: 20px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); }
                .afc-step-num { display: inline-block; background: #0ea5e9; color: white; width: 28px; height: 28px; border-radius: 50%; text-align: center; line-height: 28px; font-weight: bold; margin-right: 12px; font-size: 13px; }
                .afc-role-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; }
                .bg-broker { background: #dcfce7; color: #166534; }
                .bg-agent { background: #e0f2fe; color: #0369a1; }
                .afc-print-btn { float: right; background: white; border: 2px solid #e2e8f0; color: #64748b; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
                .afc-print-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
            </style>

            <button onclick="window.print()" class="afc-print-btn">🖨️ Print to PDF</button>
            <div style="clear: both;"></div>

            <div class="afc-manual-container">
                <!-- Cover -->
                <div class="afc-cover">
                    <h1 class="afc-manual-h1">THE REAL ESTATE MACHINE</h1>
                    <p style="font-size: 20px; color: #64748b; font-weight: 600; margin-bottom: 30px;">How to Dominate the Market Without Losing Your Keys</p>
                    <div style="font-size: 13px; opacity: 0.7; font-weight: 600; text-transform: uppercase; letter-spacing: 1px;">Volume 4.0 • S-Grade Edition</div>
                </div>

                <!-- Intro -->
                <h2 class="afc-manual-h2">1. Welcome to the "Ferrari" of Plugins</h2>
                <p>Congratulations. By installing AFCGlide, you have essentially traded in a bicycle for a rocket ship. This isn't just a "listing plugin"—it's a <strong>Real Estate Machine</strong> designed to make you look expensive, organized, and terrifyingly efficient.</p>
                <div class="afc-tips-box">
                    <strong>💡 Pro Tip:</strong> If something looks too good, that's just the "S-Grade design" kicking in. Do not panic. It is meant to look that cool.
                </div>

                <!-- Roles -->
                <h2 class="afc-manual-h2">2. The Crew: Who does what?</h2>
                <div class="afc-step-box" style="border-left: 5px solid #10b981;">
                    <h3 class="afc-manual-h3" style="margin-top:0;"><span class="afc-role-badge bg-broker">MANAGING BROKER</span> (The Boss)</h3>
                    <p>You have the "Keys to the City." You see everything.</p>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li><strong>Theme:</strong> Emerald Green Command Center.</li>
                        <li><strong>Powers:</strong> Add agents, edit anyone's listings, global settings.</li>
                        <li><strong>Vibe:</strong> "I run this town."</li>
                    </ul>
                </div>
                <div class="afc-step-box" style="border-left: 5px solid #0ea5e9;">
                    <h3 class="afc-manual-h3" style="margin-top:0;"><span class="afc-role-badge bg-agent">LISTING AGENT</span> (The Producer)</h3>
                    <p>You are here to sell. We stripped away all the boring stuff for you.</p>
                    <ul style="margin: 0; padding-left: 20px;">
                        <li><strong>Theme:</strong> Sky Blue Productivity Zone.</li>
                        <li><strong>Powers:</strong> Add listings, manage YOUR portfolio, track YOUR stats.</li>
                        <li><strong>Vibe:</strong> "Show me the money."</li>
                    </ul>
                </div>

                <!-- How to Add Listing -->
                <h2 class="afc-manual-h2">3. Launching an Asset (Adding a Listing)</h2>
                <p>We've replaced the old boring form with the <strong>"Submission Matrix."</strong> Just follow the rainbow headers:</p>
                
                <div class="afc-step-box">
                    <div style="margin-bottom: 15px;"><span class="afc-step-num" style="background: #6366f1;">1</span> <strong>Narrative (Indigo):</strong> Tell the story. Wax poetic about "sun-drenched foyers."</div>
                    <div style="margin-bottom: 15px;"><span class="afc-step-num" style="background: #166534;">2</span> <strong>Details (Green):</strong> The hardcore stats. Price, beds, baths. The "money" section.</div>
                    <div style="margin-bottom: 15px;"><span class="afc-step-num" style="background: #3b82f6;">3</span> <strong>Files (Pastel Blue):</strong> Upload floor plans or intelligence docs.</div>
                    <div><span class="afc-step-num" style="background: #f97316;">4</span> <strong>Publish (Orange):</strong> The big "Go Live" button. Push it and win.</div>
                </div>

                <div class="afc-tips-box">
                    <strong>⚠️ The Gatekeeper:</strong> The system will gently REJECT any photo smaller than 1200px wide. We run a luxury establishment here. No blurry photos allowed!
                </div>

                <!-- Bells & Whistles -->
                <h2 class="afc-manual-h2">4. The Bells & Whistles</h2>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="afc-step-box">
                        <h4 style="margin:0 0 10px 0;">🏆 The Scoreboard</h4>
                        <p style="font-size: 14px;">Tracks your Active Portfolio Value. Watch the numbers go up. It releases dopamine. That is science.</p>
                    </div>
                    <div class="afc-step-box">
                        <h4 style="margin:0 0 10px 0;">🎊 The Success Protocol</h4>
                        <p style="font-size: 14px;">Mark a listing as <strong>SOLD</strong> and the system throws a digital party. You deserve it.</p>
                    </div>
                </div>

                <p style="text-align: center; margin-top: 60px; font-weight: 600; color: #94a3b8;">AFCGlide Global Infrastructure &copy; <?php echo date('Y'); ?></p>
            </div>
        </div>
        <?php
    }

    public static function check_homepage_configuration() {
        if ( ! current_user_can('manage_options') ) return;
        
        // Check if homepage is set to 'posts' (default Hello World)
        if ( 'posts' == get_option( 'show_on_front' ) ) {
            ?>
            <div class="notice notice-warning is-dismissible" style="border-left: 5px solid #f59e0b; margin-top: 20px;">
                <p><strong>⚠️ Real Estate System Alert:</strong> Your homepage is currently displaying the default Blog Feed ("Hello World").</p>
                <p>To launch your Real Estate Engine, go to <a href="<?php echo admin_url('options-reading.php'); ?>">Settings > Reading</a> and set "Your homepage displays" to <strong>A static page</strong>.</p>
            </div>
            <?php
        }
    }
}