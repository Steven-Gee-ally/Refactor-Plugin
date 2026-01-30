<?php
namespace AFCGlide\Admin;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Shortcodes v5.0 - THE SYNERGY TERMINAL MASTER
 * World-Class Real Estate Infrastructure | No Shortcuts
 */
final class AFCGlide_Shortcodes {

    /**
     * Initialize Shortcodes
     */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_shortcodes' ], 20 );
    }

    /**
     * Register all available shortcodes for the plugin
     */
    public static function register_shortcodes() {
        // Agent Terminal
        add_shortcode( 'afc_agent_inventory', [ __CLASS__, 'render_agent_inventory' ] );
        
        // Forms & Auth
        add_shortcode( 'afcglide_login', [ __CLASS__, 'render_login_form' ] );
        add_shortcode( 'afcglide_submit_listing', [ __CLASS__, 'render_submission_form' ] );
        add_shortcode( 'afcglide_submission_form', [ __CLASS__, 'render_submission_form' ] );
        
        // Public Displays
        add_shortcode( 'afcglide_listings_grid', [ __CLASS__, 'render_listing_grid' ] );
        add_shortcode( 'afcglide_listings_slider', [ __CLASS__, 'render_listing_slider' ] );
    }

   /**
     * 1. THE SYNERGY TERMINAL (AGENT INVENTORY)
     * High-end dashboard interface for agents to manage assets.
     */
    public static function render_agent_inventory() {
        if ( ! is_user_logged_in() ) {
            return '<div class="afc-terminal-error">⚠️ SYNERGY AUTHENTICATION REQUIRED. PLEASE LOG IN.</div>';
        }

        // Connect to the Synergy Engine for Data and Query
        $stats = \AFCGlide\Core\AFCGlide_Synergy_Engine::get_synergy_stats();
        $leads = \AFCGlide\Core\AFCGlide_Synergy_Engine::get_personal_leads();
        $user  = wp_get_current_user();
        $query = \AFCGlide\Core\AFCGlide_Synergy_Engine::get_agent_inventory();

        ob_start(); ?>

        <div class="afc-synergy-terminal-wrapper">
            
            <div class="afc-synergy-header">
                <!-- 🏢 BRAND IDENTITY PIN -->
                <div class="afc-brand-identity">
                    <div class="afc-main-logo">
                        <div class="afc-logo-icon-wrap">
                            <span class="dashicons dashicons-building"></span>
                        </div>
                        <div class="afc-logo-text">
                            <strong><?php echo esc_html(get_option('afc_system_label', 'AFCGlide')); ?></strong>
                            <span>PREMIUM ASSET NETWORK</span>
                        </div>
                    </div>
                </div>

                <div class="afc-welcome-meta">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <span class="afc-avatar-ring"><?php echo get_avatar($user->ID, 48); ?></span>
                        <div>
                            <h1>Welcome, <?php echo esc_html($user->display_name); ?></h1>
                            <p>System Status: <span class="afc-status-online">● Online</span> | <span style="text-transform: uppercase; font-size: 10px; font-weight: 700;"><?php echo implode(', ', $user->roles); ?></span></p>
                        </div>
                    </div>

                    <div class="afc-focus-control" style="display: flex; align-items: center; gap: 10px; background: #eff6ff; padding: 8px 15px; border-radius: 30px; border: 1px solid #bfdbfe;">
                        <span style="font-size: 10px; font-weight: 800; color: #1e40af; letter-spacing: 0.5px;">FOCUS MODE</span>
                        <label class="afc-switch">
                            <?php $focus_mode = get_user_meta($user->ID, 'afc_focus_mode', true) === '1'; ?>
                            <input type="checkbox" id="afc-focus-toggle" <?php checked($focus_mode); ?>>
                            <span class="switch-slider"></span>
                        </label>
                    </div>
                </div>
                
                <!-- THE PERFORMANCE HUB (Marketing Pulse) -->
                <div class="afc-marketing-pulse">
                    <div class="afc-pulse-header">
                        <h4>MARKETING PULSE</h4>
                        <span>Real-time Listing Traction</span>
                    </div>
                    <div class="afc-pulse-grid">
                        <div class="afc-pulse-item">
                            <span class="afc-label">TOTAL REACH</span>
                            <strong><?php echo number_format($stats['views']); ?></strong>
                        </div>
                        <div class="afc-pulse-item">
                            <span class="afc-label">CAPTURE RATE</span>
                            <strong><?php echo ( $stats['views'] > 0 ) ? round( (count($leads) / $stats['views']) * 100, 1 ) : '0'; ?>%</strong>
                        </div>
                    </div>
                </div>

                <div class="afc-stat-tiles">
                    <div class="afc-stat-tile" style="background: white; border: 1px solid #dbeafe;">
                        <strong><?php echo $stats['count']; ?></strong>
                        <span>Asset Inventory</span>
                    </div>
                    <?php if ( ! empty($leads) ) : ?>
                        <div class="afc-stat-tile afc-leads-tile">
                            <strong><?php echo count($leads); ?></strong>
                            <span>Active Inquiries</span>
                            <div class="afc-pulse-dot"></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 🚀 AGENT PRODUCTIVITY SUITE -->
            <div class="afc-toolkit-row">
                <div class="afc-quick-actions">
                    <a href="<?php echo home_url('/submit-listing'); ?>" class="afc-vogue-btn afc-pulse-btn">
                        <span class="dashicons dashicons-plus-alt"></span> DEPLOY NEW ASSET
                    </a>
                </div>
                
                <!-- 📢 BROKER STRATEGY BROADCAST -->
                <div class="afc-broker-broadcast">
                    <div class="broadcast-icon">📢</div>
                    <div class="broadcast-content">
                        <strong>Broker Strategy:</strong>
                        <span>"Focus on high-value leads this week. The inventory pulse shows a 20% uptick in buyer interest for waterfront properties."</span>
                    </div>
                </div>
            </div>

            <div class="afc-workspace-row" style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                <!-- THE LEAD GATEWAY HUB -->
                <div class="afc-lead-hub-card">
                    <div class="afc-card-header">
                        <h3>📈 INQUIRY PULSE</h3>
                        <span>Direct Leads from your Assets</span>
                    </div>
                    <?php if ( ! empty($leads) ) : ?>
                        <div class="afc-lead-scroller">
                            <?php foreach ( $leads as $lead ) : ?>
                                <div class="afc-lead-row">
                                    <div class="afc-lead-info">
                                        <strong><?php echo esc_html($lead['data']['lead_name']); ?></strong>
                                        <span><?php echo esc_html($lead['data']['listing_title']); ?></span>
                                    </div>
                                    <div class="afc-lead-meta">
                                        <span class="afc-lead-time"><?php echo human_time_diff( strtotime($lead['time']), current_time('timestamp') ); ?> ago</span>
                                        <div class="afc-lead-actions">
                                            <a href="mailto:<?php echo esc_attr($lead['data']['lead_email']); ?>"><span class="dashicons dashicons-email"></span></a>
                                            <a href="tel:<?php echo esc_attr($lead['data']['lead_phone']); ?>"><span class="dashicons dashicons-phone"></span></a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else : ?>
                        <div class="afc-empty-leads">
                            <p>No active inquiries in the pulse. Keep your assets optimized for lead capture.</p>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- 📊 PERFORMANCE ANALYTICS -->
                <div class="afc-mini-analytics">
                    <div class="afc-card-header">
                        <h3>📊 PERFORMANCE</h3>
                        <span>Vogue Intelligence</span>
                    </div>
                    <div class="afc-ana-grid">
                        <div class="afc-ana-item">
                            <label>CONVERSION</label>
                            <strong><?php echo ( $stats['views'] > 0 ) ? round( (count($leads) / $stats['views']) * 100, 1 ) : '0'; ?>%</strong>
                        </div>
                        <div class="afc-ana-item">
                            <label>TOTAL REACH</label>
                            <strong><?php echo number_format($stats['views']); ?></strong>
                        </div>
                    </div>
                </div>
            </div>

            <div class="afc-system-ready">
                <span class="afc-pulse"></span> SYSTEM STATUS: AWAITING INPUT
            </div>

            <?php if ( $query->have_posts() ) : ?>
                <div class="afcglide-grid-container afc-cols-3">
                    <?php while ( $query->have_posts() ) : $query->the_post(); 
                        $is_personal = ( get_the_author_meta('ID') == get_current_user_id() );
                        ?>
                        <div class="afc-listing-wrapper <?php echo $is_personal ? 'afc-personal-asset' : ''; ?>">
                            <?php self::render_listing_card(); ?>
                        </div>
                    <?php endwhile; wp_reset_postdata(); ?>
                </div>
            <?php else : ?>
                <div class="afc-synergy-empty-state">
                    <div class="afc-empty-icon">📂</div>
                    <h2>Terminal Ready for Deployment</h2>
                    <p>Your synergy workspace is active and secure, but no assets have been detected in your inventory yet.</p>
                    
                    <div class="afc-empty-actions">
                        <a href="<?php echo home_url('/submit-listing'); ?>" class="afc-execute-btn">
                            + DEPLOY NEW ASSET
                        </a>
                        <p class="afc-empty-hint">Need help? Contact the Managing Broker for assistance.</p>
                    </div>

                    <div class="afc-system-ready">
                        <span class="afc-pulse"></span> SYSTEM STATUS: AWAITING INPUT
                    </div>
                </div>
            <?php endif; ?>

        </div>
        
        <?php
        return ob_get_clean();
    }

    /**
     * 2. FEATURED LISTINGS SLIDER (Full Multi-Instance Safe Logic)
     */
    public static function render_listing_slider( $atts ) {
        $atts = shortcode_atts( [ 'count' => 6 ], $atts );
        $query = new \WP_Query([
            'post_type'      => C::POST_TYPE,
            'posts_per_page' => (int) $atts['count'],
            'post_status'    => 'publish',
        ]);

        if ( ! $query->have_posts() ) return '';

        // Unique ID ensures multiple sliders on one page don't conflict
        $slider_id = 'afc-slider-' . wp_generate_password(4, false); 
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
        (function() {
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
        })();
        </script>
        <?php
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * 3. LISTINGS GRID (Public Facing Asset Wall)
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

    /**
     * HELPERS & TEMPLATE LOADERS
     */
    public static function render_listing_card() {
        $template = AFCG_PATH . 'templates/listing-card.php';
        if ( file_exists( $template ) ) {
            include $template;
        }
    }

    private static function render_search_bar($search_term, $max_price) {
        ?>
        <div class="afc-search-terminal">
            <form method="get" action="" class="afc-search-grid">
                <input type="text" name="afc_query" value="<?php echo esc_attr($search_term); ?>" class="afc-search-input" placeholder="Search Assets...">
                <select name="afc_max_price" class="afc-search-select">
                    <option value="">MAX PRICE (UNLIMITED)</option>
                    <?php 
                    $prices = [500000, 1000000, 2500000, 5000000, 10000000];
                    foreach($prices as $p) {
                        echo '<option value="'.$p.'" '.selected($max_price, $p, false).'>UNDER $'.number_format($p/1000000, 1).'M</option>';
                    }
                    ?>
                </select>
                <button type="submit" class="afc-vogue-btn">FILTER</button>
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