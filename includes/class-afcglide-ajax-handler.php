<?php
namespace AFCGlide\Listings;

use AFCGlide\Listings\Helpers\Sanitizer;
use AFCGlide\Listings\Submission\Submission_Files; 

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Ajax_Handler {

    public static function init() {
        new self();
    }

    public function __construct() {
        // --- Search/Filter Actions ---
        add_action( 'wp_ajax_afcglide_filter_listings', [ $this, 'filter_listings' ] );
        add_action( 'wp_ajax_nopriv_afcglide_filter_listings', [ $this, 'filter_listings' ] );

        // --- Agent Submission Actions ---
        add_action( 'wp_ajax_afcglide_submit_listing', [ $this, 'handle_listing_submission' ] );
        add_action( 'wp_ajax_nopriv_afcglide_submit_listing', [ $this, 'handle_listing_submission' ] );

        add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    }

    /**
     * ENQUEUE: Locals, Nonces, and Scripts
     * Synced with AFCG_URL from main plugin file.
     */
    public function enqueue_scripts() {
        // FIX: Changed AFCG_PLUGIN_URL to AFCG_URL
        wp_enqueue_script( 'afcglide-public', AFCG_URL . 'assets/js/public.js', [ 'jquery' ], '3.6.0', true );

        wp_localize_script( 'afcglide-public', 'afcglide_ajax_object', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'afcglide_ajax_nonce' ),
            'strings'  => [
                'loading'    => __( 'Searching...', 'afcglide' ),
                'success'    => __( '✨ Listing submitted successfully!', 'afcglide' ),
                'error'      => __( 'Error: Please check the form.', 'afcglide' ),
            ]
        ]);
        
        if ( is_singular( 'afcglide_listing' ) ) {
            wp_enqueue_style( 'glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css' );
            wp_enqueue_script( 'glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', [], '3.2.0', true );
        }
    }

    /* =========================================================
      1. THE SUBMISSION HANDLER (The "Create" Side)
      ========================================================= */
    public function handle_listing_submission() {
        check_ajax_referer( 'afcglide_ajax_nonce', 'nonce' );

        $post_id = wp_insert_post([
            'post_title'   => sanitize_text_field( $_POST['property_title'] ),
            'post_content' => wp_kses_post( $_POST['property_description'] ),
            'post_status'  => 'pending',
            'post_type'    => 'afcglide_listing',
        ]);

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [ 'message' => 'Listing creation failed.' ] );
        }

        // Save Price Meta (Synced Key)
        update_post_meta( $post_id, '_listing_price', Sanitizer::price( $_POST['price'] ) );

        // CALL FILE ENGINE: Processes Hero, Stack, and Slider
        if ( class_exists( 'AFCGlide\Listings\Submission\Submission_Files' ) ) {
            $file_engine = new Submission_Files();
            $file_engine->process_submission_media( $post_id );
        }

        wp_send_json_success( [ 'message' => '✨ Success! Property sent for review.' ] );
    }

    /* =========================================================
      2. THE FILTER HANDLER (The "Search" Side)
      ========================================================= */
    public function filter_listings() {
        check_ajax_referer( 'afcglide_ajax_nonce', 'nonce' );

        $page    = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $filters = isset( $_POST['filters'] ) ? $_POST['filters'] : [];
        $args    = $this->build_query_args( $page, $filters );
        
        $query = new \WP_Query( $args );
        $html  = '';

        if ( $query->have_posts() ) {
            ob_start();
            while ( $query->have_posts() ) {
                $query->the_post();
                $this->render_luxury_card();
            }
            $html = ob_get_clean();
        }

        wp_reset_postdata();
        wp_send_json_success([ 'html' => $html, 'max_pages' => $query->max_num_pages ]);
    }

    private function render_luxury_card() {
        $price = get_post_meta( get_the_ID(), '_listing_price', true );
        ?>
        <article class="afc-listing-card"> 
            <div class="afc-card-media">
                <?php if ( has_post_thumbnail() ) the_post_thumbnail('large'); ?>
                <div class="afc-card-price-tag">$<?php echo number_format($price); ?></div>
            </div>
            <div class="afc-card-content">
                <h3 class="afc-card-title"><?php the_title(); ?></h3>
                <div class="excerpt"><?php echo wp_trim_words( get_the_content(), 12 ); ?></div>
                <a href="<?php the_permalink(); ?>" class="afcglide-btn"><?php _e('View Details', 'afcglide'); ?></a>
            </div>
        </article>
        <?php
    }

    private function build_query_args( $page, $filters ) {
        return [
            'post_type'      => 'afcglide_listing',
            'post_status'    => 'publish',
            'posts_per_page' => 9,
            'paged'          => $page,
        ];
    }
}