<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Agent Protection
 * Version 1.2.0 - Secure Ownership + Rainbow Meta Duplication
 */
class AFCGlide_Agent_Protection {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'apply_ghost_mode' ], 999 );
        add_filter( 'user_has_cap', [ __CLASS__, 'prevent_accidental_delete' ], 10, 3 );
        
        // --- DUPLICATION ---
        add_filter( 'post_row_actions', [ __CLASS__, 'add_duplicate_button' ], 10, 2 );
        add_action( 'admin_action_afcg_duplicate_listing', [ __CLASS__, 'duplicate_listing' ] );
        add_action( 'admin_notices', [ __CLASS__, 'show_duplicate_notice' ] );
    }

    /**
     * Ghost Mode: Clean UI for non-tech Agents.
     */
    public static function apply_ghost_mode() {
        if ( get_option( 'afc_ghost_mode' ) !== 'yes' || current_user_can( 'manage_options' ) ) {
            return;
        }

        // Remove the "Noise"
        remove_menu_page( 'edit.php' );                   // Posts
        remove_menu_page( 'edit.php?post_type=page' );    // Pages
        remove_menu_page( 'edit-comments.php' );         // Comments
        remove_menu_page( 'themes.php' );                // Appearance
        remove_menu_page( 'plugins.php' );               // Plugins
        remove_menu_page( 'tools.php' );                 // Tools
        remove_menu_page( 'options-general.php' );       // Settings
        
        // Hide "Profile" but keep the AFCGlide Command Center visible
    }

    /**
     * Prevent Accidental Deletion with Ownership Security
     */
    public static function prevent_accidental_delete( $caps, $cap, $args ) {
        if ( get_option( 'afc_lockdown_enabled' ) !== 'yes' ) return $caps;

        if ( ! isset( $args[0] ) || $args[0] !== 'delete_post' ) return $caps;

        if ( isset( $args[2] ) ) {
            $post = get_post( $args[2] );
            if ( ! $post || $post->post_type !== 'afcglide_listing' ) return $caps;

            // Security: If they don't own it, they shouldn't even get the choice to delete
            if ( $post->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
                $caps['delete_posts'] = false;
                return $caps;
            }

            // High-End UI Interception
            if ( $post->post_status === 'publish' && ! isset( $_GET['confirm_delete'] ) ) {
                self::show_delete_confirmation( $post );
                exit;
            }
        }
        return $caps;
    }

    /**
     * Handle Listing Duplication (Rainbow Meta Sync)
     */
    public static function duplicate_listing() {
        $post_id = isset($_GET['post']) ? absint($_GET['post']) : 0;
        check_admin_referer( 'duplicate_listing_' . $post_id );
        
        $post = get_post( $post_id );
        if ( ! $post || $post->post_author != get_current_user_id() && ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Ownership Verification Failed.' );
        }

        $new_post_id = wp_insert_post([
            'post_title'   => $post->post_title . ' (COPY)',
            'post_type'    => 'afcglide_listing',
            'post_status'  => 'draft', // Always start as draft
            'post_author'  => get_current_user_id(),
        ]);

        if ( ! is_wp_error( $new_post_id ) ) {
            // DUPLICATE OUR SPECIFIC RAINBOW META
            $meta_keys_to_copy = [
                \AFCGlide\Core\Constants::META_PRICE, 
                \AFCGlide\Core\Constants::META_BEDS, 
                \AFCGlide\Core\Constants::META_BATHS,
                \AFCGlide\Core\Constants::META_SQFT, 
                \AFCGlide\Core\Constants::META_ADDRESS, 
                \AFCGlide\Core\Constants::META_STATUS,
                \AFCGlide\Core\Constants::META_INTRO,
                \AFCGlide\Core\Constants::META_NARRATIVE
            ];
            
            foreach ( $meta_keys_to_copy as $key ) {
                $value = get_post_meta( $post_id, $key, true );
                if ( $value ) update_post_meta( $new_post_id, $key, $value );
            }

            // Copy Featured Image
            $thumb_id = get_post_thumbnail_id( $post_id );
            if ( $thumb_id ) set_post_thumbnail( $new_post_id, $thumb_id );

            set_transient( 'afcg_duplicate_success_' . $new_post_id, true, 30 );
            wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
            exit;
        }
    }

    /**
     * Add "Duplicate" link to row actions
     */
    public static function add_duplicate_button( $actions, $post ) {
        if ( $post->post_type !== 'afcglide_listing' ) return $actions;
        if ( ! current_user_can( 'edit_posts' ) ) return $actions;

        $url = wp_nonce_url( 
            admin_url( 'admin.php?action=afcg_duplicate_listing&post=' . $post->ID ), 
            'duplicate_listing_' . $post->ID 
        );

        $actions['duplicate'] = '<a href="' . $url . '" title="Sync Duplicate Asset" rel="permalink">Duplicate List</a>';
        return $actions;
    }

    /**
     * Show Success Notice after duplication
     */
    public static function show_duplicate_notice() {
        if ( ! isset( $_GET['post'] ) ) return;
        $post_id = absint( $_GET['post'] );
        
        if ( get_transient( 'afcg_duplicate_success_' . $post_id ) ) {
            delete_transient( 'afcg_duplicate_success_' . $post_id );
            ?>
            <div class="notice notice-success is-dismissible" style="border-left: 4px solid #10b981;">
                <p><strong>🚀 ASSET SYNCED:</strong> Listing duplicated successfully. You are now editing the clone.</p>
            </div>
            <?php
        }
    }

    /**
     * High-End Delete Interception Screen
     */
    private static function show_delete_confirmation( $post ) {
        wp_die(
            '<div style="text-align:center; padding: 40px; font-family: sans-serif;">
                <h1 style="color:#ef4444; font-size: 48px; margin-bottom: 10px;">🛑 CRITICAL ACTION</h1>
                <p style="font-size: 18px; color:#475569; font-weight: 600;">You are about to archive a <strong>PUBLISHED</strong> luxury asset.</p>
                <div style="background:#f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; margin: 30px 0;">
                    <strong style="color:#1e293b;">' . esc_html($post->post_title) . '</strong>
                </div>
                <div style="display:flex; gap: 20px; justify-content: center;">
                    <a href="' . admin_url('edit.php?post_type=afcglide_listing') . '" style="background:#6366f1; color:white; padding: 15px 30px; border-radius: 8px; text-decoration:none; font-weight:800;">ABORT OPERATION</a>
                    <a href="' . add_query_arg('confirm_delete', '1', get_delete_post_link($post->ID)) . '" style="background:#ef4444; color:white; padding: 15px 30px; border-radius: 8px; text-decoration:none; font-weight:800;">PROCEED TO TRASH</a>
                </div>
            </div>',
            'Security Protocol Engaged'
        );
    }
}
AFCGlide_Agent_Protection::init();