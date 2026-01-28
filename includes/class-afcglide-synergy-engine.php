<?php
namespace AFCGlide\Core;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Synergy Engine v5.0.1
 * High-Performance Data Processor for the Agent Terminal
 * 
 * Enhancements:
 * - Uses Constants class for consistency
 * - Improved SQL security with $wpdb->prepare
 * - Better error handling
 * - Cache layer for stats
 * - Extended bot detection
 */
class AFCGlide_Synergy_Engine {

    public static function init() {
        // Track intelligence only on the frontend
        if ( ! is_admin() ) {
            add_action( 'wp_head', [ __CLASS__, 'track_listing_intelligence' ] );
        }
    }

    /**
     * ASSET INTELLIGENCE: Unique View Tracking
     * Uses a lightweight cookie-check and metadata increment.
     */
    public static function track_listing_intelligence() {
        // Only track single listing pages of our custom post type
        if ( ! is_singular( C::POST_TYPE ) ) {
            return;
        }

        global $post;
        
        // No-Compromise Data: Don't count the site owner or bots
        if ( current_user_can( C::CAP_MANAGE ) || self::is_bot() ) {
            return;
        }

        $post_id = $post->ID;
        $view_cookie = 'afc_viewed_' . $post_id;

        // Check if this user has already viewed this listing
        if ( ! isset( $_COOKIE[ $view_cookie ] ) ) {
            // Increment view count
            $count = get_post_meta( $post_id, C::META_VIEWS, true );
            $count = ( $count === '' ) ? 1 : intval( $count ) + 1;
            update_post_meta( $post_id, C::META_VIEWS, $count );
            
            // Set cookie with 24-hour expiration for unique session tracking
            // Use @ to suppress headers already sent warnings
            @setcookie( 
                $view_cookie, 
                '1', 
                time() + DAY_IN_SECONDS, 
                COOKIEPATH, 
                COOKIE_DOMAIN,
                is_ssl(), // Secure flag
                true      // HTTP only flag
            );
            
            // Optional: Log the view for analytics
            self::log_view_event( $post_id );
        }
    }

    /**
     * THE SYNERGY QUERY
     * Optimized to fetch only what is necessary.
     * 
     * @param int $limit Number of posts to retrieve (-1 for all)
     * @param array $args Additional WP_Query arguments
     * @return \WP_Query
     */
    public static function get_agent_inventory( $limit = -1, $args = [] ) {
        $current_user = wp_get_current_user();
        
        $default_args = [
            'post_type'      => C::POST_TYPE,
            'posts_per_page' => $limit,
            'post_status'    => [ 'publish', 'pending', 'draft', 'sold' ],
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];
        
        // Merge with custom args
        $query_args = wp_parse_args( $args, $default_args );

        // Security: Agents only see their own. Admins see the global inventory.
        if ( ! current_user_can( C::CAP_MANAGE ) ) {
            $query_args['author'] = $current_user->ID;
        }

        return new \WP_Query( $query_args );
    }

    /**
     * EXECUTIVE SUMMARY STATS (Optimized V5.0.1)
     * Direct database summation is faster than looping through WP_Post objects.
     * Now with caching for improved performance.
     * 
     * @param int|null $author_id Optional author ID to filter stats
     * @return array Array containing count and views
     */
    public static function get_synergy_stats( $author_id = null ) {
        global $wpdb;
        
        // Use current user if no author specified
        if ( $author_id === null ) {
            $author_id = get_current_user_id();
        }
        
        // Check cache first (1 hour expiration)
        $cache_key = 'afc_stats_' . $author_id;
        $cached = get_transient( $cache_key );
        
        if ( false !== $cached ) {
            return $cached;
        }

        // Build WHERE clause with proper escaping
        $post_type = C::POST_TYPE;
        
        if ( current_user_can( C::CAP_MANAGE ) && $author_id === get_current_user_id() ) {
            // Admin viewing global stats
            $where_clause = $wpdb->prepare( "p.post_type = %s", $post_type );
        } else {
            // Agent or admin viewing specific author
            $where_clause = $wpdb->prepare( 
                "p.post_type = %s AND p.post_author = %d", 
                $post_type, 
                $author_id 
            );
        }

        // Execute optimized query
        $stats = $wpdb->get_row( 
            "SELECT 
                COUNT(p.ID) as total_count,
                SUM(CAST(pm.meta_value AS UNSIGNED)) as total_views
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm 
                ON p.ID = pm.post_id 
                AND pm.meta_key = '" . esc_sql( C::META_VIEWS ) . "'
            WHERE {$where_clause}
            AND p.post_status IN ('publish', 'pending', 'draft', 'sold')"
        );

        $result = [
            'count' => intval( $stats->total_count ),
            'views' => intval( $stats->total_views )
        ];
        
        // Cache for 1 hour
        set_transient( $cache_key, $result, HOUR_IN_SECONDS );

        return $result;
    }

