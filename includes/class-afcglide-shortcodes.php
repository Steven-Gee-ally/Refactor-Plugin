<?php
namespace AFCGlide\Admin;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Shortcodes v4.1 - THE REAL ESTATE MACHINE
 */
final class AFCGlide_Shortcodes {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_shortcodes' ], 20 );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
    }

    public static function enqueue_frontend_assets() {
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) ) return;
        
        if ( has_shortcode( $post->post_content, 'afcglide_submit_listing' ) ) {
            wp_enqueue_script( 'jquery' );
            wp_enqueue_style( 'afc-submission-form', AFCG_URL . 'assets/css/admin-submission.css', [], '4.0' );
            wp_localize_script( 'jquery', 'afc_submission_vars', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('afc_nonce'),
            ]);
        }
        
        if ( has_shortcode( $post->post_content, 'afcglide_listings_grid' ) ) {
            wp_enqueue_style( 'afc-grid-styles', AFCG_URL . 'assets/css/afcglide-shortcodes.css', [], '4.0' );
        }
    }

    public static function register_shortcodes() {
        add_shortcode( 'afcglide_login', [ __CLASS__, 'render_login_form' ] );
        add_shortcode( 'afcglide_submit_listing', [ __CLASS__, 'render_submission_form' ] );
        add_shortcode( 'afcglide_listings_grid', [ __CLASS__, 'render_listing_grid' ] );
        
        if ( class_exists('\AFCGlide\Reporting\AFCGlide_Scoreboard') ) {
            add_shortcode( 'afc_scoreboard', [ '\AFCGlide\Reporting\AFCGlide_Scoreboard', 'render_scoreboard' ] );
        }
    }

    public static function render_submission_form() {
        if ( ! is_user_logged_in() ) {
            return '<div class="afc-auth-notice" style="padding:40px; text-align:center; background:#f8fafc; border-radius:12px; border:2px dashed #cbd5e1;">
                        <div style="font-size:48px; margin-bottom:15px;">🔒</div>
                        <h3 style="margin:0 0 10px 0; color:#1e293b; font-weight:800;">Access Denied</h3>
                        <p style="color:#64748b; margin:0;">Please log in to the Command Center to submit listings.</p>
                    </div>';
        }

        ob_start();
        $template_path = AFCG_PATH . 'templates/template-submit-listing.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
        return ob_get_clean();
    }

    /**
     * 2. LISTINGS GRID (The High-End Asset Wall)
     */
    public static function render_listing_grid( $atts ) {
        $atts = shortcode_atts( [
            'posts_per_page' => 12, 
            'columns' => 3,
            'status' => 'publish',
            'show_search' => 'yes'
        ], $atts );

        $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
        
        // Handle Search Queries
        $search_term = isset($_GET['afc_query']) ? sanitize_text_field($_GET['afc_query']) : '';
        $max_price   = isset($_GET['afc_max_price']) ? intval($_GET['afc_max_price']) : 0;

        $args = [
            'post_type'      => C::POST_TYPE, 
            'posts_per_page' => (int) $atts['posts_per_page'], 
            'post_status'    => sanitize_text_field( $atts['status'] ),
            'orderby'        => 'date',
            'order'          => 'DESC',
            'paged'          => $paged,
            's'              => $search_term
        ];

        // Meta Query for Price
        if ( $max_price > 0 ) {
            $args['meta_query'] = [
                [
                    'key'     => C::META_PRICE,
                    'value'   => $max_price,
                    'type'    => 'NUMERIC',
                    'compare' => '<='
                ]
            ];
        }

        $query = new \WP_Query( $args );

        ob_start();
        
        if ( $atts['show_search'] === 'yes' ) {
            self::render_search_bar($search_term, $max_price);
        }

        if ( ! $query->have_posts() ) {
            echo '<div class="afc-no-results" style="padding:60px 20px; text-align:center; background:#f8fafc; border-radius:12px;">
                    <div style="font-size:64px; margin-bottom:20px; opacity:0.3;">🏠</div>
                    <p style="color:#64748b; font-size:16px; margin:0;">No assets found matching your criteria.</p>
                  </div>';
        } else {
            echo '<div class="afcglide-grid-container">';
            while ( $query->have_posts() ) { 
                $query->the_post(); 
                self::render_listing_card(); 
            }
            echo '</div>';

            if ( $query->max_num_pages > 1 ) {
                echo '<div class="afc-pagination" style="margin-top:40px; text-align:center;">';
                echo paginate_links([
                    'total' => $query->max_num_pages,
                    'prev_text' => '← Previous',
                    'next_text' => 'Next →',
                    'current' => $paged
                ]);
                echo '</div>';
            }
        }
        
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * SEARCH BAR COMPONENT (The Pazaaz Window)
     */
    private static function render_search_bar($search_term, $max_price) {
        ?>
        <div class="afc-search-terminal">
            <form method="get" action="" class="afc-search-grid">
                <div class="afc-search-input-wrapper">
                    <span class="afc-search-icon">🔍</span>
                    <input type="text" name="afc_query" value="<?php echo esc_attr($search_term); ?>" class="afc-search-input" placeholder="Search by property title, address, or keyword...">
                </div>
                
                <div style="min-width: 180px;">
                    <select name="afc_max_price" class="afc-search-input" style="padding-left: 20px !important;">
                        <option value="">Max Price (Any)</option>
                        <option value="500000" <?php selected($max_price, 500000); ?>>Under $500k</option>
                        <option value="1000000" <?php selected($max_price, 1000000); ?>>Under $1M</option>
                        <option value="3000000" <?php selected($max_price, 3000000); ?>>Under $3M</option>
                        <option value="5000000" <?php selected($max_price, 5000000); ?>>Under $5M</option>
                        <option value="10000000" <?php selected($max_price, 10000000); ?>>Under $10M</option>
                    </select>
                </div>

                <button type="submit" class="afc-search-btn">Find Assets</button>
            </form>
        </div>
        <?php
    }

    public static function render_listing_card() {
        $template_path = AFCG_PATH . 'templates/listing-card.php';
        if ( file_exists( $template_path ) ) {
            include $template_path;
        }
    }

    public static function render_login_form() {
        if ( is_user_logged_in() ) {
            $user = wp_get_current_user();
            return '<div class="afc-success-box" style="padding:40px; background:#ecfdf5; border-radius:16px; text-align:center;">
                        <h3 style="margin:0; color:#065f46;">Welcome, ' . esc_html($user->display_name) . '</h3>
                        <p style="margin:10px 0 0; color:#059669;">Command Center Authenticated.</p>
                    </div>';
        }
        return wp_login_form( ['echo' => false] );
    }
}