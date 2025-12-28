<?php
namespace AFCGlide\Listings;

/**
 * Fired during plugin activation.
 *
 * Handles CPT registration, taxonomy setup, default terms, plugin options, and upgrades.
 *
 * @package AFCGlide_Listings
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AFCGlide_Listings_Activator {

    /**
     * Main activation method.
     */
    public static function activate() {
        
        // Security: ensure current user can activate plugins
        if ( ! \current_user_can( 'activate_plugins' ) ) {
            return;
        }

        // 1. Load CPT class if not already loaded (Safety Check)
        if ( ! \class_exists( __NAMESPACE__ . '\AFCGlide_CPT_Tax' ) ) {
            if ( file_exists( AFCG_PLUGIN_DIR . 'includes/class-cpt-tax.php' ) ) {
                require_once AFCG_PLUGIN_DIR . 'includes/class-cpt-tax.php';
            }
        }

        // 2. Register CPT and taxonomies immediately so we can flush rules
        if ( \class_exists( __NAMESPACE__ . '\AFCGlide_CPT_Tax' ) ) {
            AFCGlide_CPT_Tax::register_post_type();
            AFCGlide_CPT_Tax::register_taxonomies();
        }

        // 3. Flush rewrite rules (Fixes 404 Errors on listings)
        \flush_rewrite_rules();
        
        // 4. Set up default plugin options
        self::setup_default_options();

        // 5. Create default taxonomy terms (For Sale, Rent, etc.)
        self::create_default_terms();

        // 6. Track plugin version
        \update_option( 'afcglide_version', AFCG_VERSION );
    }

    /**
     * Set up default plugin options.
     */
    private static function setup_default_options() {
        if ( ! \get_option( 'afcglide_options' ) ) {
            \add_option( 'afcglide_options', [
                'delete_on_uninstall'    => false,
                'posts_per_page'         => 6,
                'enable_featured_badge'  => true,
                'currency_symbol'        => '$',
                'enable_ajax_filtering'  => true,
                'require_approval'       => true,
                'max_images_per_listing' => 20,
                'allowed_user_roles'     => [ 'administrator', 'editor' ],
            ] );
        }
    }

    /**
     * Create default taxonomy terms.
     */
    private static function create_default_terms() {

        // PROPERTY TYPES
        $property_types = [
            'Single Family Home' => 'Detached single-family residential property',
            'Apartment'          => 'Multi-unit residential building',
            'Condo'              => 'Individually owned unit in a larger complex',
            'Townhouse'          => 'Multi-floor home sharing walls with adjacent units',
            'Land'               => 'Vacant land or lot',
            'Commercial'         => 'Commercial real estate property',
        ];

        foreach ( $property_types as $name => $description ) {
            if ( ! \term_exists( $name, 'property_type' ) ) {
                \wp_insert_term( $name, 'property_type', [ 'description' => $description ] );
            }
        }

        // PROPERTY STATUS
        $property_statuses = [
            'For Sale'      => 'Property is available for purchase',
            'For Rent'      => 'Property is available for lease',
            'Sold'          => 'Property has been sold',
            'Pending'       => 'Sale is pending/under contract',
        ];

        foreach ( $property_statuses as $name => $description ) {
            if ( ! \term_exists( $name, 'property_status' ) ) {
                \wp_insert_term( $name, 'property_status', [ 'description' => $description ] );
            }
        }

        // PROPERTY LOCATIONS
        $property_locations = [
            'Downtown'   => 'Central business district',
            'Suburbs'    => 'Suburban residential areas',
            'Waterfront' => 'Properties near water',
        ];

        foreach ( $property_locations as $name => $description ) {
            if ( ! \term_exists( $name, 'property_location' ) ) {
                \wp_insert_term( $name, 'property_location', [ 'description' => $description ] );
            }
        }
    }
}
?>