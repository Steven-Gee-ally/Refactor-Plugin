<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Admin Menu Customizer
 * Version 1.0.0 - Clean Admin Interface
 */
class AFCGlide_Admin_Menu {

    public static function init() {
        // ESCAPE HATCH: If you need full WordPress admin, add this to wp-config.php:
        // define('AFCG_SHOW_ALL_MENUS', true);
        if ( defined( 'AFCG_SHOW_ALL_MENUS' ) && AFCG_SHOW_ALL_MENUS ) {
            return; // Don't customize admin - show everything
        }

        // Remove default menu items
        add_action( 'admin_menu', [ __CLASS__, 'remove_menu_items' ], 999 );
        
        // Redirect dashboard to AFCGlide Home
        add_action( 'admin_init', [ __CLASS__, 'redirect_dashboard' ] );
        
        // Change admin menu order
        add_filter( 'custom_menu_order', '__return_true' );
        add_filter( 'menu_order', [ __CLASS__, 'reorder_menu' ] );
        
        // Add AFCGlide as the main dashboard
        add_action( 'admin_menu', [ __CLASS__, 'add_afcglide_dashboard' ], 5 );
    }

    /**
     * Remove unwanted menu items from admin sidebar
     */
    public static function remove_menu_items() {
        // Only apply to non-administrators (optional - remove this check to apply to all users)
        // if ( ! current_user_can( 'administrator' ) ) {
            
            // Core WordPress menus to remove
            remove_menu_page( 'themes.php' );                    // Appearance
            remove_menu_page( 'options-general.php' );           // Settings
            remove_menu_page( 'edit.php' );                      // Posts
            remove_menu_page( 'edit-comments.php' );             // Comments
            remove_menu_page( 'tools.php' );                     // Tools
            
            // Uncomment below to remove additional items
            // remove_menu_page( 'upload.php' );                 // Media
            // remove_menu_page( 'edit.php?post_type=page' );    // Pages
            // remove_menu_page( 'plugins.php' );                // Plugins
            
            // KEEP Users menu - needed for agent management
            // remove_menu_page( 'users.php' );                  // Users
            
            // Remove the default "Listings" menu (if it exists separately from AFCGlide)
            remove_menu_page( 'edit.php?post_type=listing' );
            
            // Remove Elementor "Hello" from Templates menu
            remove_menu_page( 'edit.php?post_type=elementor_library' );
            
            // Remove Astra theme settings (if installed)
            remove_menu_page( 'astra' );
            
            // Remove Elementor submenu items but keep plugin
            remove_submenu_page( 'plugins.php', 'elementor' );
            
        // }
        
        // Always remove for all users (optional items)
        // remove_submenu_page( 'themes.php', 'customize.php' ); // Customizer
    }

    /**
     * Redirect default WordPress dashboard to AFCGlide Home
     */
    public static function redirect_dashboard() {
        global $pagenow;
        
        // Check if we're on the default dashboard
        if ( $pagenow === 'index.php' && ! isset( $_GET['page'] ) ) {
            // Redirect to AFCGlide Home (adjust the slug to match your actual page)
            wp_safe_redirect( admin_url( 'admin.php?page=afcglide-home' ) );
            exit;
        }
    }

    /**
     * Reorder admin menu to put AFCGlide first
     */
    public static function reorder_menu( $menu_order ) {
        if ( ! $menu_order ) return true;

        return [
            'admin.php?page=afcglide-home',          // AFCGlide Home (first)
            'edit.php?post_type=afcglide_listing',   // AFCGlide Listings
            'separator1',                             // Separator
            'upload.php',                             // Media
            'edit.php?post_type=page',                // Pages
            'users.php',                              // Users
            'plugins.php',                            // Plugins
        ];
    }

    /**
     * Add AFCGlide as the main dashboard page (optional alternative approach)
     */
    public static function add_afcglide_dashboard() {
        // Remove the default Dashboard menu
        remove_menu_page( 'index.php' );
        
        // Add AFCGlide Home as the first item with dashboard icon
        add_menu_page(
            __( 'AFCGlide Home', 'afcglide' ),           // Page title
            __( '🏠 AFCGlide Home', 'afcglide' ),        // Menu title
            'read',                                       // Capability
            'afcglide-home',                             // Menu slug
            [ __CLASS__, 'render_dashboard' ],           // Callback
            'dashicons-admin-home',                      // Icon
            1                                            // Position (1 = very top)
        );
        
        // Add hidden submenu page for Add Agent
        add_submenu_page(
            null,                                        // No parent = hidden from menu
            __( 'Add New Agent', 'afcglide' ),
            __( 'Add New Agent', 'afcglide' ),
            'create_users',
            'afcglide-add-agent',
            [ __CLASS__, 'render_add_agent_page' ]
        );
        
        // Add hidden submenu page for Manage Agents
        add_submenu_page(
            null,                                        // No parent = hidden from menu
            __( 'Manage Agents', 'afcglide' ),
            __( 'Manage Agents', 'afcglide' ),
            'list_users',
            'afcglide-manage-agents',
            [ __CLASS__, 'render_manage_agents_page' ]
        );
    }

