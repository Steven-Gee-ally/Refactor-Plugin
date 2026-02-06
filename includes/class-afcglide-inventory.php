<?php
namespace AFCGlide\Admin;

use AFCGlide\Core\Constants as C;
use AFCGlide\Core\AFCGlide_Synergy_Engine as Engine;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide High-End Inventory Management
 * Version 5.0.1 - S-Grade Asset Terminal
 * 
 * Enhancements:
 * - Uses Constants class consistently
 * - Added pagination (20 per page)
 * - Added search functionality
 * - Fixed meta key references
 * - Performance optimizations
 */
class AFCGlide_Inventory {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'register_inventory_page' ], 20 );
    }

    public static function register_inventory_page() {
        add_submenu_page(
            'afcglide-dashboard',
            'Property Inventory',
            '💼 Property Inventory',
            'read',
            'afcglide-inventory',
            [ __CLASS__, 'render_inventory_screen' ]
        );
    }
    public static function render_inventory_screen() {
        $current_user = wp_get_current_user();
        $is_broker = current_user_can( C::CAP_MANAGE );
        
        // Pagination & Filters
        $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        
        $query = self::get_inventory_query([
            'paged' => $paged,
            's' => $search,
            'status' => $status_filter
        ]);
        
        $detailed_stats = Engine::get_detailed_stats( $is_broker ? null : $current_user->ID );
        ?>
        <div class="afc-control-center" style="margin-right: 20px;">
            <?php self::render_inventory_table( $query, $detailed_stats ); ?>
        </div>
        <?php
    }

    /**
     * WORLD-CLASS: Helper to get inventory query
     */
    public static function get_inventory_query( $args = [] ) {
        $defaults = [
            'paged' => 1,
            'posts_per_page' => 20,
            's' => '',
            'status' => 'all'
        ];
        $params = wp_parse_args( $args, $defaults );
        
        $query_args = [
            'paged' => $params['paged'],
            'posts_per_page' => $params['posts_per_page'],
        ];
        
        if ( ! empty( $params['s'] ) ) {
            $query_args['s'] = $params['s'];
        }
        
        if ( ! empty( $params['status'] ) && $params['status'] !== 'all' ) {
            $query_args['post_status'] = $params['status'];
        } else {
            $query_args['post_status'] = [ 'publish', 'pending', 'draft', 'sold' ];
        }
        
        return Engine::get_agent_inventory( $params['posts_per_page'], $query_args );
    }

    /**
     * WORLD-CLASS: Refactored to allow embedding in other pages (Dashboard merge)
     */
    public static function render_inventory_table( $query, $detailed_stats ) {
        $current_user = wp_get_current_user();
        $is_broker = current_user_can( C::CAP_MANAGE );
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        ?>
            <div class="afc-inventory-top-blue">
                <div class="op-label">OPERATOR: <span><?php echo esc_html($current_user->display_name); ?></span></div>
                <div class="hub-label">💼 GLOBAL ASSET MANAGEMENT HUB</div>
                <div class="count-label">TOTAL ASSETS: <span><?php echo $query->found_posts; ?></span></div>
            </div>

            <div style="background: white; padding: 60px 80px; border-bottom: 1px solid #e2e8f0; margin: 0 -30px 30px -30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 30px;">
                    <div>
                        <h1 style="margin:0; font-size:32px; font-weight: 900; letter-spacing:-1.5px; color:#064e3b;">Global Asset Management Hub</h1>
                        <p style="margin:8px 0 0; color:#166534; font-size:16px; font-weight:600; opacity:0.8;">Precision monitoring and multi-channel deployment of the company portfolio.</p>
                    </div>
                    <a href="<?php echo admin_url('post-new.php?post_type=' . C::POST_TYPE); ?>" 
                       style="background: #10b981; color: white; padding: 14px 28px; font-size: 12px; font-weight: 800; text-decoration: none; border-radius: 8px; text-transform: uppercase; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2); display: inline-flex; align-items: center; gap: 8px;">
                        <span>➕</span> DEPLOY NEW ASSET
                    </a>
                </div>

                <!-- Stats Summary -->
                <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 30px;">
                    <div style="background: #ecfdf5; border: 1px solid #d1fae5; padding: 15px; border-radius: 10px;">
                        <div style="font-size: 10px; font-weight: 800; color: #065f46; text-transform: uppercase; margin-bottom: 5px;">Active</div>
                        <div style="font-size: 24px; font-weight: 900; color: #10b981;"><?php echo $detailed_stats['publish']; ?></div>
                    </div>
                    <div style="background: #fffbeb; border: 1px solid #fef3c7; padding: 15px; border-radius: 10px;">
                        <div style="font-size: 10px; font-weight: 800; color: #92400e; text-transform: uppercase; margin-bottom: 5px;">Pending</div>
                        <div style="font-size: 24px; font-weight: 900; color: #f59e0b;"><?php echo $detailed_stats['pending']; ?></div>
                    </div>
                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 10px;">
                        <div style="font-size: 10px; font-weight: 800; color: #475569; text-transform: uppercase; margin-bottom: 5px;">Drafts</div>
                        <div style="font-size: 24px; font-weight: 900; color: #64748b;"><?php echo $detailed_stats['draft']; ?></div>
                    </div>
                    <div style="background: #fef2f2; border: 1px solid #fee2e2; padding: 15px; border-radius: 10px;">
                        <div style="font-size: 10px; font-weight: 800; color: #991b1b; text-transform: uppercase; margin-bottom: 5px;">Sold</div>
                        <div style="font-size: 24px; font-weight: 900; color: #ef4444;"><?php echo $detailed_stats['sold']; ?></div>
                    </div>
                </div>

                <!-- Search & Filters -->
                <form method="get" action="" style="display: flex; gap: 15px; align-items: center;">
                    <input type="hidden" name="page" value="afcglide-inventory">
                    
                    <div style="flex: 1;">
                        <input type="text" name="s" value="<?php echo esc_attr( $search ); ?>" placeholder="🔍 Search assets..." style="width: 100%; padding: 12px 18px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-weight: 600;">
                    </div>
                    
                    <select name="status" style="padding: 12px 18px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 14px; font-weight: 600; cursor: pointer;">
                        <option value="all" <?php selected( $status_filter, '' ); ?>>All Statuses</option>
                        <option value="publish" <?php selected( $status_filter, 'publish' ); ?>>Active</option>
                        <option value="pending" <?php selected( $status_filter, 'pending' ); ?>>Pending</option>
                        <option value="draft" <?php selected( $status_filter, 'draft' ); ?>>Drafts</option>
                        <option value="sold" <?php selected( $status_filter, 'sold' ); ?>>Sold</option>
                    </select>
                    
                    <button type="submit" style="background: #6366f1; color: white; padding: 12px 24px; border: none; border-radius: 10px; font-size: 14px; font-weight: 800; cursor: pointer; text-transform: uppercase;">
                        Filter
                    </button>
                    
                    <?php if ( ! empty( $search ) || ! empty( $status_filter ) ) : ?>
                        <a href="<?php echo admin_url('admin.php?page=afcglide-inventory'); ?>" style="background: #f1f5f9; color: #64748b; padding: 12px 24px; border-radius: 10px; font-size: 14px; font-weight: 800; text-decoration: none; display: inline-block;">
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="afc-section" style="background: white !important; border-radius: 0 0 24px 24px; padding: 0; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; border-top: none; margin: -30px -30px -30px -30px;">
                <table class="afc-inventory-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: #60a5fa;">
                            <th style="padding: 22px 25px; font-size: 11px; font-weight: 900; color: #ffffff; letter-spacing: 1.5px; text-transform: uppercase; width: 80px; border-top-left-radius: 20px;">THUMB</th>
                            <th style="padding: 22px 25px; font-size: 11px; font-weight: 900; color: #ffffff; letter-spacing: 1.5px; text-transform: uppercase;">ASSET IDENTITY</th>
                            <th style="padding: 22px 25px; font-size: 11px; font-weight: 900; color: #ffffff; letter-spacing: 1.5px; text-transform: uppercase;">SPECIFICATIONS</th>
                            <th style="padding: 22px 25px; font-size: 11px; font-weight: 900; color: #ffffff; letter-spacing: 1.5px; text-transform: uppercase;">MARKET VALUE</th>
                            <th style="padding: 22px 25px; font-size: 11px; font-weight: 900; color: #ffffff; letter-spacing: 1.5px; text-transform: uppercase;">STATUS</th>
                            <th style="padding: 22px 25px; font-size: 11px; font-weight: 900; color: #ffffff; letter-spacing: 1.5px; text-transform: uppercase;">ENGAGEMENT</th>
                            <th style="padding: 22px 25px; font-size: 11px; font-weight: 900; color: #ffffff; letter-spacing: 1.5px; text-transform: uppercase; text-align: right; border-top-right-radius: 20px;">OPERATIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! $query->have_posts() ) : ?>
                            <tr>
                                <td colspan="7" style="padding: 80px; text-align: center; color: #94a3b8; font-size: 15px; font-weight: 600;">
                                    <?php if ( ! empty( $search ) ) : ?>
                                        No assets found matching "<?php echo esc_html( $search ); ?>". <a href="<?php echo admin_url('admin.php?page=afcglide-dashboard'); ?>" style="color:#10b981; text-decoration:none;">Clear search</a>
                                    <?php else : ?>
                                        No assets detected. Ready to <a href="<?php echo admin_url('post-new.php?post_type=' . C::POST_TYPE); ?>" style="color:#10b981; text-decoration:none;">initialize your first deployment</a>?
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php while ( $query->have_posts() ) : $query->the_post();
                                $post_id = get_the_ID();
                                $post = get_post( $post_id );
                                
                                // FIXED: Use Constants class consistently
                                $price = C::get_meta( $post_id, C::META_PRICE );
                                $beds  = C::get_meta( $post_id, C::META_BEDS );
                                $baths = C::get_meta( $post_id, C::META_BATHS );
                                $sqft  = C::get_meta( $post_id, C::META_SQFT );
                                $views = C::get_meta( $post_id, C::META_VIEWS ) ?: 0;
                                
                                $thumb = get_the_post_thumbnail_url( $post_id, 'thumbnail' ) ?: 'https://via.placeholder.com/150?text=AFC';
                                
                                $status = $post->post_status;
                                $status_config = [
                                    'publish' => ['label' => 'Active', 'color' => '#10b981', 'bg' => '#ecfdf5'],
                                    'pending' => ['label' => 'Pending', 'color' => '#f59e0b', 'bg' => '#fffbeb'],
                                    'sold'    => ['label' => 'Sold', 'color' => '#ef4444', 'bg' => '#fef2f2'],
                                    'draft'   => ['label' => 'Draft', 'color' => '#64748b', 'bg' => '#f8fafc'],
                                ];
                                
                                $status_display = $status_config[ $status ] ?? ['label' => ucfirst($status), 'color' => '#64748b', 'bg' => '#f8fafc'];
                            ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; transition: all 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 15px 25px;">
                                        <div style="width: 50px; height: 50px; border-radius: 10px; overflow: hidden; border: 2px solid #86efac; background: #f1f5f9; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
                                            <img src="<?php echo esc_url($thumb); ?>" style="width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s;" onmouseover="this.style.transform='scale(1.1)'" onmouseout="this.style.transform='scale(1)'">
                                        </div>
                                    </td>
                                    <td style="padding: 15px 25px;">
                                        <strong style="display: block; font-size: 15px; color: #1e293b; margin-bottom: 2px;"><?php echo esc_html( get_the_title() ); ?></strong>
                                        <span style="font-size: 11px; color: #94a3b8; font-weight: 600; text-transform: uppercase;">
                                            <?php echo get_the_modified_date('M j, Y'); ?> 
                                            <?php if ( $is_broker ) : ?>
                                                | AGENT: <?php echo strtoupper( get_the_author_meta('display_name', $post->post_author) ); ?>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px 25px;">
                                        <div style="display: flex; gap: 12px; font-size: 12px; font-weight: 700; color: #64748b;">
                                            <span>🛏️ <?php echo esc_html( $beds ?: '0' ); ?></span>
                                            <span>🛁 <?php echo esc_html( $baths ?: '0' ); ?></span>
                                            <span>📐 <?php echo $sqft ? number_format( $sqft ) : '0'; ?></span>
                                        </div>
                                    </td>
                                    <td style="padding: 15px 25px;">
                                        <span style="font-size: 14px; font-weight: 800; color: #059669;">
                                            <?php echo $price ? '$' . number_format( (float)$price ) : 'TBD'; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px 25px;">
                                        <span style="display: inline-block; padding: 4px 10px; border-radius: 6px; font-size: 9px; font-weight: 900; color: <?php echo $status_display['color']; ?>; background: <?php echo $status_display['bg']; ?>; border: 1px solid <?php echo $status_display['color']; ?>33; text-transform: uppercase;">
                                            <?php echo $status_display['label']; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 15px 25px;">
                                        <div style="font-size: 14px; font-weight: 800; color: #6366f1;">
                                            <?php echo number_format( $views ); ?> 
                                            <span style="font-size: 8px; opacity: 0.6; margin-left: 2px;">VIEWS</span>
                                        </div>
                                        <?php if ( $views > 50 ) : ?>
                                            <div style="font-size: 8px; font-weight: 900; color: #f43f5e; text-transform: uppercase; margin-top: 2px;">🔥 HIGH INTEREST</div>
                                        <?php endif; ?>
                                    </td>
                                    <td style="padding: 15px 25px; text-align: right;">
                                        <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                            <a href="<?php echo get_edit_post_link( $post_id ); ?>" style="background:white; color:#166534; padding: 8px 14px; border-radius: 8px; font-size:10px; font-weight:800; text-decoration:none; border: 2px solid #86efac; transition: 0.2s;" onmouseover="this.style.background='#f0fdf4'" onmouseout="this.style.background='white'">EDIT</a>
                                            <a href="<?php echo get_permalink( $post_id ); ?>" target="_blank" style="background:#10b981; color:white; padding: 8px 14px; border-radius: 8px; font-size:10px; font-weight:800; text-decoration:none; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);">VIEW</a>
                                            <a href="<?php echo get_delete_post_link( $post_id ); ?>" onclick="return confirm('Archive this asset?');" style="background:#fef2f2; color:#ef4444; padding: 8px 14px; border-radius: 8px; font-size:10px; font-weight:800; text-decoration:none; border: 2px solid #fecaca;">TRASH</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; wp_reset_postdata(); ?>
                        <?php endif; ?>
                    </tbody>
                </table>

                <!-- Pagination -->
                <?php if ( $query->max_num_pages > 1 ) : ?>
                    <div style="padding: 30px 45px; background: #f8fafc; border-top: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center;">
                        <div style="font-size: 13px; color: #64748b; font-weight: 600;">
                            Showing <?php echo ( ( $paged - 1 ) * 20 ) + 1; ?>-<?php echo min( $paged * 20, $query->found_posts ); ?> of <?php echo $query->found_posts; ?> assets
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <?php if ( $paged > 1 ) : ?>
                                <a href="<?php echo add_query_arg( 'paged', $paged - 1 ); ?>" style="background: white; color: #475569; padding: 10px 20px; border-radius: 8px; font-size: 12px; font-weight: 800; text-decoration: none; border: 1px solid #e2e8f0;">
                                    ← PREVIOUS
                                </a>
                            <?php endif; ?>
                            
                            <?php if ( $paged < $query->max_num_pages ) : ?>
                                <a href="<?php echo add_query_arg( 'paged', $paged + 1 ); ?>" style="background: #10b981; color: white; padding: 10px 20px; border-radius: 8px; font-size: 12px; font-weight: 800; text-decoration: none;">
                                    NEXT →
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- 🛡️ INFRASTRUCTURE TERMINAL FOOTER -->
                <div style="padding: 25px 45px; background: #f8fafc; border-top: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; border-radius: 0 0 24px 24px;">
                    <div style="font-size: 10px; font-weight: 800; color: #94a3b8; letter-spacing: 1.5px; text-transform: uppercase;">
                        AFC Glide Global Infrastructure at 2026
                    </div>
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span style="height: 1px; width: 40px; background: #e2e8f0;"></span>
                        <div style="font-size: 10px; font-weight: 900; color: #10b981; letter-spacing: 1px; display: flex; align-items: center; gap: 8px;">
                            <span style="width: 8px; height: 8px; background: #10b981; border-radius: 50%; box-shadow: 0 0 10px rgba(16, 185, 129, 0.4);"></span>
                            SYSTEM ACTIVE VERSION 6.91
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}