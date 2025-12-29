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
        // Updated to use the AJAX nonce we used in the form
        add_action( 'wp_ajax_afcglide_submit_property', [ $this, 'handle_submission' ] );
    }

    public function handle_submission() {
        check_ajax_referer('afcglide_ajax_nonce', 'nonce');

        if ( ! is_user_logged_in() ) {
            wp_send_json_error(['message' => 'You must be logged in.']);
        }

        $result = $this->create_listing();

        if ( is_wp_error( $result ) ) {
            wp_send_json_error(['message' => $result->get_error_message()]);
        } else {
            wp_send_json_success(['message' => 'Luxury listing submitted successfully!', 'redirect' => home_url('/thank-you/')]);
        }
    }

    private function create_listing() {
        $data = $this->get_form_data();

        if ( empty( $data['title'] ) ) {
            return new \WP_Error( 'bad_title', 'A property title is required.' );
        }

        $post_id = wp_insert_post([
            'post_title'   => $data['title'],
            'post_content' => $data['description'],
            'post_type'    => 'afcglide_listing',
            'post_status'  => 'pending', 
            'post_author'  => get_current_user_id(),
        ]);

        if ( is_wp_error( $post_id ) ) return $post_id;

        $this->save_metadata( $post_id, $data );
        $this->handle_media_upload( $post_id );

        return $post_id;
    }

    private function get_form_data() {
        return [
            'title'          => sanitize_text_field( $_POST['property_title'] ?? '' ),
            'description'    => wp_kses_post( $_POST['property_description'] ?? '' ),
            'price'          => sanitize_text_field( $_POST['price'] ?? '' ),
            'beds'           => sanitize_text_field( $_POST['beds'] ?? '' ),
            'baths'          => sanitize_text_field( $_POST['baths'] ?? '' ),
            'listing_status' => sanitize_text_field( $_POST['listing_status'] ?? 'just_listed' ),
            'amenities'      => isset($_POST['amenities']) ? (array) $_POST['amenities'] : [], // Handles the Luxury 20
        ];
    }

    private function save_metadata( $post_id, $data ) {
        update_post_meta( $post_id, '_listing_price', $data['price'] );
        update_post_meta( $post_id, '_listing_beds', $data['beds'] );
        update_post_meta( $post_id, '_listing_baths', $data['baths'] );
        update_post_meta( $post_id, '_listing_status', $data['listing_status'] );
        update_post_meta( $post_id, '_listing_amenities', $data['amenities'] );
    }

    /**
     * THE MEDIA ENGINE
     * Handles Hero, 3-Photo Stack, and Slider Gallery
     */
    private function handle_media_upload( $post_id ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // 1. Process Hero Image (The Money Shot)
        if ( ! empty( $_FILES['hero_image']['name'] ) ) {
            $attachment_id = media_handle_upload( 'hero_image', $post_id );
            if ( ! is_wp_error( $attachment_id ) ) {
                set_post_thumbnail( $post_id, $attachment_id ); // Set as Featured Image
                update_post_meta( $post_id, '_hero_image_id', $attachment_id );
            }
        }

        // 2. Process the 3-Photo Stack
        if ( ! empty( $_FILES['stack_images'] ) ) {
            $stack_ids = $this->upload_multiple_files( 'stack_images', $post_id );
            update_post_meta( $post_id, '_stack_images_json', json_encode( $stack_ids ) );
        }

        // 3. Process the Gallery Slider
        if ( ! empty( $_FILES['slider_images'] ) ) {
            $slider_ids = $this->upload_multiple_files( 'slider_images', $post_id );
            update_post_meta( $post_id, '_slider_images_json', json_encode( $slider_ids ) );
        }
    }

    private function upload_multiple_files( $file_key, $post_id ) {
        $attachment_ids = [];
        $files = $_FILES[$file_key];

        foreach ( $files['name'] as $key => $value ) {
            if ( $files['name'][$key] ) {
                $file = [
                    'name'     => $files['name'][$key],
                    'type'     => $files['type'][$key],
                    'tmp_name' => $files['tmp_name'][$key],
                    'error'    => $files['error'][$key],
                    'size'     => $files['size'][$key]
                ];

                $_FILES['single_upload'] = $file;
                $attachment_id = media_handle_upload( 'single_upload', $post_id );

                if ( ! is_wp_error( $attachment_id ) ) {
                    $attachment_ids[] = $attachment_id;
                }
            }
        }
        return $attachment_ids;
    }
}