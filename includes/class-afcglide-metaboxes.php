<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Metaboxes
 * Version 4.4.0 – Professional Refactor (CSS Decoupled)
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
        add_action( 'admin_notices', [ __CLASS__, 'show_validation_errors' ] );
        add_action( 'admin_notices', [ __CLASS__, 'render_success_portal' ] );
    }

   public static function admin_assets( $hook ) {
    global $post;
    
    // Safety check: Only run on our listing pages
    if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) || empty( $post ) || $post->post_type !== 'afcglide_listing' ) {
        return;
    }

    // Enqueue the main admin styles (Make sure the path and version are correct)
    wp_enqueue_style( 'afcglide-admin-css', AFCG_URL . 'assets/css/admin.css', [], '3.2.1' );
    
    // Enqueue the Media Uploader (required for your 16-photo slider)
    wp_enqueue_media();
    
    // Enqueue your Admin JS
    wp_enqueue_script( 'afcglide-admin-js', AFCG_URL . 'assets/js/admin.js', ['jquery'], '3.2.1', true );
}

    public static function add_metaboxes() {
        // Clean up the sidebar
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
        $agents = get_users([ 'role__in' => ['administrator', 'editor', 'author', 'contributor'] ]);
        ?>
        
        <div class="afc-agent-selector-wrapper">
            <label>👤 Auto-Fill Agent Profile:</label>
            <select id="afc_agent_selector">
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
            <a href="<?php echo esc_url(admin_url('user-new.php')); ?>" target="_blank" class="button button-secondary">➕ Register New</a>
        </div>

        <div class="afcglide-agent-container">
            <div class="afcglide-agent-photo-wrapper">
                <div class="afcglide-preview-box">
                    <img src="<?php echo esc_url( $photo_url ?: $placeholder ); ?>" class="afcglide-agent-photo">
                </div>
                <input type="hidden" name="_agent_photo_id" id="agent_photo_id" value="<?php echo esc_attr( $photo_id ); ?>">
                <button type="button" class="button afcglide-upload-image-btn" data-target="agent_photo_id">Set Agent Photo</button>
            </div>
            <div class="afcglide-agent-fields">
                <label class="afcglide-required-field">Agent Full Name</label>
                <input type="text" name="_agent_name" id="afc_agent_name" value="<?php echo esc_attr( $name ); ?>">
                
                <label class="afcglide-required-field">Phone Number</label>
                <input type="text" name="_agent_phone" id="afc_agent_phone" value="<?php echo esc_attr( $phone ); ?>">
                
                <label><input type="checkbox" name="_show_floating_whatsapp" value="1" <?php checked( $whatsapp, 1 ); ?>> Enable Floating WhatsApp</label>
            </div>
        </div>
        <?php
    }

    public static function render_media( $post ) {
        $hero_id = self::get_meta( $post->ID, '_hero_image_id' );
        $hero_url = $hero_id ? wp_get_attachment_url( $hero_id ) : '';
        $stack_ids = self::get_meta( $post->ID, '_property_stack_ids' );
        ?>
        <div class="afcglide-media-hub">
            <div class="afcglide-hero-section">
                <h4 class="afcglide-required-field">Main Hero Image</h4>
                <div class="afcglide-preview-box" id="hero-preview">
                    <?php if($hero_url): ?><img src="<?php echo esc_url($hero_url); ?>"><?php else: ?><span>No Hero Set</span><?php endif; ?>
                </div>
                <input type="hidden" name="_hero_image_id" id="hero_image_id" value="<?php echo esc_attr($hero_id); ?>">
                <button type="button" class="button afcglide-upload-image-btn" data-target="hero_image_id">Set Hero Photo</button>
            </div>
            <div class="afcglide-stack-section">
                <h4>3-Photo Stack (Optional)</h4>
                <div id="stack-images-container" class="afcglide-image-grid">
                     <?php if ( ! empty( $stack_ids ) ) : 
                        foreach ( (array)$stack_ids as $id ) : 
                            $url = wp_get_attachment_url( $id );
                            if ( $url ) echo '<img src="'.esc_url($url).'">';
                        endforeach; 
                     endif; ?>
                </div>
                <button type="button" class="button afcglide-add-stack-image-btn">Manage Stack</button>
            </div>
        </div>
        <?php
    }

    public static function render_slider( $post ) {
        $slider_ids = self::get_meta( $post->ID, '_property_slider_ids' );
        ?>
        <div class="afcglide-slider-wrapper">
            <div id="afc-slider-container">
                <?php foreach ( (array)$slider_ids as $id ) : 
                    $url = wp_get_attachment_url( $id );
                    if ( ! $url ) continue; ?>
                    <div class="afcglide-image-item">
                        <img src="<?php echo esc_url( $url ); ?>">
                        <input type="hidden" name="_property_slider_ids[]" value="<?php echo esc_attr( $id ); ?>">
                    </div>
                <?php endforeach; ?>
            </div>
            <div class="afcglide-slider-footer">
                <button type="button" class="button button-secondary afcglide-add-slider-images-btn">Add Gallery Images (Max 16)</button>
                <span id="afc-slider-count"><?php echo count((array)$slider_ids); ?> / 16 Photos</span>
            </div>
        </div>
        <?php
    }

    public static function render_details( $post ) {
        $price = self::get_meta( $post->ID, '_listing_price' );
        $beds = self::get_meta( $post->ID, '_listing_beds' );
        $baths = self::get_meta( $post->ID, '_listing_baths' );
        $sqft = self::get_meta( $post->ID, '_listing_sqft' );
        ?>
        <div class="afcglide-details-grid">
            <div><label class="afcglide-required-field">Price ($)</label><input type="number" name="_listing_price" value="<?php echo esc_attr($price); ?>"></div>
            <div><label class="afcglide-required-field">Beds</label><input type="number" name="_listing_beds" value="<?php echo esc_attr($beds); ?>"></div>
            <div><label class="afcglide-required-field">Baths</label><input type="number" name="_listing_baths" value="<?php echo esc_attr($baths); ?>" step="0.5"></div>
            <div><label>Sq Ft</label><input type="number" name="_listing_sqft" value="<?php echo esc_attr($sqft); ?>"></div>
        </div>
        <?php
    }

   public static function render_location( $post ) {
        $address = self::get_meta( $post->ID, '_property_address' );
        $lat = self::get_meta( $post->ID, '_gps_lat' );
        $lng = self::get_meta( $post->ID, '_gps_lng' );
        ?>
        <div class="afcglide-location-wrapper">
            <label class="afcglide-required-field">Street Address / Location Description</label>
            <input type="text" name="_property_address" value="<?php echo esc_attr($address); ?>" style="width:100%; margin-bottom:15px;">
            
            <div class="afcglide-gps-row">
                <div class="gps-field-group">
                    <label>Latitude</label>
                    <input type="text" name="_gps_lat" value="<?php echo esc_attr($lat); ?>" placeholder="e.g. 9.7489">
                </div>
                <div class="gps-field-group">
                    <label>Longitude</label>
                    <input type="text" name="_gps_lng" value="<?php echo esc_attr($lng); ?>" placeholder="e.g. -84.6321">
                </div>
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

        echo '<div class="afcglide-amenities-grid">';
        foreach ( $amenities as $key => $label ) {
            $checked = in_array( $key, $selected, true ) ? 'checked' : '';
            echo "<label class='afcglide-amenity-label'>";
            echo "<input type='checkbox' name='_listing_amenities[]' value='{$key}' {$checked}> {$label}</label>";
        }
        echo '</div>';
    }

    public static function render_publish() {
        ?>
        <div class="afc-publish-section">
            <p>Required fields are marked with <strong>*</strong></p>
            <input type="submit" name="publish" id="publish" class="afcglide-publish-btn" value="PUBLISH NEW LISTING">
        </div>
        <?php
    }

    public static function render_description_label() {
        global $post;
        if ( ! isset( $post ) || $post->post_type !== 'afcglide_listing' ) return;
        echo '<div class="afc-section-header"><h3>Property Description</h3></div>';
    }

    public static function save_metabox( $post_id, $post ) {
        if ( ! isset( $_POST['afcglide_meta_nonce'] ) || ! wp_verify_nonce( $_POST['afcglide_meta_nonce'], 'afcglide_meta_nonce' ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        $is_publishing = ( isset( $_POST['post_status'] ) && $_POST['post_status'] === 'publish' );
        $missing_fields = [];

        if ( $is_publishing ) {
            $schema = self::meta_schema();
            foreach ( $schema as $key => $config ) {
                if ( ! empty( $config['required'] ) ) {
                    $value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : '';
                    if ( empty( $value ) || ( is_array( $value ) && count( $value ) === 0 ) ) {
                        $missing_fields[] = ucwords( str_replace( '_', ' ', trim( $key, '_' ) ) );
                    }
                }
            }
        }

        if ( ! empty( $missing_fields ) ) {
            set_transient( 'afcg_validation_error_' . $post_id, $missing_fields, 45 );
            remove_action( 'save_post_afcglide_listing', [ __CLASS__, 'save_metabox' ], 10 );
            wp_update_post(['ID' => $post_id, 'post_status' => 'draft']);
            add_action( 'save_post_afcglide_listing', [ __CLASS__, 'save_metabox' ], 10, 2 );
        }

        foreach ( self::meta_schema() as $key => $config ) {
            $value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : $config['default'];
            switch ( $config['type'] ) {
                case 'int': $value = is_array($value) ? array_map('intval', $value) : intval($value); break;
                case 'float': $value = floatval($value); break;
                case 'bool': $value = !empty($value) ? 1 : 0; break;
                default: $value = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
            }
            update_post_meta( $post_id, $key, $value );
        }
    }

    public static function show_validation_errors() {
        global $post;
        if ( ! $post || $post->post_type !== 'afcglide_listing' ) return;
        $errors = get_transient( 'afcg_validation_error_' . $post->ID );
        if ( $errors ) {
            echo '<div class="notice notice-error is-dismissible">
                    <p><strong>⚠️ Cannot Publish:</strong> Missing ' . implode(', ', $errors) . '</p>
                  </div>';
            delete_transient( 'afcg_validation_error_' . $post->ID );
        }
    }
    /**
     * AFCGlide Luxury Success Portal
     * Displays a professional confirmation after the Emerald Button is pressed.
     */
    public static function render_success_portal() {
        global $pagenow, $post;

        // Only show on our specific listing type and after a save/publish
        if ($pagenow == 'post.php' && isset($_GET['message']) && $_GET['message'] == '6' && get_post_type($post) == 'afcglide_listing') {
            $view_link = get_permalink($post->ID);
            ?>
            <div class="afcglide-success-portal">
                <div class="afc-portal-icon">🚀</div>
                <div class="afc-portal-content">
                    <h3>Listing Successfully Broadcasted!</h3>
                    <p>Property ID: <strong>#<?php echo $post->ID; ?></strong> | Status: <strong>Live & Verified</strong></p>
                </div>
                <div class="afc-portal-actions">
                    <a href="<?php echo esc_url($view_link); ?>" class="afc-portal-btn view" target="_blank">View Live Listing</a>
                    <a href="<?php echo admin_url('edit.php?post_type=afcglide_listing'); ?>" class="afc-portal-btn inventory">Back to Inventory</a>
                </div>
            </div>

            <style>
                .afcglide-success-portal {
                    background: #ffffff;
                    border-left: 6px solid #10b981;
                    padding: 25px;
                    margin: 20px 20px 20px 0;
                    display: flex;
                    align-items: center;
                    gap: 25px;
                    border-radius: 12px;
                    box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.1);
                    animation: slideInUp 0.5s ease-out;
                }
                .afc-portal-icon { font-size: 40px; }
                .afc-portal-content h3 { 
                    margin: 0; 
                    color: #10b981; 
                    font-size: 20px; 
                    font-weight: 800; 
                }
                .afc-portal-content p { margin: 5px 0 0 0; color: #64748b; }
                .afc-portal-actions { margin-left: auto; display: flex; gap: 15px; }
                .afc-portal-btn {
                    padding: 12px 20px;
                    border-radius: 8px;
                    text-decoration: none;
                    font-weight: 700;
                    transition: all 0.2s;
                }
                .afc-portal-btn.view { background: #10b981; color: white; }
                .afc-portal-btn.inventory { background: #f1f5f9; color: #475569; }
                .afc-portal-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                
                @keyframes slideInUp {
                    from { opacity: 0; transform: translateY(20px); }
                    to { opacity: 1; transform: translateY(0); }
                }
            </style>
            <?php
        }
    }
}