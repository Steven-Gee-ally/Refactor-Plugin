<?php
namespace AFCGlide\Reporting;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Agent Scoreboard & Data Engine
 * Version 5.0.0 - High-Performance Aggregate Logic
 */
class AFCGlide_Scoreboard {

    /**
     * Fetch real-time data using direct SQL for maximum velocity
     */
    public static function get_stats( $user_id = null ) {
        global $wpdb;

        // Create a unique cache key based on the user
        $cache_key = 'afc_stats_' . ( $user_id ?: 'global' );
        $stats = get_transient( $cache_key );

        if ( false !== $stats ) return $stats;

        // Base Query Components
        $post_type = C::POST_TYPE;
        $meta_price = C::META_PRICE;
        $meta_views = C::META_VIEWS;

        $author_query = $user_id ? $wpdb->prepare("AND p.post_author = %d", $user_id) : "";

        // Performance-focused SQL: Fetch only counts, price sums, and view sums in one go
        $results = $wpdb->get_results("
            SELECT 
                p.post_status, 
                COUNT(p.ID) as asset_count, 
                SUM(CAST(m1.meta_value AS UNSIGNED)) as total_price,
                SUM(CAST(m2.meta_value AS UNSIGNED)) as total_views
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} m1 ON (p.ID = m1.post_id AND m1.meta_key = '{$meta_price}')
            LEFT JOIN {$wpdb->postmeta} m2 ON (p.ID = m2.post_id AND m2.meta_key = '{$meta_views}')
            WHERE p.post_type = '{$post_type}' 
            AND p.post_status IN ('publish', 'pending', 'sold')
            {$author_query}
            GROUP BY p.post_status
        ");

        $stats = [
            'active_count'  => 0, 'active_value'  => 0,
            'pending_count' => 0, 'pending_value' => 0,
            'sold_count'    => 0, 'sold_value'    => 0,
            'total_hits'    => 0
        ];

        foreach ( $results as $row ) {
            if ( $row->post_status === 'publish' ) {
                $stats['active_count'] = (int)$row->asset_count;
                $stats['active_value'] = (float)$row->total_price;
            } elseif ( $row->post_status === 'pending' ) {
                $stats['pending_count'] = (int)$row->asset_count;
                $stats['pending_value'] = (float)$row->total_price;
            } elseif ( $row->post_status === 'sold' ) {
                $stats['sold_count'] = (int)$row->asset_count;
                $stats['sold_value'] = (float)$row->total_price;
            }
            $stats['total_hits'] += (int)$row->total_views;
        }

        // Cache for 1 hour, or until a post is saved
        set_transient( $cache_key, $stats, HOUR_IN_SECONDS );

        return $stats;
    }

    /**
     * Render the Top Stats Scoreboard (Modern S-Grade)
     */
    public static function render_scoreboard( $user_id = null ) {
        $stats = self::get_stats( $user_id );
        $primary = get_option(C::OPT_PRIMARY_COLOR, '#6366f1');
        
        ob_start(); ?>
        <style>
            .afc-modern-scoreboard { 
                display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); 
                gap: 20px; margin-bottom: 30px; 
            }
            .afc-stat-node { 
                background: white; padding: 25px; border-radius: 16px; 
                box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); border: 1px solid #e2e8f0;
                transition: transform 0.2s ease;
            }
            .afc-stat-node:hover { transform: translateY(-3px); }
            .afc-stat-label { display: block; font-size: 10px; font-weight: 900; color: #64748b; letter-spacing: 1.5px; margin-bottom: 10px; }
            .afc-stat-value { display: block; font-size: 24px; font-weight: 900; color: #1e293b; margin-bottom: 5px; }
            .afc-stat-sub { display: block; font-size: 12px; font-weight: 600; color: #94a3b8; }
        </style>

        <div class="afc-modern-scoreboard">
            <div class="afc-stat-node" style="border-top: 4px solid #10b981;">
                <span class="afc-stat-label">ACTIVE PORTFOLIO</span>
                <span class="afc-stat-value">$<?php echo self::format_number($stats['active_value']); ?></span>
                <span class="afc-stat-sub"><?php echo $stats['active_count']; ?> Live Assets</span>
            </div>
            <div class="afc-stat-node" style="border-top: 4px solid #f59e0b;">
                <span class="afc-stat-label">ESCROW VOLUME</span>
                <span class="afc-stat-value">$<?php echo self::format_number($stats['pending_value']); ?></span>
                <span class="afc-stat-sub"><?php echo $stats['pending_count']; ?> Under Contract</span>
            </div>
            <div class="afc-stat-node" style="border-top: 4px solid #6366f1;">
                <span class="afc-stat-label">CLOSED CAREER</span>
                <span class="afc-stat-value">$<?php echo self::format_number($stats['sold_value']); ?></span>
                <span class="afc-stat-sub"><?php echo $stats['sold_count']; ?> Settled Deals</span>
            </div>
            <div class="afc-stat-node" style="border-top: 4px solid #1e293b;">
                <span class="afc-stat-label">NETWORK ENGAGEMENT</span>
                <span class="afc-stat-value"><?php echo number_format($stats['total_hits']); ?></span>
                <span class="afc-stat-sub">Interest Pings</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function format_number($num) {
        if ($num >= 1000000) {
            return number_format($num / 1000000, 2) . 'M';
        }
        return number_format($num);
    }
}