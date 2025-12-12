<?php
/**
 * AFCGlide Metaboxes & Meta registration
 *
 * @package AFCGlide_Listings
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Metaboxes {

    // Define ALL your fields here. The save function will use this list automatically.
    public static $meta_keys = [
        '_listing_price', 
        '_listing_beds', 
        '_listing_baths', 
        '_listing_sqft', 
        '_hero_image', 
        '_slider_images_json', 
        '_is_featured',
        '_gps_lat', 
        '_gps_lng', 
        '_primary_color', 
        '_secondary_color', 
        '_background_color'
    ];

    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_metaboxes' ] );
        add_action( 'save_post_afcglide_listing', [ __CLASS__, 'save_metabox' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
    }

    public static function admin_assets( $hook ) {
        global $post;
        if ( ! isset( $post ) || $post->post_type !== 'afcglide_listing' ) return;

        wp_enqueue_media();
        wp_enqueue_style( 'wp-color-picker' );
        // Ensure these files exist in your assets folder!
        wp_enqueue_style( 'afcglide-admin-css', AFCG_PLUGIN_URL . 'assets/css/afcglide-admin.css', [], AFCG_VERSION );
        wp_enqueue_script( 'afcglide-admin-js', AFCG_PLUGIN_URL . 'assets/js/afcglide-admin.js', [ 'jquery', 'wp-color-picker' ], AFCG_VERSION, true );
    }

    public static function add_metaboxes() {
        add_meta_box(
            'afcglide_listing_metabox',
            __( 'Listing Details', 'afcglide' ),
            [ __CLASS__, 'render_metabox' ],
            'afcglide_listing',
            'normal',
            'high'
        );
    }

    public static function render_metabox( $post ) {
        wp_nonce_field( 'afcglide_meta_nonce', 'afcglide_meta_nonce' );

        $price = get_post_meta( $post->ID, '_listing_price', true );
        $beds  = get_post_meta( $post->ID, '_listing_beds', true );
        $baths = get_post_meta( $post->ID, '_listing_baths', true );
        $sqft  = get_post_meta( $post->ID, '_listing_sqft', true );
        $hero_id = intval( get_post_meta( $post->ID, '_hero_image', true ) );
        $hero_src = $hero_id ? wp_get_attachment_image_url( $hero_id, 'medium' ) : '';

        // HTML Output
        ?>
        <div class="afcglide-meta-wrap afcglide-metabox-wrapper">
            <p>
                <label><strong><?php esc_html_e( 'Price ($)', 'afcglide' ); ?></strong></label>
                <input type="text" name="_listing_price" value="<?php echo esc_attr( $price ); ?>" style="width:100%;">
            </p>
            <p>
                <label><strong><?php esc_html_e( 'Beds', 'afcglide' ); ?></strong></label>
                <input type="number" name="_listing_beds" value="<?php echo esc_attr( $beds ); ?>" style="width:100%;">
            </p>
            <p>
                <label><strong><?php esc_html_e( 'Baths', 'afcglide' ); ?></strong></label>
                <input type="number" name="_listing_baths" value="<?php echo esc_attr( $baths ); ?>" step="0.5" style="width:100%;">
            </p>
            <p>
                <label><strong><?php esc_html_e( 'SqFt', 'afcglide' ); ?></strong></label>
                <input type="number" name="_listing_sqft" value="<?php echo esc_attr( $sqft ); ?>" style="width:100%;">
            </p>

            <div style="margin-top: 20px; border-top:1px solid #ddd; padding-top:10px;">
                <label><strong><?php esc_html_e( 'Hero Image', 'afcglide' ); ?></strong></label>
                <div class="afcglide-media-uploader">
                    <input type="hidden" name="_hero_image" value="<?php echo esc_attr( $hero_id ); ?>">
                    <div class="afcglide-media-preview" style="margin-bottom:10px;">
                        <?php if ( $hero_src ) : ?>
                            <img src="<?php echo esc_url( $hero_src ); ?>" style="max-width:150px; display:block;">
                        <?php else: ?>
                            <span style="color:#888;">No image selected</span>
                        <?php endif; ?>
                    </div>
                    <button type="button" class="button afcglide-select-hero">Select Image</button>
                    <button type="button" class="button afcglide-remove-hero">Remove</button>
                </div>
            </div>
            
            </div>
        <?php
    }

    public static function save_metabox( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! isset( $_POST['afcglide_meta_nonce'] ) || ! wp_verify_nonce( $_POST['afcglide_meta_nonce'], 'afcglide_meta_nonce' ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // IMPROVEMENT: Loop through the master list defined at the top
        foreach ( self::$meta_keys as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                // Sanitize based on field type if necessary, for now text is safe
                update_post_meta( $post_id, $field, sanitize_text_field( $_POST[ $field ] ) );
            }
        }
    }
}
?>