<?php
namespace AFCGlide\Reporting;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Agent Scoreboard & Inventory
 * Version 1.2.0 - The Unified Command Center
 */
class AFCGlide_Scoreboard {

    /**
     * Logic: Fetch real-time data for the current agent
     */
    public static function get_agent_stats( $user_id = 0 ) {
        if ( ! $user_id ) $user_id = get_current_user_id();

        $args = [
            'post_type'      => 'afcglide_listing',
            'posts_per_page' => -1,
            'author'         => $user_id,
            'post_status'    => ['publish', 'pending', 'draft']
        ];

        $query = new \WP_Query( $args );
        
        $stats = [
            'total_value'   => 0,
            'active_count'  => 0,
            'pending_count' => 0,
            'sold_count'    => 0
        ];

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $id = get_the_ID();
                $status = get_post_meta( $id, '_listing_status', true ) ?: 'active';
                $price  = floatval( get_post_meta( $id, '_listing_price', true ) );

                if ( isset( $stats[$status . '_count'] ) ) {
                    $stats[$status . '_count']++;
                }

                if ( $status !== 'sold' ) {
                    $stats['total_value'] += $price;
                }
            }
            wp_reset_postdata();
        }
        return $stats;
    }

    /**
     * UI: Render the Top Stats Scoreboard
     */
    public static function render_scoreboard() {
        $stats = self::get_agent_stats();
        ob_start(); ?>
        <div class="afc-scoreboard-container">
            <div class="afc-stat-card">
                <span class="afc-stat-label">PORTFOLIO VALUE</span>
                <span class="afc-stat-value">$<?php echo number_format( $stats['total_value'] / 1000000, 1 ); ?>M</span>
                <div class="afc-stat-bar"><div class="fill" style="width: 100%;"></div></div>
            </div>
            <div class="afc-stat-card">
                <span class="afc-stat-label">ACTIVE ASSETS</span>
                <span class="afc-stat-value"><?php echo $stats['active_count']; ?></span>
                <div class="afc-stat-indicator active">MARKET LIVE</div>
            </div>
            <div class="afc-stat-card">
                <span class="afc-stat-label">PENDING</span>
                <span class="afc-stat-value"><?php echo $stats['pending_count']; ?></span>
                <div class="afc-stat-indicator pending">ESCROW</div>
            </div>
            <div class="afc-stat-card">
                <span class="afc-stat-label">CLOSED</span>
                <span class="afc-stat-value"><?php echo $stats['sold_count']; ?></span>
                <div class="afc-stat-indicator sold">SOLD</div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * UI: Render the Inventory Management Table
     */
    public static function render_inventory_table() {
        $user_id = get_current_user_id();
        $args = [
            'post_type'      => 'afcglide_listing',
            'posts_per_page' => -1,
            'author'         => $user_id,
            'post_status'    => ['publish', 'draft', 'pending']
        ];

        $query = new \WP_Query( $args );

        ob_start(); ?>
        <div class="afc-inventory-wrapper">
            <div class="afc-inventory-header">
                <h3>Agent Inventory Management</h3>
                <span class="afc-count"><?php echo $query->found_posts; ?> Total Assets</span>
            </div>
            
            <table class="afc-inventory-table">
                <thead>
                    <tr>
                        <th>Asset Details</th>
                        <th>Status</th>
                        <th>Price</th>
                        <th>Date</th>
                        <th style="text-align:right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post(); 
                        $id = get_the_ID();
                        $price = get_post_meta($id, '_listing_price', true);
                        $status = get_post_meta($id, '_listing_status', true) ?: 'active';
                        // Keep the edit link within our custom Dashboard logic
                        $edit_link = admin_url( 'post.php?post=' . $id . '&action=edit' );
                    ?>
                    <tr>
                        <td class="afc-prop-cell">
                            <div class="afc-mini-thumb">
                                <?php if (has_post_thumbnail()) : the_post_thumbnail('thumbnail'); else : echo '🏙️'; endif; ?>
                            </div>
                            <div class="afc-prop-info">
                                <strong><?php the_title(); ?></strong>
                                <span>ID: #<?php echo $id; ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="afc-status-pill <?php echo esc_attr($status); ?>">
                                <?php echo strtoupper($status); ?>
                            </span>
                        </td>
                        <td class="afc-price-cell">$<?php echo number_format((float)$price); ?></td>
                        <td class="afc-date-cell"><?php echo get_the_date('M j'); ?></td>
                        <td class="afc-actions-cell" style="text-align:right;">
                            <a href="<?php echo $edit_link; ?>" class="afc-btn-icon" title="Edit Asset">✏️</a>
                            <a href="<?php the_permalink(); ?>" class="afc-btn-icon" title="View Public" target="_blank">👁️</a>
                        </td>
                    </tr>
                    <?php endwhile; wp_reset_postdata(); else : ?>
                    <tr><td colspan="5" class="afc-empty">No assets assigned to your profile.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <style>
            /* Dashboard Combined Styles */
            .afc-scoreboard-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
            .afc-stat-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
            .afc-stat-label { font-size: 10px; font-weight: 800; color: #94a3b8; letter-spacing: 0.5px; display: block; margin-bottom: 5px; }
            .afc-stat-value { font-size: 24px; font-weight: 900; color: #0f172a; display: block; margin-bottom: 10px; }
            .afc-stat-bar { height: 4px; background: #f1f5f9; border-radius: 10px; overflow: hidden; }
            .afc-stat-bar .fill { height: 100%; background: #10b981; }
            .afc-stat-indicator { font-size: 9px; font-weight: 800; padding: 3px 8px; border-radius: 4px; display: inline-block; }
            .afc-stat-indicator.active { background: #ecfdf5; color: #10b981; }
            .afc-stat-indicator.pending { background: #fffbeb; color: #f59e0b; }
            .afc-stat-indicator.sold { background: #fef2f2; color: #ef4444; }

            .afc-inventory-wrapper { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; }
            .afc-inventory-header { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; background: #fafafa; }
            .afc-inventory-header h3 { margin: 0; font-size: 14px; font-weight: 800; color: #1e293b; text-transform: uppercase; }
            .afc-inventory-table { width: 100%; border-collapse: collapse; }
            .afc-inventory-table th { text-align: left; padding: 12px 20px; font-size: 10px; font-weight: 800; color: #94a3b8; text-transform: uppercase; border-bottom: 1px solid #f1f5f9; }
            .afc-inventory-table td { padding: 12px 20px; border-bottom: 1px solid #f8fafc; font-size: 13px; vertical-align: middle; }
            .afc-prop-cell { display: flex; align-items: center; gap: 12px; }
            .afc-mini-thumb { width: 40px; height: 40px; border-radius: 6px; overflow: hidden; background: #f1f5f9; }
            .afc-mini-thumb img { width: 100%; height: 100%; object-fit: cover; }
            .afc-prop-info strong { display: block; color: #1e293b; line-height: 1.2; }
            .afc-prop-info span { font-size: 10px; color: #94a3b8; }
            .afc-status-pill { font-size: 9px; font-weight: 800; padding: 3px 8px; border-radius: 4px; }
            .afc-status-pill.active { background: #ecfdf5; color: #059669; }
            .afc-status-pill.pending { background: #fffbeb; color: #d97706; }
            .afc-status-pill.sold { background: #fef2f2; color: #dc2626; }
            .afc-btn-icon { text-decoration: none; font-size: 16px; margin-left: 10px; opacity: 0.6; transition: 0.2s; }
            .afc-btn-icon:hover { opacity: 1; }
        </style>
        <?php
        return ob_get_clean();
    }
}