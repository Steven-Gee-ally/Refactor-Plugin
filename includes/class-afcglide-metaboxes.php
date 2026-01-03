<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Metaboxes
 * Version 4.3.0 – Agent-Proof with Required Fields Validation
 * NEW: Prevents publishing incomplete listings
 */
class AFCGlide_Metaboxes {

    const MAX_SLIDER_IMAGES = 16;
    const MAX_STACK_IMAGES = 3;

    private static function meta_schema() {
        return [
            '_agent_name'            => ['type' => 'text',  'default' => '', 'required' => true],
            '_agent_phone'           => ['type' => 'phone', 'default' => '', 'required' => true],
            '_agent_photo_id'        => ['type' => 'attachment', 'default' => 0, 'required' => true],
            '_show_floating_whatsapp'=> ['type' => 'bool',  'default' => 0],
            '_listing_price'         => ['type' => 'float', 'default' => 0, 'required' => true],
            '_listing_beds'          => ['type' => 'int',   'default' => 0, 'required' => true],
            '_listing_baths'         => ['type' => 'float', 'default' => 0, 'required' => true],
            '_listing_sqft'          => ['type' => 'int',   'default' => 0],
            '_listing_property_type' => ['type' => 'text',  'default' => ''],
            '_listing_status'        => ['type' => 'text',  'default' => 'for_sale'],
            '_property_address'      => ['type' => 'text',  'default' => '', 'required' => true],
            '_gps_lat'               => ['type' => 'latitude',  'default' => ''],
            '_gps_lng'               => ['type' => 'longitude', 'default' => ''],
            '_listing_amenities'     => ['type' => 'text',  'array' => true, 'default' => []],
            '_hero_image_id'         => ['type' => 'attachment', 'default' => 0, 'required' => true],
            '_property_stack_ids'    => ['type' => 'attachment', 'array' => true, 'default' => []],
            '_property_slider_ids'   => ['type' => 'attachment', 'array' => true, 'default' => []],
        ];
    }

    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_metaboxes' ] );
        add_action( 'save_post_afcglide_listing', [ __CLASS__, 'save_metabox' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
        add_action( 'edit_form_after_title', [ __CLASS__, 'render_description_label' ] );
        
        // Show validation errors
        add_action( 'admin_notices', [ __CLASS__, 'show_validation_errors' ] );
    }

    public static function admin_assets( $hook ) {
        // Assets are now handled globally by AFCGlide_Admin_Assets
        global $post;
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) || empty( $post ) || $post->post_type !== 'afcglide_listing' ) return;

        // THE EMERALD LIFT - High-End Branding
        echo '<style>
            /* 1. Hide Sidebar & Force Full Width */
            #postbox-container-1 { display:none !important; } 
            #postbox-container-2 { width: 100% !important; }
            #edit-slug-box, #authordiv, #slugdiv { display: none !important; }

            /* 2. Style the Metabox Headers */
            .postbox-header {
                background: #ffffff !important;
                border-bottom: 2px solid #10b981 !important; /* The Emerald Line */
            }
            .postbox-header h2 {
                color: #10b981 !important; /* Emerald Text */
                font-size: 18px !important;
                font-weight: 700 !important;
                letter-spacing: -0.5px;
            }

            /* 3. Style the "Add New Listing" Page Title */
            .wp-heading-inline {
                color: #10b981 !important;
                font-weight: 800 !important;
                text-transform: uppercase;
                letter-spacing: 1px;
            }

            /* 4. Style Custom Section Labels (like Property Description) */
            .post-type-afcglide_listing h3 {
                color: #10b981 !important;
                font-weight: 700 !important;
            }
            
            /* 5. Required Field Indicators */
            .afcglide-required-field::after {
                content: " *";
                color: #ef4444;
                font-weight: bold;
            }
            
