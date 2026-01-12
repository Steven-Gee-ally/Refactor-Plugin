<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Unified Admin System
 * Version 2.0.0 - Merged Menu + Settings + Improved Logic
 * 
 * Combines functionality from:
 * - class-afcglide-admin-menu.php
 * - class-afcglide-settings.php
 */
class AFCGlide_Admin_Menu {

    public static function init() {
        // Menu Management
        add_action( 'admin_menu', [ __CLASS__, 'register_menus' ], 5 );
        add_action( 'admin_menu', [ __CLASS__, 'cleanup_sidebar_menus' ], 999 );
        add_filter( 'admin_body_class', [ __CLASS__, 'add_editor_body_class' ] );
        
        // Settings & Configuration
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_init', [ __CLASS__, 'redirect_dashboard' ] );
        add_action( 'admin_init', [ __CLASS__, 'apply_identity_lockdown' ] );
        add_action( 'admin_init', [ __CLASS__, 'apply_worker_mode_permissions' ] );
        
        // Dashboard Widgets
        add_action( 'wp_dashboard_setup', [ __CLASS__, 'add_broker_dashboard_widget' ] );
        
        // Menu Ordering
        add_filter( 'custom_menu_order', '__return_true' );
        add_filter( 'menu_order', [ __CLASS__, 'reorder_menu' ] );
    }

    /* ==========================================
       MENU REGISTRATION
       ========================================== */

    /**
     * Register all AFCGlide admin menus and submenus
     */
    public static function register_menus() {
        $user = wp_get_current_user();
        $is_admin = current_user_can( 'manage_options' );
        
        // Remove default WordPress dashboard
        remove_menu_page( 'index.php' );
        
        // Main AFCGlide Menu
        add_menu_page(
            __( 'AFCGlide Home', 'afcglide' ),
            __( '🏠 AFCGlide', 'afcglide' ),
            'read',
            'afcglide-home',
            [ __CLASS__, 'render_command_center' ],
            'dashicons-admin-home',
            1
        );
        
        // Submenu: Command Center (replaces main page for clarity)
        add_submenu_page(
            'afcglide-home',
            __( 'Command Center', 'afcglide' ),
            __( '🛡️ Command Center', 'afcglide' ),
            $is_admin ? 'manage_options' : 'do_not_allow', // Only admins see this
            'afcglide-home',
            [ __CLASS__, 'render_command_center' ]
        );
        
        // Submenu: Settings (Admin Only)
        if ( $is_admin ) {
            add_submenu_page(
                'afcglide-home',
                __( 'Settings', 'afcglide' ),
                __( '⚙️ Settings', 'afcglide' ),
                'manage_options',
                'afcglide-settings',
                [ __CLASS__, 'render_settings_page' ]
            );
        }
        
        // Submenu: Add Agent (Admin Only)
        if ( $is_admin ) {
            add_submenu_page(
                'afcglide-home',
                __( 'Add Agent', 'afcglide' ),
                __( '➕ Add Agent', 'afcglide' ),
                'create_users',
                'afcglide-add-agent',
                [ __CLASS__, 'render_add_agent_page' ]
            );
        }
        
        // Submenu: Manage Agents (Admin Only)
        if ( $is_admin ) {
            add_submenu_page(
                'afcglide-home',
                __( 'Manage Agents', 'afcglide' ),
                __( '👥 Manage Agents', 'afcglide' ),
                'list_users',
                'afcglide-manage-agents',
                [ __CLASS__, 'render_manage_agents_page' ]
            );
        }
        
        // Submenu: My Profile (For All Users)
        add_submenu_page(
            'afcglide-home',
            __( 'My Profile', 'afcglide' ),
            __( '👤 My Profile', 'afcglide' ),
            'read',
            'profile.php'
        );
    }