    /**
     * Get detailed stats by status
     * 
     * @param int|null $author_id Optional author ID
     * @return array Stats broken down by post status
     */
    public static function get_detailed_stats( $author_id = null ) {
        global $wpdb;
        
        if ( $author_id === null ) {
            $author_id = get_current_user_id();
        }
        
        $cache_key = 'afc_detailed_stats_' . $author_id;
        $cached = get_transient( $cache_key );
        
        if ( false !== $cached ) {
            return $cached;
        }

        $post_type = C::POST_TYPE;
        
        if ( current_user_can( C::CAP_MANAGE ) && $author_id === get_current_user_id() ) {
            $where_clause = $wpdb->prepare( "post_type = %s", $post_type );
        } else {
            $where_clause = $wpdb->prepare( 
                "post_type = %s AND post_author = %d", 
                $post_type, 
                $author_id 
            );
        }

        $results = $wpdb->get_results(
            "SELECT post_status, COUNT(*) as count
            FROM {$wpdb->posts}
            WHERE {$where_clause}
            GROUP BY post_status"
        );

        $stats = [
            'publish' => 0,
            'pending' => 0,
            'draft'   => 0,
            'sold'    => 0,
        ];

        foreach ( $results as $row ) {
            if ( isset( $stats[ $row->post_status ] ) ) {
                $stats[ $row->post_status ] = intval( $row->count );
            }
        }

        set_transient( $cache_key, $stats, HOUR_IN_SECONDS );

        return $stats;
    }

    /**
     * Clear stats cache for a specific user
     * Call this when listings are updated
     * 
     * @param int $author_id User ID
     */
    public static function clear_stats_cache( $author_id ) {
        delete_transient( 'afc_stats_' . $author_id );
        delete_transient( 'afc_detailed_stats_' . $author_id );
    }

    /**
     * Calculate total portfolio value
     * 
     * @param int|null $author_id Optional author ID
     * @return float Total value of all listings
     */
    public static function get_portfolio_value( $author_id = null ) {
        global $wpdb;
        
        if ( $author_id === null ) {
            $author_id = get_current_user_id();
        }

        $post_type = C::POST_TYPE;
        
        if ( current_user_can( C::CAP_MANAGE ) && $author_id === get_current_user_id() ) {
            $where_clause = $wpdb->prepare( "p.post_type = %s", $post_type );
        } else {
            $where_clause = $wpdb->prepare( 
                "p.post_type = %s AND p.post_author = %d", 
                $post_type, 
                $author_id 
            );
        }

        $total = $wpdb->get_var(
            "SELECT SUM(CAST(pm.meta_value AS DECIMAL(15,2)))
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm 
                ON p.ID = pm.post_id 
                AND pm.meta_key = '" . esc_sql( C::META_PRICE ) . "'
            WHERE {$where_clause}
            AND p.post_status IN ('publish', 'pending')"
        );

        return floatval( $total );
    }

    /**
     * Helper: Detect common bots to prevent stat inflation
     * Enhanced with more comprehensive bot detection
     * 
     * @return bool
     */
    private static function is_bot() {
        if ( ! isset( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return true; // No user agent = likely a bot
        }

        $user_agent = $_SERVER['HTTP_USER_AGENT'];
        
        // Common bot patterns
        $bot_patterns = [
            'bot', 'crawl', 'slurp', 'spider', 'mediapartners',
            'googlebot', 'bingbot', 'yahoo', 'baiduspider',
            'yandex', 'facebookexternalhit', 'twitterbot',
            'whatsapp', 'slack', 'telegram', 'curl', 'wget'
        ];

        foreach ( $bot_patterns as $pattern ) {
            if ( stripos( $user_agent, $pattern ) !== false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Log view event for analytics (optional)
     * 
     * @param int $post_id Listing ID
     */
    private static function log_view_event( $post_id ) {
        // Only log if analytics logging is enabled
        if ( ! get_option( 'afc_enable_analytics_logging', 0 ) ) {
            return;
        }

        $log_data = [
            'post_id'    => $post_id,
            'timestamp'  => current_time( 'mysql' ),
            'user_agent' => isset( $_SERVER['HTTP_USER_AGENT'] ) ? $_SERVER['HTTP_USER_AGENT'] : '',
            'ip_address' => self::get_user_ip(),
            'referer'    => isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '',
        ];

        // Store in custom table or as post meta
        // Implementation depends on your analytics needs
        do_action( 'afcglide_view_logged', $log_data );
    }

    /**
     * Get user IP address (handles proxies)
     * 
     * @return string
     */
    private static function get_user_ip() {
        $ip_keys = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];

        foreach ( $ip_keys as $key ) {
            if ( isset( $_SERVER[ $key ] ) ) {
                $ip = explode( ',', $_SERVER[ $key ] );
                $ip = trim( $ip[0] );
                
                if ( filter_var( $ip, FILTER_VALIDATE_IP ) ) {
                    return $ip;
                }
            }
        }

        return '0.0.0.0';
    }
}