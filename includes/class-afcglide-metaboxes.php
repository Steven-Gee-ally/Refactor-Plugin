<?php
namespace AFCGlide\Listings;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Metaboxes v4.0.0
 * Handles the administrative interface for luxury listings.
 * 
 * @package AFCGlide\Listings
 */
class AFCGlide_Metaboxes {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_post_content_support' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_metaboxes' ] );
        add_action( 'save_post', [ __CLASS__, 'save_metaboxes' ], 10, 2 );
        add_action( 'admin_notices', [ __CLASS__, 'render_admin_notices' ] );
    }

    /**
     * Ensure post type supports editor but hide it visually using our CSS
     */
    public static function register_post_content_support() {
        add_post_type_support( C::POST_TYPE, 'editor' );
    }

    /**
     * Register metaboxes
     */
    public static function add_metaboxes() {
        // Remove default WP clutter to force focus on our custom UI
        remove_meta_box( 'submitdiv', C::POST_TYPE, 'side' );
        remove_meta_box( 'postimagediv', C::POST_TYPE, 'side' );
        remove_meta_box( 'authordiv', C::POST_TYPE, 'side' );

        $screens = [ C::POST_TYPE ];
        foreach ( $screens as $screen ) {
            // 1. Property Description (New Headers)
            add_meta_box( 'afc_intro', '📝 1. Property Description', [ __CLASS__, 'render_intro_metabox' ], $screen, 'normal', 'high' );
            
            // 2. Property Narrative (Formerly Description)
            add_meta_box( 'afc_description', '📖 2. Property Narrative', [ __CLASS__, 'render_description_metabox' ], $screen, 'normal', 'high' );
            
            // 3. Property Specifications
            add_meta_box( 'afc_details', '🏠 3. Property Specifications', [ __CLASS__, 'render_details_metabox' ], $screen, 'normal', 'high' );
            
            // 4. Visual Command Center (Hero)
            add_meta_box( 'afc_media_hub', '📸 4. Visual Command Center', [ __CLASS__, 'render_media_metabox' ], $screen, 'normal', 'high' );
            
            // 5. Property Gallery Slider
            add_meta_box( 'afc_slider', '🖼️ 5. Property Gallery Slider', [ __CLASS__, 'render_gallery_metabox' ], $screen, 'normal', 'high' );
            
            // 6. Location & GPS
            add_meta_box( 'afc_location_v2', '📍 6. Location & GPS', [ __CLASS__, 'render_location_metabox' ], $screen, 'normal', 'high' );
            
            // 7. Property Features
            add_meta_box( 'afc_amenities', '💎 7. Property Features', [ __CLASS__, 'render_amenities_metabox' ], $screen, 'normal', 'high' );
            
            // 8. Agent Branding
            add_meta_box( 'afc_agent', '👤 8. Agent Branding', [ __CLASS__, 'render_agent_metabox' ], $screen, 'normal', 'high' );
            
            // 9. Publish Listing
            add_meta_box( 'afc_publish_box', '🚀 9. Publish Listing Control', [ __CLASS__, 'render_publish_metabox' ], $screen, 'normal', 'high' );
        }
    }

    /**
     * Section 1: Property Description (Header & Intro)
     */
    public static function render_intro_metabox( $post ) {
        $intro = get_post_meta( $post->ID, '_listing_intro_text', true );
        ?>
        <div class="afc-metabox-content">
            <div class="afc-field">
                <label class="afc-label">Property Headline</label>
                <input type="text" name="_listing_intro_text" value="<?php echo esc_attr( $intro ); ?>" class="afc-input" placeholder="e.g. Stunning Modern Villa in the Hills" style="font-size: 16px; font-weight: bold;">
                <p class="afc-help-text">A captivating one-line header for the listing.</p>
            </div>
        </div>
        <?php
    }

    /**
     * Section 1: Agent Branding
     */
    public static function render_agent_metabox( $post ) {
        wp_nonce_field( C::NONCE_META, 'afcglide_nonce' );

        $agent_name  = C::get_meta( $post->ID, C::META_AGENT_NAME );
        $agent_phone = C::get_meta( $post->ID, C::META_AGENT_PHONE );
        $agent_photo = C::get_meta( $post->ID, C::META_AGENT_PHOTO );
        $show_wa     = C::get_meta( $post->ID, C::META_SHOW_WA );

        $photo_url = $agent_photo ? wp_get_attachment_image_url( $agent_photo, 'thumbnail' ) : AFCG_URL . 'assets/images/placeholder-agent.png';
        
        // Fetch agents for the selector
        $users = get_users([ 'role__in' => ['administrator', 'editor', 'author', 'contributor'] ] );
        ?>
        <div class="afc-metabox-content">
            <div class="afc-agent-selector-wrapper">
                <label class="afc-label">Auto-Fill from Registry</label>
                <select id="afc_agent_selector" class="afc-select">
                    <option value="">-- Choose an Existing Agent --</option>
                    <?php foreach ( $users as $user ) : 
                        $u_name = $user->display_name;
                        $u_phone = get_user_meta( $user->ID, 'agent_phone', true );
                        $u_photo = get_user_meta( $user->ID, 'agent_photo', true );
                        $u_photo_url = $u_photo ? wp_get_attachment_image_url( $u_photo, 'thumbnail' ) : '';
                    ?>
                        <option value="<?php echo $user->ID; ?>" 
                                data-name="<?php echo esc_attr( $u_name ); ?>" 
                                data-phone="<?php echo esc_attr( $u_phone ); ?>"
                                data-photo-id="<?php echo esc_attr( $u_photo ); ?>"
                                data-photo-url="<?php echo esc_url( $u_photo_url ); ?>">
                            <?php echo esc_html( $u_name ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="afc-agent-container">
                <div class="afc-agent-photo-wrapper">
                    <div class="afc-agent-preview">
                        <img src="<?php echo esc_url( $photo_url ); ?>" id="agent-photo-img" alt="">
                    </div>
                    <input type="hidden" name="_agent_photo_id" id="agent_photo_id" value="<?php echo esc_attr( $agent_photo ); ?>">
                    <button type="button" class="button afcglide-upload-image-btn">Change Photo</button>
                </div>

                <div class="afc-agent-fields">
                    <div class="afc-field">
                        <label class="afc-label">Agent Branding Name</label>
                        <input type="text" name="_agent_name_display" id="afc_agent_name" value="<?php echo esc_attr( $agent_name ); ?>" class="afc-input" placeholder="e.g. John Smith">
                    </div>
                    <div class="afc-field">
                        <label class="afc-label">Direct Contact Number</label>
                        <input type="text" name="_agent_phone_display" id="afc_agent_phone" value="<?php echo esc_attr( $agent_phone ); ?>" class="afc-input" placeholder="+1 234 567 8900">
                    </div>
                    <div class="afc-field">
                        <label class="afc-checkbox-label">
                            <input type="checkbox" name="_show_floating_whatsapp" value="1" <?php checked( $show_wa, '1' ); ?>>
                            Enable Floating WhatsApp for this Listing
                        </label>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Section 2: Visual Command Center (Hero)
     */
    public static function render_media_metabox( $post ) {
        $hero_id = C::get_meta( $post->ID, C::META_HERO_ID );
        ?>
        <div class="afc-metabox-content">
            <div class="afc-upload-zone" data-type="hero" data-limit="1">
                <h4 class="afc-zone-title">Main Hero Thumbnail</h4>
                <div class="afc-preview-grid">
                    <?php if ( $hero_id ) : 
                        $url = wp_get_attachment_image_url( $hero_id, 'medium' );
                        if ( $url ) : ?>
                            <div class="afc-preview-item" data-id="<?php echo $hero_id; ?>">
                                <img src="<?php echo esc_url( $url ); ?>" alt="">
                                <span class="afc-remove-img">×</span>
                            </div>
                        <?php endif; 
                    endif; ?>
                </div>
                <input type="hidden" name="_listing_hero_id" value="<?php echo esc_attr( $hero_id ); ?>">
                <button type="button" class="button afc-upload-btn">Broadcasting Hero Photo</button>
                <p class="afc-help-text">This is the primary image displayed in search results and at the top of the luxury asset page.</p>
            </div>
        </div>
        <?php
    }

    /**
     * Section 3: Property Gallery
     */
    public static function render_gallery_metabox( $post ) {
        $gallery_ids = C::get_meta( $post->ID, C::META_GALLERY_IDS );
        if ( ! is_array( $gallery_ids ) ) $gallery_ids = [];
        ?>
        <div class="afc-metabox-content">
            <div class="afc-upload-zone" data-type="gallery" data-limit="<?php echo C::MAX_GALLERY; ?>">
                <h4 class="afc-zone-title">Interior & Exterior Gallery Slider</h4>
                <div class="afc-preview-grid afc-gallery-preview">
                    <?php foreach ( $gallery_ids as $id ) : 
                        $url = wp_get_attachment_image_url( $id, 'thumbnail' );
                        if ( $url ) : ?>
                            <div class="afc-preview-item" data-id="<?php echo $id; ?>">
                                <img src="<?php echo esc_url( $url ); ?>" alt="">
                                <span class="afc-remove-img">×</span>
                            </div>
                        <?php endif; 
                    endforeach; ?>
                </div>
                <input type="hidden" name="_listing_gallery_ids" value="<?php echo esc_attr( implode( ',', $gallery_ids ) ); ?>">
                <button type="button" class="button afc-upload-btn">Manage Luxury Gallery</button>
                <p class="afc-help-text">Drag images to reorder. Maximum <?php echo C::MAX_GALLERY; ?> high-resolution photos allowed.</p>
            </div>
        </div>
        <?php
    }

    /**
     * Section 1: Property Description
     */
    public static function render_description_metabox( $post ) {
        ?>
        <div class="afc-metabox-content">
            <p class="afc-help-text" style="margin-bottom: 20px;">Craft a compelling story for this luxury asset. Highlight unique heritage and bespoke features.</p>
            <?php 
            wp_editor( $post->post_content, 'content', [
                'textarea_name' => 'content',
                'media_buttons' => true,
                'textarea_rows' => 12,
                'teeny'         => false,
                'quicktags'     => true
            ] );
            ?>
        </div>
        <?php
    }

    /**
     * Section 2: Property Specifications
     */
    public static function render_details_metabox( $post ) {
        $price = C::get_meta( $post->ID, C::META_PRICE );
        $beds  = C::get_meta( $post->ID, C::META_BEDS );
        $baths = C::get_meta( $post->ID, C::META_BATHS );
        $sqft  = C::get_meta( $post->ID, C::META_SQFT );
        ?>
        <div class="afc-metabox-content">
            <div class="afc-details-grid">
                <div class="afc-field">
                    <label class="afc-label">Listing Price (USD)</label>
                    <input type="text" name="_listing_price" value="<?php echo esc_attr( $price ); ?>" class="afc-input" placeholder="e.g. 5,000,000">
                </div>
                <div class="afc-field">
                    <label class="afc-label">Bedrooms</label>
                    <input type="number" name="_listing_beds" value="<?php echo esc_attr( $beds ); ?>" class="afc-input" placeholder="0">
                </div>
                <div class="afc-field">
                    <label class="afc-label">Bathrooms</label>
                    <input type="number" name="_listing_baths" value="<?php echo esc_attr( $baths ); ?>" class="afc-input" step="0.5" placeholder="0.0">
                </div>
                <div class="afc-field">
                    <label class="afc-label">Total Sq Ft</label>
                    <input type="text" name="_listing_sqft" value="<?php echo esc_attr( $sqft ); ?>" class="afc-input" placeholder="0">
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Section 5: Location & GPS
     */
    public static function render_location_metabox( $post ) {
        $address = C::get_meta( $post->ID, C::META_ADDRESS );
        $lat     = C::get_meta( $post->ID, C::META_GPS_LAT );
        $lng     = C::get_meta( $post->ID, C::META_GPS_LNG );
        ?>
        <div class="afc-metabox-content">
            <div class="afc-field" style="margin-bottom: 25px;">
                <label class="afc-label">📍 Primary Physical Address</label>
                <input type="text" name="_listing_address" value="<?php echo esc_attr( $address ); ?>" class="afc-input" placeholder="Street, City, State, ZIP" style="width: 100% !important;">
            </div>
            
            <div class="afc-gps-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <div class="afc-field">
                    <label class="afc-label" style="font-size: 10px; color: #64748b; text-transform: uppercase;">Latitude</label>
                    <input type="text" name="_gps_lat" value="<?php echo esc_attr( $lat ); ?>" class="afc-input" placeholder="0.000000" style="width: 100% !important;">
                </div>
                <div class="afc-field">
                    <label class="afc-label" style="font-size: 10px; color: #64748b; text-transform: uppercase;">Longitude</label>
                    <input type="text" name="_gps_lng" value="<?php echo esc_attr( $lng ); ?>" class="afc-input" placeholder="0.000000" style="width: 100% !important;">
                </div>
            </div>
            <p class="afc-help-text" style="margin-top: 15px;">Synchronized with the global luxury network geospatial database.</p>
        </div>
        <?php
    }

    /**
     * Section 6: Property Features
     */
    public static function render_amenities_metabox( $post ) {
        $selected = C::get_meta( $post->ID, C::META_AMENITIES );
        if ( ! is_array( $selected ) ) $selected = [];

        $amenity_options = [
            'Gourmet Kitchen' => '🍳', 'Infinity Pool' => '🌊', 'Ocean View' => '🌅', 'Wine Cellar' => '🍷',
            'Private Gym' => '🏋️', 'Smart Home Tech' => '📱', 'Outdoor Cinema' => '🎬', 'Helipad Access' => '🚁',
            'Gated Community' => '🏰', 'Guest House' => '🏠', 'Solar Power' => '☀️', 'Beach Front' => '🏖️',
            'Spa / Sauna' => '🧖', '3+ Car Garage' => '🚗', 'Luxury Fire Pit' => '🔥', 'Concierge Service' => '🛎️',
            'Walk-in Closet' => '👗', 'High Ceilings' => '⤴️', 'Staff Quarters' => '👨‍💼', 'Backup Generator' => '⚡'
        ];
        ?>
        <div class="afc-metabox-content">
            <div class="afc-amenities-grid">
                <?php foreach ( $amenity_options as $amenity => $icon ) : 
                    $checked = in_array( $amenity, $selected ) ? 'checked' : '';
                ?>
                    <label class="afc-amenity-item">
                        <input type="checkbox" name="_listing_amenities[]" value="<?php echo esc_attr( $amenity ); ?>" <?php echo $checked; ?>>
                        <span class="amenity-icon"><?php echo $icon; ?></span>
                        <span><?php echo esc_html( $amenity ); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Section 7: Publish Control
     */
    public static function render_publish_metabox( $post ) {
        $status = $post->post_status;
        ?>
        <div class="afc-metabox-content">
            <div class="afc-publish-section">
                <?php if ( $status === 'publish' ) : ?>
                    <button type="submit" name="publish" id="publish" class="button button-primary button-large afc-publish-btn">🚀 Broadcast Listing Updates</button>
                <?php else : ?>
                    <button type="submit" name="publish" id="publish" class="button button-primary button-large afc-publish-btn">🚀 Launch Global Listing</button>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }


    /**
     * Save logic
     */
    public static function save_metaboxes( $post_id, $post ) {
        if ( ! isset( $_POST['afc_nonce'] ) && ! isset( $_POST['afcglide_nonce'] ) ) return;
        
        $nonce = $_POST['afc_nonce'] ?? $_POST['afcglide_nonce'];
        if ( ! wp_verify_nonce( $nonce, C::NONCE_META ) && ! wp_verify_nonce( $nonce, C::NONCE_AJAX ) ) return;

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Meta Map
        $meta_fields = [
            '_listing_intro_text'   => '_listing_intro_text', // New Field
            '_agent_name_display'   => C::META_AGENT_NAME,
            '_agent_phone_display'  => C::META_AGENT_PHONE,
            '_agent_photo_id'       => C::META_AGENT_PHOTO,
            '_show_floating_whatsapp' => C::META_SHOW_WA,
            '_listing_hero_id'      => C::META_HERO_ID,
            '_listing_price'        => C::META_PRICE,
            '_listing_beds'         => C::META_BEDS,
            '_listing_baths'        => C::META_BATHS,
            '_listing_sqft'         => C::META_SQFT,
            '_listing_address'      => C::META_ADDRESS,
            '_gps_lat'              => C::META_GPS_LAT,
            '_gps_lng'              => C::META_GPS_LNG,
        ];

        foreach ( $meta_fields as $form_key => $meta_key ) {
            if ( isset( $_POST[$form_key] ) ) {
                $value = sanitize_text_field( $_POST[$form_key] );
                C::update_meta( $post_id, $meta_key, $value );

                // Sync Hero to Featured Image
                if ( $form_key === '_listing_hero_id' && ! empty( $value ) ) {
                    set_post_thumbnail( $post_id, intval( $value ) );
                }
            }
        }

        // Gallery Sync
        if ( isset( $_POST['_listing_gallery_ids'] ) ) {
            $ids = array_map( 'intval', array_filter( explode( ',', $_POST['_listing_gallery_ids'] ) ) );
            C::update_meta( $post_id, C::META_GALLERY_IDS, $ids );
        }

        // Amenities Sync
        if ( isset( $_POST['_listing_amenities'] ) && is_array( $_POST['_listing_amenities'] ) ) {
            $amenities = array_map( 'sanitize_text_field', $_POST['_listing_amenities'] );
            C::update_meta( $post_id, C::META_AMENITIES, $amenities );
        } else {
            delete_post_meta( $post_id, C::META_AMENITIES );
        }
    }

    /**
     * Admin Notices
     */
    public static function render_admin_notices() {
        global $pagenow, $post;
        if ( $pagenow === 'post.php' && isset( $_GET['message'] ) && $_GET['message'] == '6' && get_post_type( $post ) === C::POST_TYPE ) {
            ?>
            <div class="notice notice-success is-dismissible afc-success-portal">
                <p>🚀 <strong>GLOBAL BROADCAST SUCCESSFUL:</strong> Listing is now live on the luxury network. <a href="<?php echo get_permalink( $post->ID ); ?>" target="_blank">View Live Asset &rarr;</a></p>
            </div>
            <?php
        }
    }
}
