<?php
namespace AFCGlide\Admin;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Shortcodes v4.2 - THE REAL ESTATE MACHINE
 * Refined for Vogue Green 2026 UI | MacBook Pro Precision
 */
final class AFCGlide_Shortcodes {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_shortcodes' ], 20 );
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_frontend_assets' ] );
    }

    public static function enqueue_frontend_assets() {
        global $post;
        if ( ! is_a( $post, 'WP_Post' ) ) return;
        
        // World-Class Standards: Check content AND Elementor data for shortcodes
        $content = $post->post_content;
        if ( class_exists( '\Elementor\Plugin' ) ) {
            $elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
            if ( is_string( $elementor_data ) ) { $content .= $elementor_data; }
        }

        $has_grid   = has_shortcode( $content, 'afcglide_listings_grid' );
        $has_slider = has_shortcode( $content, 'afcglide_listings_slider' );
        $has_submit = has_shortcode( $content, 'afcglide_submit_listing' ) 
                   || has_shortcode( $content, 'afcglide_submission_form' );

        if ( $has_grid || $has_slider || $has_submit ) {
            wp_enqueue_style( 'afc-global-styles', AFCG_URL . 'assets/css/afcglide-global.css', [], AFCG_VERSION );
        }

        if ( $has_grid || $has_slider ) {
            wp_enqueue_style( 'afc-shortcode-styles', AFCG_URL . 'assets/css/afcglide-shortcodes.css', ['afc-global-styles'], AFCG_VERSION );
            wp_enqueue_script( 'afc-public-js', AFCG_URL . 'assets/js/afcglide-public.js', ['jquery'], AFCG_VERSION, true );
        }
        
        if ( $has_submit ) {
            wp_enqueue_style( 'afc-submission-form', AFCG_URL . 'assets/css/admin-submission.css', ['afc-global-styles'], AFCG_VERSION );
        }
    }

    public static function register_shortcodes() {
        add_shortcode( 'afcglide_login', [ __CLASS__, 'render_login_form' ] );
        add_shortcode( 'afcglide_submit_listing', [ __CLASS__, 'render_submission_form' ] );
        add_shortcode( 'afcglide_submission_form', [ __CLASS__, 'render_submission_form' ] ); // Alias for compatibility
        add_shortcode( 'afcglide_listings_grid', [ __CLASS__, 'render_listing_grid' ] );
        add_shortcode( 'afcglide_listings_slider', [ __CLASS__, 'render_listing_slider' ] );
    }

    /**
     * 1. FEATURED LISTINGS SLIDER (Multi-Instance Safe)
     */
    public static function render_listing_slider( $atts ) {
        $atts = shortcode_atts( [ 'count' => 6 ], $atts );
        $query = new \WP_Query([
            'post_type'      => C::POST_TYPE,
            'posts_per_page' => (int) $atts['count'],
            'post_status'    => 'publish',
        ]);

        if ( ! $query->have_posts() ) return '';

        $slider_id = 'afc-slider-' . wp_generate_password(4, false); // Unique ID for multiple sliders
        ob_start();
        ?>
        <div id="<?php echo $slider_id; ?>" class="afc-vogue-slider-outer">
            <button class="afc-slider-nav afc-slider-prev">‹</button>
            <div class="afc-slider-track">
                <?php while ( $query->have_posts() ) : $query->the_post(); ?>
                    <div class="afc-slider-slide"><?php self::render_listing_card(); ?></div>
                <?php endwhile; ?>
            </div>
            <button class="afc-slider-nav afc-slider-next">›</button>
            <div class="afc-slider-dots"></div>
        </div>
        
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('<?php echo $slider_id; ?>');
            const track = container.querySelector('.afc-slider-track');
            const slides = container.querySelectorAll('.afc-slider-slide');
            const dotsContainer = container.querySelector('.afc-slider-dots');
            let current = 0;
            const getPerView = () => window.innerWidth <= 768 ? 1 : 3;
            
            const updateSlider = () => {
                const perView = getPerView();
                const maxIndex = Math.max(0, slides.length - perView);
                if(current > maxIndex) current = maxIndex;
                track.style.transform = `translateX(-${current * (100 / perView)}%)`;
                
                // Sync Dots
                dotsContainer.innerHTML = '';
                for (let i = 0; i <= maxIndex; i++) {
                    const dot = document.createElement('span');
                    dot.className = 'afc-slider-dot' + (i === current ? ' active' : '');
                    dot.onclick = () => { current = i; updateSlider(); };
                    dotsContainer.appendChild(dot);
                }
            };

            container.querySelector('.afc-slider-next').onclick = () => {
                const max = slides.length - getPerView();
                if(current < max) { current++; updateSlider(); }
            };
            container.querySelector('.afc-slider-prev').onclick = () => {
                if(current > 0) { current--; updateSlider(); }
            };
            
            updateSlider();
            window.addEventListener('resize', updateSlider);
        });
        </script>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * 2. LISTINGS GRID (The High-End Asset Wall)
     */
    public static function render_listing_grid( $atts ) {
        $atts = shortcode_atts( [
            'posts_per_page' => 12, 
            'columns'        => 3,
            'status'         => 'publish',
            'show_search'    => 'yes'
        ], $atts );

        $paged = ( get_query_var( 'paged' ) ) ? get_query_var( 'paged' ) : 1;
        
        $search_term = isset($_GET['afc_query']) ? sanitize_text_field($_GET['afc_query']) : '';
        $max_price   = isset($_GET['afc_max_price']) ? intval($_GET['afc_max_price']) : 0;

        $args = [
            'post_type'      => C::POST_TYPE, 
            'posts_per_page' => (int) $atts['posts_per_page'], 
            'post_status'    => sanitize_text_field( $atts['status'] ),
            'paged'          => $paged,
            's'              => $search_term
        ];

        if ( $max_price > 0 ) {
            $args['meta_query'] = [[
                'key'     => C::META_PRICE, 
                'value'   => $max_price, 
                'type'    => 'NUMERIC', 
                'compare' => '<='
            ]];
        }

        $query = new \WP_Query( $args );
        ob_start();
        
        echo '<div class="afc-grid-wrapper">';
        
        if ( $atts['show_search'] === 'yes' ) {
            self::render_search_bar($search_term, $max_price);
        }

        if ( ! $query->have_posts() ) {
            echo '<div class="afc-no-results">🚫 NO ASSETS MATCHING CRITERIA</div>';
        } else {
            // Precise column handling
            echo '<div class="afcglide-grid-container afc-cols-' . esc_attr($atts['columns']) . '">';
            while ( $query->have_posts() ) { 
                $query->the_post(); 
                self::render_listing_card(); 
            }
            echo '</div>';

            if ( $query->max_num_pages > 1 ) {
                self::render_pagination($query, $paged);
            }
        }
        echo '</div>';
        
        wp_reset_postdata();
        return ob_get_clean();
    }

    private static function render_search_bar($search_term, $max_price) {
        ?>
        <div class="afc-search-terminal">
            <form method="get" action="" class="afc-search-grid">
                <div class="afc-search-input-wrapper">
                    <input type="text" name="afc_query" value="<?php echo esc_attr($search_term); ?>" class="afc-search-input" placeholder="City, Zip, or Asset ID...">
                </div>
                
                <div class="afc-price-select-wrapper">
                    <select name="afc_max_price" class="afc-search-select">
                        <option value="">MAX PRICE (UNLIMITED)</option>
                        <?php 
                        $prices = [500000, 1000000, 2500000, 5000000, 10000000];
                        foreach($prices as $p) {
                            echo '<option value="'.$p.'" '.selected($max_price, $p, false).'>UNDER $'.number_format($p/1000000, 1).'M</option>';
                        }
                        ?>
                    </select>
                </div>
                <button type="submit" class="afc-vogue-btn">FILTER ASSETS</button>
            </form>
        </div>
        <?php
    }

    private static function render_pagination($query, $paged) {
        echo '<div class="afc-pagination-wrapper">';
        echo paginate_links([
            'total'     => $query->max_num_pages,
            'prev_text' => 'PREV',
            'next_text' => 'NEXT',
            'current'   => $paged,
            'type'      => 'list'
        ]);
        echo '</div>';
    }

    public static function render_listing_card() {
        // Enforce a isolated scope for the template include
        $template = AFCG_PATH . 'templates/listing-card.php';
        if ( file_exists( $template ) ) {
            include $template;
        }
    }

    public static function render_submission_form() {
        $template = AFCG_PATH . 'templates/template-submit-listing.php';
        if ( file_exists( $template ) ) {
            ob_start();
            include $template;
            return ob_get_clean();
        }
        return 'Submission template missing.';
    }

    public static function render_login_form() {
        if ( is_user_logged_in() ) {
            return '<div class="afc-logged-in-box">ACCESS GRANTED. <a href="'.wp_logout_url().'">SECURE LOGOUT</a></div>';
        }
        return wp_login_form( ['echo' => false] );
    }
}