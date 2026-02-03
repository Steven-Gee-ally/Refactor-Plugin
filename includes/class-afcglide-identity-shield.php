<?php
namespace AFCGlide\Admin;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Identity Shield v5.0.1
 * Pure Infrastructure Security & Lockdown Logic
 * 
 * CRITICAL FIX: Prevents infinite redirect loop by allowing agents
 * access to AFCGlide admin pages while blocking core WordPress admin.
 */
final class AFCGlide_Identity_Shield {

    public static function init() {
        // 1. Force Login for all pages (The Private Club Logic)
        add_action( 'template_redirect', [ __CLASS__, 'enforce_global_lockdown' ] );

        // 2. Cloak the Admin Bar for non-admins (The "App" Experience)
        add_action( 'after_setup_theme', [ __CLASS__, 'cloak_admin_bar' ] );

        // 3. Block /wp-admin access for Agents (except AFCGlide pages)
        add_action( 'admin_init', [ __CLASS__, 'restrict_admin_access' ] );

        // 4. Custom Login Branding
        add_filter( 'login_headerurl', function() { return home_url(); } );
        add_filter( 'login_headertext', function() { return get_option( C::OPT_SYSTEM_LABEL, 'AFCGlide Infrastructure' ); } );
        
        // 5. SURGICAL STRIKE: Remove update pestering at the server level
        add_action( 'admin_head', function() {
            if ( ! current_user_can( C::CAP_MANAGE ) ) {
                remove_action( 'admin_notices', 'update_nag', 3 );
                remove_action( 'admin_notices', 'maintenance_nag', 10 );
                // Also call the CSS hiding method we built
                self::hide_update_notices_for_agents();
            }
        });
    }

    /**
     * Redirects guest users to login.
     * Managed by the "Backbone" settings toggle.
     */
    public static function enforce_global_lockdown() {
        // Only enforce if the Global Lockdown option is set to '1'
        $lockdown_active = get_option( C::OPT_GLOBAL_LOCKDOWN, '0' );

        if ( '1' !== $lockdown_active ) {
            return; 
        }

        // Allow access to login pages, REST API, and don't redirect if already logged in
        if ( ! is_user_logged_in() && ! self::is_login_page() && ! self::is_rest_request() ) {
            wp_safe_redirect( wp_login_url() );
            exit;
        }
    }

    /**
     * Removes the WordPress Admin Bar for agents.
     * This is vital for the "Intuitive/Easy to Use" requirement.
     */
    public static function cloak_admin_bar() {
        if ( ! current_user_can( C::CAP_MANAGE ) ) {
            show_admin_bar( false );
        }
    }

    /**
     * Prevents Agents from accessing core WordPress admin pages.
     * Allows access to AFCGlide dashboard and related pages.
     * 
     * FIXED: No longer creates infinite redirect loop.
     */
    public static function restrict_admin_access() {
        // Never block AJAX requests (required for the submission form to work!)
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return;
        }
        
        // Never block REST API requests
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return;
        }
        
        // Allow brokers/admins full access
        if ( current_user_can( C::CAP_MANAGE ) ) {
            return;
        }
        
        // CRITICAL FIX: Allow agents to access AFCGlide pages
        if ( self::is_afcglide_admin_page() ) {
            return;
        }
        
        // CRITICAL FIX: Allow agents to access their own profile
        if ( self::is_profile_page() ) {
            return;
        }
        
        // CRITICAL FIX: Allow agents to create/edit their listings
        if ( self::is_listing_edit_page() ) {
            return;
        }
        
        // Block all other admin pages - redirect to AFCGlide dashboard
        wp_safe_redirect( admin_url( 'admin.php?page=' . C::MENU_DASHBOARD ) );
        exit;
    }

    /**
     * Check if current page is an AFCGlide admin page
     */
    private static function is_afcglide_admin_page() {
        if ( ! isset( $_GET['page'] ) ) {
            return false;
        }
        
        // Allow all AFCGlide pages (dashboard, inventory, settings, etc.)
        return strpos( $_GET['page'], 'afcglide' ) === 0;
    }

    /**
     * Check if current page is the profile page
     */
    private static function is_profile_page() {
        global $pagenow;
        return in_array( $pagenow, [ 'profile.php' ] );
    }

    /**
     * Check if current page is for creating/editing listings
     */
    private static function is_listing_edit_page() {
        global $pagenow, $typenow;
        
        // Allow post.php and post-new.php for listing post type
        if ( in_array( $pagenow, [ 'post.php', 'post-new.php' ] ) ) {
            // Check if it's our listing post type
            if ( isset( $_GET['post_type'] ) && $_GET['post_type'] === C::POST_TYPE ) {
                return true;
            }
            
            // Check if editing an existing listing
            if ( isset( $_GET['post'] ) ) {
                $post_type = get_post_type( $_GET['post'] );
                if ( $post_type === C::POST_TYPE ) {
                    return true;
                }
            }
            
            // If no post_type specified but we're on post-new.php, check $typenow
            if ( $pagenow === 'post-new.php' && $typenow === C::POST_TYPE ) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Helper to detect if we are on the login screen
     */
    private static function is_login_page() {
        global $pagenow;
        return in_array( $pagenow, [ 'wp-login.php', 'wp-register.php' ] );
    }

    /**
     * Helper to detect REST API requests
     */
    private static function is_rest_request() {
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return true;
        }
        
        // Check if it's a REST API URL
        if ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], rest_get_url_prefix() ) !== false ) {
            return true;
        }
        
        return false;
    }

    /**
     * Hide WordPress update notices from agents
     * Reduces clutter and maintains professional appearance
     */
    public static function hide_update_notices_for_agents() {
        if ( ! current_user_can( C::CAP_MANAGE ) ) {
            echo '<style>
                .update-nag,
                .updated,
                .notice,
                .error,
                #update-nag,
                .notice-warning,
                .notice-info,
                .notice-error {
                    display: none !important;
                }
                
                /* Keep AFCGlide notices visible */
                .notice.afc-notice,
                .afc-success-portal {
                    display: block !important;
                }
            </style>';
        }
    }

    /**
     * Optional: Log security events for brokers
     */
    public static function log_security_event( $event_type, $details = '' ) {
        if ( ! get_option( 'afc_security_logging', 0 ) ) {
            return;
        }
        
        $log_entry = sprintf(
            '[%s] %s - User: %s (ID: %d) - %s',
            current_time( 'mysql' ),
            $event_type,
            wp_get_current_user()->user_login,
            get_current_user_id(),
            $details
        );
        
        error_log( 'AFCGlide Security: ' . $log_entry );
    }
}