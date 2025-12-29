<?php
namespace AFCGlide\Listings;

/**
 * AFCGlide AJAX Handler - PRODUCTION MASTER
 * Handles listings submission, file processing (The Roadmap), and card rendering.
 *
 * @package AFCGlide\Listings
 * @since 3.6.6
 */


if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Ajax_Handler {

    public static function init() {
        new self();
    }

    public function __construct() {
        // Agent Submission Action
        add_action( 'wp_ajax_afcglide_submit_listing', [ $this, 'handle_listing_submission' ] );
        
        // Filter Action (For the Grid)
        add_action( 'wp_ajax_afcglide_filter_listings', [ $this, 'filter_listings' ] );
        add_action( 'wp_ajax_nopriv_afcglide_filter_listings', [ $this, 'filter_listings' ] );
        
        // Enqueue scripts
        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * Enqueue and Localize
     */
    public function enqueue_scripts() {
        $js_file = file_exists( AFCG_PATH . 'assets/js/public.js' ) ? 'public.js' : 'afcglide-public.js';
        
        wp_enqueue_script( 'afcglide-public', AFCG_URL . 'assets/js/' . $js_file, [ 'jquery' ], AFCG_VERSION, true );

        wp_localize_script( 'afcglide-public', 'afcglide_ajax_object', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'afcglide_ajax_nonce' ),
            'strings'  => [
                'loading' => __( 'Processing Luxury Listing...', 'afcglide' ),
                'success' => __( '✨ Success! Your property is live.', 'afcglide' ),
                'error'   => __( 'Error: Please check the required fields.', 'afcglide' ),
            ]
        ]);
    }

    /**
     * Handle Listing Submission
     */
    public function handle_listing_submission() {
        check_ajax_referer( 'afcglide_ajax_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Session expired. Please log in.', 'afcglide' ) ] );
        }

        if ( empty( $_POST['property_title'] ) ) {
            wp_send_json_error( [ 'message' => __( 'A property title is required.', 'afcglide' ) ] );
        }

        // Create Listing Post
        $post_id = wp_insert_post( [
            'post_title'   => sanitize_text_field( $_POST['property_title'] ),
            'post_content' => isset( $_POST['property_description'] ) ? wp_kses_post( $_POST['property_description'] ) : '',
            'post_status'  => 'pending', // Set to pending for admin review
            'post_type'    => 'afcglide_listing',
            'post_author'  => get_current_user_id(),
        ] );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Submission failed at the database level.', 'afcglide' ) ] );
        }

        // 1. Save All Meta Data
        $this->save_listing_meta( $post_id );

        // 2. Process "The Roadmap" Uploads
        if ( ! empty( $_FILES ) ) {
            $this->process_file_uploads( $post_id );
        }

        wp_send_json_success( [ 
            'message' => __( '✨ Luxury listing submitted for review!', 'afcglide' ),
            'post_id' => $post_id 
        ] );
    }

    /**
     * Meta Data Logic
     */
    private function save_listing_meta( $post_id ) {
        // Basic Info
        update_post_meta( $post_id, '_listing_price', sanitize_text_field( $_POST['price'] ?? '' ) );
        update_post_meta( $post_id, '_listing_beds', absint( $_POST['beds'] ?? 0 ) );
        update_post_meta( $post_id, '_listing_baths', sanitize_text_field( $_POST['baths'] ?? '' ) );
        update_post_meta( $post_id, '_listing_property_type', sanitize_text_field( $_POST['property_type'] ?? '' ) );

        // Location
        update_post_meta( $post_id, '_listing_address', sanitize_text_field( $_POST['property_address'] ?? '' ) );
        update_post_meta( $post_id, '_property_city', sanitize_text_field( $_POST['property_city'] ?? '' ) );
        update_post_meta( $post_id, '_property_state', sanitize_text_field( $_POST['property_state'] ?? '' ) );
        update_post_meta( $post_id, '_property_country', sanitize_text_field( $_POST['property_country'] ?? '' ) );
        update_post_meta( $post_id, '_gps_lat', sanitize_text_field( $_POST['gps_lat'] ?? '' ) );
        update_post_meta( $post_id, '_gps_lng', sanitize_text_field( $_POST['gps_lng'] ?? '' ) );

        // Amenities
        if ( isset( $_POST['amenities'] ) && is_array( $_POST['amenities'] ) ) {
            update_post_meta( $post_id, '_listing_amenities', array_map( 'sanitize_text_field', $_POST['amenities'] ) );
        }

        // Agent Branding
        update_post_meta( $post_id, 'agent_name', sanitize_text_field( $_POST['agent_name'] ?? '' ) );
        update_post_meta( $post_id, '_agent_email', sanitize_email( $_POST['agent_email'] ?? '' ) );
        update_post_meta( $post_id, 'agent_phone', sanitize_text_field( $_POST['agent_phone'] ?? '' ) );
        update_post_meta( $post_id, '_agent_license', sanitize_text_field( $_POST['agent_license'] ?? '' ) );
    }

    /**
     * The Media Roadmap Logic
     */
    private function process_file_uploads( $post_id ) {
    require_once( ABSPATH . 'wp-admin/includes/image.php' );
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
    require_once( ABSPATH . 'wp-admin/includes/media.php' );

    // 1. HERO IMAGE (The Featured Image)
    if ( ! empty( $_FILES['hero_image']['name'] ) ) {
        $hero_id = media_handle_upload( 'hero_image', $post_id );
        if ( ! is_wp_error( $hero_id ) ) {
            set_post_thumbnail( $post_id, $hero_id );
            // We also save the ID specifically for our hero logic
            update_post_meta( $post_id, '_hero_image_id', $hero_id );
        }
    }

    // 2. THE 3-PHOTO STACK (Must be JSON for v5.5 Template)
    if ( ! empty( $_FILES['stack_images']['name'][0] ) ) {
        $stack_ids = $this->handle_multiple_uploads( 'stack_images', $post_id );
        // IMPORTANT: Template v5.5 looks for '_stack_images_json'
        update_post_meta( $post_id, '_stack_images_json', json_encode( $stack_ids ) );
    }

    // 3. THE FULL GALLERY SLIDER (Must be JSON for v5.5 Template)
    if ( ! empty( $_FILES['slider_images']['name'][0] ) ) {
        $slider_ids = $this->handle_multiple_uploads( 'slider_images', $post_id );
        // IMPORTANT: Template v5.5 looks for '_slider_images_json'
        update_post_meta( $post_id, '_slider_images_json', json_encode( $slider_ids ) );
    }

    // 4. AGENT & AGENCY BRANDING
    if ( ! empty( $_FILES['agent_photo']['name'] ) ) {
        $agent_aid = media_handle_upload( 'agent_photo', $post_id );
        if ( ! is_wp_error( $agent_aid ) ) {
            update_post_meta( $post_id, '_agent_photo', $agent_aid );
        }
    }
    if ( ! empty( $_FILES['agency_logo']['name'] ) ) {
        $agency_aid = media_handle_upload( 'agency_logo', $post_id );
        if ( ! is_wp_error( $agency_aid ) ) {
            update_post_meta( $post_id, '_agency_logo', $agency_aid );
        }
    }
}
    private function handle_multiple_uploads( $file_key, $post_id ) {
        $attachment_ids = [];
        $files = $_FILES[ $file_key ];
        
        foreach ( $files['name'] as $key => $value ) {
            if ( $files['name'][ $key ] ) {
                $_FILES['temp_upload'] = [
                    'name'     => $files['name'][ $key ],
                    'type'     => $files['type'][ $key ],
                    'tmp_name' => $files['tmp_name'][ $key ],
                    'error'    => $files['error'][ $key ],
                    'size'     => $files['size'][ $key ]
                ];
                $aid = media_handle_upload( 'temp_upload', $post_id );
                if ( ! is_wp_error( $aid ) ) $attachment_ids[] = $aid;
            }
        }
        return $attachment_ids;
    }

    /**
     * Card Rendering (Luxury Sync)
     */
    private function render_listing_card() {
        $price = get_post_meta( get_the_ID(), '_listing_price', true );
        $beds  = get_post_meta( get_the_ID(), '_listing_beds', true );
        $baths = get_post_meta( get_the_ID(), '_listing_baths', true );
        ?>
        <article class="afc-listing-card">
            <div class="afc-card-media">
                <?php the_post_thumbnail( 'large' ); ?>
                <?php if ( $price ) : ?>
                    <div class="afcglide-hero-price-tag">$<?php echo number_format( (float) $price ); ?></div>
                <?php endif; ?>
            </div>
            <div class="afc-card-content">
                <h3 class="afc-card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <div class="afc-card-meta">
                    <span class="afc-meta-item">🛏️ <?php echo esc_html( $beds ); ?> beds</span>
                    <span class="afc-meta-item">🚿 <?php echo esc_html( $baths ); ?> baths</span>
                </div>
                <div class="afc-card-excerpt"><?php echo wp_trim_words( get_the_content(), 15 ); ?></div>
                <a href="<?php the_permalink(); ?>" class="afcglide-btn" style="width:100%; justify-content:center; margin-top:15px;">View Details</a>
            </div>
        </article>
        <?php
    }

    public function filter_listings() {
        check_ajax_referer( 'afcglide_ajax_nonce', 'nonce' );
        $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        
        $query = new \WP_Query( [
            'post_type'      => 'afcglide_listing',
            'post_status'    => 'publish',
            'posts_per_page' => 9,
            'paged'          => $page,
        ] );

        ob_start();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $this->render_listing_card();
            }
        }
        $html = ob_get_clean();
        wp_reset_postdata();
        wp_send_json_success( [ 'html' => $html, 'max_pages' => $query->max_num_pages ] );
    }
}