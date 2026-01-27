<?php
/**
 * Plugin Name: AFCGlide - Reset Simulation Helper
 * Description: Temporary helper to simulate broker reset token/transient and send test email. Drop into wp-content/mu-plugins/ or activate, then visit as admin to trigger. Remove after use.
 * Version: 0.1
 * Author: AFCGlide Dev
 */

if ( ! defined( 'ABSPATH' ) ) return;

add_action( 'init', function() {
    if ( empty( $_GET['afc_sim_reset'] ) ) {
        return;
    }

    // Restrict to admins to avoid accidental exposure
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Only admins can run this simulation.' );
    }

    $user_id = intval( $_GET['user'] ?? 0 );
    $email   = sanitize_email( $_GET['email'] ?? '' );
    $login   = sanitize_text_field( $_GET['login'] ?? 'agent' );

    if ( ! $user_id || empty( $email ) ) {
        wp_die( 'Provide user (id) and email as query args, e.g. ?afc_sim_reset=1&user=42&email=agent@example.test&login=agentj' );
    }

    try {
        $token = bin2hex( random_bytes( 16 ) );
    } catch ( Exception $e ) {
        $token = wp_generate_password( 32, true, true );
    }

    $token_key = 'afc_reset_token_' . $token;
    set_transient( $token_key, $user_id, 15 * MINUTE_IN_SECONDS );

    $link = add_query_arg( 'afc_reset_token', $token, home_url( '/' ) );
    $subject = sprintf( __( 'Set your %s password (expires in 15 minutes)', 'afcglide' ), get_option( 'afc_system_label', 'AFCGlide' ) );
    $body = sprintf( "Hi %s,\n\nThis is a test reset email for your AFCGlide portal. Click the link below to set a new password (expires in 15 minutes):\n\n%s\n\nIf you did not expect this, contact your broker.", $login, $link );

    $sent = wp_mail( $email, $subject, $body, [ 'Content-Type: text/plain; charset=UTF-8' ] );

    echo 'AFCGlide Reset Simulation\n';
    echo '----------------------\n';
    echo 'token: ' . esc_html( $token ) . "\n";
    echo 'transient: ' . esc_html( $token_key ) . "\n";
    echo 'link: ' . esc_url( $link ) . "\n";
    echo 'mail_sent: ' . ( $sent ? '1' : '0' ) . "\n";

    // Stop further rendering
    exit;
});
