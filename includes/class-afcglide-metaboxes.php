<?php
namespace AFCGlide\Listings;

/**
 * AFCGlide Metaboxes - Property Data Management
 * Version 3.7.1 - Corrected Syntax & Agent Proofing
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Metaboxes {

    // All meta keys we manage
    public static $meta_keys = [
        '_listing_price', 
        '_listing_beds', 
        '_listing_baths', 
        '_listing_sqft',
        '_listing_property_type',
        '_property_address',
        '_property_city',
        '_property_state',
        '_property_country',
        '_gps_lat', 
        '_gps_lng', 
        '_listing_amenities', 
        '_listing_status',
        '_agent_name', 
        '_agent_email',
        '_agent_phone', 
        '_agent_license',
        '_agent_whatsapp', 
        '_show_floating_whatsapp',
        '_agent_photo_id', 
        '_agency_logo_id', 
        '_hero_image_id',
        '_property_stack_ids',
        '_property_slider_ids',
    ];

    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_metaboxes' ] );
        add_action( 'save_post_afcglide_listing', [ __CLASS__, 'save_metabox' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
    }

    public static function admin_assets( $hook ) {
        global $post;
        
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ] ) ) {
            return;
        }
        
        if ( ! isset( $post ) || $post->post_type !== 'afcglide_listing' ) {
            return;
        }
        
        wp_enqueue_media();
        
        wp_enqueue_style( 
            'afcglide-metabox-css', 
            AFCG_URL . 'assets/css/admin.css', 
            [], 
            AFCG_VERSION 
        );
        
        wp_enqueue_script( 
            'afcglide-metabox-js', 
            AFCG_URL . 'assets/js/afcglide-admin.js', 
            [ 'jquery' ], 
            AFCG_VERSION, 
            true 
        );
    }

    public static function add_metaboxes() {
        // --- START AGENT PROOFING (Hides the clutter) ---
        remove_meta_box( 'astra_settings_meta_box', 'afcglide_listing', 'side' );
        remove_meta_box( 'tagsdiv-post_tag', 'afcglide_listing', 'side' );
        remove_meta_box( 'categorydiv', 'afcglide_listing', 'side' );
        remove_meta_box( 'commentstatusdiv', 'afcglide_listing', 'normal' );
        remove_meta_box( 'slugdiv', 'afcglide_listing', 'normal' );
        // --- END AGENT PROOFING ---

        // Registering our custom AFCGlide boxes
        add_meta_box( 'afc_details', __( 'Property Details', 'afcglide' ), [ __CLASS__, 'render_details' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_location', __( 'Location & GPS', 'afcglide' ), [ __CLASS__, 'render_location' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_media', __( '📸 Property Photos', 'afcglide' ), [ __CLASS__, 'render_media' ], 'afcglide_listing', 'normal', 'default' );
        add_meta_box( 'afc_amenities', __( '💎 LUXURY AMENITIES (VER 3.7)', 'afcglide' ), [ __CLASS__, 'render_amenities' ], 'afcglide_listing', 'normal', 'default' );
        add_meta_box( 'afc_agent', __( 'Agent Information', 'afcglide' ), [ __CLASS__, 'render_agent' ], 'afcglide_listing', 'side', 'default' );
    }

    /**
     * Property Details
     */
    public static function render_details( $post ) {
        wp_nonce_field( 'afcglide_meta_nonce', 'afcglide_meta_nonce' );
        
        $price = get_post_meta( $post->ID, '_listing_price', true );
        $beds = get_post_meta( $post->ID, '_listing_beds', true );
        $baths = get_post_meta( $post->ID, '_listing_baths', true );
        $sqft = get_post_meta( $post->ID, '_listing_sqft', true );
        $status = get_post_meta( $post->ID, '_listing_status', true );
        $property_type = get_post_meta( $post->ID, '_listing_property_type', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="listing_price">Price ($)</label></th>
                <td>
                    <input type="number" 
                           id="listing_price" 
                           name="_listing_price" 
                           value="<?php echo esc_attr( $price ); ?>" 
                           step="1000"
                           min="0"
                           class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th><label for="listing_property_type">Property Type</label></th>
                <td>
                    <select id="listing_property_type" name="_listing_property_type" class="regular-text">
                        <option value="">Select Type</option>
                        <option value="villa" <?php selected( $property_type, 'villa' ); ?>>Villa</option>
                        <option value="condo" <?php selected( $property_type, 'condo' ); ?>>Condo</option>
                        <option value="apartment" <?php selected( $property_type, 'apartment' ); ?>>Apartment</option>
                        <option value="house" <?php selected( $property_type, 'house' ); ?>>House</option>
                        <option value="penthouse" <?php selected( $property_type, 'penthouse' ); ?>>Penthouse</option>
                        <option value="estate" <?php selected( $property_type, 'estate' ); ?>>Estate</option>
                        <option value="land" <?php selected( $property_type, 'land' ); ?>>Land</option>
                        <option value="commercial" <?php selected( $property_type, 'commercial' ); ?>>Commercial</option>
                    </select>
                </td>
            </tr>
            
            <tr>
                <th><label for="listing_beds">Bedrooms</label></th>
                <td>
                    <input type="number" 
                           id="listing_beds" 
                           name="_listing_beds" 
                           value="<?php echo esc_attr( $beds ); ?>" 
                           min="0"
                           class="small-text">
                </td>
            </tr>
            
            <tr>
                <th><label for="listing_baths">Bathrooms</label></th>
                <td>
                    <input type="number" 
                           id="listing_baths" 
                           name="_listing_baths" 
                           value="<?php echo esc_attr( $baths ); ?>" 
                           step="0.5"
                           min="0"
                           class="small-text">
                </td>
            </tr>
            
            <tr>
                <th><label for="listing_sqft">Square Feet</label></th>
                <td>
                    <input type="number" 
                           id="listing_sqft" 
                           name="_listing_sqft" 
                           value="<?php echo esc_attr( $sqft ); ?>" 
                           min="0"
                           class="regular-text">
                </td>
            </tr>
            
            <tr>
                <th><label for="listing_status">Status</label></th>
                <td>
                    <select id="listing_status" name="_listing_status" class="regular-text">
                        <option value="for_sale" <?php selected( $status, 'for_sale' ); ?>>For Sale</option>
                        <option value="just_listed" <?php selected( $status, 'just_listed' ); ?>>Just Listed</option>
                        <option value="under_contract" <?php selected( $status, 'under_contract' ); ?>>Under Contract</option>
                        <option value="sold" <?php selected( $status, 'sold' ); ?>>Sold</option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Location & GPS
     */
    public static function render_location( $post ) {
        $address = get_post_meta( $post->ID, '_property_address', true );
        $city = get_post_meta( $post->ID, '_property_city', true );
        $state = get_post_meta( $post->ID, '_property_state', true );
        $country = get_post_meta( $post->ID, '_property_country', true );
        $lat = get_post_meta( $post->ID, '_gps_lat', true );
        $lng = get_post_meta( $post->ID, '_gps_lng', true );
        ?>
        <table class="form-table">
            <tr>
                <th><label for="property_address">Street Address</label></th>
                <td>
                    <input type="text" 
                           id="property_address" 
                           name="_property_address" 
                           value="<?php echo esc_attr( $address ); ?>" 
                           class="regular-text"
                           placeholder="e.g. 123 Beach Road">
                </td>
            </tr>
            
            <tr>
                <th><label for="property_city">City</label></th>
                <td>
                    <input type="text" 
                           id="property_city" 
                           name="_property_city" 
                           value="<?php echo esc_attr( $city ); ?>" 
                           class="regular-text"
                           placeholder="e.g. Tamarindo">
                </td>
            </tr>
            
            <tr>
                <th><label for="property_state">State/Province</label></th>
                <td>
                    <input type="text" 
                           id="property_state" 
                           name="_property_state" 
                           value="<?php echo esc_attr( $state ); ?>" 
                           class="regular-text"
                           placeholder="e.g. Guanacaste">
                </td>
            </tr>
            
            <tr>
                <th><label for="property_country">Country</label></th>
                <td>
                    <input type="text" 
                           id="property_country" 
                           name="_property_country" 
                           value="<?php echo esc_attr( $country ); ?>" 
                           class="regular-text"
                           placeholder="e.g. Costa Rica">
                </td>
            </tr>
            
            <tr>
                <th><label>GPS Coordinates</label></th>
                <td>
                    <p class="description">For map display. Get coordinates from Google Maps.</p>
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <input type="text" 
                               name="_gps_lat" 
                               value="<?php echo esc_attr( $lat ); ?>" 
                               placeholder="Latitude (e.g. 9.748)"
                               style="flex: 1;">
                        <input type="text" 
                               name="_gps_lng" 
                               value="<?php echo esc_attr( $lng ); ?>" 
                               placeholder="Longitude (e.g. -83.75)"
                               style="flex: 1;">
                    </div>
                </td>
            </tr>
        </table>
        <?php
    }
/**
     * Property Photos - Updated for GitHub JS Compatibility
     */
    public static function render_media( $post ) {
        $hero_id = get_post_meta( $post->ID, '_hero_image_id', true );
        $stack_ids = get_post_meta( $post->ID, '_property_stack_ids', true );
        $slider_ids = get_post_meta( $post->ID, '_property_slider_ids', true );
        
        if ( ! is_array( $stack_ids ) ) $stack_ids = [];
        if ( ! is_array( $slider_ids ) ) $slider_ids = [];
        
        $hero_url = $hero_id ? wp_get_attachment_url( $hero_id ) : '';
        ?>
        <div style="padding: 15px;">
            <div style="margin-bottom: 25px; padding-bottom: 25px; border-bottom: 1px solid #ddd;">
                <h4 style="margin-top: 0;">🏠 Main Hero Image</h4>
                <div class="afcglide-image-upload">
                    <div class="afcglide-preview-box">
                        <?php if ( $hero_url ) : ?>
                            <img src="<?php echo esc_url( $hero_url ); ?>" style="max-width: 200px; height: auto; border-radius: 8px; margin-top:10px; display:block;">
                        <?php endif; ?>
                    </div>
                    <input type="hidden" id="hero_image_id" name="_hero_image_id" value="<?php echo esc_attr( $hero_id ); ?>">
                    <button type="button" class="button afcglide-upload-image-btn" data-target="hero_image_id">
                        <?php echo $hero_url ? 'Change Image' : 'Select Image'; ?>
                    </button>
                    <button type="button" class="button afcglide-remove-image-btn" data-target="hero_image_id" style="<?php echo $hero_url ? '' : 'display:none;'; ?> color:red;">Remove</button>
                </div>
            </div>

            <div style="margin-bottom: 25px;">
                <h4 style="margin-top: 0;">📸 3-Photo Stack</h4>
                <div id="stack-images-container" class="afcglide-image-container">
                    <?php foreach ( $stack_ids as $img_id ) : 
                        $img_url = wp_get_attachment_url( $img_id );
                    ?>
                        <div class="stack-image-item" style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px; background:#f0f0f0; padding:5px; border-radius:5px;">
                            <img src="<?php echo esc_url( $img_url ); ?>" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                            <input type="hidden" name="_property_stack_ids[]" value="<?php echo esc_attr( $img_id ); ?>">
                            <button type="button" class="button remove-stack-image">Remove</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button afcglide-add-stack-image-btn" <?php disabled(count($stack_ids) >= 3); ?>>
                    Add Stack Image (<?php echo count($stack_ids); ?>/3)
                </button>
            </div>

            <div>
                <h4 style="margin-top: 0;">🖼️ Gallery Slider</h4>
                <div id="slider-images-container" class="afcglide-image-container" style="margin-bottom: 10px;">
                    <?php foreach ( $slider_ids as $img_id ) : 
                        $img_url = wp_get_attachment_url( $img_id );
                    ?>
                        <div class="slider-image-item" style="display:inline-block; margin-right:10px; position: relative;">
                            <img src="<?php echo esc_url( $img_url ); ?>" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">
                            <input type="hidden" name="_property_slider_ids[]" value="<?php echo esc_attr( $img_id ); ?>">
                            <button type="button" class="remove-slider-image" style="position: absolute; top: -5px; right: -5px; background: #ff0000; color: #fff; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;">×</button>
                        </div>
                    <?php endforeach; ?>
                </div>
                <button type="button" class="button afcglide-add-slider-image-btn" <?php disabled(count($slider_ids) >= 12); ?>>
                    Add Gallery Image (<?php echo count($slider_ids); ?>/12)
                </button>
            </div>
        </div>
        <?php
    }

  /**
     * Amenities
     */
    public static function render_amenities( $post ) {
        $amenities = get_post_meta( $post->ID, '_listing_amenities', true );
        if ( ! is_array( $amenities ) ) {
            $amenities = [];
        }
        
        // YOUR NEW LUXURY 20 LIST
        $available_amenities = [
            'gourmet_kitchen' => '👨‍🍳 Gourmet Kitchen',
            'infinity_pool'   => '♾️ Infinity Pool',
            'ocean_view'      => '🌊 Ocean View',
            'wine_cellar'     => '🍷 Wine Cellar',
            'home_gym'        => '💪 Private Gym',
            'smart_home'      => '📱 Smart Home Tech',
            'outdoor_cinema'  => '🎬 Outdoor Cinema',
            'helipad'         => '🚁 Helipad Access',
            'gated_community' => '🛡️ Gated Community',
            'guest_house'     => '🏠 Guest House',
            'solar_power'     => '☀️ Solar Power',
            'beach_front'     => '🏖️ Beach Front',
            'spa_sauna'       => '🧖 Spa / Sauna',
            'garage_3_car'    => '🚗 3+ Car Garage',
            'fire_pit'        => '🔥 Luxury Fire Pit',
            'concierge'       => '🛎️ Concierge Service',
            'walk_in_closet'  => '👕 Walk-in Closet',
            'high_ceilings'   => '🏛️ High Ceilings',
            'staff_quarters'  => '🏠 Staff Quarters',
            'backup_power'    => '🔋 Backup Generator'
        ];
        ?>
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; padding: 15px;">
            <?php foreach ( $available_amenities as $value => $label ) : ?>
                <label style="display: flex; align-items: center; padding: 8px; background: #f9f9f9; border-radius: 4px; cursor: pointer;">
                    <input type="checkbox" 
                           name="_listing_amenities[]" 
                           value="<?php echo esc_attr( $value ); ?>" 
                           <?php checked( in_array( $value, $amenities ) ); ?>
                           style="margin-right: 8px;">
                    <?php echo esc_html( $label ); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }

    /**
     * Agent Information - Aligned with GitHub JS
     */
    public static function render_agent( $post ) {
        $agent_name = get_post_meta( $post->ID, '_agent_name', true );
        $agent_email = get_post_meta( $post->ID, '_agent_email', true );
        $agent_phone = get_post_meta( $post->ID, '_agent_phone', true );
        $agent_license = get_post_meta( $post->ID, '_agent_license', true );
        $agent_whatsapp = get_post_meta( $post->ID, '_agent_whatsapp', true );
        $show_whatsapp = get_post_meta( $post->ID, '_show_floating_whatsapp', true );
        
        $agent_photo_id = get_post_meta( $post->ID, '_agent_photo_id', true );
        $agency_logo_id = get_post_meta( $post->ID, '_agency_logo_id', true );
        
        $agent_photo_url = $agent_photo_id ? wp_get_attachment_url( $agent_photo_id ) : '';
        $agency_logo_url = $agency_logo_id ? wp_get_attachment_url( $agency_logo_id ) : '';
        ?>
        <div style="padding: 10px;">
            <div style="text-align: center; margin-bottom: 20px;" class="afcglide-image-upload">
                <p><strong>Agent Photo</strong></p>
                <div class="afcglide-preview-box">
                    <?php if ( $agent_photo_url ) : ?>
                        <img src="<?php echo esc_url( $agent_photo_url ); ?>" style="width: 100px; height: 100px; border-radius: 50%; object-fit: cover; margin: 0 auto 10px; display: block;">
                    <?php else : ?>
                        <div style="width: 100px; height: 100px; border-radius: 50%; background: #f0f0f0; margin: 0 auto 10px; display: flex; align-items: center; justify-content: center; color: #999;">👤</div>
                    <?php endif; ?>
                </div>
                <input type="hidden" id="agent_photo_id" name="_agent_photo_id" value="<?php echo esc_attr( $agent_photo_id ); ?>">
                <button type="button" class="button afcglide-upload-image-btn" data-target="agent_photo_id">
                    <?php echo $agent_photo_url ? 'Change Photo' : 'Upload Photo'; ?>
                </button>
            </div>

            <p>
                <label><strong>Agent Name</strong></label><br>
                <input type="text" name="_agent_name" value="<?php echo esc_attr( $agent_name ); ?>" style="width: 100%;" placeholder="John Smith">
            </p>
            
            <p>
                <label><strong>Email</strong></label><br>
                <input type="email" name="_agent_email" value="<?php echo esc_attr( $agent_email ); ?>" style="width: 100%;" placeholder="agent@agency.com">
            </p>
            
            <p>
                <label><strong>Phone</strong></label><br>
                <input type="tel" name="_agent_phone" value="<?php echo esc_attr( $agent_phone ); ?>" style="width: 100%;" placeholder="+1 (555) 123-4567">
            </p>
            
            <p>
                <label><strong>License Number</strong></label><br>
                <input type="text" name="_agent_license" value="<?php echo esc_attr( $agent_license ); ?>" style="width: 100%;" placeholder="RE-123456">
            </p>
            
            <hr>
            
            <p>
                <label>
                    <input type="checkbox" name="_show_floating_whatsapp" value="1" <?php checked( $show_whatsapp, '1' ); ?>>
                    <strong>Enable WhatsApp Button</strong>
                </label><br>
                <span class="description">Shows a floating button on the listing</span>
            </p>
            
            <p>
                <label><strong>WhatsApp Number</strong></label><br>
                <input type="text" name="_agent_whatsapp" value="<?php echo esc_attr( $agent_whatsapp ); ?>" style="width: 100%;" placeholder="+506-1234-5678">
            </p>
            
            <hr>
            
            <div style="text-align: center;" class="afcglide-image-upload">
                <p><strong>Agency Logo</strong></p>
                <div class="afcglide-preview-box">
                    <?php if ( $agency_logo_url ) : ?>
                        <img src="<?php echo esc_url( $agency_logo_url ); ?>" style="max-width: 120px; height: auto; margin: 0 auto 10px; display: block;">
                    <?php endif; ?>
                </div>
                <input type="hidden" id="agency_logo_id" name="_agency_logo_id" value="<?php echo esc_attr( $agency_logo_id ); ?>">
                <button type="button" class="button afcglide-upload-image-btn" data-target="agency_logo_id">
                    <?php echo $agency_logo_url ? 'Change Logo' : 'Upload Logo'; ?>
                </button>
            </div>
        </div>
        <?php
    }

    /**
     * Save all metabox data
     */
    public static function save_metabox( $post_id, $post ) {
        // Security checks
        if ( ! isset( $_POST['afcglide_meta_nonce'] ) ) {
            return;
        }
        
        if ( ! wp_verify_nonce( $_POST['afcglide_meta_nonce'], 'afcglide_meta_nonce' ) ) {
            return;
        }
        
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        // Save all meta fields
        foreach ( self::$meta_keys as $field ) {
            if ( isset( $_POST[ $field ] ) ) {
                $value = $_POST[ $field ];
                
                // Handle arrays (like amenities and image IDs)
                if ( is_array( $value ) ) {
                    $value = array_map( 'sanitize_text_field', $value );
                } else {
                    $value = sanitize_text_field( $value );
                }
                
                update_post_meta( $post_id, $field, $value );
            } else {
                // Handle unchecked checkboxes
                if ( $field === '_show_floating_whatsapp' ) {
                    update_post_meta( $post_id, $field, '0' );
                } elseif ( $field === '_listing_amenities' ) {
                    update_post_meta( $post_id, $field, [] );
                }
            }
        }
    }
}