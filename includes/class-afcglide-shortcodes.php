<?php
/**
 * AFCGlide Shortcodes - Consolidated & Fixed
 * All shortcodes in one place with consistent naming
 * 
 * @package AFCGlide\Listings
 * @since 3.6.6
 */

namespace AFCGlide\Listings;

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * AFCGlide Shortcodes Handler
 * Manages all plugin shortcodes for authentication, submission, and display
 */
final class AFCGlide_Shortcodes {

    /**
     * Initialize shortcode registration
     */
    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_shortcodes' ], 20 );
    }

    /**
     * Register all shortcodes with consistent naming
     */
    public static function register_shortcodes() {
        // Authentication Shortcodes
        add_shortcode( 'afcglide_login', [ __CLASS__, 'render_login_form' ] );
        add_shortcode( 'afcglide_register', [ __CLASS__, 'render_registration_form' ] );
        
        // Submission Shortcodes
        add_shortcode( 'afcglide_submit_listing', [ __CLASS__, 'render_submit_form' ] );
        
        // Display Shortcodes
        add_shortcode( 'afcglide_listings_grid', [ __CLASS__, 'render_listing_grid' ] );
    }

    /**
     * Render login form
     * 
     * @return string Login form HTML
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
            'label_password' => __( 'Password', 'afcglide' ),
            'label_log_in'   => __( 'Sign In to My Listings', 'afcglide' ),
            'remember'       => true,
        ];

        return '<div class="afcglide-auth-card">' . wp_login_form( $args ) . '</div>';
    }

    /**
     * Render registration form
     * 
     * @return string Registration form HTML
     */
    public static function render_registration_form() {
        if ( is_user_logged_in() ) {
            return sprintf(
                '<div class="afcglide-notice afcglide-notice-info">%s</div>',
                esc_html__( 'You are already registered and logged in.', 'afcglide' )
            );
        }

        ob_start(); 
        ?>
        <div class="afcglide-auth-container">
            <form id="afcglide-registration" class="afc-premium-form" method="post">
                <?php wp_nonce_field( 'afcglide_register_nonce', 'register_nonce' ); ?>
                
                <h2><?php esc_html_e( 'Join the Agent Network', 'afcglide' ); ?></h2>
                
                <div class="form-group">
                    <label for="agent_name"><?php esc_html_e( 'Full Name', 'afcglide' ); ?></label>
                    <input 
                        type="text" 
                        id="agent_name" 
                        name="agent_name" 
                        placeholder="<?php esc_attr_e( 'Full Name', 'afcglide' ); ?>" 
                        required
                        autocomplete="name"
                    >
                </div>
                
                <div class="form-group">
                    <label for="agent_email"><?php esc_html_e( 'Email Address', 'afcglide' ); ?></label>
                    <input 
                        type="email" 
                        id="agent_email" 
                        name="agent_email" 
                        placeholder="<?php esc_attr_e( 'Email Address', 'afcglide' ); ?>" 
                        required
                        autocomplete="email"
                    >
                </div>
                
                <div class="form-group">
                    <label for="agent_pass"><?php esc_html_e( 'Password', 'afcglide' ); ?></label>
                    <input 
                        type="password" 
                        id="agent_pass" 
                        name="agent_pass" 
                        placeholder="<?php esc_attr_e( 'Create Password', 'afcglide' ); ?>" 
                        required 
                        minlength="8"
                        autocomplete="new-password"
                    >
                    <small class="form-hint"><?php esc_html_e( 'Minimum 8 characters', 'afcglide' ); ?></small>
                </div>
                
                <button type="submit" class="afcglide-submit-btn">
                    <?php esc_html_e( 'Create Agent Account', 'afcglide' ); ?>
                </button>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render property submission form (AJAX-powered)
     * 
     * @return string Submission form HTML
     */
    public static function render_submit_form() {
        if ( ! is_user_logged_in() ) {
            return sprintf(
                '<div class="afcglide-notice afcglide-notice-error">%s</div>',
                esc_html__( 'Please log in to submit a listing.', 'afcglide' )
            );
        }

        ob_start();
        ?>
        <div id="afc-form-messages"></div>

        <div class="afcglide-form-wrapper afc-fade-in">
            <div class="form-header">
                <h2><?php esc_html_e( 'List Your Property', 'afcglide' ); ?></h2>
                <p><?php esc_html_e( 'Fill in the details below to create your luxury listing.', 'afcglide' ); ?></p>
            </div>

            <form id="afcglide-submit-property" method="POST" enctype="multipart/form-data">
                <?php wp_nonce_field( 'afcglide_ajax_nonce', 'nonce' ); ?>

                <!-- Property Basics -->
                <div class="form-section">
                    <h3><?php esc_html_e( 'Property Basics', 'afcglide' ); ?></h3>
                    
                    <div class="afcglide-form-full">
                        <label for="property_title">
                            <?php esc_html_e( 'Property Title', 'afcglide' ); ?>
                            <span class="required">*</span>
                        </label>
                        <input 
                            type="text" 
                            id="property_title" 
                            name="property_title" 
                            placeholder="<?php esc_attr_e( 'e.g. Luxury Beachfront Estate', 'afcglide' ); ?>" 
                            required
                        >
                    </div>

                    <div class="afcglide-form-grid afcglide-grid-3">
                        <div>
                            <label for="price"><?php esc_html_e( 'Price ($)', 'afcglide' ); ?></label>
                            <input 
                                type="number" 
                                id="price" 
                                name="price" 
                                placeholder="500000" 
                                step="1000"
                                min="0"
                            >
                        </div>
                        <div>
                            <label for="beds"><?php esc_html_e( 'Bedrooms', 'afcglide' ); ?></label>
                            <input 
                                type="number" 
                                id="beds" 
                                name="beds" 
                                placeholder="4" 
                                min="0"
                                max="50"
                            >
                        </div>
                        <div>
                            <label for="baths"><?php esc_html_e( 'Bathrooms', 'afcglide' ); ?></label>
                            <input 
                                type="number" 
                                id="baths" 
                                name="baths" 
                                step="0.5" 
                                placeholder="3.5" 
                                min="0"
                                max="50"
                            >
                        </div>
                    </div>
                    
                    <div class="afcglide-form-full">
                        <label for="property_description"><?php esc_html_e( 'Description', 'afcglide' ); ?></label>
                        <textarea 
                            id="property_description" 
                            name="property_description" 
                            rows="5" 
                            placeholder="<?php esc_attr_e( 'Describe the luxury lifestyle...', 'afcglide' ); ?>"
                        ></textarea>
                    </div>
                </div>

                <!-- Property Type -->
                <div class="form-section">
                    <h3><?php esc_html_e( 'Property Type', 'afcglide' ); ?></h3>
                    
                    <div class="afcglide-form-full">
                        <label for="property_type"><?php esc_html_e( 'Property Type', 'afcglide' ); ?></label>
                        <select id="property_type" name="property_type">
                            <option value=""><?php esc_html_e( 'Select Type', 'afcglide' ); ?></option>
                            <option value="villa"><?php esc_html_e( 'Villa', 'afcglide' ); ?></option>
                            <option value="condo"><?php esc_html_e( 'Condo', 'afcglide' ); ?></option>
                            <option value="apartment"><?php esc_html_e( 'Apartment', 'afcglide' ); ?></option>
                            <option value="house"><?php esc_html_e( 'House', 'afcglide' ); ?></option>
                            <option value="penthouse"><?php esc_html_e( 'Penthouse', 'afcglide' ); ?></option>
                            <option value="estate"><?php esc_html_e( 'Estate', 'afcglide' ); ?></option>
                            <option value="land"><?php esc_html_e( 'Land', 'afcglide' ); ?></option>
                            <option value="commercial"><?php esc_html_e( 'Commercial', 'afcglide' ); ?></option>
                        </select>
                    </div>
                </div>

                <!-- Location -->
                <div class="form-section form-section-alt">
                    <h3><?php esc_html_e( 'Location', 'afcglide' ); ?></h3>
                    
                    <div class="afcglide-form-full">
                        <label for="property_address"><?php esc_html_e( 'Street Address (Optional)', 'afcglide' ); ?></label>
                        <input 
                            type="text" 
                            id="property_address" 
                            name="property_address" 
                            placeholder="<?php esc_attr_e( 'e.g. 123 Beach Road', 'afcglide' ); ?>"
                        >
                    </div>
                    
                    <div class="afcglide-form-grid afcglide-grid-3">
                        <div>
                            <label for="property_city"><?php esc_html_e( 'City', 'afcglide' ); ?></label>
                            <input 
                                type="text" 
                                id="property_city" 
                                name="property_city" 
                                placeholder="<?php esc_attr_e( 'e.g. Tamarindo', 'afcglide' ); ?>"
                            >
                        </div>
                        <div>
                            <label for="property_state"><?php esc_html_e( 'State/Province', 'afcglide' ); ?></label>
                            <input 
                                type="text" 
                                id="property_state" 
                                name="property_state" 
                                placeholder="<?php esc_attr_e( 'e.g. Guanacaste', 'afcglide' ); ?>"
                            >
                        </div>
                        <div>
                            <label for="property_country"><?php esc_html_e( 'Country', 'afcglide' ); ?></label>
                            <input 
                                type="text" 
                                id="property_country" 
                                name="property_country" 
                                placeholder="<?php esc_attr_e( 'e.g. Costa Rica', 'afcglide' ); ?>"
                            >
                        </div>
                    </div>
                    
                    <p class="afcglide-description" style="margin-top: 20px;">
                        <?php esc_html_e( 'No address? No problem. Use GPS coordinates below for map display.', 'afcglide' ); ?>
                    </p>
                    
                    <div class="afcglide-form-grid afcglide-grid-2">
                        <div>
                            <label for="gps_lat"><?php esc_html_e( 'Latitude', 'afcglide' ); ?></label>
                            <input 
                                type="text" 
                                id="gps_lat" 
                                name="gps_lat" 
                                placeholder="9.748"
                                pattern="^-?([0-9]{1,2}|1[0-7][0-9]|180)(\.[0-9]{1,10})?$"
                            >
                        </div>
                        <div>
                            <label for="gps_lng"><?php esc_html_e( 'Longitude', 'afcglide' ); ?></label>
                            <input 
                                type="text" 
                                id="gps_lng" 
                                name="gps_lng" 
                                placeholder="-83.75"
                                pattern="^-?([0-9]{1,2}|1[0-7][0-9]|180)(\.[0-9]{1,10})?$"
                            >
                        </div>
                    </div>
                </div>

                <!-- Amenities -->
                <div class="form-section">
                    <h3><?php esc_html_e( 'Property Amenities', 'afcglide' ); ?></h3>
                    <p class="afcglide-description">
                        <?php esc_html_e( 'Select all that apply', 'afcglide' ); ?>
                    </p>
                    
                    <div class="afcglide-amenities-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px;">
                        <label class="afcglide-checkbox-label">
                            <input type="checkbox" name="amenities[]" value="pool"> 
                            <?php esc_html_e( '🏊 Swimming Pool', 'afcglide' ); ?>
                        </label>
                        <label class="afcglide-checkbox-label">
                            <input type="checkbox" name="amenities[]" value="gym"> 
                            <?php esc_html_e( '💪 Gym/Fitness Center', 'afcglide' ); ?>
                        </label>
                        <label class="afcglide-checkbox-label">
                            <input type="checkbox" name="amenities[]" value="ocean_view"> 
                            <?php esc_html_e( '🌊 Ocean View', 'afcglide' ); ?>
                        </label>
                        <label class="afcglide-checkbox-label">
                            <input type="checkbox" name="amenities[]" value="beach_access"> 
                            <?php esc_html_e( '🏖️ Beach Access', 'afcglide' ); ?>
                        </label>
                        <label class="afcglide-checkbox-label">
                            <input type="checkbox" name="amenities[]" value="air_conditioning"> 
                            <?php esc_html_e( '❄️ Air Conditioning', 'afcglide' ); ?>
                        </label>
                        <label class="afcglide-checkbox-label">
                            <input type="checkbox" name="amenities[]" value="parking"> 
                            <?php esc_html_e( '🚗 Parking', 'afcglide' ); ?>
                        </label>
                        <label class="afcglide-checkbox-label">
                            <input type="checkbox" name="amenities[]" value="security"> 
                            <?php esc_html_e( '🔒 24/7 Security', 'afcglide' ); ?>
                        </label>
                        <label class="afcglide-checkbox-label">
                            <input type="checkbox" name="amenities[]" value="furnished"> 
                            <?php esc_html_e( '🛋️ Fully Furnished', 'afcglide' ); ?>
                        </label>
                        <label class="afcglide-checkbox-label">
                            <input type="checkbox" name="amenities[]" value="garden"> 
                            <?php esc_html_e( '🌳 Garden', 'afcglide' ); ?>
                        </label>
                        <label class="afcglide-checkbox-label">
                            <input type="checkbox" name="amenities[]" value="terrace"> 
                            <?php esc_html_e( '🏡 Terrace/Balcony', 'afcglide' ); ?>
                        </label>
                        <label class="afcglide-checkbox-label">
                            <input type="checkbox" name="amenities[]" value="wifi"> 
                            <?php esc_html_e( '📶 WiFi', 'afcglide' ); ?>
                        </label>
                        <label class="afcglide-checkbox-label">
                            <input type="checkbox" name="amenities[]" value="hot_water"> 
                            <?php esc_html_e( '🚿 Hot Water', 'afcglide' ); ?>
                        </label>
                    </div>
                </div>

                <!-- Property Media -->
                <div class="form-section">
                    <h3><?php esc_html_e( 'Property Media (The Roadmap)', 'afcglide' ); ?></h3>
                    
                    <div class="afcglide-form-full">
                        <label for="hero_image">
                            <strong><?php esc_html_e( '1. The Money Shot (Hero Image)', 'afcglide' ); ?></strong>
                        </label>
                        <p class="afcglide-description">
                            <?php esc_html_e( 'The main image used in the header and grid.', 'afcglide' ); ?>
                        </p>
                        <input 
                            type="file" 
                            id="hero_image" 
                            name="hero_image" 
                            accept="image/jpeg,image/png,image/webp"
                        >
                    </div>

                    <div class="afcglide-form-full">
                        <label for="stack_images">
                            <strong><?php esc_html_e( '2. The 3-Photo Stack', 'afcglide' ); ?></strong>
                        </label>
                        <p class="afcglide-description">
                            <?php esc_html_e( 'Select exactly 3 photos for the side-stack display.', 'afcglide' ); ?>
                        </p>
                        <input 
                            type="file" 
                            id="stack_images" 
                            name="stack_images[]" 
                            accept="image/jpeg,image/png,image/webp" 
                            multiple
                        >
                    </div>

                    <div class="afcglide-form-full">
                        <label for="slider_images">
                            <strong><?php esc_html_e( '3. The Full Gallery Slider', 'afcglide' ); ?></strong>
                        </label>
                        <p class="afcglide-description">
                            <?php esc_html_e( 'Upload all remaining photos for the lightbox slider.', 'afcglide' ); ?>
                        </p>
                        <input 
                            type="file" 
                            id="slider_images" 
                            name="slider_images[]" 
                            accept="image/jpeg,image/png,image/webp" 
                            multiple
                        >
                    </div>
                </div>

                <!-- Agent Information -->
                <div class="form-section">
                    <h3><?php esc_html_e( 'Agent Information', 'afcglide' ); ?></h3>
                    
                    <div class="afcglide-form-grid afcglide-grid-2">
                        <div>
                            <label for="agent_name"><?php esc_html_e( 'Agent Name', 'afcglide' ); ?></label>
                            <input 
                                type="text" 
                                id="agent_name" 
                                name="agent_name" 
                                placeholder="<?php esc_attr_e( 'John Smith', 'afcglide' ); ?>"
                                value="<?php echo esc_attr( wp_get_current_user()->display_name ); ?>"
                            >
                        </div>
                        <div>
                            <label for="agent_phone"><?php esc_html_e( 'Phone Number', 'afcglide' ); ?></label>
                            <input 
                                type="tel" 
                                id="agent_phone" 
                                name="agent_phone" 
                                placeholder="<?php esc_attr_e( '+1 (555) 123-4567', 'afcglide' ); ?>"
                            >
                        </div>
                    </div>
                    
                    <div class="afcglide-form-grid afcglide-grid-2" style="margin-top: 15px;">
                        <div>
                            <label for="agent_email"><?php esc_html_e( 'Agent Email', 'afcglide' ); ?></label>
                            <input 
                                type="email" 
                                id="agent_email" 
                                name="agent_email" 
                                placeholder="<?php esc_attr_e( 'agent@agency.com', 'afcglide' ); ?>"
                                value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>"
                            >
                        </div>
                        <div>
                            <label for="agent_license"><?php esc_html_e( 'License Number (Optional)', 'afcglide' ); ?></label>
                            <input 
                                type="text" 
                                id="agent_license" 
                                name="agent_license" 
                                placeholder="<?php esc_attr_e( 'RE-123456', 'afcglide' ); ?>"
                            >
                        </div>
                    </div>
                    
                    <div class="afcglide-form-grid afcglide-grid-2" style="margin-top: 20px;">
                        <div>
                            <label for="agent_photo"><?php esc_html_e( 'Agent Profile Photo', 'afcglide' ); ?></label>
                            <input 
                                type="file" 
                                id="agent_photo" 
                                name="agent_photo" 
                                accept="image/jpeg,image/png,image/webp"
                            >
                        </div>
                        <div>
                            <label for="agency_logo"><?php esc_html_e( 'Agency Logo', 'afcglide' ); ?></label>
                            <input 
                                type="file" 
                                id="agency_logo" 
                                name="agency_logo" 
                                accept="image/jpeg,image/png,image/webp,image/svg+xml"
                            >
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="form-footer">
                    <button type="submit" class="afcglide-btn afcglide-submit-btn">
                        <span class="btn-text"><?php esc_html_e( 'Submit Luxury Listing', 'afcglide' ); ?></span>
                        <span class="btn-spinner" style="display:none;">⏳</span>
                    </button>
                </div>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Render listings grid
     * 
     * @param array $atts Shortcode attributes
     * @return string Grid HTML
     */
    public static function render_listing_grid( $atts ) {
        $atts = shortcode_atts( [
            'posts_per_page' => 6,
            'columns'        => 3,
        ], $atts, 'afcglide_listings_grid' );
        
        $query = new \WP_Query( [
            'post_type'      => 'afcglide_listing',
            'posts_per_page' => absint( $atts['posts_per_page'] ),
            'post_status'    => 'publish',
            'orderby'        => 'date',
            'order'          => 'DESC',
        ] );

        if ( ! $query->have_posts() ) {
            return sprintf(
                '<div class="afcglide-no-results"><p>%s</p></div>',
                esc_html__( 'No properties found.', 'afcglide' )
            );
        }

        ob_start();
        
        $columns = absint( $atts['columns'] );
        $columns = max( 1, min( 4, $columns ) );
        
        printf( 
            '<div class="afcglide-grid-wrapper afcglide-grid-cols-%d" data-columns="%d">',
            (int) $columns,
            (int) $columns
        );
        
        while ( $query->have_posts() ) {
            $query->the_post();
            self::render_listing_card();
        }
        
        echo '</div>';
        
        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Render individual listing card
     * 
     * @return void Outputs HTML directly
     */
    private static function render_listing_card() {
        $post_id = get_the_ID();
        $price   = get_post_meta( $post_id, '_listing_price', true );
        $beds    = get_post_meta( $post_id, '_listing_beds', true );
        $baths   = get_post_meta( $post_id, '_listing_baths', true );
        ?>
        <article class="afcglide-listing-card" data-listing-id="<?php echo esc_attr( $post_id ); ?>">
            <div class="afc-card-media">
                <?php if ( has_post_thumbnail() ) : ?>
                    <a href="<?php the_permalink(); ?>" class="afc-card-link">
                        <?php the_post_thumbnail( 'large', [ 'class' => 'afc-card-image' ] ); ?>
                    </a>
                <?php else : ?>
                    <div class="afc-card-placeholder">
                        <span class="afc-placeholder-icon">🏠</span>
                    </div>
                <?php endif; ?>
                
                <?php if ( $price ) : ?>
                    <div class="afc-card-price-tag">
                        $<?php echo number_format( (float) $price ); ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="afc-card-content">
                <h3 class="afc-card-title">
                    <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
                </h3>
                
                <?php if ( $beds || $baths ) : ?>
                    <div class="afc-card-meta">
                        <?php if ( $beds ) : ?>
                            <span class="afc-meta-item">
                                <span class="afc-meta-icon">🛏️</span>
                                <?php echo esc_html( $beds ); ?> 
                                <?php esc_html_e( 'beds', 'afcglide' ); ?>
                            </span>
                        <?php endif; ?>
                        <?php if ( $baths ) : ?>
                            <span class="afc-meta-item">
                                <span class="afc-meta-icon">🚿</span>
                                <?php echo esc_html( $baths ); ?> 
                                <?php esc_html_e( 'baths', 'afcglide' ); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
                
                <div class="afc-card-excerpt">
                    <?php echo wp_trim_words( get_the_content(), 15, '...' ); ?>
                </div>
                
                <a href="<?php the_permalink(); ?>" class="afcglide-btn afc-btn-primary">
                    <?php esc_html_e( 'View Details', 'afcglide' ); ?>
                    <span class="afc-btn-arrow">→</span>
                </a>
            </div>
        </article>
        <?php
    }
}