            /* 6. Validation Error Styling */
            .afcglide-field-error {
                border: 2px solid #ef4444 !important;
                background: #fef2f2 !important;
            }
        </style>';
    }

    private static function get_meta( $post_id, $key ) {
        $schema = self::meta_schema()[ $key ] ?? null;
        $value  = get_post_meta( $post_id, $key, true );
        if ( $schema && ! empty( $schema['array'] ) ) return is_array( $value ) ? $value : [];
        return $value !== '' ? $value : ( $schema['default'] ?? '' );
    }

    public static function add_metaboxes() {
        $side_boxes = ['submitdiv', 'postimagediv', 'property_locationdiv', 'property_typediv', 'property_statusdiv', 'property_amenitydiv', 'astra_settings_meta_box', 'authordiv', 'slugdiv'];
        foreach ( $side_boxes as $box ) remove_meta_box( $box, 'afcglide_listing', 'side' );

        add_meta_box( 'afc_agent', '👤 1. Agent Branding', [ __CLASS__, 'render_agent' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_media_hub', '📸 2. Visual Command Center (Hero & 3-Stack)', [ __CLASS__, 'render_media' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_slider', '🖼️ 3. Main Property Gallery Slider (Max 16)', [ __CLASS__, 'render_slider' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_details', '🏠 4. Property Specifications', [ __CLASS__, 'render_details' ], 'afcglide_listing', 'normal', 'default' );
        add_meta_box( 'afc_location', '📍 5. Location & GPS', [ __CLASS__, 'render_location' ], 'afcglide_listing', 'normal', 'default' );
        add_meta_box( 'afc_amenities', '💎 6. Property Features (20 Points)', [ __CLASS__, 'render_amenities' ], 'afcglide_listing', 'normal', 'default' );
        add_meta_box( 'afc_publish_box', '🚀 7. Publish New Listing', [ __CLASS__, 'render_publish' ], 'afcglide_listing', 'normal', 'low' );
    }

    public static function render_agent( $post ) {
        wp_nonce_field( 'afcglide_meta_nonce', 'afcglide_meta_nonce' );
        $name = self::get_meta( $post->ID, '_agent_name' );
        $phone = self::get_meta( $post->ID, '_agent_phone' );
        $whatsapp = self::get_meta( $post->ID, '_show_floating_whatsapp' );
        $photo_id = self::get_meta( $post->ID, '_agent_photo_id' );
        $photo_url = $photo_id ? wp_get_attachment_url( $photo_id ) : '';
        $placeholder = AFCG_URL . 'assets/images/agent-placeholder.png';

        // Fetch All Users (Agents) for the Selector
        $agents = get_users([ 'role__in' => ['administrator', 'editor', 'author', 'contributor'] ]);
        ?>
        
        <!-- AGENT SELECTOR BAR -->
        <div style="background:#f0f9ff; border:1px solid #bae6fd; padding:15px; border-radius:8px; margin-bottom:20px; display:flex; align-items:center; justify-content:space-between;">
            <div>
                <label style="font-weight:bold; color:#0369a1; margin-right:10px;">👤 Auto-Fill Agent Profile:</label>
                <select id="afc_agent_selector" style="min-width:250px;">
                    <option value="">-- Choose an Agent to Auto-Fill --</option>
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
            <a href="<?php echo esc_url(admin_url('user-new.php')); ?>" target="_blank" class="button button-secondary">➕ Register New Agent</a>
        </div>

        <!-- AGENT FIELDS -->
        <div class="afcglide-agent-container" style="padding:20px; display:flex; gap:30px; background:#fff; border-radius:12px;">
            <div class="afcglide-agent-photo-wrapper">
                <div class="afcglide-preview-box" style="width:120px; height:120px; border:1px solid #e2e8f0; border-radius:12px; overflow:hidden; margin-bottom:10px;">
                    <img src="<?php echo esc_url( $photo_url ?: $placeholder ); ?>" style="width:100%; height:100%; object-fit:cover;">
                </div>
                <input type="hidden" name="_agent_photo_id" id="agent_photo_id" value="<?php echo esc_attr( $photo_id ); ?>">
                <button type="button" class="button afcglide-upload-image-btn" data-target="agent_photo_id">Set Agent Photo</button>
                <small class="afcglide-required-field" style="display:block; margin-top:5px; color:#64748b;">Required</small>
            </div>
            <div class="afcglide-agent-fields" style="flex-grow:1;">
                <label class="afcglide-required-field" style="font-weight:bold; display:block; margin-bottom:5px;">Agent Full Name</label>
                <input type="text" name="_agent_name" id="afc_agent_name" value="<?php echo esc_attr( $name ); ?>" placeholder="Agent Full Name" style="width:100%; margin-bottom:10px; padding:10px;">
                
                <label class="afcglide-required-field" style="font-weight:bold; display:block; margin-bottom:5px;">Phone Number</label>
                <input type="text" name="_agent_phone" id="afc_agent_phone" value="<?php echo esc_attr( $phone ); ?>" placeholder="Phone Number" style="width:100%; margin-bottom:10px; padding:10px;">
                
                <label style="cursor:pointer;"><input type="checkbox" name="_show_floating_whatsapp" value="1" <?php checked( $whatsapp, 1 ); ?>> Enable Floating WhatsApp Button</label>
            </div>
        </div>
        <?php
    }

    public static function render_media( $post ) {
        $hero_id = self::get_meta( $post->ID, '_hero_image_id' );
        $hero_url = $hero_id ? wp_get_attachment_url( $hero_id ) : '';
        $stack_ids = self::get_meta( $post->ID, '_property_stack_ids' );
        ?>
        <div style="padding:20px; display:grid; grid-template-columns: 1fr 1fr; gap:30px;">
            <div>
                <h4 class="afcglide-required-field">Main Hero Image</h4>
                <div class="afcglide-preview-box" style="border:2px dashed #e2e8f0; height:150px; margin-bottom:10px; display:flex; align-items:center; justify-content:center;">
                    <?php if($hero_url): ?><img src="<?php echo esc_url($hero_url); ?>" style="max-height:100%;"><?php else: ?><span style="color:#94a3b8;">No Hero Set (Required)</span><?php endif; ?>
                </div>
                <input type="hidden" name="_hero_image_id" id="hero_image_id" value="<?php echo esc_attr($hero_id); ?>">
                <button type="button" class="button afcglide-upload-image-btn" data-target="hero_image_id">Set Hero Photo</button>
            </div>
            <div>
                <h4>3-Photo Stack (Optional)</h4>
                <div id="stack-images-container" class="afcglide-image-grid" style="display:grid; grid-template-columns:repeat(3, 1fr); gap:5px; height:150px; border:2px dashed #e2e8f0; margin-bottom:10px; padding:5px;">
                     <?php if ( ! empty( $stack_ids ) ) : 
                        foreach ( (array)$stack_ids as $id ) : 
                            $url = wp_get_attachment_url( $id );
                            if ( $url ) echo '<img src="'.esc_url($url).'" style="width:100%; height:100%; object-fit:cover;">';
                        endforeach; 
                     endif; ?>
                </div>
                <button type="button" class="button afcglide-add-stack-image-btn">Manage Stack Photos</button>
            </div>
        </div>
        <?php
    }

    public static function render_slider( $post ) {
        $slider_ids = self::get_meta( $post->ID, '_property_slider_ids' );
        ?>
        <div class="afcglide-slider-wrapper" style="padding:20px;">
            <div id="afc-slider-container" class="afcglide-image-grid" style="display:grid; grid-template-columns:repeat(8, 1fr); gap:10px; margin-bottom:15px;">
                <?php foreach ( (array)$slider_ids as $id ) : 
                    $url = wp_get_attachment_url( $id );
                    if ( ! $url ) continue; ?>
                    <div class="afcglide-image-item" style="position:relative;">
                        <img src="<?php echo esc_url( $url ); ?>" style="width:100%; height:80px; object-fit:cover; border-radius:4px;">
                        <input type="hidden" name="_property_slider_ids[]" value="<?php echo esc_attr( $id ); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" class="button button-secondary afcglide-add-slider-images-btn">Add Gallery Images (Max 16)</button>
            <span id="afc-slider-count" style="margin-left:15px; color:#64748b;"><?php echo count((array)$slider_ids); ?> / 16 Photos</span>
        </div>
        <?php
    }

    public static function render_details( $post ) {
        $price = self::get_meta( $post->ID, '_listing_price' );
        $beds = self::get_meta( $post->ID, '_listing_beds' );
        $baths = self::get_meta( $post->ID, '_listing_baths' );
        $sqft = self::get_meta( $post->ID, '_listing_sqft' );
        ?>
        <div style="display:grid; grid-template-columns: repeat(4, 1fr); gap:20px; padding:20px;">
            <div>
                <label class="afcglide-required-field" style="font-weight:bold;">Price ($)</label><br>
                <input type="number" name="_listing_price" value="<?php echo esc_attr($price); ?>" style="width:100%; padding:8px;" min="0">
            </div>
            <div>
                <label class="afcglide-required-field" style="font-weight:bold;">Beds</label><br>
                <input type="number" name="_listing_beds" value="<?php echo esc_attr($beds); ?>" style="width:100%; padding:8px;" min="0">
            </div>
            <div>
                <label class="afcglide-required-field" style="font-weight:bold;">Baths</label><br>
                <input type="number" name="_listing_baths" value="<?php echo esc_attr($baths); ?>" style="width:100%; padding:8px;" min="0" step="0.5">
            </div>
            <div>
                <label style="font-weight:bold;">Sq Ft</label><br>
                <input type="number" name="_listing_sqft" value="<?php echo esc_attr($sqft); ?>" style="width:100%; padding:8px;" min="0">
            </div>
        </div>
        <?php
    }

    public static function render_location( $post ) {
        $address = self::get_meta( $post->ID, '_property_address' );
        $lat = self::get_meta( $post->ID, '_gps_lat' );
        $lng = self::get_meta( $post->ID, '_gps_lng' );
        ?>
        <div style="padding:20px;">
            <label class="afcglide-required-field" style="font-weight:bold;">Street Address / Location Description</label><br>
            <input type="text" name="_property_address" value="<?php echo esc_attr($address); ?>" style="width:100%; margin-bottom:15px; padding:10px;">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div><label style="font-weight:bold;">Latitude (Optional)</label><input type="text" name="_gps_lat" value="<?php echo esc_attr($lat); ?>" style="width:100%; padding:10px;"></div>
                <div><label style="font-weight:bold;">Longitude (Optional)</label><input type="text" name="_gps_lng" value="<?php echo esc_attr($lng); ?>" style="width:100%; padding:10px;"></div>
            </div>
        </div>
        <?php
    }

    public static function render_amenities( $post ) {
        $selected = self::get_meta( $post->ID, '_listing_amenities' );
        $selected = is_array($selected) ? $selected : [];
        
        $amenities = [
            'infinity_pool' => '♾️ Infinity Pool', 'wine_cellar' => '🍷 Wine Cellar',
            'home_theater' => '🎬 Home Theater', 'smart_home' => '📱 Smart Home',
            'private_gym' => '💪 Private Gym', 'ocean_view' => '🌊 Ocean View',
            'helipad' => '🚁 Helipad', 'gourmet_kit' => '👨‍🍳 Gourmet Kitchen',
            'spa_sauna' => '🧖 Spa & Sauna', 'gated_entry' => '🛡️ Gated Entry',
            'tennis_court' => '🎾 Tennis Court', 'guest_house' => '🏡 Guest House',
            'elevator' => '🛗 Elevator', 'outdoor_kit' => '🔥 Outdoor Kitchen',
            'beach_front' => '🏖️ Beach Front', 'solar' => '☀️ Solar Power',
            'staff_quarters' => '🧹 Staff Quarters', 'garage_4' => '🏎️ 4+ Car Garage',
            'fire_pit' => '🔥 Fire Pit', 'dock' => '🛥️ Private Dock'
        ];

        echo '<div style="display:grid; grid-template-columns:repeat(4, 1fr); gap:12px; padding:25px; background:#fff; border-radius:12px;">';
        foreach ( $amenities as $key => $label ) {
            $checked = in_array( $key, $selected, true ) ? 'checked' : '';
            echo "<label style='background:#f8fafc; padding:14px; border:1px solid #e2e8f0; border-radius:10px; cursor:pointer; display:flex; align-items:center; gap:10px; font-size:14px;'>";
            echo "<input type='checkbox' name='_listing_amenities[]' value='{$key}' {$checked} style='accent-color:#10b981;'> {$label}</label>";
        }
        echo '</div>';
    }

    public static function render_publish() {
        ?>
        <div style="text-align:center; padding:60px 20px; background: #f8fafc; border-radius: 0 0 20px 20px; border-top: 1px solid #e2e8f0;">
            <p style="margin-bottom: 20px; color: #64748b; font-size: 14px;">
                <strong>Required fields are marked with *</strong><br>
                Complete all required fields before publishing.
            </p>
            <input type="submit" 
                   name="publish" 
                   id="publish"
                   class="button button-primary" 
                   value="PUBLISH NEW LISTING" 
                   style="background: #10b981 !important; 
                          border: none !important; 
                          color: white !important; 
                          padding: 25px 80px !important; 
                          font-size: 22px !important; 
                          font-weight: 800 !important; 
                          height: auto !important; 
                          line-height: 1 !important; 
                          border-radius: 12px !important; 
                          cursor: pointer; 
                          box-shadow: 0 15px 30px rgba(16, 185, 129, 0.25);">
        </div>
        <?php
    }

    public static function render_description_label() {
        global $post;
        if ( ! isset( $post ) || $post->post_type !== 'afcglide_listing' ) return;
        echo '<div style="padding:10px 0;"><h3>Property Description</h3></div>';
    }

    /**
     * VALIDATE REQUIRED FIELDS BEFORE PUBLISH
     */
    public static function save_metabox( $post_id, $post ) {
        if ( ! isset( $_POST['afcglide_meta_nonce'] ) || ! wp_verify_nonce( $_POST['afcglide_meta_nonce'], 'afcglide_meta_nonce' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Check if trying to publish
        $is_publishing = ( isset( $_POST['post_status'] ) && $_POST['post_status'] === 'publish' );
        $missing_fields = [];

        // Validate required fields BEFORE saving
        if ( $is_publishing ) {
            $schema = self::meta_schema();
            foreach ( $schema as $key => $config ) {
                if ( ! empty( $config['required'] ) ) {
                    $value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : '';
                    
                    // Check if empty
                    if ( empty( $value ) || ( is_array( $value ) && count( $value ) === 0 ) ) {
                        $field_label = ucwords( str_replace( '_', ' ', trim( $key, '_' ) ) );
                        $missing_fields[] = $field_label;
                    }
                }
            }
        }

        // If missing required fields, force to draft and show error
        if ( ! empty( $missing_fields ) ) {
            // Store error for display
            set_transient( 'afcg_validation_error_' . $post_id, $missing_fields, 45 );
            
            // Force back to draft
            remove_action( 'save_post_afcglide_listing', [ __CLASS__, 'save_metabox' ], 10 );
            wp_update_post([
                'ID'          => $post_id,
                'post_status' => 'draft'
            ]);
            add_action( 'save_post_afcglide_listing', [ __CLASS__, 'save_metabox' ], 10, 2 );
        }

        // Save all fields (even if validation failed)
        foreach ( self::meta_schema() as $key => $config ) {
            $value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : $config['default'];
            
            switch ( $config['type'] ) {
                case 'int': $value = is_array($value) ? array_map('intval', $value) : intval($value); break;
                case 'float': $value = floatval($value); break;
                case 'bool': $value = !empty($value) ? 1 : 0; break;
                case 'attachment': $value = is_array($value) ? array_map('intval', $value) : intval($value); break;
                default: $value = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
            }
            update_post_meta( $post_id, $key, $value );
        }
    }

    /**
     * SHOW VALIDATION ERRORS AS ADMIN NOTICE
     */
    public static function show_validation_errors() {
        global $post;
        if ( ! $post || $post->post_type !== 'afcglide_listing' ) return;
        
        $errors = get_transient( 'afcg_validation_error_' . $post->ID );
        if ( $errors ) {
            echo '<div class="notice notice-error is-dismissible" style="border-left: 4px solid #ef4444;">
                    <p style="font-size:16px;"><strong>⚠️ Cannot Publish Listing</strong></p>
                    <p>The following required fields are missing:</p>
                    <ul style="list-style: disc; margin-left: 25px;">';
            foreach ( $errors as $field ) {
                echo '<li><strong>' . esc_html( $field ) . '</strong></li>';
            }
            echo '</ul>
                    <p style="color:#64748b;">Complete all required fields (marked with *) and try publishing again.</p>
                  </div>';
            delete_transient( 'afcg_validation_error_' . $post->ID );
        }
    }
}