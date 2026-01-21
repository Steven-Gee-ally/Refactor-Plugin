<?php
namespace AFCGlide\Reporting;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Agent Scoreboard & Data Engine
 * Version 4.0.0 - The Real Estate Machine
 */
class AFCGlide_Scoreboard {

    /**
     * Fetch real-time data for the current agent or global
     */
    public static function get_stats( $user_id = null ) {
        $stats = [
            'active_count'  => 0,
            'active_value'  => 0,
            'pending_count' => 0,
            'pending_value' => 0,
            'sold_count'    => 0,
            'sold_value'    => 0,
            'total_hits'    => 0
        ];

        $args = [
            'post_type'      => C::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => ['publish', 'pending', 'sold']
        ];

        if ( $user_id ) {
            $args['author'] = $user_id;
        }

        $query = new \WP_Query( $args );

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $id = get_the_ID();
                $status = get_post_status( $id );
                $price  = floatval( get_post_meta( $id, C::META_PRICE, true ) );
                $views  = intval( get_post_meta( $id, C::META_VIEWS, true ) );

                if ( $status === 'publish' ) {
                    $stats['active_count']++;
                    $stats['active_value'] += $price;
                } elseif ( $status === 'pending' ) {
                    $stats['pending_count']++;
                    $stats['pending_value'] += $price;
                } elseif ( $status === 'sold' ) {
                    $stats['sold_count']++;
                    $stats['sold_value'] += $price;
                }

                $stats['total_hits'] += $views;
            }
            wp_reset_postdata();
        }

        return $stats;
    }

    /**
     * Render the Top Stats Scoreboard (Modern S-Grade)
     */
    public static function render_scoreboard( $user_id = null ) {
        $stats = self::get_stats( $user_id );
        ob_start(); ?>
        <div class="afc-modern-scoreboard">
            <div class="afc-stat-node" style="border-left: 4px solid #10b981;">
                <span class="afc-stat-label">ACTIVE PORTFOLIO</span>
                <span class="afc-stat-value">$<?php echo number_format( $stats['active_value'] ); ?></span>
                <span class="afc-stat-sub"><?php echo $stats['active_count']; ?> Live Assets</span>
            </div>
            <div class="afc-stat-node" style="border-left: 4px solid #f59e0b;">
                <span class="afc-stat-label">PENDING (ESCROW)</span>
                <span class="afc-stat-value">$<?php echo number_format( $stats['pending_value'] ); ?></span>
                <span class="afc-stat-sub"><?php echo $stats['pending_count']; ?> Under Contract</span>
            </div>
            <div class="afc-stat-node" style="border-left: 4px solid #ef4444;">
                <span class="afc-stat-label">CAREER VOLUME (SOLD)</span>
                <span class="afc-stat-value">$<?php echo number_format( $stats['sold_value'] ); ?></span>
                <span class="afc-stat-sub"><?php echo $stats['sold_count']; ?> Closed Transactions</span>
            </div>
            <div class="afc-stat-node" style="border-left: 4px solid #6366f1;">
                <span class="afc-stat-label">NETWORK ENGAGEMENT</span>
                <span class="afc-stat-value"><?php echo number_format( $stats['total_hits'] ); ?></span>
                <span class="afc-stat-sub">Interest Pings</span>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}