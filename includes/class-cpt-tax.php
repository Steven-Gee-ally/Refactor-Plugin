<?php
/**
 * Registers Custom Post Types and Taxonomies.
 *
 * @package AFCGlide_Listings
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_CPT_Tax {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_post_type' ] );
        add_action( 'init', [ __CLASS__, 'register_taxonomies' ] );
    }

    public static function register_post_type() {
        $labels = [
            'name'               => __( 'Listings', 'afcglide' ),
            'singular_name'      => __( 'Listing', 'afcglide' ),
            'add_new'            => __( 'Add New', 'afcglide' ),
            'add_new_item'       => __( 'Add New Listing', 'afcglide' ),
            'edit_item'          => __( 'Edit Listing', 'afcglide' ),
            'new_item'           => __( 'New Listing', 'afcglide' ),
            'view_item'          => __( 'View Listing', 'afcglide' ),
            'search_items'       => __( 'Search Listings', 'afcglide' ),
            'not_found'          => __( 'No listings found', 'afcglide' ),
            'not_found_in_trash' => __( 'No listings found in Trash', 'afcglide' ),
            'menu_name'          => __( 'Listings', 'afcglide' ),
        ];

        $args = [
            'labels'              => $labels,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_position'       => 5,
            'menu_icon'           => 'dashicons-admin-home',
            'has_archive'         => 'listings',
            'rewrite'             => [ 'slug' => 'listings', 'with_front' => false ],
            'supports'            => [ 'title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'author' ],
            'taxonomies'          => [ 'property_type', 'property_status', 'property_location' ],
            'show_in_rest'        => true,
        ];

        // ✅ Singular Name
        register_post_type( 'afcglide_listing', $args );
    }

    public static function register_taxonomies() {
        // Location
        register_taxonomy( 'property_location', 'afcglide_listing', [
            'label'             => __( 'Locations', 'afcglide' ),
            'labels'            => [ 'name' => 'Locations', 'singular_name' => 'Location' ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'location', 'with_front' => false ],
        ] );

        // Type
        register_taxonomy( 'property_type', 'afcglide_listing', [
            'label'             => __( 'Property Types', 'afcglide' ),
            'labels'            => [ 'name' => 'Types', 'singular_name' => 'Type' ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'property-type', 'with_front' => false ],
        ] );

        // Status
        register_taxonomy( 'property_status', 'afcglide_listing', [
            'label'             => __( 'Statuses', 'afcglide' ),
            'labels'            => [ 'name' => 'Statuses', 'singular_name' => 'Status' ],
            'hierarchical'      => true,
            'show_ui'           => true,
            'show_admin_column' => true,
            'show_in_rest'      => true,
            'rewrite'           => [ 'slug' => 'property-status', 'with_front' => false ],
        ] );
    }
}
?>