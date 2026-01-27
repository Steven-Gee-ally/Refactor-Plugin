<?php
namespace AFCGlide\Admin;

use AFCGlide\Core\Constants;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Identity Shield
 * Dedicated Security & Lockdown Controller
 */
class AFCGlide_Identity_Shield {

    public static function init() {
        // Enforce the Master Switch Lockdown
        add_action( 'admin_init', [ __CLASS__, 'enforce_lockdown' ] );
    }

    /**
     * Enforce Global Network Lockdown
     * Uses the Brain (Constants) to check the Master Switch.
     */
    public static function enforce_lockdown() {
        // Don't interrupt AJAX or background processes
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;

        // Pull the Master Switch status from the database
        $is_locked = get_option( Constants::OPT_GLOBAL_LOCKDOWN, 0 );
        
        if ( ! $is_locked ) return;

        // If they are an Admin or Managing Broker, grant access
        if ( current_user_can( 'manage_options' ) || current_user_can( Constants::CAP_MANAGE ) ) {
            return;
        }

        // Everyone else gets redirected to the home page with a lockdown status
        wp_safe_redirect( add_query_arg( 'afc_status', 'lockdown', home_url() ) );
        exit;
    }
}