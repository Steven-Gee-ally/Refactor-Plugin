<?php
/**
 * Upload Helper - Refactored for AFCGlide v3
 * Handles Hero Images and JSON-based Gallery Sliders.
 *
 * @package AFCGlide\Listings\Helpers
 */

namespace AFCGlide\Listings\Helpers;

use AFCGlide\Core\Constants;

if ( ! defined( 'ABSPATH' ) ) exit;

class Upload_Helper {

    const MAX_FILE_SIZE = 5242880; // 5MB
    const ALLOWED_TYPES = [ 'image/jpeg', 'image/jpg', 'image/png', 'image/webp' ];

    /**
     * Ensure WP Media functions are available
     */
    private static function load_wp_functions() {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
    }

    /**
     * 1. THE HERO IMAGE (Single Upload)
     */
    public static function upload_single( $file_key, $post_id = 0 ) {
        self::load_wp_functions();

        if ( ! Validator::file_upload( $file_key ) ) {
            return new \WP_Error( 'no_file', 'No file uploaded.' );
        }

        // Standard WP Media Upload
        $attachment_id = media_handle_upload( $file_key, $post_id );

        if ( is_wp_error( $attachment_id ) ) return $attachment_id;

        // Set as Featured Image automatically
        set_post_thumbnail( $post_id, $attachment_id );

        return $attachment_id;
    }

    /**
     * 2. THE GALLERY (Multiple Uploads)
     */
    public static function upload_multiple( $file_key, $post_id = 0 ) {
        self::load_wp_functions();

        if ( ! isset( $_FILES[ $file_key ] ) || ! is_array( $_FILES[ $file_key ]['name'] ) ) {
            return [];
        }

        $files = $_FILES[ $file_key ];
        $attachment_ids = [];

        for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
            if ( empty( $files['name'][$i] ) ) continue;

            // Reconstruct $_FILES for media_handle_upload
            $file_id = "temp_img_$i";
            $_FILES[$file_id] = [
                'name'     => $files['name'][$i],
                'type'     => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error'    => $files['error'][$i],
                'size'     => $files['size'][$i],
            ];

            $aid = media_handle_upload( $file_id, $post_id );
            if ( ! is_wp_error( $aid ) ) {
                $attachment_ids[] = $aid;
            }
            unset($_FILES[$file_id]);
        }

        return $attachment_ids;
    }

    /**
     * 3. THE DATA SYNC (Saving to Database)
     */
    public static function save_gallery( $attachment_ids, $post_id ) {
        if ( empty( $attachment_ids ) ) return false;

        // CRITICAL SYNC: Save as JSON for the Slider
        return update_post_meta( $post_id, Constants::META_SLIDER_JSON, json_encode( $attachment_ids ) );
    }

    public static function get_gallery( $post_id ) {
        $data = get_post_meta( $post_id, Constants::META_SLIDER_JSON, true );
        $ids  = json_decode( $data, true );
        return is_array( $ids ) ? $ids : [];
    }

    /**
     * 4. UTILITIES
     */
    public static function get_attachment_url( $attachment_id, $size = 'large' ) {
        return wp_get_attachment_image_url( $attachment_id, $size );
    }

    public static function delete_attachment( $attachment_id ) {
        return wp_delete_attachment( $attachment_id, true );
    }
}