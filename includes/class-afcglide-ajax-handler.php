<?php
namespace AFCGlide\Admin; 

use AFCGlide\Core\Constants as C;
use AFCGlide\Core\AFCGlide_Synergy_Engine as Engine;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Ajax_Handler {
    
    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_welcome_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_protocol_execution' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_agent_creation' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_core_setup' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_backbone_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_dashboard_scripts' ] );
        add_action( 'admin_notices', [ __CLASS__, 'check_homepage_configuration' ] );
        
        // Form Handlers
        add_action( 'wp_ajax_afc_handle_submission', [ __CLASS__, 'afc_handle_submission' ] );
        add_action( 'wp_ajax_nopriv_afc_handle_submission', [ __CLASS__, 'afc_handle_submission' ] );
    }

    /**
     * Enqueue Dashboard Scripts & Styles
     */
    public static function enqueue_dashboard_scripts( $hook ) {
        // Only load on our dashboard page
        if ( $hook !== 'toplevel_page_afcglide-dashboard' && $hook !== 'afcglide_page_afcglide-manual' ) {
            return;
        }

        // Inline JavaScript for dashboard interactions
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
            
            // ============================================
            // SYSTEM BACKBONE SYNC
            // ============================================
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

            // ============================================
            // AGENT RECRUITMENT
            // ============================================
            $('#afc-recruit-btn').on('click', function(e) {
                e.preventDefault();
                const btn = $(this);
                const originalText = btn.text();
                
                const username = $('#afc-new-user').val().trim();
                const email = $('#afc-new-email').val().trim();
                const password = $('#afc-new-pass').val();
                
                // Validation
                if (!username || !email || !password) {
                    alert('All fields are required for agent recruitment.');
                    return;
                }
                
                if (username.length < 4) {
                    alert('Username must be at least 4 characters.');
                    return;
                }
                
                if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                    alert('Please enter a valid email address.');
                    return;
                }
                
                if (password.length < 8) {
                    alert('Password must be at least 8 characters.');
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
                            alert('Agent recruited successfully!\\n\\nUsername: ' + username + '\\nPassword: ' + password + '\\n\\nPlease save these credentials.');
                            
                            // Clear form
                            $('#afc-new-user').val('');
                            $('#afc-new-email').val('');
                            $('#afc-new-pass').val('');
                            
                            setTimeout(function() {
                                btn.text(originalText).css('background', '').prop('disabled', false);
                                location.reload(); // Refresh to show new agent
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

            // ============================================
            // FOCUS MODE TOGGLE
            // ============================================
            $('#afc-focus-toggle').on('change', function() {
                const status = $(this).is(':checked') ? '1' : '0';
                
                $.ajax({
                    url: '{$ajax_url}',
                    type: 'POST',
                    data: {
                        action: 'afcg_toggle_focus',
                        afc_listing_nonce: '{$nonce}',
                        status: status
                    },
                    success: function(response) {
                        if (response.success) {
                            console.log('Focus mode updated');
                        }
                    }
                });
            });

            // ============================================
            // REAL-TIME TOGGLE SYNC (Lockdown & Gatekeeper)
            // ============================================
            // These are saved when user clicks "EXECUTE SYSTEM SYNC"
            // No individual AJAX calls needed - they're part of backbone sync
            
        });
        JS;
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
                    wp_insert_post([
                        'post_title'   => $page['title'],
                        'post_content' => $page['content'],
                        'post_status'  => 'publish',
                        'post_type'    => 'page',
                        'post_name'    => $page['slug']
                    ]);
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

                <!-- 🚀 SYSTEM INITIALIZER -->
                <div class="afc-matrix-card afc-header-orange">
                    <h2><span class="dashicons dashicons-admin-links"></span> SYSTEM INITIALIZER</h2>
                    <div class="afc-backbone-grid">
                        <?php 
                        $pages_to_create = [
                            'agent-hub' => 'Agent Hub',
                            'submit-listing' => 'Add New Listing',
                            'portfolio' => 'Listings Portfolio',
                            'agent-login' => 'Agent Login'
                        ];
                        $existing_count = 0;
                        foreach ($pages_to_create as $slug => $title) {
                            if (get_page_by_path($slug)) $existing_count++;
                        }
                        $all_exist = ($existing_count === count($pages_to_create));
                        ?>
                        
                        <?php if (isset($_GET['core_setup']) && $_GET['core_setup'] === 'executed') : ?>
                        <div class="afc-initializer-success" style="grid-column: span 2; background: #dcfce7; border: 1px solid #86efac; padding: 15px; border-radius: 10px; color: #166534; font-weight: 700;">
                            ✅ Core pages have been deployed successfully!
                        </div>
                        <?php elseif ($all_exist) : ?>
                        <div class="afc-initializer-alert" style="grid-column: span 2; background: #eff6ff; border: 1px solid #93c5fd; padding: 15px; border-radius: 10px; color: #1e40af;">
                            <strong>✓ FULLY DEPLOYED:</strong> All <?php echo count($pages_to_create); ?> core pages are already live.
                        </div>
                        <?php else : ?>
                        <div class="afc-initializer-alert" style="grid-column: span 2;">
                            <strong>AWAITING DEPLOYMENT:</strong> <?php echo (count($pages_to_create) - $existing_count); ?> core pages ready to be auto-generated.
                        </div>
                        <?php endif; ?>
                        
                        <div class="afc-matrix-footer">
                            <form method="post" action="" class="afc-full-width-flex">
                                <?php wp_nonce_field('afc_initializer', 'afc_initializer_nonce'); ?>
                                <button type="submit" name="afc_run_initializer" value="1" class="afc-vogue-btn afc-initialize-btn" <?php echo $all_exist ? 'disabled style="opacity:0.5;cursor:not-allowed;"' : ''; ?>>
                                    <?php echo $all_exist ? '✓ PAGES DEPLOYED' : 'INITIALIZE CORE PAGES'; ?>
                                </button>
                            </form>
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
                        <div class="afc-backbone-item full-width">
                            <label>Agent Password</label>
                            <input type="text" id="afc-new-pass" value="<?php echo wp_generate_password(12, false); ?>">
                        </div>
                        <div class="afc-matrix-footer">
                            <button id="afc-recruit-btn" class="afc-vogue-btn afc-recruit-btn">RECRUIT AGENT</button>
                        </div>
                    </div>
                </div>

                <!-- 🎛️ COMMAND CENTER -->
                <div class="afc-matrix-card afc-header-blue">
                    <h2><span class="dashicons dashicons-dashboard"></span> COMMAND CENTER</h2>
                    <div class="afc-backbone-grid">
                        <?php 
                        $pending_count = wp_count_posts(C::POST_TYPE)->pending ?? 0;
                        $draft_count = wp_count_posts(C::POST_TYPE)->draft ?? 0;
                        ?>
                        <!-- Pending Approvals -->
                        <div class="afc-command-stat">
                            <div class="afc-stat-icon" style="background: #fef3c7; color: #d97706;">
                                <span class="dashicons dashicons-clock"></span>
                            </div>
                            <div class="afc-stat-info">
                                <span class="afc-stat-count"><?php echo esc_html($pending_count); ?></span>
                                <span class="afc-stat-label">Pending Review</span>
                            </div>
                            <?php if ($pending_count > 0) : ?>
                            <a href="<?php echo admin_url('edit.php?post_status=pending&post_type=' . C::POST_TYPE); ?>" class="afc-stat-action">Review →</a>
                            <?php endif; ?>
                        </div>
                        <!-- Drafts -->
                        <div class="afc-command-stat">
                            <div class="afc-stat-icon" style="background: #e0e7ff; color: #4f46e5;">
                                <span class="dashicons dashicons-edit"></span>
                            </div>
                            <div class="afc-stat-info">
                                <span class="afc-stat-count"><?php echo esc_html($draft_count); ?></span>
                                <span class="afc-stat-label">Drafts</span>
                            </div>
                            <?php if ($draft_count > 0) : ?>
                            <a href="<?php echo admin_url('edit.php?post_status=draft&post_type=' . C::POST_TYPE); ?>" class="afc-stat-action">View →</a>
                            <?php endif; ?>
                        </div>
                        <!-- Quick Actions -->
                        <div class="afc-quick-actions">
                            <a href="<?php echo admin_url('edit.php?post_type=' . C::POST_TYPE); ?>" class="afc-quick-link">
                                <span class="dashicons dashicons-list-view"></span> All Listings
                            </a>
                            <a href="<?php echo admin_url('users.php?role=listing_agent'); ?>" class="afc-quick-link">
                                <span class="dashicons dashicons-groups"></span> All Agents
                            </a>
                            <a href="<?php echo home_url('/portfolio/'); ?>" target="_blank" class="afc-quick-link">
                                <span class="dashicons dashicons-external"></span> View Portfolio
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="afc-performance-grid">
                <!-- 💓 SYSTEM HEARTBEAT -->
                <div class="afc-section afc-header-red afc-performance-section">
                    <div class="afc-section-header">
                        <div class="afc-pulse"></div>
                        <h2>SYSTEM HEARTBEAT</h2>
                    </div>
                    <?php self::render_activity_stream(); ?>
                </div>

                <!-- 👥 TEAM PERFORMANCE -->
                <div class="afc-section afc-header-orange afc-performance-section">
                    <div class="afc-section-header">
                        <h2>TEAM PERFORMANCE ROSTER</h2>
                    </div>
                    <?php self::render_team_roster(); ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 💼 UNIVERSAL INVENTORY -->
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
        </div>
<?php
    }

    public static function render_manual_page() {
        ?>
        <div class="wrap afc-system-manual">
            <button onclick="window.print()" class="afc-manual-print-btn">🖨️ Print to PDF</button>
            <div class="afc-clearfix"></div>

            <div class="afc-manual-container">
                <div class="afc-cover">
                    <h1 class="afc-manual-h1">THE REAL ESTATE MACHINE</h1>
                    <p style="font-size: 20px; color: #64748b; font-weight: 600;">System Operator Manual: S-Grade Edition</p>
                </div>

                <h2 class="afc-manual-h2">1. The Core Infrastructure</h2>
                <p>Congratulations. AFCGlide isn't just a plugin; it's a <strong>Real Estate Machine</strong> designed for high-volume asset broadcasting.</p>

                <h2 class="afc-manual-h2">2. Roles & Permissions</h2>
                <div class="afc-step-box">
                    <span class="afc-role-badge bg-broker">MANAGING BROKER</span>
                    <p>Full control over the global inventory, agent onboarding, and security protocols.</p>
                </div>
                <div class="afc-step-box">
                    <span class="afc-role-badge bg-agent">LISTING AGENT</span>
                    <p>Focused strictly on production. Manage your portfolio and track your performance stats.</p>
                </div>

                <h2 class="afc-manual-h2">3. The Submission Matrix</h2>
                <p>Our world-class submission form ensures no listing is ever subpar:</p>
                <ul>
                    <li><strong>Bilingual Sync:</strong> Every asset requires English and Spanish data for maximum reach.</li>
                    <li><strong>Quality Gatekeeper:</strong> System will reject images under 1200px to maintain luxury standards.</li>
                    <li><strong>GPS Precision:</strong> Coordinates are used for high-fidelity mapping.</li>
                </ul>

                <div class="afc-tips-box">
                    <strong>💡 PRO TIP:</strong> Use the "Rapid Onboarding" tool to create an agent in 5 seconds and generate a custom Access Guide for them.
                </div>
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
        $meta_key = \AFCGlide\Core\Constants::META_PRICE;
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

    /**
     * PUBLIC SUBMISSION HANDLER (The Engine)
     */
    public static function afc_handle_submission() {
        // 1. Security Check
        check_ajax_referer( C::NONCE_AJAX, 'afc_listing_nonce' );

        // 2. Data Sanitization
        $title = sanitize_text_field( $_POST['listing_title'] );
        $price = sanitize_text_field( str_replace( [',','$'], '', $_POST['listing_price'] ) );
        $type  = sanitize_text_field( $_POST['listing_type'] );
        $status = sanitize_text_field( $_POST['listing_status'] );
        
        // 3. Create/Update Post
        $post_data = [
            'post_title'   => $title,
            'post_content' => wp_kses_post( $_POST['listing_narrative'] ), // Safe HTML
            'post_status'  => $status,
            'post_type'    => C::POST_TYPE,
            'post_author'  => get_current_user_id()
        ];

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( ['message' => 'System Error: Could not create asset.'] );
        }

        // 4. Save Core Metadata (Using New _afc_ Keys)
        update_post_meta( $post_id, C::META_PRICE, $price );
        update_post_meta( $post_id, C::META_BEDS, sanitize_text_field($_POST['listing_beds']) );
        update_post_meta( $post_id, C::META_BATHS, sanitize_text_field($_POST['listing_baths']) );
        update_post_meta( $post_id, C::META_SQFT, sanitize_text_field($_POST['listing_sqft']) );
        update_post_meta( $post_id, C::META_TYPE, $type );
        update_post_meta( $post_id, C::META_ADDRESS, sanitize_textarea_field($_POST['listing_address']) );
        update_post_meta( $post_id, C::META_LOCATION, sanitize_text_field($_POST['listing_location']) );
        update_post_meta( $post_id, C::META_STATUS, $status );

        // 5. Handle Agent Info
        update_post_meta( $post_id, C::META_AGENT_NAME, sanitize_text_field($_POST['agent_name']) );
        update_post_meta( $post_id, C::META_AGENT_PHONE, sanitize_text_field($_POST['agent_phone']) );

        // 6. Handle File Uploads (Hero, Agent Photo, Broker Logo)
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // A. Hero Image
        if ( ! empty( $_FILES['hero_image']['name'] ) ) {
            $hero_id = media_handle_upload( 'hero_image', $post_id );
            if ( ! is_wp_error( $hero_id ) ) {
                update_post_meta( $post_id, C::META_HERO_ID, $hero_id );
                set_post_thumbnail( $post_id, $hero_id );
            }
        }

        // B. Agent Photo
        if ( ! empty( $_FILES['agent_photo_file']['name'] ) ) {
            $agent_photo_id = media_handle_upload( 'agent_photo_file', $post_id );
            if ( ! is_wp_error( $agent_photo_id ) ) {
                update_post_meta( $post_id, C::META_AGENT_PHOTO, $agent_photo_id );
            }
        }

        // C. BROKERAGE LOGO (New!)
        if ( ! empty( $_FILES['broker_logo_file']['name'] ) ) {
            $logo_id = media_handle_upload( 'broker_logo_file', $post_id );
            if ( ! is_wp_error( $logo_id ) ) {
                update_post_meta( $post_id, C::META_BROKER_LOGO, $logo_id );
            }
        }

        // D. Gallery (Multiple)
        // Handled via separate logic usually, or loop through $_FILES['gallery_files']
        // For V1, we'll rely on the dedicated gallery uploader or add simple loop here if needed.

        // 7. Success Response
        wp_send_json_success([
            'message' => 'Asset Initialized Successfully.',
            'url'     => get_permalink( $post_id )
        ]);
    }
}