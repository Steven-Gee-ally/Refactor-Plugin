<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Agent Manager
 * Handles agent selection and creation from the dashboard
 */
class AFCGlide_Agent_Manager {

    public static function init() {
        // AJAX handlers
        add_action( 'wp_ajax_afcglide_get_agent_details', [ __CLASS__, 'get_agent_details' ] );
        add_action( 'wp_ajax_afcglide_create_agent', [ __CLASS__, 'create_agent' ] );
        add_action( 'wp_ajax_afcglide_get_all_agents', [ __CLASS__, 'get_all_agents' ] );
    }

    /**
     * Get all agents for the selector
     */
    public static function get_all_agents() {
        check_ajax_referer( 'afcglide_admin_nonce', 'nonce' );

        $users = get_users([
            'orderby' => 'display_name',
            'order'   => 'ASC'
        ]);

        $agents = [];
        foreach ( $users as $user ) {
            $agents[] = [
                'id'   => $user->ID,
                'name' => $user->display_name,
                'email' => $user->user_email
            ];
        }

        wp_send_json_success( $agents );
    }

    /**
     * Get detailed information for a specific agent
     */
    public static function get_agent_details() {
        check_ajax_referer( 'afcglide_admin_nonce', 'nonce' );

        $user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

        if ( ! $user_id ) {
            wp_send_json_error( __( 'Invalid user ID', 'afcglide' ) );
        }

        $user = get_user_by( 'id', $user_id );
        if ( ! $user ) {
            wp_send_json_error( __( 'User not found', 'afcglide' ) );
        }

        // Get agent meta fields
        $whatsapp = get_user_meta( $user_id, 'afc_agent_whatsapp', true );
        $headshot = get_user_meta( $user_id, 'afc_agent_headshot', true );
        $logo     = get_user_meta( $user_id, 'afc_agency_logo', true );
        $license  = get_user_meta( $user_id, 'afc_agent_license', true );

        $agent_data = [
            'id'       => $user_id,
            'name'     => $user->display_name,
            'email'    => $user->user_email,
            'whatsapp' => $whatsapp,
            'headshot' => $headshot,
            'logo'     => $logo,
            'license'  => $license,
            'role'     => implode( ', ', $user->roles )
        ];

        wp_send_json_success( $agent_data );
    }

    /**
     * Create a new agent
     */
    public static function create_agent() {
        check_ajax_referer( 'afcglide_admin_nonce', 'nonce' );

        // Verify user has permission
        if ( ! current_user_can( 'create_users' ) ) {
            wp_send_json_error( __( 'You do not have permission to create users', 'afcglide' ) );
        }

        // Get form data
        $name     = isset( $_POST['agent_name'] ) ? sanitize_text_field( $_POST['agent_name'] ) : '';
        $email    = isset( $_POST['agent_email'] ) ? sanitize_email( $_POST['agent_email'] ) : '';
        $whatsapp = isset( $_POST['agent_whatsapp'] ) ? sanitize_text_field( $_POST['agent_whatsapp'] ) : '';
        $username = isset( $_POST['agent_username'] ) ? sanitize_user( $_POST['agent_username'] ) : '';

        // Validation
        if ( empty( $name ) || empty( $email ) ) {
            wp_send_json_error( __( 'Name and email are required', 'afcglide' ) );
        }

        if ( ! is_email( $email ) ) {
            wp_send_json_error( __( 'Invalid email address', 'afcglide' ) );
        }

        if ( email_exists( $email ) ) {
            wp_send_json_error( __( 'This email is already registered', 'afcglide' ) );
        }

        // Generate username if not provided
        if ( empty( $username ) ) {
            $username = sanitize_user( str_replace( ' ', '', strtolower( $name ) ) );
            // Make sure username is unique
            $base_username = $username;
            $counter = 1;
            while ( username_exists( $username ) ) {
                $username = $base_username . $counter;
                $counter++;
            }
        }

        if ( username_exists( $username ) ) {
            wp_send_json_error( __( 'This username already exists', 'afcglide' ) );
        }

        // Generate random password
        $password = wp_generate_password( 12, true, true );

        // Create the user
        $user_id = wp_create_user( $username, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            wp_send_json_error( $user_id->get_error_message() );
        }

        // Update user display name
        wp_update_user([
            'ID'           => $user_id,
            'display_name' => $name,
            'first_name'   => $name
        ]);

        // Set user role to author (can create listings)
        $user = new \WP_User( $user_id );
        $user->set_role( 'author' );

        // Add WhatsApp if provided
        if ( ! empty( $whatsapp ) ) {
            update_user_meta( $user_id, 'afc_agent_whatsapp', $whatsapp );
        }

        // Send password reset email
        wp_send_new_user_notifications( $user_id, 'user' );

        wp_send_json_success([
            'message' => sprintf( 
                __( 'Agent "%s" created successfully! Password reset email sent to %s', 'afcglide' ),
                $name,
                $email
            ),
            'agent_id' => $user_id,
            'agent_name' => $name
        ]);
    }
}
