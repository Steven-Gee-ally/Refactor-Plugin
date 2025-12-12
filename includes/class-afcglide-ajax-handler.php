<?php
/**
 * AJAX Handler for AFCGlide Listings
 *
 * @package AFCGlide_Listings
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Ajax_Handler {

    public static function init() {
        // Handle Filtering & Load More (Same handler for both)
        add_action( 'wp_ajax_afcglide_filter_listings', [ __CLASS__, 'handle_request' ] );
        add_action( 'wp_ajax_nopriv_afcglide_filter_listings', [ __CLASS__, 'handle_request' ] );

        add_action( 'wp_ajax_afcglide_load_more_listings', [ __CLASS__, 'handle_request' ] );
        add_action( 'wp_ajax_nopriv_afcglide_load_more_listings', [ __CLASS__, 'handle_request' ] );
    }

    public static function handle_request() {
        check_ajax_referer( 'afcglide_ajax_nonce', 'nonce' );

        $page = isset( $_POST['page'] ) ? absint( $_POST['page'] ) : 1;
        $filters = isset( $_POST['filters'] ) ? $_POST['filters'] : [];

        // Build query
        $args = [
            'post_type'      => 'afcglide_listing',
            'post_status'    => 'publish',
            'posts_per_page' => apply_filters( 'afcglide_listings_per_page', 6 ),
            'paged'          => $page,
        ];

        // 1. Taxonomy Filters (Location, Type, Status)
        $tax_query = [ 'relation' => 'AND' ];

        if ( ! empty( $filters['location'] ) ) {
            $tax_query[] = [ 'taxonomy' => 'property_location', 'field' => 'slug', 'terms' => sanitize_text_field( $filters['location'] ) ];
        }
        if ( ! empty( $filters['type'] ) ) {
            $tax_query[] = [ 'taxonomy' => 'property_type', 'field' => 'slug', 'terms' => sanitize_text_field( $filters['type'] ) ];
        }
        if ( ! empty( $filters['status'] ) ) {
            $tax_query[] = [ 'taxonomy' => 'property_status', 'field' => 'slug', 'terms' => sanitize_text_field( $filters['status'] ) ];
        }
        if ( count( $tax_query ) > 1 ) {
            $args['tax_query'] = $tax_query;
        }

        // 2. Meta Query (Price Filtering)
        $meta_query = [ 'relation' => 'AND' ];
        
        if ( ! empty( $filters['min_price'] ) ) {
            $meta_query[] = [
                'key'     => '_listing_price',
                'value'   => floatval( $filters['min_price'] ),
                'type'    => 'NUMERIC',
                'compare' => '>='
            ];
        }
        
        if ( ! empty( $filters['max_price'] ) ) {
            $meta_query[] = [
                'key'     => '_listing_price',
                'value'   => floatval( $filters['max_price'] ),
                'type'    => 'NUMERIC',
                'compare' => '<='
            ];
        }

        if ( count( $meta_query ) > 1 ) {
            $args['meta_query'] = $meta_query;
        }

        // Run Query
        $listings_query = new \WP_Query( $args );

        ob_start();

        if ( $listings_query->have_posts() ) {
            while ( $listings_query->have_posts() ) {
                $listings_query->the_post();
                
                // UPGRADE: Safe Template Loading
                // If the separate template file exists, use it.
                $template_path = AFCG_PLUGIN_DIR . 'templates/listing-card.php';
                
                if ( file_exists( $template_path ) ) {
                    include $template_path;
                } else {
                    // FALLBACK: If template file is missing, render basic card so user sees SOMETHING
                    $price = get_post_meta( get_the_ID(), '_listing_price', true );
                    echo '<div class="afcglide-card-fallback" style="border:1px solid #ddd; padding:10px; margin-bottom:10px;">';
                    if ( has_post_thumbnail() ) { the_post_thumbnail('medium'); }
                    echo '<h3><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
                    echo '<p><strong>Price:</strong> $' . esc_html( $price ) . '</p>';
                    echo '</div>';
                }
            }
            $html = ob_get_clean();
            wp_send_json_success([ 'html' => $html, 'max_pages' => $listings_query->max_num_pages ]);
        } else {
            ob_get_clean();
            wp_send_json_error([ 'html' => '<div class="afcglide-no-results"><p>' . esc_html__( 'No listings found matching your criteria.', 'afcglide' ) . '</p></div>' ]);
        }

        wp_reset_postdata();
        wp_die();
    }
}
?>