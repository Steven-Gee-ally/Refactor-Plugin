<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Identity Shield v5.0
 * World-Class Security Hardening
 * 
 * - Scrub REST API User Endpoints
 * - Block Author Enumeration
 * - Disable XML-RPC
 * - Remove oEmbed Discovery
 */
class AFCGlide_Identity_Shield {

    public static function init() {
        // Enforce Global Lockdown at the highest level
        add_action( 'template_redirect', [ __CLASS__, 'enforce_global_lockdown' ], 1 );

        // Only run if Identity Shield is ACTIVE
        if ( get_option('afc_identity_shield', '0') !== '1' ) {
            return;
        }

        // 1. REST API Scrubber
        add_filter( 'rest_endpoints', [ __CLASS__, 'scrub_rest_api' ] );

        // 2. Author Enumeration Blocker
        add_action( 'template_redirect', [ __CLASS__, 'block_author_enumeration' ] );

        // 3. XML-RPC Disabler
        add_filter( 'xmlrpc_enabled', '__return_false' );
        add_filter( 'xmlrpc_methods', [ __CLASS__, 'disable_xmlrpc_methods' ] );

        // 4. oEmbed Hardening (Prevent user info leaks via embed)
        remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
        remove_action( 'wp_head', 'wp_oembed_add_discovery_links', 10 );
    }

    /**
     * Remove /wp/v2/users endpoints to prevent JSON scraping
     */
    public static function scrub_rest_api( $endpoints ) {
        if ( isset( $endpoints['/wp/v2/users'] ) ) {
            unset( $endpoints['/wp/v2/users'] );
        }
        if ( isset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] ) ) {
            unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
        }
        return $endpoints;
    }

    /**
     * Block /?author=N scans
     */
    public static function block_author_enumeration() {
        if ( is_author() && isset( $_GET['author'] ) ) {
            // Log the attempt (Optional: could add to a security log CPT later)
            // error_log( 'AFCGlide Security: Blocked author enumeration attempt from ' . $_SERVER['REMOTE_ADDR'] );
            
            wp_redirect( home_url() );
            exit;
        }
    }

    /**
     * Aggressively unset XML-RPC methods
     */
    public static function disable_xmlrpc_methods( $methods ) {
        unset( $methods['pingback.ping'] );
        return $methods;
    }

    /**
     * 🚨 GLOBAL LOCKDOWN ENFORCEMENT
     * Rejects access to critical pages before they render
     */
    public static function enforce_global_lockdown() {
        // 1. Check if Lockdown is ON
        if ( get_option('afc_global_lockdown', '0') !== '1' ) {
            return;
        }

        // 2. Allow Managing Brokers & Admins to pass
        if ( current_user_can('manage_options') ) {
            return;
        }

        // 3. Detect Targeted Pages (Submission Page)
        // We check for the 'afc_upload_key' which triggers the submission logic
        // OR if it's the specific submission page (permalink structure dependent, but typically targeted via ID or template match)
        // Ideally, we check if the current query relates to 'afcglide_listing' submission.
        
        global $post;
        $is_submission_page = ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'afcglide_submission_form' ) );
        
        // Also block the "Add New" page if someone tries to brute force the URL
        $is_admin_submission = is_admin() && isset($_GET['post_type']) && $_GET['post_type'] === 'afcglide_listing';

        if ( $is_submission_page || $is_admin_submission ) {
            wp_die( '<h1>🚫 SYSTEM LOCKDOWN ACTIVE</h1><p>The Global Infrastructure is currently in lockdown mode. All submissions are paused.</p>', 'System Lockdown', [ 'response' => 403 ] );
        }
    }
}
