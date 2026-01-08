<?php
namespace AFCGlide\Listings;

/**
 * AFCGlide AJAX Handler - PRODUCTION MASTER
 * Handles listings submission, file processing, and Dashboard toggles.
 *
 * @package AFCGlide\Listings
 * @since 3.6.6
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Ajax_Handler {

    public static function init() {
        $instance = new self();
        
        // Dashboard Lockdown Toggles (The switches you just styled!)
        add_action( 'wp_ajax_afc_toggle_lockdown_ajax', [ $instance, 'handle_lockdown_toggle' ] );
        
        // Agent Submission Action
        add_action( 'wp_ajax_afcglide_submit_listing', [ $instance, 'handle_listing_submission' ] );
        
        // Filter Action (For the Grid)
        add_action( 'wp_ajax_afcglide_filter_listings', [ $instance, 'filter_listings' ] );
        add_action( 'wp_ajax_nopriv_afcglide_filter_listings', [ $instance, 'filter_listings' ] );
    }

    /**
     * Handle the AJAX request from the Dashboard Lockdown Switches
     */
    public function handle_lockdown_toggle() {
        check_ajax_referer( 'afc_lockdown_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $status = sanitize_text_field( $_POST['status'] ); // 'yes' or 'no'
        $type   = sanitize_text_field( $_POST['type'] );   // 'lockdown' or 'ghost'

        $option_name = ( $type === 'lockdown' ) ? 'afc_lockdown_enabled' : 'afc_ghost_mode';
        update_option( $option_name, $status );

        wp_send_json_success( [ 'message' => 'System updated' ] );
    }

    /**
     * Handle Listing Submission
     */
    public function handle_listing_submission() {
        check_ajax_referer( 'afcglide_ajax_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => __( 'Session expired. Please log in.', 'afcglide' ) ] );
        }

        $post_id = wp_insert_post( [
            'post_title'   => sanitize_text_field( $_POST['property_title'] ),
            'post_content' => isset( $_POST['property_description'] ) ? wp_kses_post( $_POST['property_description'] ) : '',
            'post_status'  => 'pending',
            'post_type'    => 'afcglide_listing',
            'post_author'  => get_current_user_id(),
        ] );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [ 'message' => __( 'Submission failed.', 'afcglide' ) ] );
        }

        $this->save_listing_meta( $post_id );

        if ( ! empty( $_FILES ) ) {
            $this->process_file_uploads( $post_id );
        }

        wp_send_json_success( [ 
            'message' => __( '✨ Luxury listing submitted for review!', 'afcglide' ),
            'post_id' => $post_id 
        ] );
    }

    private function save_listing_meta( $post_id ) {
        update_post_meta( $post_id, '_listing_price', sanitize_text_field( $_POST['price'] ?? '' ) );
        update_post_meta( $post_id, '_listing_beds', absint( $_POST['beds'] ?? 0 ) );
        update_post_meta( $post_id, '_listing_baths', sanitize_text_field( $_POST['baths'] ?? '' ) );
        update_post_meta( $post_id, 'agent_name', sanitize_text_field( $_POST['agent_name'] ?? '' ) );
    }

    private function process_file_uploads( $post_id ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        if ( ! empty( $_FILES['hero_image']['name'] ) ) {
            $hero_id = media_handle_upload( 'hero_image', $post_id );
            if ( ! is_wp_error( $hero_id ) ) {
                set_post_thumbnail( $post_id, $hero_id );
                update_post_meta( $post_id, '_hero_image_id', $hero_id );
            }
        }

        if ( ! empty( $_FILES['stack_images']['name'][0] ) ) {
            $stack_ids = $this->handle_multiple_uploads( 'stack_images', $post_id );
            update_post_meta( $post_id, '_stack_images_json', json_encode( $stack_ids ) );
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

    public function filter_listings() {
        check_ajax_referer( 'afcglide_ajax_nonce', 'nonce' );
        $query = new \WP_Query( [
            'post_type'      => 'afcglide_listing',
            'post_status'    => 'publish',
            'posts_per_page' => 9,
        ] );
        // ... rendering logic ...
        wp_send_json_success( [ 'html' => 'Rendered Grid' ] );
    }
}