<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Ajax_Handler {

    public static function init() {
        add_action( 'wp_ajax_afcglide_submit_listing', [ __CLASS__, 'handle_front_submission' ] );
        add_action( 'wp_ajax_nopriv_afcglide_submit_listing', [ __CLASS__, 'handle_front_submission' ] ); // For logged-out users if needed
        add_action( 'wp_ajax_afc_toggle_lockdown_ajax', [ __CLASS__, 'handle_lockdown_toggle' ] );
    }

    public static function handle_front_submission() {
        // SECURITY HANDSHAKE
        check_ajax_referer( 'afc_nonce', 'security' );

        // 🔒 GLOBAL LOCKDOWN CHECK
        if ( get_option('afc_global_lockdown', '0') === '1' && ! current_user_can('manage_options') ) {
            wp_send_json_error([
                'message' => '🔒 SYSTEM LOCKDOWN ACTIVE: All listing updates are currently frozen by the Lead Broker. Contact your administrator.'
            ]);
        }

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            wp_send_json_error([ 'message' => 'Session expired. Please log in again.' ]);
        }

        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        $title   = sanitize_text_field( $_POST['listing_title'] ?? '' );

        // Quality Gatekeeper: Server-side Title Check
        if ( empty($title) ) {
            wp_send_json_error([ 'message' => 'A Property Title is required for luxury broadcasting.' ]);
        }

        // Prepare post data
        $post_data = [
            'post_title'   => $title,
            'post_content' => wp_kses_post( $_POST['listing_description'] ?? '' ),
            'post_status'  => 'publish',
            'post_type'    => 'afcglide_listing',
            'post_author'  => $user_id,
        ];

        // Update vs Insert
        if ( $post_id > 0 ) {
            $existing_post = get_post( $post_id );
            if ( ! $existing_post || ( $existing_post->post_author != $user_id && ! current_user_can( 'manage_options' ) ) ) {
                wp_send_json_error([ 'message' => 'Access Denied: You do not own this asset.' ]);
            }
            $post_data['ID'] = $post_id;
            $final_id = wp_update_post( $post_data );
            $message = '✅ Asset Synced Successfully!';
        } else {
            $final_id = wp_insert_post( $post_data );
            $message = '🚀 Asset Broadcasted Live!';
        }

        if ( is_wp_error( $final_id ) ) {
            wp_send_json_error([ 'message' => 'Database Sync Failed: ' . $final_id->get_error_message() ]);
        }

        // DATA MAPPING - Core Listing Fields
        $meta_map = [
            'listing_price'   => '_listing_price',
            'listing_address' => '_listing_address',
            'listing_beds'    => '_listing_beds',
            'listing_baths'   => '_listing_baths',
            'listing_sqft'    => '_listing_sqft',
            'listing_status'  => '_listing_status',
        ];

        foreach ( $meta_map as $form_key => $meta_key ) {
            if ( isset( $_POST[$form_key] ) ) {
                update_post_meta( $final_id, $meta_key, sanitize_text_field( $_POST[$form_key] ) );
            }
        }

        // Handle Amenities (array)
        if ( isset( $_POST['listing_amenities'] ) && is_array( $_POST['listing_amenities'] ) ) {
            $amenities = array_map( 'sanitize_text_field', $_POST['listing_amenities'] );
            update_post_meta( $final_id, '_listing_amenities', $amenities );
        } else {
            // Clear amenities if none selected
            delete_post_meta( $final_id, '_listing_amenities' );
        }

        // MEDIA BROADCAST - Hero Image
        if ( ! empty( $_FILES['hero_file']['name'] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            $hero_id = media_handle_upload( 'hero_file', $final_id );
            
            if ( ! is_wp_error( $hero_id ) ) {
                set_post_thumbnail( $final_id, $hero_id );
                update_post_meta( $final_id, '_listing_hero_id', $hero_id );
            } else {
                // Log error but don't fail the whole submission
                error_log( 'AFCGlide Hero Upload Error: ' . $hero_id->get_error_message() );
            }
        }

        // Success Response
        wp_send_json_success([
            'url'     => get_permalink( $final_id ),
            'message' => $message,
            'post_id' => $final_id
        ]);
    }

    public static function handle_lockdown_toggle() {
        check_ajax_referer( 'afc_lockdown_nonce', 'security' );
        
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Unauthorized' );
        }

        $type   = sanitize_text_field( $_POST['type'] ?? '' );
        $status = sanitize_text_field( $_POST['status'] ?? '' );

        update_option( 'afc_' . $type, $status );
        wp_send_json_success( 'Settings Updated' );
    }
}