    /**
     * Render the AFCGlide Dashboard
     */
    public static function render_dashboard() {
        ?>
        <div class="wrap afcglide-dashboard">
            <h1><?php esc_html_e( 'AFCGlide Luxury Real Estate', 'afcglide' ); ?></h1>
            
            <div class="afcglide-dashboard-grid">
                <!-- Quick Stats -->
                <div class="afcglide-dashboard-card">
                    <h2>📊 <?php esc_html_e( 'Quick Stats', 'afcglide' ); ?></h2>
                    <?php
                    $total_listings = wp_count_posts( 'afcglide_listing' );
                    $published = $total_listings->publish ?? 0;
                    $draft = $total_listings->draft ?? 0;
                    ?>
                    <div class="afcglide-stats">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo esc_html( $published ); ?></span>
                            <span class="stat-label"><?php esc_html_e( 'Published Listings', 'afcglide' ); ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo esc_html( $draft ); ?></span>
                            <span class="stat-label"><?php esc_html_e( 'Drafts', 'afcglide' ); ?></span>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="afcglide-dashboard-card">
                    <h2>⚡ <?php esc_html_e( 'Quick Actions', 'afcglide' ); ?></h2>
                    <div class="afcglide-actions">
                        <a href="<?php echo esc_url( admin_url( 'post-new.php?post_type=afcglide_listing' ) ); ?>" class="button button-primary button-hero">
                            ➕ <?php esc_html_e( 'Add New Listing', 'afcglide' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=afcglide-add-agent' ) ); ?>" class="button button-secondary">
                            👤 <?php esc_html_e( 'Add New Agent', 'afcglide' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'edit.php?post_type=afcglide_listing' ) ); ?>" class="button button-secondary">
                            📋 <?php esc_html_e( 'View All Listings', 'afcglide' ); ?>
                        </a>
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=afcglide-manage-agents' ) ); ?>" class="button button-secondary">
                            👥 <?php esc_html_e( 'Manage Agents', 'afcglide' ); ?>
                        </a>
                        <a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="button button-secondary" target="_blank">
                            🌐 <?php esc_html_e( 'Visit Site', 'afcglide' ); ?>
                        </a>
                    </div>
                </div>

                <!-- Recent Listings -->
                <div class="afcglide-dashboard-card afcglide-full-width">
                    <h2>🏠 <?php esc_html_e( 'Recent Listings', 'afcglide' ); ?></h2>
                    <?php
                    $recent_listings = get_posts([
                        'post_type'      => 'afcglide_listing',
                        'posts_per_page' => 5,
                        'post_status'    => 'any',
                        'orderby'        => 'modified',
                        'order'          => 'DESC'
                    ]);

                    if ( $recent_listings ) : ?>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th><?php esc_html_e( 'Title', 'afcglide' ); ?></th>
                                    <th><?php esc_html_e( 'Agent', 'afcglide' ); ?></th>
                                    <th><?php esc_html_e( 'Price', 'afcglide' ); ?></th>
                                    <th><?php esc_html_e( 'Status', 'afcglide' ); ?></th>
                                    <th><?php esc_html_e( 'Modified', 'afcglide' ); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ( $recent_listings as $listing ) : 
                                    $agent = get_post_meta( $listing->ID, '_agent_name', true );
                                    $price = get_post_meta( $listing->ID, '_listing_price', true );
                                    $edit_link = get_edit_post_link( $listing->ID );
                                ?>
                                    <tr>
                                        <td>
                                            <strong>
                                                <a href="<?php echo esc_url( $edit_link ); ?>">
                                                    <?php echo esc_html( $listing->post_title ?: __( '(no title)', 'afcglide' ) ); ?>
                                                </a>
                                            </strong>
                                        </td>
                                        <td><?php echo esc_html( $agent ?: '—' ); ?></td>
                                        <td><?php echo $price ? '$' . number_format( $price ) : '—'; ?></td>
                                        <td>
                                            <span class="afcglide-status-badge status-<?php echo esc_attr( $listing->post_status ); ?>">
                                                <?php echo esc_html( ucfirst( $listing->post_status ) ); ?>
                                            </span>
                                        </td>
                                        <td><?php echo esc_html( human_time_diff( strtotime( $listing->post_modified ), current_time( 'timestamp' ) ) . ' ago' ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else : ?>
                        <p><?php esc_html_e( 'No listings yet. Create your first luxury property!', 'afcglide' ); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <style>
                .afcglide-dashboard-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 20px;
                    margin-top: 20px;
                }
                .afcglide-dashboard-card {
                    background: #fff;
                    padding: 20px;
                    border: 1px solid #ccd0d4;
                    border-radius: 8px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                }
                .afcglide-dashboard-card h2 {
                    margin-top: 0;
                    font-size: 18px;
                    border-bottom: 2px solid #f0f0f0;
                    padding-bottom: 10px;
                }
                .afcglide-full-width {
                    grid-column: 1 / -1;
                }
                .afcglide-stats {
                    display: grid;
                    grid-template-columns: repeat(2, 1fr);
                    gap: 15px;
                }
                .stat-item {
                    text-align: center;
                    padding: 20px;
                    background: #f8f9fa;
                    border-radius: 6px;
                }
                .stat-number {
                    display: block;
                    font-size: 32px;
                    font-weight: bold;
                    color: #2271b1;
                }
                .stat-label {
                    display: block;
                    font-size: 13px;
                    color: #666;
                    margin-top: 5px;
                }
                .afcglide-actions {
                    display: flex;
                    flex-direction: column;
                    gap: 10px;
                }
                .afcglide-status-badge {
                    padding: 4px 12px;
                    border-radius: 12px;
                    font-size: 12px;
                    font-weight: 600;
                }
                .status-publish {
                    background: #d4edda;
                    color: #155724;
                }
                .status-draft {
                    background: #fff3cd;
                    color: #856404;
                }
                .status-pending {
                    background: #cce5ff;
                    color: #004085;
                }
            </style>
        </div>
        <?php
    }

