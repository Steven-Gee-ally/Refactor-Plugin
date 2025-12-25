<?php
/**
 * AFCGlide Metaboxes - The Master Storage Engine
 * Version 3.2.0 - Optimized for GPS, Amenities, and Hero-16 Gallery
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Metaboxes {

    /**
     * THE MASTER LIST: These match your form inputs exactly.
     */
    public static $meta_keys = [
        '_listing_price', 
        '_listing_beds', 
        '_listing_baths', 
        '_listing_sqft', 
        '_gps_lat', 
        '_gps_lng', 
        '_listing_amenities',
        '_agent_photo', 
        '_agency_logo', 
        '_hero_image', 
        '_slider_images_json', 
        '_stack_images_json',
        '_is_featured'
    ];

    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_metaboxes' ] );
        add_action( 'save_post', [ __CLASS__, 'save_metabox' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
    }

    public static function admin_assets( $hook ) {
        global $post;
        if ( ! isset( $post ) || $post->post_type !== 'afcglide_listing' ) return;
        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
    }

    public static function add_metaboxes() {
        add_meta_box(
            'afcglide_listing_details',
            __( 'Listing Details, GPS & Amenities', 'afcglide' ),
            [ __CLASS__, 'render_metabox' ],
            'afcglide_listing',
            'normal',
            'high'
        );
    }

    public static function render_metabox( $post ) {
        wp_nonce_field( 'afcglide_meta_nonce', 'afcglide_meta_nonce' );

        // Pull Data
        $price     = get_post_meta( $post->ID, '_listing_price', true );
        $beds      = get_post_meta( $post->ID, '_listing_beds', true );
        $baths     = get_post_meta( $post->ID, '_listing_baths', true );
        $lat       = get_post_meta( $post->ID, '_gps_lat', true );
        $lng       = get_post_meta( $post->ID, '_gps_lng', true );
        $amenities = get_post_meta( $post->ID, '_listing_amenities', true ) ?: [];
        ?>

        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div>
                <label><strong><?php _e('Price ($)', 'afcglide'); ?></strong></label>
                <input type="text" name="_listing_price" value="<?php echo esc_attr($price); ?>" style="width:100%;">
            </div>
            <div>
                <label><strong><?php _e('Beds', 'afcglide'); ?></strong></label>
                <input type="number" name="_listing_beds" value="<?php echo esc_attr($beds); ?>" style="width:100%;">
            </div>
            <div>
                <label><strong><?php _e('Baths', 'afcglide'); ?></strong></label>
                <input type="number" name="_listing_baths" value="<?php echo esc_attr($baths); ?>" step="0.5" style="width:100%;">
            </div>
        </div>

        <div style="background: #f0f6fb; padding: 15px; border: 1px solid #ccd0d4; border-radius: 4px; margin-bottom: 20px;">
            <label><strong><?php _e('GPS Location (Pinpoint Accuracy)', 'afcglide'); ?></strong></label>
            <div style="display: flex; gap: 15px; margin-top: 10px;">
                <input type="text" name="_gps_lat" placeholder="Latitude" value="<?php echo esc_attr($lat); ?>" style="flex:1;">
                <input type="text" name="_gps_lng" placeholder="Longitude" value="<?php echo esc_attr($lng); ?>" style="flex:1;">
            </div>
        </div>

        <div style="margin-bottom: 20px;">
            <label><strong><?php _e('Amenities', 'afcglide'); ?></strong></label>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 8px; margin-top: 10px;">
                <?php 
                $options = ['Pool', 'Ocean View', 'Gated', 'Backup Power', 'Guest House', 'High-Speed Internet', 'Garage', 'Furnished', 'AC', 'Waterfront', 'Hiking Trails', 'Solar Power', 'Gym', 'Wine Cellar', 'Spa'];
                foreach($options as $opt) : ?>
                    <label>
                        <input type="checkbox" name="_listing_amenities[]" value="<?php echo $opt; ?>" <?php checked(in_array($opt, $amenities)); ?>> 
                        <?php echo $opt; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    public static function save_metabox( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! isset( $_POST['afcglide_meta_nonce'] ) || ! wp_verify_nonce( $_POST['afcglide_meta_nonce'], 'afcglide_meta_nonce' ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        foreach ( self::$meta_keys as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = $_POST[ $field ];
                $sanitized = is_array( $value ) ? array_map( 'sanitize_text_field', $value ) : sanitize_text_field( $value );
                update_post_meta( $post_id, $field, $sanitized );
            } else {
                // If it's a checkbox and it's missing from POST, it was unchecked.
                if ( $field === '_listing_amenities' ) delete_post_meta( $post_id, $field );
            }
        }
    }
}