<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Agent Protections
 * Version 1.1.0 - Ghost Mode + Integrated Dashboard Controls
 */
class AFCGlide_Agent_Protections {

    public static function init() {
        // --- GHOST MODE & MENU HIDING ---
        add_action( 'admin_menu', [ __CLASS__, 'apply_ghost_mode' ], 999 );

        // --- DELETE PROTECTION ---
        // Prevent accidental deletion of published listings
        add_filter( 'user_has_cap', [ __CLASS__, 'prevent_accidental_delete' ], 10, 3 );
        
        // --- DUPLICATION ENGINE ---
        add_filter( 'post_row_actions', [ __CLASS__, 'add_duplicate_button' ], 10, 2 );
        add_action( 'admin_action_afcg_duplicate_listing', [ __CLASS__, 'duplicate_listing' ] );
        add_action( 'admin_notices', [ __CLASS__, 'show_duplicate_notice' ] );
    }

    /**
     * Ghost Mode: Hides technical clutter from non-admins
     * Controlled by the "Ghost Mode" toggle on the AFCGlide Dashboard
     */
    public static function apply_ghost_mode() {
        // Only run if Ghost Mode is toggled ON
        if ( get_option( 'afc_ghost_mode' ) !== 'yes' ) {
            return;
        }

        // Never hide menus from the Master Administrator
        if ( current_user_can( 'manage_options' ) ) {
            return;
        }

        // Hide the messy WordPress internals for a clean Agent experience
        remove_menu_page( 'edit.php?post_type=page' );    // Hide Pages
        remove_menu_page( 'edit-comments.php' );         // Hide Comments
        remove_menu_page( 'themes.php' );                // Hide Appearance
        remove_menu_page( 'plugins.php' );               // Hide Plugins
        remove_menu_page( 'tools.php' );                 // Hide Tools
        remove_menu_page( 'options-general.php' );       // Hide WP Settings
        remove_menu_page( 'edit.php' );                  // Hide Default Posts
    }

    /**
     * Prevent Accidental Deletion
     * Links your confirmation page to the "Delete Protection" dashboard toggle
     */
    public static function prevent_accidental_delete( $caps, $cap, $args ) {
        // Only check if Delete Protection is toggled ON in Dashboard
        if ( get_option( 'afc_lockdown_enabled' ) !== 'yes' ) {
            return $caps;
        }

        if ( ! isset( $args[0] ) || $args[0] !== 'delete_post' ) {
            return $caps;
        }

        if ( isset( $args[2] ) ) {
            $post = get_post( $args[2] );
            
            if ( $post && $post->post_type === 'afcglide_listing' && $post->post_status === 'publish' ) {
                if ( ! isset( $_GET['confirm_delete'] ) || $_GET['confirm_delete'] !== '1' ) {
                    self::show_delete_confirmation( $post );
                    exit;
                }
            }
        }

        return $caps;
    }

    /**
     * Show Delete Confirmation Page (Your High-End UI)
     */
    private static function show_delete_confirmation( $post ) {
        $delete_url = add_query_arg( 'confirm_delete', '1', $_SERVER['REQUEST_URI'] );
        $cancel_url = admin_url( 'edit.php?post_type=afcglide_listing' );
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <title>Confirm Delete</title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                    background: #f0f0f1;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    height: 100vh;
                    margin: 0;
                }
                .confirm-box {
                    background: white;
                    padding: 40px;
                    border-radius: 12px;
                    box-shadow: 0 10px 40px rgba(0,0,0,0.1);
                    max-width: 500px;
                    text-align: center;
                }
                .warning-icon { font-size: 64px; margin-bottom: 20px; }
                h1 { color: #ef4444; margin: 0 0 20px 0; font-size: 28px; }
                .listing-title {
                    background: #f8fafc;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 20px 0;
                    font-weight: bold;
                    color: #334155;
                }
                .buttons { display: flex; gap: 15px; margin-top: 30px; }
                .btn {
                    flex: 1; padding: 15px 30px; border: none; border-radius: 8px;
                    font-size: 16px; font-weight: 600; cursor: pointer;
                    text-decoration: none; display: inline-block;
                }
                .btn-delete { background: #ef4444; color: white; }
                .btn-cancel { background: #e2e8f0; color: #334155; }
            </style>
        </head>
        <body>
            <div class="confirm-box">
                <div class="warning-icon">⚠️</div>
                <h1>Delete Published Listing?</h1>
                <p style="color: #64748b; font-size: 16px;">This action cannot be undone.</p>
                <div class="listing-title">"<?php echo esc_html( $post->post_title ); ?>"</div>
                <div class="buttons">
                    <a href="<?php echo esc_url( $cancel_url ); ?>" class="btn btn-cancel">← Cancel</a>
                    <a href="<?php echo esc_url( $delete_url ); ?>" class="btn btn-delete">Yes, Delete Forever</a>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Add Duplicate Button
     */
    public static function add_duplicate_button( $actions, $post ) {
        if ( $post->post_type === 'afcglide_listing' && current_user_can( 'edit_posts' ) ) {
            $url = wp_nonce_url(
                admin_url( 'admin.php?action=afcg_duplicate_listing&post=' . $post->ID ),
                'duplicate_listing_' . $post->ID
            );
            $actions['duplicate'] = sprintf( '<a href="%s">📋 Duplicate</a>', esc_url( $url ) );
        }
        return $actions;
    }

    /**
     * Handle Listing Duplication
     */
    public static function duplicate_listing() {
        if ( ! isset( $_GET['post'] ) ) wp_die( 'No listing to duplicate.' );
        $post_id = absint( $_GET['post'] );
        check_admin_referer( 'duplicate_listing_' . $post_id );
        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'afcglide_listing' ) wp_die( 'Invalid listing.' );
        if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Permission denied.' );

        $new_post_id = wp_insert_post([
            'post_title'   => $post->post_title . ' (Copy)',
            'post_content' => $post->post_content,
            'post_type'    => 'afcglide_listing',
            'post_status'  => 'draft',
            'post_author'  => get_current_user_id(),
        ]);

        if ( is_wp_error( $new_post_id ) ) wp_die( 'Error duplicating.' );

        // Copy Meta
        $meta = get_post_meta( $post_id );
        foreach ( $meta as $key => $values ) {
            if ( in_array( $key, [ '_edit_lock', '_edit_last' ] ) ) continue;
            foreach ( $values as $value ) {
                add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
            }
        }

        set_transient( 'afcg_duplicate_success_' . $new_post_id, true, 30 );
        wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
        exit;
    }

    /**
     * Success Notice
     */
    public static function show_duplicate_notice() {
        global $post;
        if ( $post && $post->post_type === 'afcglide_listing' && get_transient( 'afcg_duplicate_success_' . $post->ID ) ) {
            echo '<div class="notice notice-success is-dismissible"><p><strong>✅ Listing Duplicated Successfully!</strong> This is a draft copy.</p></div>';
            delete_transient( 'afcg_duplicate_success_' . $post->ID );
        }
    }
}

// Initialize
AFCGlide_Agent_Protections::init();