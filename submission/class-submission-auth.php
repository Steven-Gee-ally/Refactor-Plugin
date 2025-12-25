<?php
/**
 * Submission Auth - Refactored for AFCGlide v3
 * Handles Logic for Login and Registration.
 */

namespace AFCGlide\Listings\Submission;

use AFCGlide\Listings\Helpers\Message_Helper;
use AFCGlide\Listings\Helpers\Sanitizer;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Submission_Auth {

    public static function init() {
        $instance = new self();
        add_action( 'init', array( $instance, 'handle_registration' ) );
        add_action( 'init', array( $instance, 'handle_logout' ) );
        // Note: handle_login is optional if you use the standard wp_login_form in shortcodes
    }

    /**
     * Logic: Handle Registration
     */
    public function handle_registration() {
        // Check if our specific registration form was submitted
        if ( ! isset( $_POST['agent_email'] ) || ! isset( $_POST['agent_pass'] ) ) return;

        // 1. Collect and Sanitize (Matching your Shortcode Form Names!)
        $name     = Sanitizer::text( $_POST['agent_name'] ?? '' );
        $email    = Sanitizer::email( $_POST['agent_email'] ?? '' );
        $password = $_POST['agent_pass'] ?? '';

        // 2. Validation Logic
        if ( empty( $email ) || empty( $password ) ) {
            Message_Helper::error( __('All fields are required.', 'afcglide') );
            return;
        }

        if ( ! is_email( $email ) ) {
            Message_Helper::error( __('Please enter a valid email.', 'afcglide') );
            return;
        }

        if ( email_exists( $email ) ) {
            Message_Helper::error( __('This email is already registered. Please log in.', 'afcglide') );
            return;
        }

        // 3. Create the WordPress User
        // Using email as the username to keep it simple for the agent
        $user_id = wp_create_user( $email, $password, $email );

        if ( is_wp_error( $user_id ) ) {
            Message_Helper::error( $user_id->get_error_message() );
            return;
        }

        // 4. Set Profile Data
        wp_update_user([
            'ID'           => $user_id,
            'display_name' => $name,
            'role'         => 'author' // Important: Allows them to upload the 16 photos
        ]);

        // 5. Automatic Handshake (Log them in and send to Submit page)
        wp_set_current_user( $user_id );
        wp_set_auth_cookie( $user_id );
        
        wp_safe_redirect( home_url( '/submit-listing/' ) );
        exit;
    }

    public function handle_logout() {
        if ( isset( $_GET['afcglide_logout'] ) && $_GET['afcglide_logout'] === '1' ) {
            wp_logout();
            wp_safe_redirect( home_url() );
            exit;
        }
    }
}