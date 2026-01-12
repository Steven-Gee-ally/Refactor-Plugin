<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @package AFCGlide_Listings
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

// 1. Check if user allowed data deletion
$options = get_option( 'afcglide_options' );
if ( empty( $options['delete_on_uninstall'] ) ) {
    // User wants to keep data. Exit.
    return;
}

// 2. Delete All Listings (Singular CPT: 'afcglide_listing')
// We use get_posts to ensure we get IDs regardless of post status
$listings = get_posts( [
    'post_type'      => 'afcglide_listing', // ✅ Matches Step 2 (Singular)
    'post_status'    => 'any',
    'posts_per_page' => -1,
    'fields'         => 'ids',
] );

if ( ! empty( $listings ) ) {
    foreach ( $listings as $post_id ) {
        wp_delete_post( $post_id, true ); // Force delete (bypass trash)
    }
}

// 3. Delete Taxonomies (Terms)
$taxonomies = [ 'property_location', 'property_type', 'property_status' ];
foreach ( $taxonomies as $taxonomy ) {
    $terms = get_terms( [ 'taxonomy' => $taxonomy, 'hide_empty' => false, 'fields' => 'ids' ] );
    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
        foreach ( $terms as $term_id ) {
            wp_delete_term( $term_id, $taxonomy );
        }
    }
}

// 4. Delete Options & Transients
delete_option( 'afcglide_options' );
delete_option( 'afcglide_version' );

global $wpdb;
// Clean up any leftover transients
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_afcglide_%'" );
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_afcglide_%'" );

// 5. Flush Rules
flush_rewrite_rules();
?>