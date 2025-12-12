<?php
/**
 * Handles frontend listing submissions.
 *
 * @package AFCGlide_Listings
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Submission_Handler {

    const MAX_IMAGES = 20;
    const MAX_FILE_SIZE = 5242880; // 5MB

    public static function init() {
        add_action( 'admin_post_afcglide_submit_listing', [ __CLASS__, 'handle_submission' ] );
        add_action( 'admin_post_nopriv_afcglide_submit_listing', [ __CLASS__, 'handle_submission' ] );
    }

    public static function handle_submission() {
        
        // 1. Security
        if ( ! isset( $_POST['afcglide_submit_listing_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_POST['afcglide_submit_listing_nonce'] ), 'afcglide_submit_listing_action' ) ) {
            wp_die('Security check failed');
        }

        if ( ! is_user_logged_in() || ! current_user_can( 'edit_posts' ) ) {
            wp_die('Permission denied. You must be logged in to submit.');
        }

        // 2. Sanitize Data
        $title = sanitize_text_field( $_POST['listing_title'] );
        $desc  = wp_kses_post( $_POST['listing_description'] );
        
        $meta = [
            '_listing_price' => floatval( $_POST['listing_price'] ),
            '_listing_beds'  => intval( $_POST['beds'] ),
            '_listing_baths' => floatval( $_POST['baths'] ),
            '_listing_sqft'  => intval( $_POST['sqft'] ),
        ];

        // 3. Create Post
        $post_id = wp_insert_post([
            'post_type'    => 'afcglide_listing',
            'post_title'   => $title,
            'post_content' => $desc,
            'post_status'  => 'pending',
            'post_author'  => get_current_user_id(),
            'meta_input'   => $meta,
        ]);

        if ( is_wp_error( $post_id ) ) {
            wp_die('Error creating listing');
        }

        // 4. Handle Taxonomies (Locations, Types, Status)
        // Expects inputs like: name="tax_input[property_location]"
        if ( ! empty( $_POST['tax_input'] ) ) {
            foreach ( $_POST['tax_input'] as $taxonomy => $term_id ) {
                $term_id = intval( $term_id );
                if ( $term_id > 0 ) {
                    wp_set_object_terms( $post_id, [ $term_id ], sanitize_key( $taxonomy ) );
                }
            }
        }

        // 5. Handle Images
        if ( ! empty( $_FILES['listing_images']['name'][0] ) ) {
            self::handle_images( $post_id, $_FILES['listing_images'] );
        }

        // 6. Redirect
        $redirect = add_query_arg( ['status' => 'success'], wp_get_referer() );
        wp_safe_redirect( $redirect );
        exit;
    }

    private static function handle_images( $post_id, $files ) {
        if ( ! function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            require_once ABSPATH . 'wp-admin/includes/image.php';
            require_once ABSPATH . 'wp-admin/includes/media.php';
        }

        $count = count( $files['name'] );
        $gallery_ids = [];

        for ( $i = 0; $i < $count; $i++ ) {
            if ( $files['error'][$i] !== UPLOAD_ERR_OK ) continue;

            $file = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i]
            ];

            $upload = wp_handle_upload( $file, [ 'test_form' => false ] );
            if ( ! isset( $upload['error'] ) && isset( $upload['file'] ) ) {
                $attach_id = wp_insert_attachment( [
                    'post_mime_type' => $upload['type'],
                    'post_title'     => sanitize_file_name( $file['name'] ),
                    'post_content'   => '',
                    'post_status'    => 'inherit'
                ], $upload['file'], $post_id );

                $attach_data = wp_generate_attachment_metadata( $attach_id, $upload['file'] );
                wp_update_attachment_metadata( $attach_id, $attach_data );

                // First image becomes Featured Image (Hero)
                if ( empty( $gallery_ids ) ) {
                    set_post_thumbnail( $post_id, $attach_id );
                    update_post_meta( $post_id, '_hero_image', $attach_id );
                }

                $gallery_ids[] = $attach_id;
            }
        }

        // UPGRADE: Save the Gallery IDs for the slider
        if ( ! empty( $gallery_ids ) ) {
            update_post_meta( $post_id, '_slider_images_json', json_encode( $gallery_ids ) );
        }
    }
}
?>