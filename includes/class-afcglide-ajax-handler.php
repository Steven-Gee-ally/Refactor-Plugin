<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Ajax_Handler {

    public static function init() {
        // Front-end Submission
        add_action( 'wp_ajax_afc_submit_listing', [ __CLASS__, 'handle_front_submission' ] );
        
        // Command Center Toggles (Broker Dashboard)
        add_action( 'wp_ajax_afc_toggle_lockdown_ajax', [ __CLASS__, 'handle_command_center_toggles' ] );

        // Frontend Filtering
        add_action( 'wp_ajax_afcglide_filter_listings', [ __CLASS__, 'handle_filtering' ] );
        add_action( 'wp_ajax_nopriv_afcglide_filter_listings', [ __CLASS__, 'handle_filtering' ] );

        // Agent Registration
        add_action( 'wp_ajax_afc_register_agent', [ __CLASS__, 'handle_registration' ] );
        add_action( 'wp_ajax_nopriv_afc_register_agent', [ __CLASS__, 'handle_registration' ] );
    }

    /**
     * HANDLE FRONT-END SUBMISSION
     * Processes the "Luxury Listing" form from the agent portal.
     */
    public static function handle_front_submission() {
        check_ajax_referer( 'afcglide_ajax_nonce', 'nonce' );

        if ( ! is_user_logged_in() ) {
            wp_send_json_error( 'Unauthorized.' );
        }

        $user_id = get_current_user_id();
        
        // 1. Create the Listing Post
        $post_data = [
            'post_title'   => sanitize_text_field( $_POST['listing_title'] ),
            'post_status'  => ( get_option('afc_listing_approval') === 'yes' ) ? 'pending' : 'publish',
            'post_type'    => 'afcglide_listing',
            'post_author'  => $user_id,
        ];

        $post_id = wp_insert_post( $post_data );

        if ( is_wp_error( $post_id ) ) {
            wp_send_json_error( 'Database Error.' );
        }

        // 2. Save Meta Data
        update_post_meta( $post_id, '_listing_price', sanitize_text_field( $_POST['listing_price'] ) );
        update_post_meta( $post_id, '_agent_name_display', sanitize_text_field( $_POST['agent_name'] ) );
        update_post_meta( $post_id, '_agent_phone_display', sanitize_text_field( $_POST['agent_phone'] ) );

        // 3. Handle Luxury Media (The 16-Photo Gallery)
        if ( ! empty( $_FILES['property_photos'] ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );

            $files = $_FILES['property_photos'];
            $attachment_ids = [];

            foreach ( $files['name'] as $key => $value ) {
                if ( $files['name'][$key] ) {
                    $file = [
                        'name'     => $files['name'][$key],
                        'type'     => $files['type'][$key],
                        'tmp_name' => $files['tmp_name'][$key],
                        'error'    => $files['error'][$key],
                        'size'     => $files['size'][$key]
                    ];

                    $attachment_id = media_handle_sideload( $file, $post_id );

                    if ( ! is_wp_error( $attachment_id ) ) {
                        $attachment_ids[] = $attachment_id;
                        
                        // Set the first image as the Featured (Hero) Image
                        if ( count($attachment_ids) === 1 ) {
                            set_post_thumbnail( $post_id, $attachment_id );
                        }
                    }
                }
            }
            // Save the array of IDs for your "Shutterbug" Gallery
            update_post_meta( $post_id, '_stack_images_json', json_encode( $attachment_ids ) );
        }

        wp_send_json_success( [
            'message' => '🚀 Listing Received! ' . ( (get_option('afc_listing_approval') === 'yes') ? 'Awaiting broker approval.' : 'It is now live.' ),
            'redirect' => get_permalink( $post_id )
        ] );
    }

    /**
     * HANDLE COMMAND CENTER TOGGLES
     * Instant saves for the Broker Dashboard.
     */
    public static function handle_command_center_toggles() {
        check_ajax_referer( 'afc_lockdown_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions.' );
        }

        $type   = sanitize_text_field( $_POST['type'] ); // e.g., 'lockdown'
        $status = sanitize_text_field( $_POST['status'] ); // 'yes' or 'no'

        // Map the toggle types to your option keys
        $option_map = [
            'lockdown' => 'afc_identity_lockdown',
            'approval' => 'afc_listing_approval',
            'worker'   => 'afc_worker_mode'
        ];

        if ( isset( $option_map[$type] ) ) {
            update_option( $option_map[$type], $status );
            wp_send_json_success( "Setting updated: $type set to $status" );
        } else {
            wp_send_json_error( 'Invalid toggle type.' );
        }
    }

    /**
     * HANDLE FRONTEND FILTERING
     */
    public static function handle_filtering() {
        check_ajax_referer( 'afcglide_ajax_nonce', 'nonce' );

        $page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        $filters = isset( $_POST['filters'] ) ? $_POST['filters'] : [];

        $args = [
            'post_type'      => 'afcglide_listing',
            'post_status'    => 'publish',
            'posts_per_page' => 6,
            'paged'          => $page,
        ];

        // Meta Query for Filtering
        $meta_query = [];
        if ( ! empty( $filters['min_price'] ) ) {
            $meta_query[] = [ 'key' => '_listing_price', 'value' => $filters['min_price'], 'type' => 'NUMERIC', 'compare' => '>=' ];
        }
        if ( ! empty( $filters['max_price'] ) ) {
            $meta_query[] = [ 'key' => '_listing_price', 'value' => $filters['max_price'], 'type' => 'NUMERIC', 'compare' => '<=' ];
        }
        if ( ! empty( $filters['beds'] ) ) {
            $meta_query[] = [ 'key' => '_listing_beds', 'value' => $filters['beds'], 'compare' => '>=' ];
        }

        if ( ! empty( $meta_query ) ) {
            $args['meta_query'] = $meta_query;
        }

        $query = new \WP_Query( $args );
        $html = '';

        if ( $query->have_posts() ) {
            ob_start();
            while ( $query->have_posts() ) {
                $query->the_post();
                if ( class_exists( 'AFCGlide\\Listings\\AFCGlide_Shortcodes' ) ) {
                    AFCGlide_Shortcodes::render_listing_card();
                }
            }
            $html = ob_get_clean();
            wp_reset_postdata();
        }

        wp_send_json_success([
            'html'      => $html,
            'max_pages' => $query->max_num_pages
        ]);
    }

    /**
     * HANDLE AGENT REGISTRATION
     */
    public static function handle_registration() {
        check_ajax_referer( 'afcglide_register_nonce', 'nonce' );

        $name  = sanitize_text_field( $_POST['agent_name'] );
        $email = sanitize_email( $_POST['agent_email'] );
        $pass  = $_POST['agent_pass'];

        if ( empty( $name ) || empty( $email ) || empty( $pass ) ) {
            wp_send_json_error( 'Please fill all required fields.' );
        }

        if ( ! is_email( $email ) ) {
            wp_send_json_error( 'Invalid email address.' );
        }

        if ( email_exists( $email ) ) {
            wp_send_json_error( 'This email is already registered.' );
        }

        $username = sanitize_user( str_replace( ' ', '', strtolower( $name ) ) );
        if ( username_exists( $username ) ) {
            $username .= time();
        }

        $user_id = wp_create_user( $username, $pass, $email );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( $user_id->get_error_message() );
        }

        wp_update_user([
            'ID'           => $user_id,
            'display_name' => $name,
            'role'         => 'author'
        ]);

        wp_send_json_success( 'Registration successful! You can now log in.' );
    }
}