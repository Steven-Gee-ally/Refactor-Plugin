<?php
namespace AFCGlide\Core;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Synergy Engine v5.1.0 - THE BRAIN
 * High-Performance Data Processor, Lead Dispatcher & Co-Broker Logic
 * Engineered for world-class real estate networks.
 */
class AFCGlide_Synergy_Engine {

    /**
     * Initialize Global Intelligence
     */
    public static function init() {
        // Track intelligence only on the frontend
        if ( ! is_admin() ) {
            add_action( 'wp_head', [ __CLASS__, 'track_listing_intelligence' ] );
        }

        // --- THE SYNERGY ROUTER ---
        add_filter( 'login_redirect', [ __CLASS__, 'handle_synergy_routing' ], 10, 3 );

        // --- LEAD INQUIRY AJAX HANDLERS ---
        add_action( 'wp_ajax_afc_submit_inquiry', [ __CLASS__, 'handle_inquiry_submission' ] );
        add_action( 'wp_ajax_nopriv_afc_submit_inquiry', [ __CLASS__, 'handle_inquiry_submission' ] );
    }

    /**
     * THE SYNERGY ROUTER
     * Automatically warps agents to the Command Center upon login.
     */
    public static function handle_synergy_routing( $redirect_to, $request, $user ) {
        if ( is_wp_error( $user ) || ! isset( $user->roles ) ) {
            return $redirect_to;
        }

        $is_broker = in_array( 'administrator', (array) $user->roles ) || in_array( 'managing_broker', (array) $user->roles );
        $target_slug = $is_broker ? 'broker-master-terminal' : 'agent-command-center';
        
        $page = get_page_by_path( $target_slug );
        if ( $page ) {
            return home_url( '/' . $target_slug . '/' );
        }

        return admin_url( 'admin.php?page=' . C::MENU_DASHBOARD );
    }

    /**
     * ASSET INTELLIGENCE: Tracking Views Without Bloat
     */
    public static function track_listing_intelligence() {
        if ( ! is_singular( C::POST_TYPE ) ) return;

        global $post;
        if ( current_user_can( C::CAP_MANAGE ) || self::is_bot() ) return;

        $post_id = $post->ID;
        $view_cookie = 'afc_viewed_' . $post_id;

        if ( ! isset( $_COOKIE[ $view_cookie ] ) ) {
            $count = get_post_meta( $post_id, C::META_VIEWS, true );
            $count = ( $count === '' ) ? 1 : intval( $count ) + 1;
            update_post_meta( $post_id, C::META_VIEWS, $count );
            
            @setcookie( $view_cookie, '1', time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true );
        }
    }

    /**
     * THE GLOBAL INVENTORY QUERY (Co-Broker Enabled)
     * Agents see ALL listings to sell other agents' inventory and split commissions.
     * Their own listings are surgically floated to the top.
     */
    public static function get_agent_inventory( $limit = -1, $args = [] ) {
        $current_user_id = get_current_user_id();
        
        $default_args = [
            'post_type'      => C::POST_TYPE,
            'posts_per_page' => $limit,
            'post_status'    => [ 'publish', 'sold' ], // Global brokerage inventory
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        $query_args = wp_parse_args( $args, $default_args );

        // 🚀 THE SYNERGY SORT: Own assets first, then the rest of the network.
        add_filter( 'posts_orderby', function( $orderby, $query ) use ( $current_user_id ) {
            global $wpdb;
            if ( $query->get( 'post_type' ) === C::POST_TYPE ) {
                $orderby = "CASE WHEN {$wpdb->posts}.post_author = $current_user_id THEN 0 ELSE 1 END ASC, " . $orderby;
            }
            return $orderby;
        }, 10, 2 );

        return new \WP_Query( $query_args );
    }

    /**
     * Fetch Personal Leads for the Agent Pulse
     */
    public static function get_personal_leads( $limit = 5 ) {
        $agent_id = get_current_user_id();
        $leads = get_user_meta( $agent_id, 'afc_recent_leads', true ) ?: [];
        
        if ( empty($leads) ) return [];

        if ( isset($leads['time']) ) { $leads = [ $leads ]; }

        usort( $leads, function($a, $b) {
            return strtotime($b['time']) - strtotime($a['time']);
        });

        return array_slice( $leads, 0, $limit );
    }

    /**
     * MARKETING PULSE STATS
     * Calculates Views and Counts for the specific Agent to track their own reach.
     */
    public static function get_synergy_stats( $author_id = null ) {
        global $wpdb;
        if ( $author_id === null ) $author_id = get_current_user_id();
        
        $cache_key = 'afc_stats_' . $author_id;
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) return $cached;

        $post_type = C::POST_TYPE;
        
        // Surgical Filter: Stats reflect the individual agent's performance
        $where_clause = $wpdb->prepare( "p.post_type = %s AND p.post_author = %d", $post_type, $author_id );

        $stats = $wpdb->get_row( 
            "SELECT COUNT(p.ID) as total_count, SUM(CAST(pm.meta_value AS UNSIGNED)) as total_views
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '" . esc_sql( C::META_VIEWS ) . "'
            WHERE {$where_clause}
            AND p.post_status IN ('publish', 'pending', 'draft', 'sold')"
        );

        $result = ['count' => intval( $stats->total_count ), 'views' => intval( $stats->total_views )];
        set_transient( $cache_key, $result, HOUR_IN_SECONDS );
        return $result;
    }