    /**
     * Clean up sidebar - Role-based removal
     */
    public static function cleanup_sidebar_menus() {
        $user = wp_get_current_user();
        $is_admin = current_user_can( 'manage_options' );
        
        // Items to ALWAYS hide (for everyone, even admins)
        $always_hide = [
            'edit.php?post_type=elementor_library', // Elementor Library
            'elementor',                             // Elementor main menu
            'edit.php',                              // Default WordPress Posts
            'edit-comments.php',                     // Comments
            'profile.php',                           // Profile (use Users > Your Profile instead)
            'themes.php',                            // Appearance
            'tools.php',                             // Tools
            'options-general.php',                   // Settings
        ];
        
        foreach ( $always_hide as $menu ) {
            remove_menu_page( $menu );
        }
        
        // Hide taxonomy submenus from EVERYONE (not needed in sidebar)
        // These are still accessible when editing listings, just not as separate pages
        remove_submenu_page( 'edit.php?post_type=afcglide_listing', 'edit-tags.php?taxonomy=location&post_type=afcglide_listing' );
        remove_submenu_page( 'edit.php?post_type=afcglide_listing', 'edit-tags.php?taxonomy=property_type&post_type=afcglide_listing' );
        remove_submenu_page( 'edit.php?post_type=afcglide_listing', 'edit-tags.php?taxonomy=listing_status&post_type=afcglide_listing' );
        
        // Additional items to hide for NON-ADMINS only
        if ( ! $is_admin ) {
            $hide_for_agents = [
                'users.php',            // Users (agents can't manage other agents)
            ];
            
            foreach ( $hide_for_agents as $menu ) {
                remove_menu_page( $menu );
            }
        }
        
        // Keep visible for everyone:
        // - AFCGlide Home (custom menu above)
        // - Listings > All Listings (edit.php?post_type=afcglide_listing)
        // - Listings > Add New (post-new.php?post_type=afcglide_listing)
        // - Media (upload.php)
        // - Pages (edit.php?post_type=page)
        // - Users (admin only)
    }

    /**
     * Reorder admin menu items
     */
    public static function reorder_menu( $menu_order ) {
        if ( ! $menu_order ) return true;
        
        return [
            'afcglide-home',                        // AFCGlide Home
            'edit.php?post_type=afcglide_listing',  // Listings
            'separator1',                           
            'upload.php',                           // Media
            'edit.php?post_type=page',              // Pages
            'users.php',                            // Users (admin only)
            'plugins.php',                          // Plugins (admin only)
            'themes.php',                           // Appearance (admin only)
            'tools.php',                            // Tools (admin only)
            'options-general.php',                  // Settings (admin only)
        ];
    }

    /* ==========================================
       SETTINGS REGISTRATION
       ========================================== */

    public static function register_settings() {
        register_setting( 'afcglide_settings_group', 'afc_identity_lockdown' );
        register_setting( 'afcglide_settings_group', 'afc_listing_approval' );
        register_setting( 'afcglide_settings_group', 'afc_worker_mode' );
        register_setting( 'afcglide_settings_group', 'afc_default_lat' );
        register_setting( 'afcglide_settings_group', 'afc_default_long' );
    }

    /* ==========================================
       PAGE RENDERS
       ========================================== */

