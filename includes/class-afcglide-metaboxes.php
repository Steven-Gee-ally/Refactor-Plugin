<?php
/**
 * AFCGlide Metaboxes - The Master Storage Engine
 * Version 3.7.0 - Luxury Real Estate Suite
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Metaboxes {

    // One place to manage every single data point
    public static $meta_keys = [
        '_listing_price', '_listing_beds', '_listing_baths', '_listing_sqft', 
        '_gps_lat', '_gps_lng', '_listing_amenities', '_listing_status',
        '_agent_name', '_agent_phone', '_agent_license', '_agent_bio',
        '_agent_whatsapp', '_whatsapp_message', '_show_floating_whatsapp',
        '_agent_photo', '_agency_logo', '_hero_image', 
        '_stack_images_json', '_is_featured'
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
    }

    public static function add_metaboxes() {
        add_meta_box( 'afc_details', __( '1. Property Details & Status', 'afcglide' ), [ __CLASS__, 'render_details' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_media', __( '2. Luxury Media (Hero & Photo Stack)', 'afcglide' ), [ __CLASS__, 'render_media' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_agent', __( '3. Agent Branding & WhatsApp', 'afcglide' ), [ __CLASS__, 'render_agent' ], 'afcglide_listing', 'side', 'default' );
    }

    // --- 1. PROPERTY DETAILS & STATUS ---
    public static function render_details( $post ) {
        wp_nonce_field( 'afcglide_meta_nonce', 'afcglide_meta_nonce' );
        $price  = get_post_meta( $post->ID, '_listing_price', true );
        $beds   = get_post_meta( $post->ID, '_listing_beds', true );
        $baths  = get_post_meta( $post->ID, '_listing_baths', true );
        $status = get_post_meta( $post->ID, '_listing_status', true );
        $lat    = get_post_meta( $post->ID, '_gps_lat', true );
        $lng    = get_post_meta( $post->ID, '_gps_lng', true );
        $amenities = get_post_meta( $post->ID, '_listing_amenities', true ) ?: [];
        ?>
        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; margin-bottom: 20px;">
            <div><label><strong>Price ($)</strong></label><input type="text" name="_listing_price" value="<?php echo esc_attr($price); ?>" style="width:100%;"></div>
            <div><label><strong>Beds</strong></label><input type="number" name="_listing_beds" value="<?php echo esc_attr($beds); ?>" style="width:100%;"></div>
            <div><label><strong>Baths</strong></label><input type="number" name="_listing_baths" value="<?php echo esc_attr($baths); ?>" step="0.5" style="width:100%;"></div>
            <div>
                <label><strong>Status</strong></label>
                <select name="_listing_status" style="width:100%;">
                    <option value="For Sale" <?php selected($status, 'For Sale'); ?>>For Sale</option>
                    <option value="Just Listed" <?php selected($status, 'Just Listed'); ?>>Just Listed</option>
                    <option value="Under Contract" <?php selected($status, 'Under Contract'); ?>>Under Contract</option>
                    <option value="Sold" <?php selected($status, 'Sold'); ?>>Sold</option>
                </select>
            </div>
        </div>

        <div style="background: #f0f6fb; padding: 15px; border-radius: 4px; border: 1px solid #ccd0d4; margin-bottom: 20px;">
            <label><strong>GPS Coordinates (Pinpoint)</strong></label>
            <div style="display: flex; gap: 10px; margin-top: 5px;">
                <input type="text" name="_gps_lat" placeholder="Latitude" value="<?php echo esc_attr($lat); ?>" style="flex:1;">
                <input type="text" name="_gps_lng" placeholder="Longitude" value="<?php echo esc_attr($lng); ?>" style="flex:1;">
            </div>
        </div>

        <label><strong>Amenities</strong></label>
        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 5px; margin-top: 10px;">
            <?php $opts = ['Infinity Pool', 'Ocean View', 'Gated', 'Solar Power', 'Gym', 'Fiber Optic'];
            foreach($opts as $opt) : ?>
                <label><input type="checkbox" name="_listing_amenities[]" value="<?php echo $opt; ?>" <?php checked(in_array($opt, $amenities)); ?>> <?php echo $opt; ?></label>
            <?php endforeach; ?>
        </div>
        <?php
    }

    // --- 2. LUXURY MEDIA ---
    public static function render_media( $post ) {
        $hero_id = get_post_meta( $post->ID, '_hero_image', true );
        $hero_url = $hero_id ? wp_get_attachment_url($hero_id) : '';
        ?>
        <div style="margin-bottom: 20px;">
            <label><strong>Primary Hero Image</strong></label>
            <img id="hero-preview" src="<?php echo esc_url($hero_url); ?>" style="max-width: 200px; display: <?php echo $hero_url ? 'block' : 'none'; ?>; margin: 10px 0; border-radius: 8px;">
            <input type="hidden" name="_hero_image" id="hero-image-id" value="<?php echo esc_attr($hero_id); ?>">
            <button type="button" class="button afc-upload-btn" data-target="hero">Select Hero Image</button>
        </div>
        <hr>
        <label><strong>Photo Stack (3 Images)</strong></label>
        <p class="description">Click to manage the luxury grid stack.</p>
        <button type="button" class="button afc-upload-btn" data-target="stack">Manage Photo Stack</button>
        <input type="hidden" name="_stack_images_json" id="stack-images-data" value="">
        <?php
    }

    // --- 3. AGENT & WHATSAPP ---
    public static function render_agent( $post ) {
        $agent_id = get_post_meta( $post->ID, '_agent_photo', true );
        $logo_id  = get_post_meta( $post->ID, '_agency_logo', true );
        $wa_num   = get_post_meta( $post->ID, '_agent_whatsapp', true );
        $show_wa  = get_post_meta( $post->ID, '_show_floating_whatsapp', true );

        $agent_url = $agent_id ? wp_get_attachment_url($agent_id) : '';
        $logo_url  = $logo_id ? wp_get_attachment_url($logo_id) : '';
        ?>
        <div style="text-align: center; background: #f9f9f9; padding: 15px; border-radius: 8px; border: 1px solid #ddd;">
            <img id="agent-preview" src="<?php echo esc_url($agent_url); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; display: <?php echo $agent_url ? 'block' : 'none'; ?>; margin: 0 auto 10px;">
            <input type="hidden" name="_agent_photo" id="agent-photo-id" value="<?php echo esc_attr($agent_id); ?>">
            <button type="button" class="button afc-upload-btn" data-target="agent">Agent Photo</button>

            <div style="text-align: left; margin-top: 15px;">
                <label>Name</label><input type="text" name="_agent_name" value="<?php echo esc_attr(get_post_meta($post->ID, '_agent_name', true)); ?>" style="width:100%; margin-bottom:10px;">
                <label>License #</label><input type="text" name="_agent_license" value="<?php echo esc_attr(get_post_meta($post->ID, '_agent_license', true)); ?>" style="width:100%; margin-bottom:10px;">
                <label>Bio Blurb</label><textarea name="_agent_bio" rows="2" style="width:100%; font-size:11px;"><?php echo esc_textarea(get_post_meta($post->ID, '_agent_bio', true)); ?></textarea>
            </div>

            <div style="background: #e7f3ff; padding: 10px; border-radius: 5px; margin-top: 15px; text-align: left;">
                <label><input type="checkbox" name="_show_floating_whatsapp" value="1" <?php checked($show_wa, '1'); ?>> <strong>Enable WhatsApp</strong></label>
                <input type="text" name="_agent_whatsapp" value="<?php echo esc_attr($wa_num); ?>" placeholder="WhatsApp (+506...)" style="width:100%; margin-top:5px;">
            </div>

            <hr>
            <img id="logo-preview" src="<?php echo esc_url($logo_url); ?>" style="max-width: 100px; display: <?php echo $logo_url ? 'block' : 'none'; ?>; margin: 10px auto;">
            <input type="hidden" name="_agency_logo" id="logo-image-id" value="<?php echo esc_attr($logo_id); ?>">
            <button type="button" class="button afc-upload-btn" data-target="logo">Agency Logo</button>
        </div>
        <?php
    }

    public static function save_metabox( $post_id ) {
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! isset( $_POST['afcglide_meta_nonce'] ) || ! wp_verify_nonce( $_POST['afcglide_meta_nonce'], 'afcglide_meta_nonce' ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        foreach ( self::$meta_keys as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $val = $_POST[ $field ];
                update_post_meta( $post_id, $field, is_array($val) ? array_map('sanitize_text_field', $val) : sanitize_text_field($val) );
            } else {
                // Handle unchecking the WhatsApp toggle
                if ($field === '_show_floating_whatsapp') update_post_meta($post_id, $field, '0');
            }
        }
    }
}