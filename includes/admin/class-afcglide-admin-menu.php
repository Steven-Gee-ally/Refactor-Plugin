<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Admin Menu Customizer
 * Version 1.0.5 - Agent Management Kept, Dashboard Ghost Removed
 */
class AFCGlide_Admin_Menu {

    public static function init() {
        if ( defined( 'AFCG_SHOW_ALL_MENUS' ) && AFCG_SHOW_ALL_MENUS ) {
            return; 
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            add_action( 'admin_menu', [ __CLASS__, 'remove_menu_items' ], 999 );
        }

        add_action( 'admin_init', [ __CLASS__, 'redirect_dashboard' ] );
        add_filter( 'custom_menu_order', '__return_true' );
        add_filter( 'menu_order', [ __CLASS__, 'reorder_menu' ] );
        add_action( 'admin_menu', [ __CLASS__, 'add_afcglide_dashboard' ], 5 );
        add_action( 'admin_head', [ __CLASS__, 'clean_up_listing_editor' ] );
    }

    public static function remove_menu_items() {
        remove_menu_page( 'themes.php' );
        remove_menu_page( 'options-general.php' );
        remove_menu_page( 'edit.php' );
        remove_menu_page( 'edit-comments.php' );
        remove_menu_page( 'tools.php' );
        remove_menu_page( 'edit.php?post_type=elementor_library' );
        remove_menu_page( 'astra' );
        remove_submenu_page( 'plugins.php', 'elementor' );
    }

    public static function redirect_dashboard() {
        global $pagenow;
        if ( $pagenow === 'index.php' && ! isset( $_GET['page'] ) ) {
            wp_safe_redirect( admin_url( 'admin.php?page=afcglide-home' ) );
            exit;
        }
    }

    public static function reorder_menu( $menu_order ) {
        if ( ! $menu_order ) return true;
        return [
            'afcglide-home',
            'edit.php?post_type=afcglide_listing',   
            'separator1',                            
            'upload.php',                            
            'edit.php?post_type=page',               
            'users.php',                             
            'plugins.php',                           
        ];
    }

    public static function add_afcglide_dashboard() {
        remove_menu_page( 'index.php' );
        
        // 🏠 WE REGISTER THE MENU, BUT LEAVE THE CONTENT CALLBACK EMPTY ('')
        // This keeps the sidebar icon but lets the "Green" file handle the screen.
        add_menu_page(
            __( 'AFCGlide Home', 'afcglide' ),
            __( '🏠 AFCGlide', 'afcglide' ),
            'read',
            'afcglide-home',
            '', // <--- This empty string kills the "White Grid" content
            'dashicons-admin-home',
            1
        );
        
        remove_submenu_page( 'afcglide-home', 'afcglide-home' );
        
        add_submenu_page( null, __( 'Add New Agent', 'afcglide' ), __( 'Add New Agent', 'afcglide' ), 'create_users', 'afcglide-add-agent', [ __CLASS__, 'render_add_agent_page' ] );
        add_submenu_page( null, __( 'Manage Agents', 'afcglide' ), __( 'Manage Agents', 'afcglide' ), 'list_users', 'afcglide-manage-agents', [ __CLASS__, 'render_manage_agents_page' ] );
    }