    /**
     * Render Command Center Dashboard
     */
    public static function render_command_center() {
        $user = wp_get_current_user();
        $is_commander = current_user_can( 'manage_options' );
        ?>
        <div class="wrap afcglide-wrapper">
            <!-- PRIMARY GLOBAL AGENT HEADER (Darker Green Pastel) -->
            <div class="afc-agent-header" style="background: linear-gradient(135deg, #dcfce7 0%, #bbf7d0 100%); padding:20px; border-radius:12px; box-shadow:0 4px 6px -1px rgba(0,0,0,0.05); display:flex; justify-content:space-between; align-items:center; margin-bottom:30px; border: 1px solid #86efac;">
                <div>
                    <span style="display:block; font-size:11px; font-weight:800; color:#166534; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Primary Global Agent</span>
                    <h2 style="margin:0; font-size:24px; color:#14532d; display:flex; align-items:center;">
                        <?php echo esc_html( $user->display_name ); ?> 
                        <span style="font-size:12px; background:#fff; color:#166534; padding:2px 8px; border-radius:12px; margin-left:12px; font-weight:600; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">● Online</span>
                    </h2>
                </div>
                <a href="javascript:location.reload();" class="button" style="background:#fff; color:#15803d; border:1px solid #bbf7d0; padding:8px 16px; border-radius:8px; font-weight:600; display:flex; align-items:center; height:auto; line-height:1.5; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
                    <span style="font-size:16px; margin-right:8px;">🔄</span> Sync System
                </a>
            </div>

            <?php if ( $is_commander ) : ?>
                <!-- ADMIN VIEW: Command & Control -->
                
                <?php
                // 1. CALCULATE PORTFOLIO VOLUME && STATS
                $count_posts = wp_count_posts( 'afcglide_listing' );
                $published_count = $count_posts->publish;
                $pending_count   = $count_posts->pending;

                // Efficiently Sum Price Meta for Published Listings
                global $wpdb;
                $volume = $wpdb->get_var( "
                    SELECT SUM(meta_value) 
                    FROM $wpdb->postmeta pm
                    JOIN $wpdb->posts p ON pm.post_id = p.ID
                    WHERE pm.meta_key = 'afc_price' 
                    AND p.post_status = 'publish' 
                    AND p.post_type = 'afcglide_listing'
                " );
                
                // Format Volume (e.g. $2.5M)
                $volume_formatted = '$0';
                if ( $volume > 1000000 ) {
                    $volume_formatted = '$' . number_format( $volume / 1000000, 1 ) . 'M';
                } elseif ( $volume > 1000 ) {
                    $volume_formatted = '$' . number_format( $volume / 1000, 0 ) . 'K';
                } else {
                    $volume_formatted = '$' . number_format( (float)$volume );
                }
                ?>

                <!-- BROKER INTELLIGENCE ROW -->
                <div class="afcglide-grid afcglide-grid-cols-3" style="margin-bottom: 30px;">
                    <!-- Card 1: Portfolio Volume -->
                    <div class="afc-listing-card" style="padding: 25px; border-left: 5px solid #3b82f6;">
                        <span style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Portfolio Volume</span>
                        <span style="font-size: 32px; font-weight: 800; color: #0f172a;"><?php echo $volume_formatted; ?></span>
                    </div>

                    <!-- Card 2: Active Listings -->
                    <div class="afc-listing-card" style="padding: 25px; border-left: 5px solid #10b981;">
                        <span style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Active Listings</span>
                        <span style="font-size: 32px; font-weight: 800; color: #0f172a;"><?php echo $published_count; ?></span>
                    </div>

                    <!-- Card 3: Pending Approvals -->
                    <div class="afc-listing-card" style="padding: 25px; border-left: 5px solid <?php echo $pending_count > 0 ? '#ef4444' : '#e2e8f0'; ?>;">
                        <span style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Pending Approval</span>
                        <span style="font-size: 32px; font-weight: 800; color: <?php echo $pending_count > 0 ? '#ef4444' : '#94a3b8'; ?>;"><?php echo $pending_count; ?></span>
                    </div>
                </div>

                <!-- OPERATIONS GRID (2x2 Layout) -->
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                    
                    <!-- 1. ADD LISTING BLOCK (Pastel Blue) -->
                    <a href="<?php echo admin_url('post-new.php?post_type=afcglide_listing'); ?>" class="afc-listing-card" style="text-decoration:none; padding:25px; transition: transform 0.2s; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); border: 1px solid #bfdbfe;">
                        <div style="display:flex; align-items:center; margin-bottom:10px;">
                            <div style="background:#fff; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin-right:15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                <span style="font-size:20px;">➕</span>
                            </div>
                            <h3 style="margin:0; color:#1e3a8a; font-size:18px;">Add Listing</h3>
                        </div>
                        <p style="margin:0; color:#475569; font-size:13px; line-height:1.5;">Launch a new property listing. Includes Smart GPS and Auto-Gallery.</p>
                    </a>

                    <!-- 2. INVENTORY BLOCK (Pastel Green) -->
                    <a href="<?php echo admin_url('edit.php?post_type=afcglide_listing'); ?>" class="afc-listing-card" style="text-decoration:none; padding:25px; transition: transform 0.2s; background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%); border: 1px solid #bbf7d0;">
                        <div style="display:flex; align-items:center; margin-bottom:10px;">
                            <div style="background:#fff; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin-right:15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                <span style="font-size:20px;">🏠</span>
                            </div>
                            <h3 style="margin:0; color:#14532d; font-size:18px;">Inventory</h3>
                        </div>
                        <p style="margin:0; color:#475569; font-size:13px; line-height:1.5;">Manage active portfolio, review pending items, and edit details.</p>
                    </a>

                    <!-- 3. AGENT IDENTITY BLOCK (Pastel Pink) -->
                    <div class="afc-listing-card" style="padding:25px; background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%); border: 1px solid #fbcfe8;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                            <div style="display:flex; align-items:center;">
                                <div style="background:#fff; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin-right:15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                    <span style="font-size:20px;">🛡️</span>
                                </div>
                                <h3 style="margin:0; color:#831843; font-size:18px;">Agent Identity</h3>
                            </div>
                            <!-- Mini Form for Lockdown Toggle -->
                            <form method="post" action="options.php">
                                <?php settings_fields( 'afcglide_settings_group' ); ?>
                                <?php $lockdown = get_option( 'afc_identity_lockdown', 'no' ); ?>
                                <label class="switch" style="position:relative; display:inline-block; width:40px; height:24px;">
                                    <input type="checkbox" name="afc_identity_lockdown" value="yes" <?php checked( $lockdown, 'yes' ); ?> onchange="this.form.submit()" style="opacity:0; width:0; height:0;">
                                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:<?php echo $lockdown === 'yes' ? '#ec4899' : '#e2e8f0'; ?>; border-radius:24px; transition:.4s; box-shadow: inset 0 2px 4px rgba(0,0,0,0.05);"></span>
                                    <span class="slider-knob" style="position:absolute; content:''; height:16px; width:16px; left:4px; bottom:4px; background-color:white; transition:.4s; border-radius:50%; transform: translateX(<?php echo $lockdown === 'yes' ? '16px' : '0'; ?>);"></span>
                                </label>
                                <!-- Hidden fields to preset other options so they don't get wiped -->
                                <input type="hidden" name="afc_listing_approval" value="<?php echo esc_attr( get_option('afc_listing_approval', 'no') ); ?>">
                                <input type="hidden" name="afc_worker_mode" value="<?php echo esc_attr( get_option('afc_worker_mode', 'no') ); ?>">
                                <input type="hidden" name="afc_default_lat" value="<?php echo esc_attr( get_option('afc_default_lat', '') ); ?>">
                                <input type="hidden" name="afc_default_long" value="<?php echo esc_attr( get_option('afc_default_long', '') ); ?>">
                            </form>
                        </div>
                        <p style="margin:0; color:#475569; font-size:13px; line-height:1.5;">Status: <strong style="color:<?php echo $lockdown === 'yes' ? '#db2777' : '#64748b'; ?>"><?php echo $lockdown === 'yes' ? 'LOCKED' : 'UNLOCKED'; ?></strong>. Prevent agents from changing bios/photos.</p>
                    </div>

                    <!-- 4. CONFIGURATION BLOCK (Pastel Purple) -->
                    <a href="<?php echo admin_url('admin.php?page=afcglide-settings'); ?>" class="afc-listing-card" style="text-decoration:none; padding:25px; transition: transform 0.2s; background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border: 1px solid #e9d5ff;">
                        <div style="display:flex; align-items:center; margin-bottom:10px;">
                            <div style="background:#fff; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center; margin-right:15px; box-shadow: 0 2px 4px rgba(0,0,0,0.05);">
                                <span style="font-size:20px;">⚙️</span>
                            </div>
                            <h3 style="margin:0; color:#581c87; font-size:18px;">Configuration</h3>
                        </div>
                        <p style="margin:0; color:#475569; font-size:13px; line-height:1.5;">Manage API Keys, Branding Assets, and System Defaults.</p>
                    </a>

                </div>

                <!-- TEAM MANAGEMENT (Pastel Yellow) -->
                <div class="afc-listing-card" style="margin-bottom: 30px; background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%); border: 1px solid #fde047; padding: 25px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <h3 style="margin:0; color:#a16207; font-size:18px; display:flex; align-items:center;">
                                <span style="background:#fff; width:32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; margin-right:12px; font-size:18px; box-shadow:0 1px 2px rgba(0,0,0,0.05);">👥</span>
                                Team Management
                            </h3>
                            <p style="margin:5px 0 0 44px; color:#854d0e; font-size:13px;">Onboard new staff and manage existing accounts.</p>
                        </div>
                        <div style="display: flex; gap: 15px;">
                            <a href="<?php echo admin_url('admin.php?page=afcglide-add-agent'); ?>" class="button button-primary button-large" style="background:#ca8a04 !important; border-color:#a16207 !important; color:#fff !important;">➕ Add New Agent</a>
                            <a href="<?php echo admin_url('admin.php?page=afcglide-manage-agents'); ?>" class="button" style="background:#fff !important; color:#a16207 !important; border-color:#fde047 !important;">👥 Manage Agents</a>
                        </div>
                    </div>
                </div>
                
            <?php else : ?>
                <!-- AGENT VIEW: Performance Dashboard -->
                <?php
                // Calculate Agent's Personal Stats
                $agent_id = get_current_user_id();
                
                // Count agent's active listings
                $agent_active = new WP_Query([
                    'post_type' => 'afcglide_listing',
                    'post_status' => 'publish',
                    'author' => $agent_id,
                    'posts_per_page' => -1,
                    'fields' => 'ids'
                ]);
                $my_active_count = $agent_active->found_posts;
                
                // Calculate agent's total volume
                $my_volume = 0;
                if ($agent_active->have_posts()) {
                    foreach ($agent_active->posts as $listing_id) {
                        $price = get_post_meta($listing_id, 'afc_price', true);
                        $my_volume += floatval($price);
                    }
                }
                wp_reset_postdata();
                
                // Format volume
                $my_volume_formatted = '$0';
                if ( $my_volume > 1000000 ) {
                    $my_volume_formatted = '$' . number_format( $my_volume / 1000000, 1 ) . 'M';
                } elseif ( $my_volume > 1000 ) {
                    $my_volume_formatted = '$' . number_format( $my_volume / 1000, 0 ) . 'K';
                } else {
                    $my_volume_formatted = '$' . number_format( $my_volume );
                }
                
                // Calculate office rank (simple: count agents with more listings)
                $all_agents = get_users(['role__in' => ['editor', 'administrator']]);
                $agent_counts = [];
                foreach ($all_agents as $agent) {
                    $count = count_user_posts($agent->ID, 'afcglide_listing', true);
                    $agent_counts[$agent->ID] = $count;
                }
                arsort($agent_counts);
                $rank_position = array_search($agent_id, array_keys($agent_counts)) + 1;
                $total_agents = count($agent_counts);
                ?>

                <!-- AGENT PERFORMANCE HEADER -->
                <div class="afc-listing-card" style="margin-bottom: 30px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd; padding: 25px;">
                    <div style="display:flex; justify-content:space-between; align-items:center;">
                        <div>
                            <span style="display:block; font-size:11px; font-weight:800; color:#0369a1; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">Your Performance</span>
                            <h2 style="margin:0; font-size:24px; color:#0c4a6e;">
                                <?php echo esc_html( $user->display_name ); ?>
                                <span style="font-size:14px; background:#fff; color:#0369a1; padding:4px 10px; border-radius:12px; margin-left:12px; font-weight:600; box-shadow: 0 1px 2px rgba(0,0,0,0.1);">
                                    #<?php echo $rank_position; ?> of <?php echo $total_agents; ?> 🏆
                                </span>
                            </h2>
                        </div>
                    </div>
                </div>

                <!-- PERSONAL STATS ROW -->
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin-bottom: 30px;">
                    <!-- My Active Listings -->
                    <div class="afc-listing-card" style="padding: 25px; border-left: 5px solid #10b981;">
                        <span style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">My Active Listings</span>
                        <span style="font-size: 32px; font-weight: 800; color: #0f172a;"><?php echo $my_active_count; ?></span>
                    </div>

                    <!-- My Total Volume -->
                    <div class="afc-listing-card" style="padding: 25px; border-left: 5px solid #3b82f6;">
                        <span style="display:block; font-size:11px; font-weight:800; color:#64748b; text-transform:uppercase; letter-spacing:1px; margin-bottom:5px;">My Portfolio Value</span>
                        <span style="font-size: 32px; font-weight: 800; color: #0f172a;"><?php echo $my_volume_formatted; ?></span>
                    </div>
                </div>

                <!-- QUICK ACTIONS -->
                <div class="afc-listing-card" style="margin-bottom: 30px; background: linear-gradient(135deg, #faf5ff 0%, #f3e8ff 100%); border: 1px solid #e9d5ff; padding: 25px;">
                    <h3 style="margin:0 0 15px 0; color:#581c87; font-size:18px;">Quick Actions</h3>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 15px;">
                        <a href="<?php echo admin_url('post-new.php?post_type=afcglide_listing'); ?>" class="button button-primary button-large" style="background:#7c3aed !important; border:none !important; text-align:center; padding:15px !important; height:auto !important;">
                            <span style="font-size:20px; display:block; margin-bottom:5px;">➕</span>
                            Add New Listing
                        </a>
                        <a href="<?php echo admin_url('edit.php?post_type=afcglide_listing&author=' . $agent_id); ?>" class="button button-large" style="background:#fff !important; color:#7c3aed !important; border:1px solid #e9d5ff !important; text-align:center; padding:15px !important; height:auto !important;">
                            <span style="font-size:20px; display:block; margin-bottom:5px;">🏠</span>
                            View My Listings
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render Settings Page
     */
    public static function render_settings_page() {
        ?>
        <div class="wrap afcglide-wrapper">
            <h1>⚙️ AFCGlide Settings</h1>
            
            <form method="post" action="options.php">
                <?php 
                settings_fields( 'afcglide_settings_group' );
                ?>
                
                <div class="afc-form-section" style="margin-bottom: 30px;">
                    <h3>🛡️ Operational Controls</h3>
                    <p style="color: #64748b; margin-bottom: 20px;">Manage global permissions and approval workflows.</p>

                    <table class="form-table">
                        <!-- Identity Lockdown -->
                        <tr>
                            <th scope="row">Identity Lockdown</th>
                            <td>
                                <?php $lockdown = get_option( 'afc_identity_lockdown', 'no' ); ?>
                                <label class="switch" style="position:relative; display:inline-block; width:40px; height:24px; vertical-align:middle; margin-right:10px;">
                                    <input type="checkbox" name="afc_identity_lockdown" value="yes" <?php checked( $lockdown, 'yes' ); ?>>
                                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#ccc; border-radius:34px; transition:.4s;"></span>
                                </label>
                                <span style="color:#64748b; font-size:13px;">Prevent agents from changing their profile photo/bio.</span>
                            </td>
                        </tr>

                        <!-- Listing Approval -->
                        <tr>
                            <th scope="row">Listing Approval Mode</th>
                            <td>
                                <?php $approval = get_option( 'afc_listing_approval', 'no' ); ?>
                                <label class="switch" style="position:relative; display:inline-block; width:40px; height:24px; vertical-align:middle; margin-right:10px;">
                                    <input type="checkbox" name="afc_listing_approval" value="yes" <?php checked( $approval, 'yes' ); ?>>
                                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#ccc; border-radius:34px; transition:.4s;"></span>
                                </label>
                                <span style="color:#64748b; font-size:13px;">Require Broker approval before listings go live.</span>
                            </td>
                        </tr>

                        <!-- Worker Mode -->
                        <tr>
                            <th scope="row">Worker Mode</th>
                            <td>
                                <?php $worker = get_option( 'afc_worker_mode', 'no' ); ?>
                                <label class="switch" style="position:relative; display:inline-block; width:40px; height:24px; vertical-align:middle; margin-right:10px;">
                                    <input type="checkbox" name="afc_worker_mode" value="yes" <?php checked( $worker, 'yes' ); ?>>
                                    <span class="slider round" style="position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#ccc; border-radius:34px; transition:.4s;"></span>
                                </label>
                                <span style="color:#64748b; font-size:13px;">Grant "Office Manager" role global editing capabilities.</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="afc-form-section">
                    <h3>🌍 Default GPS Coordinates</h3>
                    <p style="color: #64748b;">Set default latitude/longitude for new listings.</p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Default Latitude</th>
                            <td>
                                <input type="text" name="afc_default_lat" value="<?php echo esc_attr( get_option('afc_default_lat', '') ); ?>" class="regular-text" placeholder="8.6294">
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">Default Longitude</th>
                            <td>
                                <input type="text" name="afc_default_long" value="<?php echo esc_attr( get_option('afc_default_long', '') ); ?>" class="regular-text" placeholder="-83.1754">
                            </td>
                        </tr>
                    </table>
                </div>
                
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render Add Agent Page
     */
    public static function render_add_agent_page() {
        if ( isset( $_POST['afcglide_add_agent'] ) && check_admin_referer( 'afcglide_add_agent_nonce' ) ) {
            $user_id = wp_create_user( 
                sanitize_user( $_POST['agent_username'] ), 
                $_POST['agent_password'], 
                sanitize_email( $_POST['agent_email'] ) 
            );
            
            if ( ! is_wp_error( $user_id ) ) {
                wp_update_user([
                    'ID' => $user_id, 
                    'first_name' => sanitize_text_field( $_POST['agent_first_name'] ), 
                    'last_name' => sanitize_text_field( $_POST['agent_last_name'] ), 
                    'role' => 'editor'
                ]);
                update_user_meta( $user_id, 'agent_phone', sanitize_text_field( $_POST['agent_phone'] ) );
                echo '<div class="notice notice-success is-dismissible"><p>✅ Agent Created Successfully!</p></div>';
            } else {
                echo '<div class="notice notice-error"><p>Error: ' . esc_html( $user_id->get_error_message() ) . '</p></div>';
            }
        }
        ?>
        <div class="wrap afcglide-wrapper">
            <h1>👤 Add New Agent</h1>
            <form method="post" class="afc-form-section">
                <?php wp_nonce_field( 'afcglide_add_agent_nonce' ); ?>
                <table class="form-table">
                    <tr><th>Username</th><td><input type="text" name="agent_username" class="regular-text" required></td></tr>
                    <tr><th>Email</th><td><input type="email" name="agent_email" class="regular-text" required></td></tr>
                    <tr><th>Password</th><td><input type="password" name="agent_password" class="regular-text" required></td></tr>
                    <tr><th>First Name</th><td><input type="text" name="agent_first_name" class="regular-text" required></td></tr>
                    <tr><th>Last Name</th><td><input type="text" name="agent_last_name" class="regular-text" required></td></tr>
                    <tr><th>Phone</th><td><input type="text" name="agent_phone" class="regular-text" required></td></tr>
                </table>
                <p class="submit">
                    <input type="submit" name="afcglide_add_agent" class="button button-primary button-large" value="✅ Create Agent">
                    <a href="<?php echo admin_url( 'admin.php?page=afcglide-home' ); ?>" class="button button-secondary">← Back</a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render Manage Agents Page
     */
    public static function render_manage_agents_page() {
        $agents = get_users(['role__in' => ['editor', 'administrator']]);
        ?>
        <div class="wrap afcglide-wrapper">
            <h1>👥 Manage Agents</h1>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr><th>Name</th><th>Email</th><th>Phone</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ( $agents as $agent ) : ?>
                        <tr>
                            <td><strong><?php echo esc_html( $agent->display_name ); ?></strong></td>
                            <td><?php echo esc_html( $agent->user_email ); ?></td>
                            <td><?php echo esc_html( get_user_meta( $agent->ID, 'agent_phone', true ) ); ?></td>
                            <td><a href="<?php echo get_edit_user_link( $agent->ID ); ?>" class="button button-small">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /* ==========================================
       HELPER FUNCTIONS
       ========================================== */

    /**
     * Add body class to listing editor
     */
    public static function add_editor_body_class( $classes ) {
        $screen = get_current_screen();
        
        if ( $screen && $screen->post_type === 'afcglide_listing' ) {
            $classes .= ' afcglide-editor-active';
        }
        
        return $classes;
    }

    /**
     * Redirect default dashboard to AFCGlide home
     */
    public static function redirect_dashboard() {
        global $pagenow;
        if ( $pagenow === 'index.php' && ! isset( $_GET['page'] ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=afcglide-home' ) );
            exit;
        }
    }

    /**
     * Apply Identity Lockdown
     */
    public static function apply_identity_lockdown() {
        $lockdown = get_option( 'afc_identity_lockdown', 'no' );
        if ( 'yes' !== $lockdown || current_user_can( 'manage_options' ) ) return;

        add_action( 'admin_head-profile.php', function() {
            ?>
            <style>
                .user-profile-picture, .user-description-wrap, .user-display-name-wrap { 
                    pointer-events: none; 
                    opacity: 0.6; 
                }
                .user-profile-picture a { display: none !important; }
            </style>
            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    const inputs = document.querySelectorAll('#profile-page input, #profile-page textarea, #profile-page select');
                    inputs.forEach(input => {
                        if(input.name !== '_wp_http_referer' && input.name !== '_wpnonce') {
                            input.disabled = true;
                        }
                    });
                });
            </script>
            <?php
        });
    }

    /**
     * Apply Worker Mode Permissions
     */
    public static function apply_worker_mode_permissions() {
        $worker_mode_enabled = get_option('afc_worker_mode', 'no') === 'yes';
        $role = get_role('office_manager');

        if ( ! $role ) return;

        if ( $worker_mode_enabled ) {
            $role->add_cap('edit_others_afcglide_listings');
            $role->add_cap('delete_others_afcglide_listings');
            $role->add_cap('publish_afcglide_listings');
        } else {
            $role->remove_cap('edit_others_afcglide_listings');
            $role->remove_cap('delete_others_afcglide_listings');
            $role->remove_cap('publish_afcglide_listings');
        }
    }

    /**
     * Add Broker Dashboard Widget
     */
    public static function add_broker_dashboard_widget() {
        if ( current_user_can( 'manage_options' ) ) {
            wp_add_dashboard_widget(
                'afcglide_broker_radar',
                '🛡️ AFCGlide Office Radar',
                [ __CLASS__, 'render_radar_contents' ]
            );
        }
    }

    /**
     * Render Dashboard Widget Contents
     */
    public static function render_radar_contents() {
        $pending_count = wp_count_posts( 'afcglide_listing' )->pending;
        $lockdown_active = get_option('afc_identity_lockdown', 'no') === 'yes';
        $lockdown_text = $lockdown_active ? 'ACTIVE' : 'OFF';
        ?>
        <div style="padding: 10px 0;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background: #f0f9ff; padding: 15px; border-radius: 10px; border: 1px solid #e0f2fe;">
                <div>
                    <span style="display: block; font-size: 11px; text-transform: uppercase; color: #0369a1; font-weight: 800; letter-spacing: 1px;">Pending Approvals</span>
                    <span style="font-size: 28px; font-weight: 800; color: <?php echo $pending_count > 0 ? '#ef4444' : '#1e293b'; ?>;">
                        <?php echo $pending_count; ?>
                    </span>
                </div>
                <a href="edit.php?post_status=pending&post_type=afcglide_listing" class="button button-primary" style="background: #0369a1 !important; border: none !important; border-radius: 6px !important;">Review Now</a>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="display:block; font-size:10px; color:#64748b; font-weight:bold;">IDENTITY LOCK</span>
                    <span style="font-weight:bold; color: <?php echo $lockdown_active ? '#10b981' : '#64748b'; ?>; display: flex; align-items: center;">
                        <span style="display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: <?php echo $lockdown_active ? '#10b981' : '#94a3b8'; ?>; margin-right: 6px;"></span>
                        <?php echo $lockdown_text; ?>
                    </span>
                </div>
                <div style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <span style="display:block; font-size:10px; color:#64748b; font-weight:bold;">QUICK LINK</span>
                    <a href="admin.php?page=afcglide-home" style="text-decoration:none; font-weight:bold; color:#3b82f6;">Command Center →</a>
                </div>
            </div>
        </div>
        <?php
    }
}