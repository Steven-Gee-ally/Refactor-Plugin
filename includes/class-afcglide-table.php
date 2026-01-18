<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Executive Table
 * Overhauls the "All Listings" view for a premium experience.
 */
class AFCGlide_Table {

    public static function init() {
        // Only run this on the 'afcglide_listing' post type
        add_filter( 'manage_afcglide_listing_posts_columns', [ __CLASS__, 'set_custom_columns' ] );
        add_action( 'manage_afcglide_listing_posts_custom_column', [ __CLASS__, 'render_custom_columns' ], 10, 2 );
        add_filter( 'manage_edit-afcglide_listing_sortable_columns', [ __CLASS__, 'make_columns_sortable' ] );
    }

    // Define which columns we want to see
    public static function set_custom_columns( $columns ) {
        $new_columns = [];
        $new_columns['cb'] = $columns['cb']; // Checkbox
        $new_columns['afc_thumb'] = 'Photo';
        $new_columns['title'] = 'Property Address';
        $new_columns['afc_price'] = 'Listing Price';
        $new_columns['afc_agent'] = 'Assigned Agent';
        $new_columns['date'] = 'Date Published';
        return $new_columns;
    }

    // Pull the data for each listing
    public static function render_custom_columns( $column, $post_id ) {
        switch ( $column ) {
            case 'afc_thumb' :
                // Pull from our Hero Image ID
                $hero_id = get_post_meta( $post_id, '_listing_hero_id', true );
                if ( $hero_id ) {
                    echo wp_get_attachment_image( $hero_id, [50, 50], true, [
                        'style' => 'border-radius:8px; border: 1px solid #e2e8f0; object-fit: cover;'
                    ] );
                } else {
                    echo '<div style="width:50px; height:50px; background:#f1f5f9; border-radius:8px; border:1px solid #e2e8f0;"></div>';
                }
                break;

            case 'afc_price' :
                $price = get_post_meta( $post_id, '_listing_price', true );
                if ( $price ) {
                    echo '<span style="color:#10b981; font-weight:800; font-size:14px;">$' . number_format(floatval($price)) . '</span>';
                } else {
                    echo '<span style="color:#94a3b8;">Set Price...</span>';
                }
                break;

            case 'afc_agent' :
                $agent = get_post_meta( $post_id, '_afc_agent_name', true );
                echo '<span style="font-weight:600; color:#1e293b;">' . esc_html( $agent ?: 'Unassigned' ) . '</span>';
                break;
        }
    }

    // Allow clicking the column headers to sort by Price
    public static function make_columns_sortable( $columns ) {
        $columns['afc_price'] = 'afc_price';
        return $columns;
    }
}