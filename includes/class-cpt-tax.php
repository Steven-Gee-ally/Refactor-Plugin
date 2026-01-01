<?php
namespace AFCGlide\Listings;

/**
 * Registers Custom Post Types and Taxonomies.
 * Version 3.8.1 - "The One Page Luxury Glide" Final Sync
 *
 * @package AFCGlide_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_CPT_Tax {

    /**
     * Initialize Registration
     */
    public static function init() {
        self::register_post_type();
        self::register_taxonomies();
        
        if ( is_admin() ) {
            add_action( 'admin_init', [ __CLASS__, 'populate_default_amenities' ] );
        }
    }

    /**
     * Register the 'afcglide_listing' Custom Post Type
     */
    public static function register_post_type() {
        $labels = [
            'name'               => __( 'Listings', 'afcglide' ),
            'singular_name'      => __( 'Listing', 'afcglide' ),
            'add_new'            => __( 'Add New', 'afcglide' ),
            'add_new_item'       => __( 'Add New Listing', 'afcglide' ),
            'edit_item'          => __( 'Edit Listing', 'afcglide' ),
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
            'supports'            => [ 'title', 'editor', 'thumbnail', 'author' ], // Description/Editor is ON
            'taxonomies'          => [ 'property_type', 'property_status', 'property_location', 'property_amenity' ],
            'show_in_rest'        => false, // Forces Classic layout for our custom look
        ];

        register_post_type( 'afcglide_listing', $args );
    }

    /**
     * Register Taxonomies
     */
    public static function register_taxonomies() {
        $taxonomies = [
            'property_location' => [ 'name' => 'Locations', 'slug' => 'location' ],
            'property_type'     => [ 'name' => 'Property Types', 'slug' => 'property-type' ],
            'property_status'   => [ 'name' => 'Statuses', 'slug' => 'property-status' ],
            'property_amenity'  => [ 'name' => 'Amenities', 'slug' => 'amenity' ]
        ];

        foreach ( $taxonomies as $slug => $args ) {
            register_taxonomy( $slug, 'afcglide_listing', [
                'labels' => [
                    'name'          => $args['name'],
                    'singular_name' => rtrim($args['name'], 's'),
                    'menu_name'     => $args['name'],
                ],
                'hierarchical'      => ($slug === 'property_amenity') ? false : true,
                'public'            => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_nav_menus' => true,
                'show_in_rest'      => true,
                'rewrite'           => [ 'slug' => $args['slug'], 'with_front' => false ],
                'meta_box_cb'       => false, // This keeps the sidebar clean!
            ] );
            
            register_taxonomy_for_object_type( $slug, 'afcglide_listing' );
        }
    }

    /**
     * Auto-populate exactly 20 default luxury amenities
     */
    public static function populate_default_amenities() {
        if ( ! taxonomy_exists('property_amenity') ) return;

        $amenities = [
            'Gourmet Kitchen', 'Infinity Pool', 'Ocean View', 'Wine Cellar',
            'Private Gym', 'Smart Home Tech', 'Outdoor Cinema', 'Helipad Access',
            'Gated Community', 'Guest House', 'Solar Power', 'Beach Front',
            'Spa / Sauna', '3+ Car Garage', 'Luxury Fire Pit', 'Concierge Service',
            'Walk-in Closet', 'High Ceilings', 'Staff Quarters', 'Backup Generator'
        ];

        foreach ( $amenities as $amenity ) {
            if ( ! term_exists( $amenity, 'property_amenity' ) ) {
                wp_insert_term( $amenity, 'property_amenity' );
            }
        }
    }
}