<?php
namespace AFCGlide\Admin;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide High-End Inventory Management
 * Version 1.0 - S-Grade Aesthetic Table
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
        $is_broker = current_user_can('manage_options');
        
        // Fetch listings
        $args = [
            'post_type'      => C::POST_TYPE,
            'posts_per_page' => -1,
            'post_status'    => ['publish', 'pending', 'sold', 'draft', 'future'],
            'orderby'        => 'date',
            'order'          => 'DESC'
        ];

        if ( ! $is_broker ) {
            $args['author'] = $current_user->ID;
        }

        $listings = get_posts( $args );
        ?>


        <div class="afc-control-center">
            <!-- Top Bar (Refined & Streamlined) -->
            <div class="afc-top-bar" style="background: linear-gradient(90deg, #f0fdf4 0%, #dcfce7 100%); border-bottom: 2px solid #86efac; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center;">
                <div style="font-size: 11px; font-weight: 600; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">Network Operator: <span style="color:#059669; font-weight: 800;"><?php echo esc_html($current_user->display_name); ?></span></div>
                <div style="font-size: 13px; font-weight: 700; color: #059669; letter-spacing: 1px;">💼 Global Asset Inventory</div>
                <div style="font-size: 11px; font-weight: 600; color: #64748b; letter-spacing: 0.5px; text-transform: uppercase;">Assets Detected: <span style="color:#059669; font-weight: 800;"><?php echo count($listings); ?></span></div>
            </div>

            <!-- Hero Section (Clean & Upscale) -->
            <div style="background: linear-gradient(135deg, #fafafa 0%, #f8fafc 100%); padding: 50px 45px; border-bottom: 1px solid #e2e8f0; margin-bottom: 35px;">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <div>
                        <h1 style="margin:0; font-size:28px; font-weight: 600; letter-spacing:-0.5px; color:#1e293b; font-family: 'Inter', -apple-system, sans-serif;">Asset Management Terminal</h1>
                        <p style="margin:12px 0 0; color:#64748b; font-size:15px; font-weight:500; line-height: 1.6;">Monitor, deploy, and refine your high-end listing portfolio with precision.</p>
                    </div>
                    <a href="<?php echo admin_url('post-new.php?post_type=' . C::POST_TYPE); ?>" 
                       style="background: #10b981; 
                              color: white; 
                              padding: 14px 28px; 
                              font-size: 13px; 
                              font-weight: 700; 
                              text-decoration: none; 
                              border-radius: 12px; 
                              letter-spacing: 0.5px;
                              text-transform: uppercase;
                              box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);
                              transition: all 0.2s ease;
                              border: none;
                              display: inline-flex;
                              align-items: center;
                              gap: 8px;"
                       onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 16px rgba(16, 185, 129, 0.35)';"
                       onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(16, 185, 129, 0.25)';">
                        <span style="font-size: 16px;">➕</span> New Asset
                    </a>
                </div>
            </div>

            <!-- Inventory Table -->
            <div class="afc-section" style="background: white !important; border-radius: 24px; padding: 0; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
                <table class="afc-inventory-table" style="width: 100%; border-collapse: collapse; text-align: left;">
                    <thead>
                        <tr style="background: #f8fafc; border-bottom: 2px solid #f1f5f9;">
                            <th style="padding: 25px; font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 1.5px; text-transform: uppercase; width: 80px;">THUMB</th>
                            <th style="padding: 25px; font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 1.5px; text-transform: uppercase;">ASSET IDENTITY</th>
                            <th style="padding: 25px; font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 1.5px; text-transform: uppercase;">SPECIFICATIONS</th>
                            <th style="padding: 25px; font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 1.5px; text-transform: uppercase;">MARKET VALUE</th>
                            <th style="padding: 25px; font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 1.5px; text-transform: uppercase;">STATUS</th>
                            <th style="padding: 25px; font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 1.5px; text-transform: uppercase;">INTEREST</th>
                            <th style="padding: 25px; font-size: 11px; font-weight: 800; color: #64748b; letter-spacing: 1.5px; text-transform: uppercase; text-align: right;">OPERATIONS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ( empty($listings) ) : ?>
                            <tr>
                                <td colspan="6" style="padding: 60px; text-align: center; color: #94a3b8; font-weight: 600; font-style: italic;">
                                    No assets detected in the current node. Initialize a new asset to begin deployment.
                                </td>
                            </tr>
                        <?php else : ?>
                            <?php foreach ( $listings as $listing ) : 
                                $price = get_post_meta( $listing->ID, '_listing_price', true );
                                $beds  = get_post_meta( $listing->ID, '_listing_beds', true );
                                $baths = get_post_meta( $listing->ID, '_listing_baths', true );
                                $sqft  = get_post_meta( $listing->ID, '_listing_sqft', true );
                                $views = get_post_meta( $listing->ID, '_listing_views_count', true ) ?: 0;
                                $thumb = get_the_post_thumbnail_url( $listing->ID, 'thumbnail' ) ?: AFCG_URL . 'assets/images/placeholder.png';
                                $status = $listing->post_status;
                                $status_color = ($status === 'publish') ? '#10b981' : (($status === 'pending') ? '#f59e0b' : (($status === 'sold') ? '#ef4444' : '#64748b'));
                                $status_bg = ($status === 'publish') ? '#ecfdf5' : (($status === 'pending') ? '#fffbeb' : (($status === 'sold') ? '#fef2f2' : '#f8fafc'));
                            ?>
                                <tr style="border-bottom: 1px solid #f1f5f9; transition: all 0.2s;" onmouseover="this.style.background='#fcfdff'" onmouseout="this.style.background='transparent'">
                                    <td style="padding: 20px 25px;">
                                        <div style="width: 60px; height: 60px; border-radius: 12px; overflow: hidden; border: 2px solid #e2e8f0; background: #f1f5f9;">
                                            <img src="<?php echo esc_url($thumb); ?>" style="width: 100%; height: 100%; object-fit: cover;">
                                        </div>
                                    </td>
                                    <td style="padding: 20px 25px;">
                                        <strong style="display: block; font-size: 16px; color: #0f172a; margin-bottom: 4px;"><?php echo esc_html($listing->post_title); ?></strong>
                                        <span style="font-size: 12px; color: #64748b; font-weight: 600;"><?php echo get_the_modified_date('M j, Y', $listing->ID); ?> | Agent: <?php echo get_the_author_meta('display_name', $listing->post_author); ?></span>
                                    </td>
                                    <td style="padding: 20px 25px;">
                                        <div style="display: flex; gap: 15px; font-size: 13px; font-weight: 700; color: #475569;">
                                            <span>🛏️ <?php echo esc_html($beds); ?></span>
                                            <span>🛁 <?php echo esc_html($baths); ?></span>
                                            <span>📐 <?php echo number_format($sqft); ?> ft</span>
                                        </div>
                                    </td>
                                    <td style="padding: 20px 25px;">
                                        <span style="font-size: 15px; font-weight: 900; color: #059669;">$<?php echo number_format((float)$price); ?></span>
                                    </td>
                                    <td style="padding: 20px 25px;">
                                        <span style="display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 10px; font-weight: 900; letter-spacing: 1px; color: <?php echo $status_color; ?>; background: <?php echo $status_bg; ?>; text-transform: uppercase; border: 1px solid <?php echo $status_color; ?>4d;">
                                            <?php 
                                            if ($status === 'publish') echo 'Active';
                                            elseif ($status === 'pending') echo 'Pending';
                                            elseif ($status === 'sold') echo 'Sold';
                                            else echo esc_html($status);
                                            ?>
                                        </span>
                                    </td>
                                    <td style="padding: 20px 25px;">
                                        <div style="font-size: 13px; font-weight: 900; color: #6366f1;">📈 <?php echo number_format($views); ?></div>
                                        <div style="font-size: 9px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">PINGS</div>
                                    </td>
                                    <td style="padding: 20px 25px; text-align: right;">
                                        <div style="display: flex; gap: 8px; justify-content: flex-end;">
                                            <a href="<?php echo get_edit_post_link($listing->ID); ?>" class="afc-mini-btn" style="background:#eff6ff !important; color:#1e40af !important; border:1px solid #bfdbfe; padding: 6px 14px; border-radius: 8px; font-size:11px; font-weight:800; text-decoration:none;">EDIT</a>
                                            <a href="<?php echo get_permalink($listing->ID); ?>" target="_blank" class="afc-mini-btn" style="background:#f0fdf4 !important; color:#166534 !important; border:1px solid #bbf7d0; padding: 6px 14px; border-radius: 8px; font-size:11px; font-weight:800; text-decoration:none;">VIEW</a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <style>
            .afc-mini-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            }
        </style>
        <?php
    }
}