    /**
     * Detailed Status Breakdown
     */
    public static function get_detailed_stats( $author_id = null ) {
        global $wpdb;
        if ( $author_id === null ) $author_id = get_current_user_id();
        
        $cache_key = 'afc_detailed_stats_' . $author_id;
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) return $cached;

        $post_type = C::POST_TYPE;
        $where_clause = $wpdb->prepare( "post_type = %s AND post_author = %d", $post_type, $author_id );

        $results = $wpdb->get_results(
            "SELECT post_status, COUNT(*) as count
            FROM {$wpdb->posts}
            WHERE {$where_clause}
            GROUP BY post_status"
        );

        $stats = [ 'publish' => 0, 'pending' => 0, 'draft' => 0, 'sold' => 0 ];
        foreach ( $results as $row ) {
            if ( isset( $stats[ $row->post_status ] ) ) {
                $stats[ $row->post_status ] = intval( $row->count );
            }
        }

        set_transient( $cache_key, $stats, HOUR_IN_SECONDS );
        return $stats;
    }

    /**
     * Cache Management
     */
    public static function clear_stats_cache( $author_id ) {
        delete_transient( 'afc_stats_' . $author_id );
        delete_transient( 'afc_detailed_stats_' . $author_id );
    }

    /**
     * Portfolio Valuation Engine
     */
    public static function get_portfolio_value( $author_id = null ) {
        global $wpdb;
        if ( $author_id === null ) $author_id = get_current_user_id();

        $post_type = C::POST_TYPE;
        $where_clause = $wpdb->prepare( "p.post_type = %s AND p.post_author = %d", $post_type, $author_id );

        $total = $wpdb->get_var(
            "SELECT SUM(CAST(pm.meta_value AS DECIMAL(15,2)))
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '" . esc_sql( C::META_PRICE ) . "'
            WHERE {$where_clause}
            AND p.post_status IN ('publish', 'pending')"
        );

        return floatval( $total );
    }

    /**
     * LEAD DISPATCH SYSTEM
     */
    public static function handle_inquiry_submission() {
        check_ajax_referer( C::NONCE_INQUIRY, 'security' );

        $listing_id = intval( $_POST['listing_id'] );
        $lead_name  = sanitize_text_field( $_POST['lead_name'] );
        $lead_email = sanitize_email( $_POST['lead_email'] );
        $lead_phone = sanitize_text_field( $_POST['lead_phone'] );
        $lead_msg   = sanitize_textarea_field( $_POST['lead_message'] );

        $listing = get_post( $listing_id );
        $agent_id = $listing->post_author;

        $alert_data = [
            'listing_title' => $listing->post_title,
            'lead_name'     => $lead_name,
            'lead_email'    => $lead_email,
            'lead_phone'    => $lead_phone,
            'message'       => $lead_msg
        ];

        self::dispatch_agent_alert( $agent_id, $alert_data );
        wp_send_json_success( ['message' => 'Inquiry transmitted to agent successfully.'] );
    }

    private static function dispatch_agent_alert( $agent_id, $data ) {
        $whatsapp = get_user_meta( $agent_id, 'agent_whatsapp', true );
        
        // Log to DB for the Synergy Terminal "Inquiry Pulse"
        self::log_lead_to_db( $agent_id, $data );

        // Future Expansion: Trigger WhatsApp/SMS Gateway
        if ( ! empty( $whatsapp ) ) {
            // World-Class Gateway Logic goes here
        }
    }

    private static function log_lead_to_db( $agent_id, $data ) {
        add_user_meta( $agent_id, 'afc_recent_leads', [
            'time' => current_time('mysql'),
            'data' => $data
        ]);
    }

    /**
     * BOT DETECTION & LOGGING
     */
    private static function is_bot() {
        if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) return true;
        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        $bot_patterns = ['bot', 'crawl', 'spider', 'googlebot', 'bingbot'];
        foreach ( $bot_patterns as $pattern ) {
            if ( stripos( $user_agent, $pattern ) !== false ) return true;
        }
        return false;
    }
}