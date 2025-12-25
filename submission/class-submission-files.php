<?php
namespace AFCGlide\Listings\Submission;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class Submission_Files
 * Handles the "Roadmap" media logic for AFCGlide v3.0
 */
class Submission_Files {

    /**
     * Initialize the class
     */
    public static function init() {
        // Initialization hook for future use
        // This method is required by the main plugin bootstrap
    }

    public function __construct() {
        // Load WP Media requirements for front-end processing
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
    }

    /**
     * Primary handler for all media zones
     */
    public function process_submission_media( $post_id ) {
        
        // ZONE 1: The Hero (Featured Image)
        if ( ! empty( $_FILES['hero_image']['name'] ) ) {
            $hero_id = media_handle_upload( 'hero_image', $post_id );
            if ( ! is_wp_error( $hero_id ) ) {
                set_post_thumbnail( $post_id, $hero_id );
                update_post_meta( $post_id, '_hero_image_id', $hero_id );
            }
        }

        // ZONE 2: The 3-Photo Stack
        if ( ! empty( $_FILES['stack_images']['name'][0] ) ) {
            $stack_ids = $this->handle_multi_upload( 'stack_images', $post_id );
            update_post_meta( $post_id, '_property_stack_ids', $stack_ids );
        }

        // ZONE 3: The Full Slider Gallery
        if ( ! empty( $_FILES['slider_images']['name'][0] ) ) {
            $slider_ids = $this->handle_multi_upload( 'slider_images', $post_id );
            update_post_meta( $post_id, '_property_slider_ids', $slider_ids );
        }

        // AGENT BRANDING: Photo & Agency Logo
        $this->process_branding_strip( $post_id );
    }

    /**
     * The Multi-Upload Engine
     * Converts PHP's messy multi-file array into single handles WP can understand.
     */
    private function handle_multi_upload( $file_key, $post_id ) {
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

                $_FILES['temp_roadmap_upload'] = $file;
                $aid = media_handle_upload( 'temp_roadmap_upload', $post_id );

                if ( ! is_wp_error( $aid ) ) {
                    $attachment_ids[] = $aid;
                }
            }
        }
        return $attachment_ids;
    }

    /**
     * Processes Agent-specific branding visuals
     */
    private function process_branding_strip( $post_id ) {
        $branding = ['agent_photo' => '_agent_photo_id', 'agency_logo' => '_agency_logo_id'];

        foreach ( $branding as $key => $meta ) {
            if ( ! empty( $_FILES[$key]['name'] ) ) {
                $aid = media_handle_upload( $key, $post_id );
                if ( ! is_wp_error( $aid ) ) {
                    update_post_meta( $post_id, $meta, $aid );
                }
            }
        }
    }
}