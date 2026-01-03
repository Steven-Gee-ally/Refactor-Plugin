<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Agent Protections
 * Version 1.0.0 - Delete Protection & Duplicate Listings
 */
class AFCGlide_Agent_Protections {

    public static function init() {
        // Prevent accidental deletion of published listings
        add_filter( 'user_has_cap', [ __CLASS__, 'prevent_accidental_delete' ], 10, 3 );
        
        // Add duplicate button to listing table
        add_filter( 'post_row_actions', [ __CLASS__, 'add_duplicate_button' ], 10, 2 );
        
        // Handle duplication
        add_action( 'admin_action_afcg_duplicate_listing', [ __CLASS__, 'duplicate_listing' ] );
        
        // Show confirmation notice
        add_action( 'admin_notices', [ __CLASS__, 'show_duplicate_notice' ] );
    }

    /**
     * Prevent Accidental Deletion of Published Listings
     * Requires confirmation before allowing delete
     */
    public static function prevent_accidental_delete( $caps, $cap, $args ) {
        // Only check delete_post capability
        if ( ! isset( $args[0] ) || $args[0] !== 'delete_post' ) {
            return $caps;
        }

        // Only for our listing post type
        if ( isset( $args[2] ) ) {
            $post = get_post( $args[2] );
            
            if ( $post && $post->post_type === 'afcglide_listing' && $post->post_status === 'publish' ) {
                // Check if confirmation parameter is present
                if ( ! isset( $_GET['confirm_delete'] ) || $_GET['confirm_delete'] !== '1' ) {
                    // Show confirmation page
                    self::show_delete_confirmation( $post );
                    exit;
                }
            }
        }

        return $caps;
    }

    /**
     * Show Delete Confirmation Page
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
                .warning-icon {
                    font-size: 64px;
                    margin-bottom: 20px;
                }
                h1 {
                    color: #ef4444;
                    margin: 0 0 20px 0;
                    font-size: 28px;
                }
                .listing-title {
                    background: #f8fafc;
                    padding: 15px;
                    border-radius: 8px;
                    margin: 20px 0;
                    font-weight: bold;
                    color: #334155;
                }
                .buttons {
                    display: flex;
                    gap: 15px;
                    margin-top: 30px;
                }
                .btn {
                    flex: 1;
                    padding: 15px 30px;
                    border: none;
                    border-radius: 8px;
                    font-size: 16px;
                    font-weight: 600;
                    cursor: pointer;
                    text-decoration: none;
                    display: inline-block;
                }
                .btn-delete {
                    background: #ef4444;
                    color: white;
                }
                .btn-delete:hover {
                    background: #dc2626;
                }
                .btn-cancel {
                    background: #e2e8f0;
                    color: #334155;
                }
                .btn-cancel:hover {
                    background: #cbd5e1;
                }
            </style>
        </head>
        <body>
            <div class="confirm-box">
                <div class="warning-icon">⚠️</div>
                <h1>Delete Published Listing?</h1>
                <p style="color: #64748b; font-size: 16px;">
                    You're about to <strong>permanently delete</strong> a published listing. This action cannot be undone.
                </p>
                <div class="listing-title">
                    "<?php echo esc_html( $post->post_title ); ?>"
                </div>
                <p style="color: #94a3b8; font-size: 14px;">
                    This listing is currently <strong>LIVE</strong> on your website.
                </p>
                <div class="buttons">
                    <a href="<?php echo esc_url( $cancel_url ); ?>" class="btn btn-cancel">
                        ← Cancel
                    </a>
                    <a href="<?php echo esc_url( $delete_url ); ?>" class="btn btn-delete">
                        Yes, Delete Forever
                    </a>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Add Duplicate Button to Listing Table
     */
    public static function add_duplicate_button( $actions, $post ) {
        if ( $post->post_type === 'afcglide_listing' && current_user_can( 'edit_posts' ) ) {
            $url = wp_nonce_url(
                admin_url( 'admin.php?action=afcg_duplicate_listing&post=' . $post->ID ),
                'duplicate_listing_' . $post->ID
            );
            
            $actions['duplicate'] = sprintf(
                '<a href="%s">📋 Duplicate</a>',
                esc_url( $url )
            );
        }
        return $actions;
    }

    /**
     * Handle Listing Duplication
     */
    public static function duplicate_listing() {
        // Check if post ID is provided
        if ( ! isset( $_GET['post'] ) ) {
            wp_die( __( 'No listing to duplicate.', 'afcglide' ) );
        }

        $post_id = absint( $_GET['post'] );
        
        // Verify nonce
        check_admin_referer( 'duplicate_listing_' . $post_id );

        // Get the original post
        $post = get_post( $post_id );

        if ( ! $post || $post->post_type !== 'afcglide_listing' ) {
            wp_die( __( 'Invalid listing.', 'afcglide' ) );
        }

        // Check permissions
        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_die( __( 'You do not have permission to duplicate listings.', 'afcglide' ) );
        }

        // Create the duplicate
        $new_post_id = wp_insert_post([
            'post_title'   => $post->post_title . ' (Copy)',
            'post_content' => $post->post_content,
            'post_excerpt' => $post->post_excerpt,
            'post_type'    => 'afcglide_listing',
            'post_status'  => 'draft', // Always start as draft
            'post_author'  => get_current_user_id(),
        ]);

        if ( is_wp_error( $new_post_id ) ) {
            wp_die( __( 'Error creating duplicate listing.', 'afcglide' ) );
        }

        // Copy all post meta
        $meta_keys = get_post_meta( $post_id );
        foreach ( $meta_keys as $key => $values ) {
            // Skip internal WordPress meta
            if ( substr( $key, 0, 1 ) === '_' && in_array( $key, [ '_edit_lock', '_edit_last' ] ) ) {
                continue;
            }
            
            foreach ( $values as $value ) {
                add_post_meta( $new_post_id, $key, maybe_unserialize( $value ) );
            }
        }

        // Copy taxonomies
        $taxonomies = get_object_taxonomies( 'afcglide_listing' );
        foreach ( $taxonomies as $taxonomy ) {
            $terms = wp_get_object_terms( $post_id, $taxonomy, [ 'fields' => 'ids' ] );
            if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                wp_set_object_terms( $new_post_id, $terms, $taxonomy );
            }
        }

        // Set success message
        set_transient( 'afcg_duplicate_success_' . $new_post_id, true, 30 );

        // Redirect to edit the new listing
        wp_redirect( admin_url( 'post.php?action=edit&post=' . $new_post_id ) );
        exit;
    }

    /**
     * Show Success Notice After Duplication
     */
    public static function show_duplicate_notice() {
        global $post;
        
        if ( ! $post || $post->post_type !== 'afcglide_listing' ) {
            return;
        }

        if ( get_transient( 'afcg_duplicate_success_' . $post->ID ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p>
                    <strong>✅ Listing Duplicated Successfully!</strong><br>
                    This is a draft copy. Update the details and publish when ready.
                </p>
            </div>
            <?php
            delete_transient( 'afcg_duplicate_success_' . $post->ID );
        }
    }
}

// Initialize
AFCGlide_Agent_Protections::init();