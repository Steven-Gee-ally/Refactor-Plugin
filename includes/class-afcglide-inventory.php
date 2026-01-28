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
        
        // Pagination
        $paged = isset( $_GET['paged'] ) ? max( 1, intval( $_GET['paged'] ) ) : 1;
        
        // Search
        $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
        
        // Status filter
        $status_filter = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
        
        // Build query args
        $query_args = [
            'paged' => $paged,
            'posts_per_page' => 20,
        ];
        
        // Add search
        if ( ! empty( $search ) ) {
            $query_args['s'] = $search;
        }
        
        // Add status filter
        if ( ! empty( $status_filter ) && $status_filter !== 'all' ) {
            $query_args['post_status'] = $status_filter;
        } else {
            $query_args['post_status'] = [ 'publish', 'pending', 'draft', 'sold' ];
        }
        
        // WORLD-CLASS: Fetch via Synergy Engine for data consistency
        $query = Engine::get_agent_inventory( 20, $query_args );
        
        // Get detailed stats
        $detailed_stats = Engine::get_detailed_stats( $is_broker ? null : $current_user->ID );
        ?>

        <div class="afc-control-center" style="margin-right: 20px;">
            <div class="afc-top-bar" style="background: linear-gradient(90deg, #f0fdf4 0%, #dcfce7 100%); border-bottom: 2px solid #86efac; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; border-radius: 12px 12px 0 0;">
                <div style="font-size: 11px; font-weight: 600; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">Operator: <span style="color:#059669; font-weight: 800;"><?php echo esc_html($current_user->display_name); ?></span></div>
                <div style="font-size: 13px; font-weight: 700; color: #059669; letter-spacing: 1px;">💼 GLOBAL ASSET INVENTORY</div>
                <div style="font-size: 11px; font-weight: 600; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">Total Assets: <span style="color:#059669; font-weight: 800;"><?php echo $query->found_posts; ?></span></div>
            </div>

            <div style="background: white; padding: 40px 45px; border-bottom: 1px solid #e2e8f0; margin-bottom: 0;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; margin-bottom: 30px;">
                    <div>
                        <h1 style="margin:0; font-size:28px; font-weight: 800; letter-spacing:-1px; color:#1e293b;">Asset Management Terminal</h1>
                        <p style="margin:8px 0 0; color:#64748b; font-size:15px; font-weight:500;">Precision monitoring and deployment of your high-end portfolio.</p>
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

            <div class="afc-section" style="background: white !important; border-radius: 0 0 24px 24px; padding: 0; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; border-top: none;">
                <table class="afc-inventory-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #f1f5f9;">
                            <th style="padding: 20px 25px; font-size: 10px; font-weight: 900; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; width: 60px;">THUMB</th>
                            <th style="padding: 20px 25px; font-size: 10px; font-weight: 900; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase;">ASSET IDENTITY</th>
                            <th style="padding: 20px 25px; font-size: 10px; font-weight: 900; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase;">SPECIFICATIONS</th>
                            <th style="padding: 20px 25px; font-size: 10px; font-weight: 900; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase;">MARKET VALUE</th>
                            <th style="padding: 20px 25px; font-size: 10px; font-weight: 900; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase;">STATUS</th>
                            <th style="padding: 20px 25px; font-size: 10px; font-weight: 900; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase;">ENGAGEMENT</th>
                            <th style="padding: 20px 25px; font-size: 10px; font-weight: 900; color: #94a3b8; letter-spacing: 1px; text-transform: uppercase; text-align: right;">OPERATIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( ! $query->have_posts() ) : ?>
                            <tr>
                                <td colspan="7" style="padding: 80px; text-align: center; color: #94a3b8; font-size: 15px; font-weight: 600;">
                                    <?php if ( ! empty( $search ) ) : ?>
                                        No assets found matching "<?php echo esc_html( $search ); ?>". <a href="<?php echo admin_url('admin.php?page=afcglide-inventory'); ?>" style="color:#10b981; text-decoration:none;">Clear search</a>
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
                                        <div style="width: 50px; height: 50px; border-radius: 10px; overflow: hidden; border: 2px solid #f1f5f9; background: #f1f5f9;">
                                            <img src="<?php echo esc_url($thumb); ?>" style="width: 100%; height: 100%; object-fit: cover;">
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
                                            <a href="<?php echo get_edit_post_link( $post_id ); ?>" style="background:#f1f5f9; color:#475569; padding: 8px 12px; border-radius: 6px; font-size:10px; font-weight:800; text-decoration:none; border: 1px solid #e2e8f0;">EDIT</a>
                                            <a href="<?php echo get_permalink( $post_id ); ?>" target="_blank" style="background:#10b981; color:white; padding: 8px 12px; border-radius: 6px; font-size:10px; font-weight:800; text-decoration:none;">VIEW</a>
                                            <a href="<?php echo get_delete_post_link( $post_id ); ?>" onclick="return confirm('Archive this asset?');" style="background:#fef2f2; color:#ef4444; padding: 8px 12px; border-radius: 6px; font-size:10px; font-weight:800; text-decoration:none; border: 1px solid #fecaca;">TRASH</a>
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
            </div>
        </div>
        <?php
    }
}