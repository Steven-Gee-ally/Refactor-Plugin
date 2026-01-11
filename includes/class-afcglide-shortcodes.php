<?php
namespace AFCGlide\Listings;

/**
 * AFCGlide Shortcodes - Consolidated & Professional
 * Refactored for speed, "Global Brain" data syncing, and no-compromise security.
 * * @package AFCGlide\Listings
 * @since 3.6.6
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class AFCGlide_Shortcodes {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_shortcodes' ], 20 );
    }

    public static function register_shortcodes() {
        add_shortcode( 'afcglide_login', [ __CLASS__, 'render_login_form' ] );
        add_shortcode( 'afcglide_register', [ __CLASS__, 'render_registration_form' ] );
        add_shortcode( 'afcglide_signature', [ __CLASS__, 'render_signature_card' ] );
        add_shortcode( 'afcglide_submit_listing', [ __CLASS__, 'render_submit_form' ] );
        add_shortcode( 'afcglide_listings_grid', [ __CLASS__, 'render_listing_grid' ] );
    }

    /**
     * Renders a professional login form
     */
    public static function render_login_form() {
        if ( is_user_logged_in() ) {
            return sprintf(
                '<div class="afcglide-notice afcglide-notice-info">%s <a href="%s">%s</a></div>',
                esc_html__( 'You are already logged in.', 'afcglide' ),
                esc_url( wp_logout_url( get_permalink() ) ),
                esc_html__( 'Logout', 'afcglide' )
            );
        }
        $args = [
            'echo'           => false,
            'redirect'       => home_url( '/agent-dashboard/' ),
            'form_id'        => 'afcglide-login-form',
            'label_username' => __( 'Email Address', 'afcglide' ),
            'label_log_in'   => __( 'Sign In to My Listings', 'afcglide' ),
            'remember'       => true,
        ];
        return '<div class="afcglide-auth-card">' . wp_login_form( $args ) . '</div>';
    }

    /**
     * Renders the Registration form for new Agents
     */
    public static function render_registration_form() {
        if ( is_user_logged_in() ) {
            return sprintf('<div class="afcglide-notice afcglide-notice-info">%s</div>', esc_html__( 'You are already registered.', 'afcglide' ));
        }
        ob_start(); 
        ?>
        <div class="afcglide-auth-container">
            <form id="afcglide-registration" class="afc-premium-form" method="post">
                <?php wp_nonce_field( 'afcglide_register_nonce', 'register_nonce' ); ?>
                <h2><?php esc_html_e( 'Join the Agent Network', 'afcglide' ); ?></h2>
                <div class="form-group">
                    <label><?php esc_html_e( 'Full Name', 'afcglide' ); ?></label>
                    <input type="text" name="agent_name" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label><?php esc_html_e( 'Email Address', 'afcglide' ); ?></label>
                    <input type="email" name="agent_email" placeholder="agent@example.com" required>
                </div>
                <div class="form-group">
                    <label><?php esc_html_e( 'Password', 'afcglide' ); ?></label>
                    <input type="password" name="agent_pass" required minlength="8">
                </div>
                <button type="submit" class="afcglide-submit-btn"><?php esc_html_e( 'Create Agent Account', 'afcglide' ); ?></button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renders the Luxury Listing Submission Form
     */
    public static function render_submit_form() {
        if ( ! is_user_logged_in() ) {
            return sprintf('<div class="afcglide-notice afcglide-notice-error">%s</div>', esc_html__( 'Please log in to submit a listing.', 'afcglide' ));
        }
        ob_start();
        ?>
        <div id="afc-form-messages"></div>
        <div class="afcglide-form-wrapper afc-fade-in">
            <div class="form-header">
                <h2><?php esc_html_e( 'List Your Property', 'afcglide' ); ?></h2>
                <p><?php esc_html_e( 'Your listing will be reviewed by our luxury specialists.', 'afcglide' ); ?></p>
            </div>
            <form id="afcglide-submit-property" method="POST" enctype="multipart/form-data">
                <?php wp_nonce_field( 'afcglide_ajax_nonce', 'nonce' ); ?>
                
                <div class="form-section">
                    <h3><?php esc_html_e( 'Property Basics', 'afcglide' ); ?></h3>
                    <div class="afcglide-form-full">
                        <label><?php esc_html_e( 'Property Title', 'afcglide' ); ?> <span class="required">*</span></label>
                        <input type="text" name="property_title" placeholder="e.g. Oceanfront Villa" required>
                    </div>
                    <div class="afcglide-form-grid afcglide-grid-3">
                        <div>
                            <label><?php esc_html_e( 'Price ($)', 'afcglide' ); ?></label>
                            <input type="number" name="listing_price" placeholder="1250000">
                        </div>
                        <div>
                            <label><?php esc_html_e( 'Bedrooms', 'afcglide' ); ?></label>
                            <input type="number" name="listing_beds" placeholder="4">
                        </div>
                        <div>
                            <label><?php esc_html_e( 'Bathrooms', 'afcglide' ); ?></label>
                            <input type="number" name="listing_baths" step="0.5" placeholder="3.5">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3><?php esc_html_e( 'Property Type', 'afcglide' ); ?></h3>
                    <select name="listing_property_type">
                        <option value=""><?php esc_html_e( 'Select Property Type', 'afcglide' ); ?></option>
                        <option value="villa">Villa</option>
                        <option value="condo">Condo</option>
                        <option value="house">House</option>
                        <option value="estate">Estate</option>
                    </select>
                </div>

                <div class="form-section">
                    <h3><?php esc_html_e( 'Property Media', 'afcglide' ); ?></h3>
                    <div class="afcglide-form-full">
                        <label><strong><?php esc_html_e( 'Hero Image (The Money Shot)', 'afcglide' ); ?></strong></label>
                        <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp">
                    </div>
                </div>

                <div class="form-section">
                    <h3><?php esc_html_e( 'Agent Details', 'afcglide' ); ?></h3>
                    <div class="afcglide-form-grid afcglide-grid-2">
                        <div>
                            <label><?php esc_html_e( 'Display Name', 'afcglide' ); ?></label>
                            <input type="text" name="agent_name_display" value="<?php echo esc_attr( wp_get_current_user()->display_name ); ?>">
                        </div>
                        <div>
                            <label><?php esc_html_e( 'Phone Number', 'afcglide' ); ?></label>
                            <input type="tel" name="agent_phone_display" placeholder="+1 555-0000">
                        </div>
                    </div>
                </div>

                <button type="submit" class="afcglide-submit-btn">
                    <span class="btn-text"><?php esc_html_e( 'Submit Luxury Listing', 'afcglide' ); ?></span>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Renders the main listings grid
     */
    public static function render_listing_grid( $atts ) {
        $atts = shortcode_atts( ['posts_per_page' => 6, 'columns' => 3], $atts );
        $query = new \WP_Query( [
            'post_type'      => 'afcglide_listing', 
            'posts_per_page' => (int) $atts['posts_per_page'], 
            'post_status'    => 'publish'
        ] );

        if ( ! $query->have_posts() ) return '<p class="afcglide-no-results">No properties found.</p>';

        ob_start();
        printf( '<div class="afcglide-grid afcglide-grid-cols-%d">', (int) $atts['columns'] );
        while ( $query->have_posts() ) { 
            $query->the_post(); 
            self::render_listing_card(); 
        }
        echo '</div>';
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * The Listing Card Component - Used by the Grid
     */
    private static function render_listing_card() {
        $post_id = get_the_ID();
        
        // Use consistent meta keys (mapped to form names)
        $price   = get_post_meta( $post_id, '_listing_price', true );
        $beds    = get_post_meta( $post_id, '_listing_beds', true );
        $baths   = get_post_meta( $post_id, '_listing_baths', true );
        
        // Standard WordPress Featured Image is best for performance
        $hero_html = has_post_thumbnail() ? get_the_post_thumbnail( $post_id, 'large', ['class' => 'afc-hero-img'] ) : '<div class="afc-placeholder">🏙️</div>';

        // AGENT FALLBACK LOGIC
        $agent_name  = get_post_meta( $post_id, '_agent_name_display', true ) ?: get_the_author();
        
        // Global Brain Check: Priority 1: User Meta, Priority 2: Gravatar
        $author_id = get_post_field( 'post_author', $post_id );
        $photo_id  = get_user_meta( $author_id, 'agent_photo', true );
        $agent_url = $photo_id ? wp_get_attachment_image_url( $photo_id, 'thumbnail' ) : get_avatar_url( $author_id );
        
        ?>
        <article class="afc-listing-card">
            <div class="afc-card-media">
                <a href="<?php the_permalink(); ?>"><?php echo $hero_html; ?></a>
                <?php if ($price) : ?>
                    <div class="afc-card-price-tag">$<?php echo number_format( (float) $price ); ?></div>
                <?php endif; ?>
            </div>
            <div class="afc-card-content">
                <h3 class="afc-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <div class="afc-card-specs">
                    <span><strong><?php echo esc_html( $beds ); ?></strong> Beds</span> | 
                    <span><strong><?php echo esc_html( $baths ); ?></strong> Baths</span>
                </div>
                <div class="afc-card-agent-bar">
                    <div class="afc-agent-pill">
                        <img src="<?php echo esc_url($agent_url); ?>" alt="<?php echo esc_attr($agent_name); ?>">
                        <span><?php echo esc_html( $agent_name ); ?></span>
                    </div>
                </div>
            </div>
        </article>
        <?php
    }

    /**
     * Renders a standalone signature card [afcglide_signature]
     */
    public static function render_signature_card() {
        $agent_id = get_option('afc_global_agent_id');
        if ( ! $agent_id ) return '';

        $user_data = get_userdata( $agent_id );
        if ( ! $user_data ) return '';

        $photo_id  = get_user_meta( $agent_id, 'agent_photo', true );
        $photo_url = $photo_id ? wp_get_attachment_image_url( $photo_id, 'medium' ) : get_avatar_url($agent_id);

        ob_start();
        ?>
        <div class="afc-agent-signature-card">
            <div class="afc-sig-photo-wrap">
                <img src="<?php echo esc_url( $photo_url ); ?>" class="afc-agent-photo">
            </div>
            <div class="afc-agent-details">
                <span class="afc-label"><?php esc_html_e( 'Listing Agent', 'afcglide' ); ?></span>
                <h4><?php echo esc_html( $user_data->display_name ); ?></h4>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}