    /**
     * Agent Pages - We keep these because they are essential!
     */
    public static function render_add_agent_page() {
        if ( isset( $_POST['afcglide_add_agent'] ) && check_admin_referer( 'afcglide_add_agent_nonce' ) ) {
            $user_id = wp_create_user( sanitize_user( $_POST['agent_username'] ), $_POST['agent_password'], sanitize_email( $_POST['agent_email'] ) );
            if ( ! is_wp_error( $user_id ) ) {
                wp_update_user(['ID' => $user_id, 'first_name' => sanitize_text_field( $_POST['agent_first_name'] ), 'last_name' => sanitize_text_field( $_POST['agent_last_name'] ), 'role' => 'editor']);
                update_user_meta( $user_id, 'agent_phone', sanitize_text_field( $_POST['agent_phone'] ) );
                update_user_meta( $user_id, 'agent_whatsapp', sanitize_text_field( $_POST['agent_whatsapp'] ) );
                echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Agent Created Successfully!</strong></p></div>';
            } else {
                echo '<div class="notice notice-error"><p><strong>Error:</strong> ' . esc_html( $user_id->get_error_message() ) . '</p></div>';
            }
        }
        ?>
        <div class="wrap">
            <h1>👤 Add New Agent</h1>
            <form method="post" style="max-width: 700px; background: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-top: 20px;">
                <?php wp_nonce_field( 'afcglide_add_agent_nonce' ); ?>
                <table class="form-table">
                    <tr><th><label>Username *</label></th><td><input type="text" name="agent_username" class="regular-text" required></td></tr>
                    <tr><th><label>Email *</label></th><td><input type="email" name="agent_email" class="regular-text" required></td></tr>
                    <tr><th><label>Password *</label></th><td><input type="password" name="agent_password" class="regular-text" required></td></tr>
                    <tr><th><label>First Name *</label></th><td><input type="text" name="agent_first_name" class="regular-text" required></td></tr>
                    <tr><th><label>Last Name *</label></th><td><input type="text" name="agent_last_name" class="regular-text" required></td></tr>
                    <tr><th><label>Phone *</label></th><td><input type="text" name="agent_phone" class="regular-text" placeholder="+506 1234 5678" required></td></tr>
                    <tr><th><label>💬 WhatsApp *</label></th><td><input type="text" name="agent_whatsapp" class="regular-text" placeholder="+506 1234 5678" required></td></tr>
                </table>
                <p class="submit">
                    <input type="submit" name="afcglide_add_agent" class="button button-primary button-large" value="✅ Create Agent">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=afcglide-home' ) ); ?>" class="button button-secondary button-large">← Back</a>
                </p>
            </form>
        </div>
        <?php
    }

    public static function render_manage_agents_page() {
        $agents = get_users(['role__in' => ['editor', 'administrator'], 'orderby' => 'registered', 'order' => 'DESC']);
        ?>
        <div class="wrap">
            <h1>👥 Manage Agents</h1>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=afcglide-add-agent' ) ); ?>" class="button button-primary" style="margin: 20px 0;">➕ Add New Agent</a>
            <table class="wp-list-table widefat fixed striped" style="margin-top: 20px;">
                <thead>
                    <tr><th>Agent Name</th><th>Username</th><th>Email</th><th>📞 Phone</th><th>💬 WhatsApp</th><th>Role</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php foreach ( $agents as $agent ) : 
                        $phone = get_user_meta( $agent->ID, 'agent_phone', true );
                        $whatsapp = get_user_meta( $agent->ID, 'agent_whatsapp', true );
                    ?>
                        <tr>
                            <td><strong><?php echo esc_html( $agent->first_name . ' ' . $agent->last_name ); ?></strong></td>
                            <td><?php echo esc_html( $agent->user_login ); ?></td>
                            <td><?php echo esc_html( $agent->user_email ); ?></td>
                            <td><?php echo $phone ? esc_html( $phone ) : '—'; ?></td>
                            <td><?php echo $whatsapp ? esc_html( $whatsapp ) : '<span style="color:#ef4444;">Not Set</span>'; ?></td>
                            <td><?php echo esc_html( ucfirst( implode( ', ', $agent->roles ) ) ); ?></td>
                            <td><a href="<?php echo esc_url( get_edit_user_link( $agent->ID ) ); ?>" class="button button-small">Edit</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }
    /**
     * Clean up the Listing Editor to meet "Best in the World" standards.
     * We move all styling to assets/css/admin.css for maximum performance.
     */
    public static function clean_up_listing_editor() {
        $screen = get_current_screen();
        
        if ( ! $screen || $screen->post_type !== 'afcglide_listing' ) {
            return;
        }

        // We inject a tiny body class so our admin.css knows when to apply the luxury layout
        echo '<script>document.body.classList.add("afcglide-editor-active");</script>';
    }
}