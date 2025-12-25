<?php
namespace AFCGlide\Listings\Submission;

use AFCGlide\Listings\Helpers\Validator;
use AFCGlide\Listings\Helpers\Sanitizer;
use AFCGlide\Listings\Helpers\Message_Helper;

if ( ! defined( 'ABSPATH' ) ) exit;

class Submission_Listing {

    public static function init() {
        new self();
    }

    public function __construct() {
        add_action( 'template_redirect', [ $this, 'handle_submission' ] );
    }

    public function handle_submission() {
        if ( ! isset( $_POST['afcglide_submission_form'] ) ) return;

        if ( ! isset( $_POST['afcglide_nonce'] ) || ! wp_verify_nonce( $_POST['afcglide_nonce'], 'afcglide_new_listing' ) ) {
            Message_Helper::error( 'Security check failed. Please try again.' );
            return;
        }

        if ( ! is_user_logged_in() ) {
            Message_Helper::error( 'You must be logged in to submit a listing.' );
            return;
        }

        $result = $this->create_listing();

        if ( is_wp_error( $result ) ) {
            Message_Helper::error( $result->get_error_message() );
        } else {
            $redirect_url = add_query_arg( 'listing_submitted', 'success', wp_get_referer() );
            wp_safe_redirect( $redirect_url );
            exit;
        }
    }

    private function create_listing() {
        $data = $this->get_form_data();

        if ( empty( $data['title'] ) || ! Validator::min_length( $data['title'], 5 ) ) {
            return new \WP_Error( 'bad_title', 'A valid property title is required (min 5 chars).' );
        }

        $post_id = wp_insert_post([
            'post_title'   => $data['title'],
            'post_content' => $data['description'],
            'post_type'    => 'afcglide_listing',
            'post_status'  => 'pending', 
            'post_author'  => get_current_user_id(),
        ]);

        if ( is_wp_error( $post_id ) ) return $post_id;

        // TAXONOMY HANDSHAKE
        if ( ! empty( $_POST['listing_location'] ) ) {
            wp_set_object_terms( $post_id, (int)$_POST['listing_location'], 'listing_location' );
        }
        if ( ! empty( $_POST['listing_type'] ) ) {
            wp_set_object_terms( $post_id, (int)$_POST['listing_type'], 'listing_type' );
        }

        $this->save_metadata( $post_id, $data );

        do_action( 'afcglide_after_listing_created', $post_id, $_FILES );

        return $post_id;
    }

    /**
     * 3. THE CLEANER (Updated for Luxury Fields)
     */
    private function get_form_data() {
        return [
            'title'       => Sanitizer::text( $_POST['listing_title'] ?? '' ),
            'description' => Sanitizer::html( $_POST['listing_description'] ?? '' ),
            'price'       => Sanitizer::price( $_POST['_listing_price'] ?? '' ),
            'address'     => Sanitizer::text( $_POST['_listing_address'] ?? '' ),    // ADDED
            'beds'        => Sanitizer::int( $_POST['_listing_beds'] ?? '' ),
            'baths'       => Sanitizer::decimal( $_POST['_listing_baths'] ?? '' ),
            'sqft'        => Sanitizer::int( $_POST['_listing_sqft'] ?? '' ),
            'amenities'   => Sanitizer::text( $_POST['_listing_amenities'] ?? '' ),  // ADDED
            'cta'         => Sanitizer::text( $_POST['_listing_cta'] ?? '' ),        // ADDED
            'gps_lat'     => Sanitizer::text( $_POST['_gps_lat'] ?? '' ),
            'gps_lng'     => Sanitizer::text( $_POST['_gps_lng'] ?? '' ),
        ];
    }

    /**
     * 4. THE STORAGE (Updated Mapping)
     */
    private function save_metadata( $post_id, $data ) {
        // We explicitly map array keys to database meta keys
        $meta_map = [
            'price'     => '_listing_price',
            'address'   => '_listing_address',
            'beds'      => '_listing_beds',
            'baths'     => '_listing_baths',
            'sqft'      => '_listing_sqft',
            'amenities' => '_listing_amenities',
            'cta'       => '_listing_cta',
            'gps_lat'   => '_gps_lat',
            'gps_lng'   => '_gps_lng',
        ];

        foreach ( $meta_map as $data_key => $db_key ) {
            if ( isset( $data[$data_key] ) ) {
                update_post_meta( $post_id, $db_key, $data[$data_key] );
            }
        }
        
        update_post_meta( $post_id, '_listing_status', 'for-sale' );
    }
}