<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Ajax Handler - Meticulous Save Logic v1.2
 * Specifically mapped to Protected Meta Keys
 */
class AFCGlide_Ajax_Handler {

    public static function init() {
        $self = new self();
        // Hook for logged-in agents
        add_action( 'wp_ajax_afcglide_submit_listing', [ $self, 'handle_submission' ] );
    }

    public static function handle_submission() {
        // 1. Security First (The Nonce)
        check_ajax_referer( 'afcglide_ajax_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Unauthorized Access' ] );
        }

        // 2. Collect and Sanitize Form Data
        // These keys on the left MUST match your HTML form 'name' attributes
        $raw_data = [
            'price'   => isset($_POST['listing_price']) ? sanitize_text_field($_POST['listing_price']) : '',
            'beds'    => isset($_POST['listing_beds']) ? sanitize_text_field($_POST['listing_beds']) : '',
            'baths'   => isset($_POST['listing_baths']) ? sanitize_text_field($_POST['listing_baths']) : '',
            'sqft'    => isset($_POST['listing_sqft']) ? sanitize_text_field($_POST['listing_sqft']) : '',
            'address' => isset($_POST['property_address']) ? sanitize_text_field($_POST['property_address']) : '',
            'lat'     => isset($_POST['gps_lat']) ? sanitize_text_field($_POST['gps_lat']) : '',
            'lng'     => isset($_POST['gps_lng']) ? sanitize_text_field($_POST['gps_lng']) : '',
        ];

        // 3. Create the Post (Listing)
        $listing_id = wp_insert_post([
            'post_title'   => sanitize_text_field($_POST['listing_title']),
            'post_content' => wp_kses_post($_POST['listing_description']),
            'post_status'  => 'publish', // Or 'pending' if you want to review first
            'post_type'    => 'afcglide_listing',
            'post_author'  => get_current_user_id(),
        ]);

        if ( is_wp_error( $listing_id ) ) {
            wp_send_json_error( [ 'message' => 'Database Error' ] );
        }

        // 4. THE METICULOUS MAPPING (Burning data to Meta Keys)
        update_post_meta( $listing_id, '_listing_price', $raw_data['price'] );
        update_post_meta( $listing_id, '_listing_beds', $raw_data['beds'] );
        update_post_meta( $listing_id, '_listing_baths', $raw_data['baths'] );
        update_post_meta( $listing_id, '_listing_sqft', $raw_data['sqft'] );
        update_post_meta( $listing_id, '_property_address', $raw_data['address'] );
        update_post_meta( $listing_id, '_gps_lat', $raw_data['lat'] );
        update_post_meta( $listing_id, '_gps_lng', $raw_data['lng'] );

        // 5. Handle Amenities (The Array)
        if ( isset($_POST['amenities']) && is_array($_POST['amenities']) ) {
            $amenities = array_map( 'sanitize_text_field', $_POST['amenities'] );
            update_post_meta( $listing_id, '_listing_amenities', $amenities );
        }

        // 6. Handle Images (Hero & Stack)
        if ( isset($_POST['hero_id']) ) {
            update_post_meta( $listing_id, '_hero_image_id', absint($_POST['hero_id']) );
            set_post_thumbnail( $listing_id, absint($_POST['hero_id']) ); // Also set as Featured
        }

        if ( isset($_POST['stack_ids']) && is_array($_POST['stack_ids']) ) {
            $stack_ids = array_map( 'absint', $_POST['stack_ids'] );
            update_post_meta( $listing_id, '_stack_images_json', json_encode($stack_ids) );
        }

        wp_send_json_success( [ 'message' => 'Listing Created Successfully!', 'url' => get_permalink($listing_id) ] );
    }
}