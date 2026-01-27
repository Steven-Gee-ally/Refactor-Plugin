<?php
namespace AFCGlide\Listings;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_CPT_Tax {

    public static function init() {
        self::register_post_type();
        self::register_taxonomies();
        self::register_post_statuses();
        
        if ( is_admin() ) {
            add_action( 'admin_init', [ __CLASS__, 'populate_default_amenities' ] );
            // High-End Touch: Inject custom status into the Post Edit dropdown
            add_action( 'admin_footer-post.php', [ __CLASS__, 'inject_status_into_dropdown' ] );
            add_action( 'admin_footer-post-new.php', [ __CLASS__, 'inject_status_into_dropdown' ] );
        }
    }

    /**
     * Register Custom Post Statuses
     * Added: 'private' => false to ensure they show up in the right lists
     */
    public static function register_post_statuses() {
        register_post_status( 'sold', [
            'label'                     => _x( 'Sold', 'post status', 'afcglide' ),
            'public'                    => true,
            'exclude_from_search'       => false,
            'show_in_admin_all_list'    => true,
            'show_in_admin_status_list' => true,
            'label_count'               => _n_noop( 'Sold <span class="count">(%s)</span>', 'Sold <span class="count">(%s)</span>', 'afcglide' ),
        ]);
    }

    /**
     * JS Injection: Ensures "Sold" appears in the WP Admin status dropdown
     */
    public static function inject_status_into_dropdown() {
        global $post;
        if ( $post->post_type !== C::POST_TYPE ) return;
        $selected = ( $post->post_status === 'sold' ) ? 'selected="selected"' : '';
        ?>
        <script>
            jQuery(document).ready(function($){
                $("select#post_status").append('<option value="sold" <?php echo $selected; ?>>Sold</option>');
                <?php if ( $post->post_status === 'sold' ) : ?>
                    $('#post-status-display').text('Sold');
                <?php endif; ?>
            });
        </script>
        <?php
    }

    public static function register_post_type() {
        $labels = [
            'name'               => __( 'Listings', 'afcglide' ),
            'singular_name'      => __( 'Listing', 'afcglide' ),
            'add_new'            => __( 'Add New', 'afcglide' ),
            'add_new_item'       => __( 'Add New Listing', 'afcglide' ),
            'edit_item'          => __( 'Edit Listing', 'afcglide' ),
            'all_items'          => __( 'All Listings', 'afcglide' ),
            'menu_name'          => __( '🏠 Listings', 'afcglide' ),
        ];
  
        $args = [
            'labels'              => $labels,
            'public'              => true,
            'show_ui'             => true,
            'show_in_menu'        => true,
            'menu_icon'           => 'dashicons-admin-multisite', // Global Network Icon
            'menu_position'       => 6,
            'capability_type'     => 'post',
            'has_archive'         => 'listings',
            'rewrite'             => [ 'slug' => 'listings', 'with_front' => false ],
            'supports'            => [ 'title', 'editor', 'thumbnail', 'author', 'revisions' ],
            'taxonomies'          => [ C::TAX_TYPE, C::TAX_STATUS, C::TAX_LOCATION, C::TAX_AMENITY ],
            'show_in_rest'        => false, 
        ];

        register_post_type( C::POST_TYPE, $args );
    }

    public static function register_taxonomies() {
        $taxonomies = [
            C::TAX_LOCATION => [ 'plural' => 'Locations', 'singular' => 'Location', 'slug' => 'location', 'hierarchical' => true ],
            C::TAX_TYPE     => [ 'plural' => 'Property Types', 'singular' => 'Property Type', 'slug' => 'property-type', 'hierarchical' => true ],
            C::TAX_STATUS   => [ 'plural' => 'Statuses', 'singular' => 'Status', 'slug' => 'property-status', 'hierarchical' => true ],
            C::TAX_AMENITY  => [ 'plural' => 'Amenities', 'singular' => 'Amenity', 'slug' => 'amenity', 'hierarchical' => false ],
        ];

        foreach ( $taxonomies as $tax_slug => $config ) {
            register_taxonomy( $tax_slug, C::POST_TYPE, [
                'labels'            => [
                    'name'          => $config['plural'],
                    'singular_name' => $config['singular'],
                    'add_new_item'  => 'Add New ' . $config['singular'],
                ],
                'hierarchical'      => $config['hierarchical'],
                'public'            => true,
                'show_ui'           => true,
                'show_admin_column' => true,
                'show_in_rest'      => true,
                'rewrite'           => [ 'slug' => $config['slug'], 'with_front' => false ],
                'meta_box_cb'       => false, // Handled by our custom UI
            ] );
        }
    }

    public static function populate_default_amenities() {
        if ( ! taxonomy_exists( C::TAX_AMENITY ) ) return;

        $amenities = [
            'Gourmet Kitchen', 'Infinity Pool', 'Ocean View', 'Wine Cellar',
            'Private Gym', 'Smart Home Tech', 'Outdoor Cinema', 'Helipad Access',
            'Gated Community', 'Guest House', 'Solar Power', 'Beach Front',
            'Spa / Sauna', '3+ Car Garage', 'Luxury Fire Pit', 'Concierge Service',
            'Walk-in Closet', 'High Ceilings', 'Staff Quarters', 'Backup Generator'
        ];

        foreach ( $amenities as $amenity ) {
            if ( ! term_exists( $amenity, C::TAX_AMENITY ) ) {
                wp_insert_term( $amenity, C::TAX_AMENITY );
            }
        }
    }
}