    /**
     * Render Add Agent Page
     */
    public static function render_add_agent_page() {
        // Handle form submission
        if ( isset( $_POST['afcglide_add_agent'] ) && check_admin_referer( 'afcglide_add_agent_nonce' ) ) {
            $username = sanitize_user( $_POST['agent_username'] );
            $email = sanitize_email( $_POST['agent_email'] );
            $first_name = sanitize_text_field( $_POST['agent_first_name'] );
            $last_name = sanitize_text_field( $_POST['agent_last_name'] );
            $phone = sanitize_text_field( $_POST['agent_phone'] );
            $whatsapp = sanitize_text_field( $_POST['agent_whatsapp'] );
            $password = $_POST['agent_password'];
            
            $user_id = wp_create_user( $username, $password, $email );
            
            if ( ! is_wp_error( $user_id ) ) {
                wp_update_user([
                    'ID' => $user_id,
                    'first_name' => $first_name,
                    'last_name' => $last_name,
                    'role' => 'editor'
                ]);
                
                // Save phone and WhatsApp as user meta
                update_user_meta( $user_id, 'agent_phone', $phone );
                update_user_meta( $user_id, 'agent_whatsapp', $whatsapp );
                
                echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Agent Created Successfully!</strong><br>Username: <strong>' . esc_html( $username ) . '</strong><br>WhatsApp: <strong>' . esc_html( $whatsapp ) . '</strong></p></div>';
            } else {
                echo '<div class="notice notice-error"><p><strong>❌ Error:</strong> ' . esc_html( $user_id->get_error_message() ) . '</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1>👤 Add New Agent</h1>
            <p>Create a new agent account for managing listings.</p>
            
            <form method="post" style="max-width: 700px; background: white; padding: 30px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-top: 20px;">
                <?php wp_nonce_field( 'afcglide_add_agent_nonce' ); ?>
                
                <table class="form-table">
                    <tr>
                        <th><label for="agent_username">Username *</label></th>
                        <td><input type="text" name="agent_username" id="agent_username" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="agent_email">Email *</label></th>
                        <td><input type="email" name="agent_email" id="agent_email" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="agent_password">Password *</label></th>
                        <td>
                            <input type="password" name="agent_password" id="agent_password" class="regular-text" required>
                            <p class="description">Minimum 8 characters</p>
                        </td>
                    </tr>
                    <tr style="border-top: 2px solid #10b981; padding-top: 20px;">
                        <td colspan="2"><h3 style="color: #10b981; margin: 20px 0 10px 0;">📋 Agent Profile Information</h3></td>
                    </tr>
                    <tr>
                        <th><label for="agent_first_name">First Name *</label></th>
                        <td><input type="text" name="agent_first_name" id="agent_first_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="agent_last_name">Last Name *</label></th>
                        <td><input type="text" name="agent_last_name" id="agent_last_name" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th><label for="agent_phone">Phone Number *</label></th>
                        <td>
                            <input type="text" name="agent_phone" id="agent_phone" class="regular-text" placeholder="+506 1234 5678" required>
                            <p class="description">Include country code (e.g., +506 for Costa Rica)</p>
                        </td>
                    </tr>
                    <tr style="background: #f0fdf4; border-left: 4px solid #10b981;">
                        <th><label for="agent_whatsapp">💬 WhatsApp Number *</label></th>
                        <td>
                            <input type="text" name="agent_whatsapp" id="agent_whatsapp" class="regular-text" placeholder="+506 1234 5678" required>
                            <p class="description" style="color: #059669; font-weight: 600;">
                                ⚠️ IMPORTANTE: Must include country code (e.g., +50612345678)<br>
                                This enables the floating WhatsApp button on listings.
                            </p>
                        </td>
                    </tr>
                </table>
                
                <p class="submit" style="border-top: 1px solid #e5e7eb; padding-top: 20px; margin-top: 20px;">
                    <input type="submit" name="afcglide_add_agent" class="button button-primary button-large" value="✅ Create Agent Account" style="background: #10b981 !important; border-color: #10b981 !important;">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=afcglide-home' ) ); ?>" class="button button-secondary button-large" style="margin-left: 10px;">← Back to Dashboard</a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render Manage Agents Page
     */
    public static function render_manage_agents_page() {
        $agents = get_users([
            'role__in' => ['editor', 'administrator'],
            'orderby' => 'registered',
            'order' => 'DESC'
        ]);
        ?>
        <div class="wrap">
            <h1>👥 Manage Agents</h1>
            <p>View and manage all agent accounts.</p>
            
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=afcglide-add-agent' ) ); ?>" class="button button-primary" style="margin: 20px 0; background: #10b981 !important; border-color: #10b981 !important;">➕ Add New Agent</a>
            
            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th>Agent Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>📞 Phone</th>
                        <th>💬 WhatsApp</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( ! empty( $agents ) ) : ?>
                        <?php foreach ( $agents as $agent ) : 
                            $phone = get_user_meta( $agent->ID, 'agent_phone', true );
                            $whatsapp = get_user_meta( $agent->ID, 'agent_whatsapp', true );
                        ?>
                            <tr>
                                <td><strong><?php echo esc_html( $agent->first_name . ' ' . $agent->last_name ); ?></strong></td>
                                <td><?php echo esc_html( $agent->user_login ); ?></td>
                                <td><?php echo esc_html( $agent->user_email ); ?></td>
                                <td><?php echo $phone ? esc_html( $phone ) : '<span style="color:#94a3b8;">—</span>'; ?></td>
                                <td>
                                    <?php if ( $whatsapp ) : ?>
                                        <a href="https://wa.me/<?php echo esc_attr( str_replace( [' ', '+', '-'], '', $whatsapp ) ); ?>" target="_blank" style="color: #10b981; text-decoration: none;">
                                            <?php echo esc_html( $whatsapp ); ?> ✓
                                        </a>
                                    <?php else : ?>
                                        <span style="color:#ef4444;">Not Set</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( ucfirst( implode( ', ', $agent->roles ) ) ); ?></td>
                                <td><?php echo esc_html( date( 'M j, Y', strtotime( $agent->user_registered ) ) ); ?></td>
                                <td>
                                    <a href="<?php echo esc_url( get_edit_user_link( $agent->ID ) ); ?>" class="button button-small">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8;">
                                No agents found. Click "Add New Agent" to create one.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <div style="background: #f0fdf4; border-left: 4px solid #10b981; padding: 15px; margin-top: 20px; border-radius: 8px;">
                <p style="margin: 0; color: #059669;">
                    <strong>💡 Pro Tip:</strong> Make sure each agent has a WhatsApp number set up for the floating button to work on their listings.
                </p>
            </div>
            
            <p style="margin-top: 20px;">
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=afcglide-home' ) ); ?>" class="button button-secondary">← Back to Dashboard</a>
            </p>
        </div>
        <?php
    }
}

// Initialize
AFCGlide_Admin_Menu::init();