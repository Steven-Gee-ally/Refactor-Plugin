<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Metaboxes v4.4.2 - FULL INTEGRATION
 * VERSION: Luxury Photo Wall Sync (Hero + 3-Stack + 16-Slider)
 * Features: Agent Auto-fill, GPS Logic, Success Portal, and Media Matrix.
 */
class AFCGlide_Metaboxes {

    const MAX_SLIDER_IMAGES = 16;
    const MAX_STACK_IMAGES = 3;

    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_metaboxes' ] );
        add_action( 'save_post_afcglide_listing', [ __CLASS__, 'save_metabox' ], 10, 2 );
        add_action( 'edit_form_after_title', [ __CLASS__, 'render_description_label' ] );
        add_action( 'admin_notices', [ __CLASS__, 'render_success_portal' ] );
    }

    public static function add_metaboxes() {
        // Remove default WP clutter
        $side_boxes = ['submitdiv', 'postimagediv', 'authordiv'];
        foreach ( $side_boxes as $box ) remove_meta_box( $box, 'afcglide_listing', 'side' );

        add_meta_box( 'afc_agent', '👤 1. Agent Branding', [ __CLASS__, 'render_agent' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_media_hub', '📸 2. Visual Command Center (Hero & 3-Stack)', [ __CLASS__, 'render_media' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_slider', '🖼️ 3. Main Property Gallery Slider (Max 16)', [ __CLASS__, 'render_slider' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_details', '🏠 4. Property Specifications', [ __CLASS__, 'render_details' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_location', '📍 5. Location & GPS', [ __CLASS__, 'render_location' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_amenities', '💎 6. Property Features', [ __CLASS__, 'render_amenities' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_publish_box', '🚀 7. Publish New Listing', [ __CLASS__, 'render_publish' ], 'afcglide_listing', 'normal', 'high' );
    }

    /* ============================================================
       1. AGENT BRANDING RENDERER
       ============================================================ */
    public static function render_agent( $post ) {
        wp_nonce_field( 'afcglide_meta_nonce', 'afcglide_meta_nonce' );
        $name = get_post_meta($post->ID, '_agent_name_display', true);
        $phone = get_post_meta($post->ID, '_agent_phone_display', true);
        $whatsapp = get_post_meta($post->ID, '_show_floating_whatsapp', true);
        $photo_id = get_post_meta($post->ID, '_agent_photo_id', true);
        $photo_url = $photo_id ? wp_get_attachment_url( $photo_id ) : '';
        $agents = get_users([ 'role__in' => ['administrator', 'editor', 'author', 'contributor'] ]);
        ?>
        <div class="afc-agent-selector-wrapper" style="margin-bottom:20px;">
            <label><strong>👤 Auto-Fill Agent Profile:</strong></label>
            <select id="afc_agent_selector" style="width:100%; max-width:400px; display:block; margin-top:5px;">
                <option value="">-- Choose an Agent --</option>
                <?php foreach ($agents as $agent): 
                    $a_name = get_user_meta($agent->ID, 'first_name', true) . ' ' . get_user_meta($agent->ID, 'last_name', true);
                    if (trim($a_name) === '') $a_name = $agent->display_name;
                    $a_phone = get_user_meta($agent->ID, 'agent_phone', true);
                    $a_photo = get_user_meta($agent->ID, 'agent_photo', true);
                    $a_photo_url = $a_photo ? wp_get_attachment_url($a_photo) : '';
                ?>
                    <option value="<?php echo esc_attr($agent->ID); ?>" 
                            data-name="<?php echo esc_attr($a_name); ?>"
                            data-phone="<?php echo esc_attr($a_phone); ?>"
                            data-photo-id="<?php echo esc_attr($a_photo); ?>"
                            data-photo-url="<?php echo esc_attr($a_photo_url); ?>">
                        <?php echo esc_html($a_name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="afcglide-agent-container" style="display:flex; gap:30px; align-items:center;">
            <div class="afcglide-agent-photo-wrapper">
                <div class="afcglide-preview-box" style="width:120px; height:120px; border-radius:60px; overflow:hidden; border:2px solid #e2e8f0; margin-bottom:10px;">
                    <img src="<?php echo esc_url( $photo_url ?: '/wp-content/plugins/afcglide-listings/assets/images/placeholder-agent.png' ); ?>" id="agent-photo-img" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <input type="hidden" name="_agent_photo_id" id="agent_photo_id" value="<?php echo esc_attr( $photo_id ); ?>">
                <button type="button" class="afc-upload-btn" id="set-agent-photo">Set Agent Photo</button>
            </div>
            <div class="afcglide-agent-fields" style="flex-grow:1;">
                <label style="display:block; font-weight:bold;">Agent Full Name</label>
                <input type="text" name="_agent_name_display" id="afc_agent_name" value="<?php echo esc_attr( $name ); ?>" style="width:100%; margin-bottom:15px;">
                <label style="display:block; font-weight:bold;">Phone Number</label>
                <input type="text" name="_agent_phone_display" id="afc_agent_phone" value="<?php echo esc_attr( $phone ); ?>" style="width:100%; margin-bottom:10px;">
                <label><input type="checkbox" name="_show_floating_whatsapp" value="1" <?php checked( $whatsapp, 1 ); ?>> Enable Floating WhatsApp</label>
            </div>
        </div>
        <?php
    }

    /* ============================================================
       2. MEDIA MATRIX (HERO & STACK)
       ============================================================ */
    public static function render_media( $post ) {
        $hero_id = get_post_meta($post->ID, '_hero_image_id', true);
        $stack_json = get_post_meta($post->ID, '_stack_images_json', true);
        $stack_ids = json_decode($stack_json, true) ?: [];
        ?>
        <div class="afc-media-matrix">
            <div class="afc-upload-zone" data-type="hero" data-limit="1">
                <h4>Main Hero Image</h4>
                <div class="afc-preview-grid" id="hero-preview">
                    <?php if($hero_id) echo self::render_thumb($hero_id); ?>
                </div>
                <button type="button" class="afc-upload-btn">Set Hero Image</button>
                <input type="hidden" name="_hero_image_id" value="<?php echo esc_attr($hero_id); ?>">
            </div>

            <div class="afc-upload-zone" data-type="stack" data-limit="3">
                <h4>3-Photo Stack</h4>
                <div class="afc-preview-grid" id="stack-preview">
                    <?php foreach($stack_ids as $id) echo self::render_thumb($id); ?>
                </div>
                <button type="button" class="afc-upload-btn">Manage Stack</button>
                <input type="hidden" name="_stack_images_raw" value="<?php echo implode(',', $stack_ids); ?>">
            </div>
        </div>
        <?php
    }

    /* ============================================================
       3. SHUTTER-BUG SLIDER (16 PHOTOS)
       ============================================================ */
    public static function render_slider( $post ) {
        $gallery_ids = get_post_meta($post->ID, '_listing_gallery_ids', true) ?: [];
        ?>
        <div class="afc-upload-zone" data-type="gallery" data-limit="16">
            <h4>Gallery Slider (Max 16)</h4>
            <div class="afc-preview-grid" id="gallery-preview">
                <?php foreach($gallery_ids as $id) echo self::render_thumb($id); ?>
            </div>
            <button type="button" class="afc-upload-btn">Bulk Upload Gallery</button>
            <input type="hidden" name="_listing_gallery_raw" value="<?php echo implode(',', $gallery_ids); ?>">
        </div>
        <?php
    }

    /* ============================================================
       4. PROPERTY SPECS
       ============================================================ */
    public static function render_details( $post ) {
        $price = get_post_meta( $post->ID, '_listing_price', true );
        $beds  = get_post_meta( $post->ID, '_listing_beds', true );
        $baths = get_post_meta( $post->ID, '_listing_baths', true );
        $sqft  = get_post_meta( $post->ID, '_listing_sqft', true );
        ?>
        <div class="afcglide-details-grid">
            <div class="afc-detail-column"><label>Price ($)</label><input type="number" name="_listing_price" value="<?php echo esc_attr($price); ?>"></div>
            <div class="afc-detail-column"><label>Beds</label><input type="number" name="_listing_beds" value="<?php echo esc_attr($beds); ?>"></div>
            <div class="afc-detail-column"><label>Baths</label><input type="number" name="_listing_baths" value="<?php echo esc_attr($baths); ?>" step="0.5"></div>
            <div class="afc-detail-column"><label>Sq Ft</label><input type="number" name="_listing_sqft" value="<?php echo esc_attr($sqft); ?>"></div>
        </div>
        <?php
    }

    /* ============================================================
       5. LOCATION & GPS
       ============================================================ */
    public static function render_location( $post ) {
        $address = get_post_meta( $post->ID, '_listing_address', true );
        $lat = get_post_meta( $post->ID, '_gps_lat', true );
        $lng = get_post_meta( $post->ID, '_gps_lng', true );
        ?>
        <div class="afcglide-location-wrapper">
            <label style="display:block; margin-bottom:5px;"><strong>Street Address / Area</strong></label>
            <input type="text" name="_listing_address" value="<?php echo esc_attr($address); ?>" style="width:100%; margin-bottom:15px;">
            <div class="afcglide-gps-row">
                <div class="gps-field-group"><label>Latitude</label><input type="text" name="_gps_lat" value="<?php echo esc_attr($lat); ?>"></div>
                <div class="gps-field-group"><label>Longitude</label><input type="text" name="_gps_lng" value="<?php echo esc_attr($lng); ?>"></div>
            </div>
        </div>
        <?php
    }

    /* ============================================================
       6. AMENITIES
       ============================================================ */
    public static function render_amenities( $post ) {
        $selected = get_post_meta( $post->ID, '_listing_amenities', true ) ?: [];
        $amenities = [
            'infinity_pool' => '♾️ Infinity Pool', 'wine_cellar' => '🍷 Wine Cellar',
            'home_theater' => '🎬 Home Theater', 'smart_home' => '📱 Smart Home',
            'ocean_view' => '🌊 Ocean View', 'private_gym' => '💪 Private Gym'
        ];
        echo '<div class="afcglide-amenities-grid">';
        foreach ( $amenities as $key => $label ) {
            $checked = in_array( $key, $selected ) ? 'checked' : '';
            echo "<label style='display:block; padding:8px;'><input type='checkbox' name='_listing_amenities[]' value='{$key}' {$checked}> {$label}</label>";
        }
        echo '</div>';
    }

    public static function render_publish() {
        echo '<div class="afc-publish-section"><input type="submit" name="publish" id="publish" class="afcglide-publish-btn" value="PUBLISH NEW LISTING"></div>';
    }

    public static function render_description_label() {
        global $post;
        if ( ! $post || $post->post_type !== 'afcglide_listing' ) return;
        echo '<div class="afc-section-header" style="background:#f1f5f9; padding:10px 20px; border-radius:10px; margin:20px 0;"><h3>Property Description</h3></div>';
    }

    private static function render_thumb($id) {
        $url = wp_get_attachment_thumb_url($id);
        if(!$url) return '';
        return sprintf(
            '<div class="afc-preview-item" data-id="%d"><img src="%s"><span class="afc-remove-img">×</span></div>',
            $id, $url
        );
    }

    /* ============================================================
       7. THE WORLD-CLASS SAVE LOGIC
       ============================================================ */
    public static function save_metabox( $post_id ) {
        if ( ! isset( $_POST['afcglide_meta_nonce'] ) || ! wp_verify_nonce( $_POST['afcglide_meta_nonce'], 'afcglide_meta_nonce' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;

        // Photo Matrix Saving
        if ( isset($_POST['_hero_image_id']) ) {
            update_post_meta($post_id, '_hero_image_id', intval($_POST['_hero_image_id']));
            set_post_thumbnail($post_id, intval($_POST['_hero_image_id']));
        }
        if ( isset($_POST['_stack_images_raw']) ) {
            $ids = array_filter(explode(',', $_POST['_stack_images_raw']));
            update_post_meta($post_id, '_stack_images_json', json_encode(array_map('intval', $ids)));
        }
        if ( isset($_POST['_listing_gallery_raw']) ) {
            $ids = array_filter(explode(',', $_POST['_listing_gallery_raw']));
            update_post_meta($post_id, '_listing_gallery_ids', array_map('intval', $ids));
        }

        // Standard Fields
        $fields = ['_listing_price', '_listing_beds', '_listing_baths', '_listing_sqft', '_listing_address', '_gps_lat', '_gps_lng', '_agent_name_display', '_agent_phone_display', '_show_floating_whatsapp', '_agent_photo_id'];
        foreach($fields as $field) {
            if(isset($_POST[$field])) update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
        }

        if(isset($_POST['_listing_amenities'])) {
            update_post_meta($post_id, '_listing_amenities', array_map('sanitize_text_field', $_POST['_listing_amenities']));
        }
    }

    /* ============================================================
       8. SUCCESS PORTAL
       ============================================================ */
    public static function render_success_portal() {
        global $pagenow, $post;
        if ($pagenow == 'post.php' && isset($_GET['message']) && $_GET['message'] == '6' && get_post_type($post) == 'afcglide_listing') {
            ?>
            <div class="afcglide-success-portal" style="background:#10b981; color:white; padding:20px; border-radius:15px; margin:20px 0; display:flex; align-items:center; justify-content:space-between;">
                <div><strong>🚀 Listing Successfully Broadcasted!</strong> Property is Live & Verified.</div>
                <a href="<?php echo get_permalink($post->ID); ?>" target="_blank" style="background:white; color:#10b981; padding:8px 15px; border-radius:8px; text-decoration:none; font-weight:bold;">View Live</a>
            </div>
            <?php
        }
    }
}