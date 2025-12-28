<?php
namespace AFCGlide\Listings;

/**
 * Registers Custom Post Types and Taxonomies.
 * Refactored for v3.6.6 - Direct Execution for Sidebar Visibility
 *
 * @package AFCGlide_Listings
 */


if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_CPT_Tax {

    /**
     * Initialize Registration
     * Called by the Main Plugin File during the 'init' hook
     */
    public static function init() {
        /**
         * FIX: We are already inside the 'init' hook from the Main Plugin File.
         * Calling these functions directly ensures WordPress registers them 
         * BEFORE it finishes building the Admin Sidebar menu.
         */
        self::register_post_type();
        self::register_taxonomies();
        
        // Auto-populate amenities stays on admin_init
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
            // FIX: Removed 'editor' and 'excerpt' to kill the Gutenberg Block area
            'supports'            => [ 'title', 'thumbnail', 'author' ], 
            'taxonomies'          => [ 'property_type', 'property_status', 'property_location', 'property_amenity' ],
            // FIX: Set show_in_rest to false to force Classic Editor / Metabox layout
            'show_in_rest'        => false, 
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
            ] );
            
            register_taxonomy_for_object_type( $slug, 'afcglide_listing' );
        }
    }

    /**
     * Auto-populate default luxury amenities
     */
    public static function populate_default_amenities() {
        if ( ! taxonomy_exists('property_amenity') ) return;

        $amenities = [
            'Infinity Pool', 'Home Gym', 'Outdoor Shower', 'Hot Tub', 
            'Wrap-around Deck', 'Fire Pit', 'Vaulted Ceilings', 
            'Gourmet Kitchen', 'Smart Home Tech', 'EV Charging'
        ];

        foreach ( $amenities as $amenity ) {
            if ( ! term_exists( $amenity, 'property_amenity' ) ) {
                wp_insert_term( $amenity, 'property_amenity' );
            }
        }
    }
}