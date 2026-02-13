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
        add_action( 'admin_init', [ __CLASS__, 'handle_core_setup' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_backbone_settings' ] );
        add_action( 'wp_ajax_afcg_sync_backbone', [ __CLASS__, 'ajax_sync_backbone' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_dashboard_scripts' ] );
        add_action( 'admin_notices', [ __CLASS__, 'check_homepage_configuration' ] );
    }

    /**
     * Enqueue Dashboard Scripts & Styles
     */
    public static function enqueue_dashboard_scripts( $hook ) {
        if ( $hook !== 'toplevel_page_afcglide-dashboard' && $hook !== 'afcglide_page_afcglide-manual' ) {
            return;
        }
        
        // ENQUEUE CSS
        wp_enqueue_style( 
            'afc-dashboard-css', 
            AFCG_URL . 'assets/css/afcglide-dashboard.css', 
            [], 
            AFCG_VERSION 
        );
        
        // ENQUEUE INLINE JS
        wp_add_inline_script( 'jquery', self::get_dashboard_js() );
    }

    /**
     * Get Dashboard JavaScript
     */
    private static function get_dashboard_js() {
        $ajax_url = admin_url( 'admin-ajax.php' );
        $nonce = wp_create_nonce( C::NONCE_AJAX );
        $recruitment_nonce = wp_create_nonce( C::NONCE_RECRUITMENT );

        return <<<JS
        jQuery(document).ready(function($) {
            
            // SYSTEM BACKBONE SYNC
            $('#afc-save-backbone').on('click', function(e) {
                e.preventDefault();
                const btn = $(this);
                const originalText = btn.text();
                btn.prop('disabled', true).text('SYNCING...');
                
                $.ajax({
                    url: '{$ajax_url}',
                    type: 'POST',
                    data: {
                        action: 'afcg_sync_backbone',
                        afc_listing_nonce: '{$nonce}',
                        system_label: $('#afc-system-label').val(),
                        whatsapp_color: $('#afc-whatsapp-color').val(),
                        agent_name: $('#afc-agent-name').val(),
                        agent_phone: $('#afc-agent-phone').val(),
                        lockdown: $('#afc-lockdown-toggle').is(':checked') ? '1' : '0',
                        gatekeeper: $('#afc-gatekeeper-toggle').is(':checked') ? '1' : '0'
                    },
                    success: function(response) {
                        if (response.success) {
                            btn.text('✓ SYNCED').css('background', '#22c55e');
                            setTimeout(function() {
                                btn.text(originalText).css('background', '').prop('disabled', false);
                            }, 2000);
                        } else {
                            alert('Sync failed: ' + (response.data?.message || 'Unknown error'));
                            btn.text(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                        btn.text(originalText).prop('disabled', false);
                    }
                });
            });

            // AGENT RECRUITMENT
            $('#afc-recruit-btn').on('click', function(e) {
                e.preventDefault();
                const btn = $(this);
                const originalText = btn.text();
                const username = $('#afc-new-user').val().trim();
                const email = $('#afc-new-email').val().trim();
                const password = $('#afc-new-pass').val();
                
                if (!username || !email || !password) {
                    alert('All fields are required for agent recruitment.');
                    return;
                }
                
                btn.prop('disabled', true).text('RECRUITING...');
                
                $.ajax({
                    url: '{$ajax_url}',
                    type: 'POST',
                    data: {
                        action: 'afcg_recruit_agent',
                        afc_listing_nonce: '{$recruitment_nonce}',
                        agent_username: username,
                        agent_email: email,
                        password: password
                    },
                    success: function(response) {
                        if (response.success) {
                            btn.text('✓ RECRUITED').css('background', '#22c55e');
                            alert('Agent recruited successfully!\\n\\nUsername: ' + username + '\\nPassword: ' + password);
                            $('#afc-new-user, #afc-new-email, #afc-new-pass').val('');
                            setTimeout(function() {
                                btn.text(originalText).css('background', '').prop('disabled', false);
                                location.reload();
                            }, 2000);
                        } else {
                            alert('Recruitment failed: ' + (response.data?.message || 'Unknown error'));
                            btn.text(originalText).prop('disabled', false);
                        }
                    },
                    error: function() {
                        alert('Network error. Please try again.');
                        btn.text(originalText).prop('disabled', false);
                    }
                });
            });

            // FOCUS MODE TOGGLE
            $('#afc-focus-toggle').on('change', function() {
                $.ajax({
                    url: '{$ajax_url}',
                    type: 'POST',
                    data: {
                        action: 'afcg_toggle_focus',
                        afc_listing_nonce: '{$nonce}',
                        status: $(this).is(':checked') ? '1' : '0'
                    }
                });
            });
        });
        JS;
    }

    /**
     * AJAX: Sync Backbone Settings
     */
    public static function ajax_sync_backbone() {
        check_ajax_referer( C::NONCE_AJAX, 'afc_listing_nonce' );
        
        if ( ! current_user_can( C::CAP_MANAGE ) ) {
            wp_send_json_error( ['message' => 'Insufficient permissions.'] );
        }

        update_option( 'afc_system_label', sanitize_text_field( $_POST['system_label'] ) );
        update_option( 'afc_whatsapp_color', sanitize_hex_color( $_POST['whatsapp_color'] ) );
        update_option( 'afc_agent_name', sanitize_text_field( $_POST['agent_name'] ) );
        update_option( 'afc_agent_phone_display', sanitize_text_field( $_POST['agent_phone'] ) );
        update_option( 'afc_global_lockdown', $_POST['lockdown'] === '1' ? '1' : '0' );
        update_option( 'afc_quality_gatekeeper', $_POST['gatekeeper'] === '1' ? '1' : '0' );

        wp_send_json_success();
    }

    public static function register_backbone_settings() {
        register_setting( 'afcglide_settings_group', 'afc_agent_name' );
        register_setting( 'afcglide_settings_group', 'afc_agent_phone_display' ); 
        register_setting( 'afcglide_settings_group', 'afc_primary_color' );
        register_setting( 'afcglide_settings_group', 'afc_whatsapp_color' );
        register_setting( 'afcglide_settings_group', 'afc_brokerage_address' );
        register_setting( 'afcglide_settings_group', 'afc_license_number' );
        register_setting( 'afcglide_settings_group', 'afc_quality_gatekeeper' );
        register_setting( 'afcglide_settings_group', 'afc_global_lockdown' );
        register_setting( 'afcglide_settings_group', 'afc_whatsapp_global' );
    }

    public static function register_welcome_page() {
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
                set_transient('afc_last_created_agent', ['user' => $user_login, 'pass' => $user_pass, 'url' => wp_login_url()], 300);
                wp_redirect( admin_url('admin.php?page=afcglide-dashboard&agent_added=1') );
                exit;
            }
        }
    }

    public static function handle_core_setup() {
        if ( isset($_POST['afc_run_initializer']) && check_admin_referer('afc_initializer', 'afc_initializer_nonce') ) {
            $pages = [
                ['title' => 'Agent Hub', 'content' => '[afc_agent_inventory]', 'slug' => 'agent-hub'],
                ['title' => 'Add New Listing', 'content' => '[afcglide_submit_listing]', 'slug' => 'submit-listing'],
                ['title' => 'Listings Portfolio', 'content' => '[afcglide_listings_grid]', 'slug' => 'portfolio'],
                ['title' => 'Agent Login', 'content' => '[afcglide_login]', 'slug' => 'agent-login']
            ];
            foreach ( $pages as $page ) {
                if ( ! get_page_by_path( $page['slug'] ) ) {
                    wp_insert_post(['post_title' => $page['title'], 'post_content' => $page['content'], 'post_status' => 'publish', 'post_type' => 'page', 'post_name' => $page['slug']]);
                }
            }
            wp_redirect( admin_url('admin.php?page=afcglide-dashboard&core_setup=executed') );
            exit;
        }
    }

    public static function render_welcome_screen() {
        $current_user = wp_get_current_user();
        $is_broker = current_user_can('manage_options');
        $display_name = strtoupper($current_user->first_name ?: $current_user->display_name);
        $focus_mode = get_user_meta(get_current_user_id(), 'afc_focus_mode', true) === '1';
        $stats = Engine::get_synergy_stats();
        ?>

        <div class="wrap afcglide-dashboard-wrap afc-admin">
            <div class="afc-control-center">
            
            <!-- 🏢 BRAND IDENTITY -->
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

            <?php if ( isset($_GET['agent_added']) && $guide = get_transient('afc_last_created_agent') ) : ?>
                <!-- 🔐 AGENT ACCESS GUIDE -->
                <div style="background:#f0fdf4; border:2px dashed #22c55e; padding:35px; border-radius:24px; margin-bottom:35px; position:relative; overflow:hidden;">
                    <div style="background:#22c55e; color:white; font-size:10px; font-weight:900; padding:4px 12px; border-radius:50px; display:inline-block; margin-bottom:15px; letter-spacing:1px;">ACCESS GUIDE GENERATED</div>
                    <h3 style="color:#166534; margin:0 0 10px 0; font-size:20px; font-weight:900;">NEW OPERATOR RECRUITED</h3>
                    <p style="font-size:14px; color:#166534; font-weight:600; margin-bottom:20px;">Copy and transmit these credentials to the new agent immediately. This guide will expire shortly.</p>
                    <textarea readonly style="width:100%; height:110px; padding:20px; border-radius:15px; background:white; border:1px solid #bbf7d0; font-family:monospace; font-size:13px; color:#1e293b; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);">Welcome to the AFCGlide Global Network!
Your secure portal is active.

Portal URL: <?php echo esc_url($guide['url']); ?>
Username: <?php echo esc_html($guide['user']); ?>
Password: <?php echo esc_html($guide['pass']); ?></textarea>
                </div>
            <?php endif; ?>

            <!-- 💼 GLOBAL ASSET MANAGEMENT HUB (MOVED TO TOP) -->
            <div class="afc-section afc-inventory-container">
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

            <?php if ( $is_broker ) : ?>
            <!-- ⚡ BROKER QUICK ACTIONS -->
            <div class="afc-quick-actions">
                <a href="<?php echo admin_url('post-new.php?post_type=' . C::POST_TYPE); ?>" class="afc-action-card add-asset">
                    <span>➕</span><h3>Add Asset</h3>
                </a>
                <a href="<?php echo admin_url('admin.php?page=afcglide-inventory'); ?>" class="afc-action-card inventory">
                    <span>💼</span><h3>Inventory</h3>
                </a>
                <a href="<?php echo admin_url('profile.php'); ?>" class="afc-action-card profile">
                    <span>👤</span><h3>Profile</h3>
                </a>
                <a href="#backbone-settings" class="afc-action-card backbone">
                    <span>⚙️</span><h3>Backbone</h3>
                </a>
            </div>
            <?php endif; ?>
            
            <?php if ( $is_broker ) : ?>
            <!-- 🏰 BROKER COMMAND MATRIX (PASTEL CARDS) - MOVED HIGHER -->
            <div class="afc-broker-matrix">
                
                <!-- ⚙️ SYSTEM BACKBONE (Green Pastel) -->
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
                        <div class="afc-backbone-item">
                            <label>Office Name</label>
                            <input type="text" id="afc-agent-name" value="<?php echo esc_attr(get_option('afc_agent_name', 'AFCGlide Realty')); ?>">
                        </div>
                        <div class="afc-backbone-item">
                            <label>Contact Phone</label>
                            <input type="text" id="afc-agent-phone" value="<?php echo esc_attr(get_option('afc_agent_phone_display', '+1.555.0123')); ?>">
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

                <!-- 🚀 SYSTEM INITIALIZER (Blue Pastel) -->
                <div class="afc-matrix-card afc-header-blue">
                    <h2><span class="dashicons dashicons-admin-links"></span> SYSTEM INITIALIZER</h2>
                    <div class="afc-backbone-grid">
                        <div class="afc-initializer-alert">
                            <strong>AWAITING DEPLOYMENT:</strong> Core platform pages (Agent Hub, Portfolio, etc.) can be auto-generated with a single sync.
                        </div>
                        <div class="afc-matrix-footer">
                            <form method="post" class="afc-full-width-flex">
                                <?php wp_nonce_field('afc_initializer', 'afc_initializer_nonce'); ?>
                                <button type="submit" name="afc_run_initializer" class="afc-vogue-btn afc-initialize-btn">INITIALIZE CORE PAGES</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 👤 RAPID ONBOARDING (Yellow Pastel) -->
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
                        <div class="afc-backbone-item full-width">
                            <label>Agent Password</label>
                            <input type="text" id="afc-new-pass" value="<?php echo wp_generate_password(12, false); ?>">
                        </div>
                        <div class="afc-matrix-footer">
                            <button id="afc-recruit-btn" class="afc-vogue-btn afc-recruit-btn">RECRUIT AGENT</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- 🗲 HERO SECTION - FAST SUBMIT ASSET -->
            <div class="afc-hero">
                <div>
                    <h1>PROPERTY PRODUCTION: HQ</h1>
                    <p>Synergy active. Initialize your next global asset now.</p>
                </div>
                <a href="<?php echo admin_url('post-new.php?post_type='.C::POST_TYPE); ?>" class="afc-hero-btn">
                    <span>🚀 FAST SUBMIT ASSET</span>
                </a>
            </div>

            <?php if ( ! $is_broker ) : 
                // DATA CALCULATION FOR AGENT INSIGHTS
                $drafts = get_posts(['post_type' => C::POST_TYPE, 'post_status' => 'draft', 'author' => $current_user->ID, 'posts_per_page' => -1]);
                $recent = get_posts(['post_type' => C::POST_TYPE, 'post_status' => 'publish', 'author' => $current_user->ID, 'posts_per_page' => 5]);
                $has_photo = get_user_meta($current_user->ID, '_agent_photo_id', true);
                $total_views = 0;
                foreach ($recent as $p) { $total_views += intval(get_post_meta($p->ID, '_listing_views_count', true)); }
            ?>
            <!-- 💡 AGENT INSIGHT CARDS -->
            <div class="afc-insight-grid">
                <div class="afc-insight-card quick-start">
                    <div class="icon">🎯</div>
                    <h3>Quick Start</h3>
                    <ul>
                        <?php if (!$has_photo) : ?>
                            <li><span style="color:#ef4444;">⚠️</span> <a href="<?php echo admin_url('profile.php'); ?>" style="color:inherit; text-decoration:none;">Upload Profile Photo</a></li>
                        <?php endif; ?>
                        <li><span>✓</span> <a href="<?php echo admin_url('admin.php?page=afcglide-inventory'); ?>" style="color:inherit; text-decoration:none;">Review Portfolio</a></li>
                        <li><span>🛸</span> <a href="<?php echo admin_url('post-new.php?post_type='.C::POST_TYPE); ?>" style="color:inherit; text-decoration:none;">Deploy New Asset</a></li>
                    </ul>
                </div>
                <div class="afc-insight-card performance">
                    <div class="icon">📈</div>
                    <h3>Performance</h3>
                    <ul>
                        <li><strong><?php echo count($recent); ?></strong> Active Listings</li>
                        <li><strong><?php echo number_format($total_views); ?></strong> Total Views</li>
                        <li><strong><?php echo count($drafts); ?></strong> Pending Drafts</li>
                    </ul>
                </div>
                <div class="afc-insight-card attention">
                    <div class="icon">⚡</div>
                    <h3>Attention</h3>
                    <?php if (!empty($drafts)) : ?>
                        <p style="font-size:13px; margin:0 0 10px 0;">You have <strong><?php echo count($drafts); ?></strong> draft assets requiring attention before deployment.</p>
                        <a href="<?php echo admin_url('admin.php?page=afcglide-inventory&status=draft'); ?>" style="display:inline-block; background:#92400e; color:white; padding:6px 15px; border-radius:8px; text-decoration:none; font-size:12px; font-weight:800;">FINALIZE DRAFTS</a>
                    <?php else : ?>
                        <p style="font-size:13px; margin:0;">All systems nominal. No urgent actions detected for your currently active portfolio.</p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 📊 UNIFIED SCOREBOARD -->
            <?php echo \AFCGlide\Admin\AFCGlide_Scoreboard::render_scoreboard( $is_broker ? null : $current_user->ID ); ?>

            <?php if ( $is_broker ) : ?>

            <!-- 📊 PERFORMANCE GRID -->
            <div class="afc-performance-grid">
                
                <!-- 💓 SYSTEM HEARTBEAT (Red Pastel) -->
                <div class="afc-section afc-performance-section afc-header-red">
                    <div class="afc-section-header">
                        <div class="afc-pulse"></div>
                        <h2>SYSTEM HEARTBEAT</h2>
                    </div>
                    <?php self::render_activity_stream(); ?>
                </div>

                <!-- 👥 TEAM PERFORMANCE ROSTER (Green Pastel) -->
                <div class="afc-section afc-performance-section afc-header-green">
                    <div class="afc-section-header">
                        <h2>TEAM PERFORMANCE ROSTER</h2>
                    </div>
                    <?php self::render_team_roster(); ?>
                </div>
            </div>
            <?php endif; ?>
            </div><!-- .afc-control-center -->

                <!-- 🎚️ SYSTEM PREFERENCES (BOTTOM) -->
                <div class="afc-system-preferences-footer">
                    <div class="afc-focus-wrap">
                        <span>EYE_FOCUS MODE</span>
                        <label class="afc-switch">
                            <input type="checkbox" id="afc-focus-toggle" <?php checked($focus_mode); ?>>
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                </div>
            </div><!-- .wrap -->
<?php
    }

    public static function render_manual_page() {
        ?>
        <div class="wrap afc-system-manual afc-admin">
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
                .afc-manual-print-btn { float: right; background: white; border: 2px solid #e2e8f0; color: #64748b; padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
                .afc-manual-print-btn:hover { background: #f8fafc; border-color: #cbd5e1; color: #1e293b; }
            </style>

            <button onclick="window.print()" class="afc-manual-print-btn">🖨️ Print to PDF</button>
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
                    <ul style="margin: 0; padding-left: 200px;">
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

    public static function render_activity_stream() {
        $recent = get_posts([
            'post_type' => C::POST_TYPE,
            'post_status' => ['publish', 'pending', 'sold', 'draft'],
            'posts_per_page' => 8,
            'orderby' => 'modified'
        ]);
        
        if (empty($recent)) {
            echo '<p class="afc-activity-empty">No recent activity.</p>';
            return;
        }
        
        foreach ($recent as $post) {
            $author = get_the_author_meta('display_name', $post->post_author);
            $time = human_time_diff(get_the_modified_time('U', $post->ID), current_time('timestamp')) . ' ago';
            $status = strtoupper($post->post_status);
            
            echo '<div class="afc-activity-item">';
            echo '<div>';
            echo '<span class="afc-activity-status">' . esc_html($status) . '</span>';
            echo '<strong>' . esc_html($post->post_title) . '</strong> ';
            echo '<small>by ' . esc_html($author) . '</small>';
            echo '</div>';
            echo '<div class="afc-activity-time">' . esc_html($time) . '</div>';
            echo '</div>';
        }
    }

    public static function render_team_roster() {
        $agents = get_users(['role__in' => ['listing_agent', 'managing_broker', 'administrator']]);
        
        echo '<table class="afc-team-table">';
        echo '<thead><tr>';
        echo '<th>AGENT</th>';
        echo '<th>UNITS</th>';
        echo '<th>VOLUME</th>';
        echo '</tr></thead>';
        echo '<tbody>';
        
        foreach ($agents as $user) {
            $count = count(get_posts([
                'post_type' => C::POST_TYPE,
                'post_status' => 'publish',
                'author' => $user->ID,
                'fields' => 'ids',
                'posts_per_page' => -1
            ]));
            
            $vol = self::calculate_portfolio_volume($user->ID);
            
            echo '<tr>';
            echo '<td>' . esc_html($user->display_name) . '</td>';
            echo '<td>' . esc_html($count) . '</td>';
            echo '<td>$' . number_format($vol) . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody></table>';
    }

    public static function calculate_portfolio_volume($author_id = null) {
        global $wpdb;
        $meta_key = C::META_PRICE;
        $sql = "SELECT SUM(CAST(meta_value AS UNSIGNED)) FROM $wpdb->postmeta pm JOIN $wpdb->posts p ON pm.post_id = p.ID WHERE pm.meta_key = '$meta_key' AND p.post_status = 'publish'";
        if ($author_id) {
            $sql .= $wpdb->prepare(" AND p.post_author = %d", $author_id);
        }
        return (float) $wpdb->get_var($sql) ?: 0;
    }

    public static function check_homepage_configuration() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        if ( get_option( 'show_on_front' ) !== 'page' ) {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>AFCGlide Alert:</strong> For best results, set a static Front Page in ';
            echo '<a href="' . admin_url('options-reading.php') . '">Settings > Reading</a>.</p>';
            echo '</div>';
        }
    }
}