<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

final class AFCGlide_Shortcodes {

    public static function init() {
        // Use a priority of 20 to ensure all CPTs are registered first
        add_action( 'init', [ __CLASS__, 'register_shortcodes' ], 20 );
    }

    public static function register_shortcodes() {
        // Auth Shortcodes
        add_shortcode( 'afcglide_login', [ __CLASS__, 'render_login_form' ] );
        add_shortcode( 'afcglide_register', [ __CLASS__, 'render_registration_form' ] );
        
        // Display Shortcodes
        add_shortcode( 'afcglide_signature', [ __CLASS__, 'render_signature_card' ] );
        add_shortcode( 'afcglide_listings_grid', [ __CLASS__, 'render_listing_grid' ] );
        add_shortcode( 'afcglide_hero_gallery', [ __CLASS__, 'render_gallery_shortcode' ] );

        // THE MASTER SUBMISSION FORM (Linking to your new template)
        add_shortcode( 'afcglide_submit_listing', [ __CLASS__, 'render_submission_form' ] );
    }

    /**
     * Renders the Professional Submission Form from Template
     */
    public static function render_submission_form() {
        if ( ! is_user_logged_in() ) {
            return '<div class="afcglide-notice afcglide-notice-error">⚠️ Please log in to submit a luxury listing.</div>';
        }

        ob_start();
        // This pulls the professional HTML we created earlier
        $template_path = AFCG_PATH . 'includes/templates/template-submit-listing.php';
        
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            echo '<p>Error: Submission template missing.</p>';
        }
        
        return ob_get_clean();
    }

    /**
     * Helper for the Gallery Shortcode
     */
    public static function render_gallery_shortcode() {
        return self::render_luxury_hero_gallery( get_the_ID() );
    }

    /**
     * Renders the Login Form
     */
    public static function render_login_form() {
        if ( is_user_logged_in() ) {
            return sprintf(
                '<div class="afcglide-notice afcglide-notice-info">You are logged in. <a href="%s">Logout</a></div>',
                esc_url( wp_logout_url( get_permalink() ) )
            );
        }
        $args = [
            'echo'     => false,
            'redirect' => home_url( '/agent-dashboard/' ),
            'form_id'  => 'afcglide-login-form',
        ];
        return '<div class="afcglide-auth-card">' . wp_login_form( $args ) . '</div>';
    }

    /**
     * Renders the Registration form
     */
    public static function render_registration_form() {
        if ( is_user_logged_in() ) return '';
        ob_start(); 
        ?>
        <div class="afcglide-auth-container">
            <form id="afcglide-registration" class="afc-premium-form" method="post">
                <?php wp_nonce_field( 'afcglide_register_nonce', 'register_nonce' ); ?>
                <h2>Join the Agent Network</h2>
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="agent_name" placeholder="John Doe" required>
                </div>
                <div class="form-group">
                    <label>Email Address</label>
                    <input type="email" name="agent_email" required>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="agent_pass" required minlength="8">
                </div>
                <button type="submit" class="afcglide-submit-btn">Create Agent Account</button>
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

        if ( ! $query->have_posts() ) return '<p>No properties found.</p>';

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
     * Individual Listing Card
     */
    public static function render_listing_card() {
        $post_id = get_the_ID();
        $price   = get_post_meta( $post_id, '_listing_price', true );
        $beds    = get_post_meta( $post_id, '_listing_beds', true );
        $baths   = get_post_meta( $post_id, '_listing_baths', true );
        $hero    = has_post_thumbnail() ? get_the_post_thumbnail( $post_id, 'large' ) : '🏙️';
        $agent_name = get_post_meta( $post_id, '_agent_name_display', true ) ?: get_the_author();
        
        ?>
        <article class="afc-listing-card">
            <div class="afc-card-media">
                <a href="<?php the_permalink(); ?>"><?php echo $hero; ?></a>
                <?php if ($price) : ?><div class="afc-card-price-tag">$<?php echo number_format((float)$price); ?></div><?php endif; ?>
            </div>
            <div class="afc-card-content">
                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <p><?php echo $beds; ?> Beds | <?php echo $baths; ?> Baths</p>
                <div class="afc-agent-pill"><span><?php echo esc_html($agent_name); ?></span></div>
            </div>
        </article>
        <?php
    }

    /**
     * Standalone Signature Card
     */
    public static function render_signature_card() {
        $agent_id = get_the_author_meta('ID'); 
        $photo_id = get_user_meta( $agent_id, 'agent_photo', true );
        $photo_url = $photo_id ? wp_get_attachment_image_url( $photo_id, 'medium' ) : get_avatar_url($agent_id);

        ob_start();
        ?>
        <div class="afc-agent-signature-card">
            <img src="<?php echo esc_url( $photo_url ); ?>" width="60">
            <h4><?php echo get_the_author_meta('display_name'); ?></h4>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Luxury 4-Up Shutterbug Gallery
     */
    public static function render_luxury_hero_gallery($post_id) {
        $stack_json = get_post_meta($post_id, '_stack_images_json', true);
        $image_ids  = json_decode($stack_json, true);

        if (empty($image_ids)) return '';

        ob_start();
        ?>
        <div class="afc-luxury-gallery-wrapper">
            <div class="afc-shutterbug-row" style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px;">
                <?php foreach (array_slice((array)$image_ids, 0, 16) as $img_id) : ?>
                    <div class="afc-gallery-item">
                        <?php echo wp_get_attachment_image($img_id, 'medium'); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }  
}