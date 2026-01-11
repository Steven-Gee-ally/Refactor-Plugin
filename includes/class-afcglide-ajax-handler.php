<?php
namespace AFCGlide\Listings;

/**
 * AFCGlide AJAX Handler - PRODUCTION MASTER
 * Handles listings submission, file processing, and Command Center logic.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Ajax_Handler {

    public static function init() {
        $instance = new self();
        
        // Command Center Action (The switches you built!)
        add_action( 'wp_ajax_afc_toggle_lockdown_ajax', [ $instance, 'handle_lockdown_toggle' ] );
        
        // Agent Submission Action
        add_action( 'wp_ajax_afcglide_submit_listing', [ $instance, 'handle_listing_submission' ] );
        
        // Filter Action (For the Grid)
        add_action( 'wp_ajax_afcglide_filter_listings', [ $instance, 'filter_listings' ] );
        add_action( 'wp_ajax_nopriv_afcglide_filter_listings', [ $instance, 'filter_listings' ] );
    }

    /**
     * Broker Control: This connects the front-end switches to the database.
     */
    public function handle_lockdown_toggle() {
        check_ajax_referer( 'afc_lockdown_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized Clearance Level' );
        }

        $status = sanitize_text_field( $_POST['status'] ); 
        $type   = sanitize_text_field( $_POST['type'] );   

        $option_map = [
            'lockdown' => 'afc_identity_lockdown',
            'approval' => 'afc_listing_approval',
            'worker'   => 'afc_worker_mode'
        ];

        if ( isset( $option_map[$type] ) ) {
            update_option( $option_map[$type], $status );
            wp_send_json_success( [ 'message' => 'Command Center Updated' ] );
        }

        wp_send_json_error( 'Invalid Control Type' );
    }

    /**
     * Handle Listing Submission with "Command Center" logic
     */
    public function handle_listing_submission() {
        check_ajax_referer( 'afcglide_ajax_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( [ 'message' => 'Session expired. Please log in.' ] );
        }

        $approval_required = get_option('afc_listing_approval', 'no') === 'yes';
        $is_admin = current_user_can('manage_options');

        $target_status = ( $approval_required && !$is_admin ) ? 'pending' : 'publish';

        $post_id = wp_insert_post( [
            'post_title'   => sanitize_text_field( $_POST['property_title'] ),
            'post_status'  => $target_status,
            'post_type'    => 'afcglide_listing',
            'post_author'  => get_current_user_id(),
        ] );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( [ 'message' => 'Submission failed.' ] );
        }

        // Run the synced meta save
        $this->save_listing_meta( $post_id );

        if ( ! empty( $_FILES ) ) {
            $this->process_file_uploads( $post_id );
        }

        $success_msg = ($target_status === 'pending') 
            ? '✨ Listing received! Awaiting Broker approval.' 
            : '🚀 Success! Your luxury listing is now LIVE.';

        wp_send_json_success( [ 
            'message' => $success_msg,
            'post_id' => $post_id 
        ] );
    }

    /**
     * SYNCED META SAVING
     * Uses the exact keys from our render_submit_form()
     */
    private function save_listing_meta( $post_id ) {
        update_post_meta( $post_id, '_listing_price', sanitize_text_field( $_POST['listing_price'] ?? '' ) );
        update_post_meta( $post_id, '_listing_beds', absint( $_POST['listing_beds'] ?? 0 ) );
        update_post_meta( $post_id, '_listing_baths', sanitize_text_field( $_POST['listing_baths'] ?? '' ) );
        update_post_meta( $post_id, '_listing_property_type', sanitize_text_field( $_POST['listing_property_type'] ?? '' ) );
        update_post_meta( $post_id, '_agent_name_display', sanitize_text_field( $_POST['agent_name_display'] ?? '' ) );
        update_post_meta( $post_id, '_agent_phone_display', sanitize_text_field( $_POST['agent_phone_display'] ?? '' ) );
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
        // We can now call the Grid logic from Shortcodes here to keep it DRY (Don't Repeat Yourself)
        wp_send_json_success( [ 'html' => 'Rendered Grid' ] );
    }
}