<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Metaboxes
 * Version 4.1.0 – Hardened Security, Validation, Agent-First, 16-Photo Gallery
 */
class AFCGlide_Metaboxes {

    const MAX_SLIDER_IMAGES = 16;
    const MAX_STACK_IMAGES = 3;

    private static function meta_schema() {
        return [
            '_agent_name'            => ['type' => 'text',  'default' => ''],
            '_agent_phone'           => ['type' => 'phone', 'default' => ''],
            '_agent_photo_id'        => ['type' => 'attachment', 'default' => 0],
            '_show_floating_whatsapp'=> ['type' => 'bool',  'default' => 0],
            '_listing_price'         => ['type' => 'float', 'default' => 0],
            '_listing_beds'          => ['type' => 'int',   'default' => 0],
            '_listing_baths'         => ['type' => 'float', 'default' => 0],
            '_listing_sqft'          => ['type' => 'int',   'default' => 0],
            '_listing_property_type' => ['type' => 'text',  'default' => ''],
            '_listing_status'        => ['type' => 'text',  'default' => 'for_sale'],
            '_property_address'      => ['type' => 'text',  'default' => ''],
            '_gps_lat'               => ['type' => 'latitude',  'default' => ''],
            '_gps_lng'               => ['type' => 'longitude', 'default' => ''],
            '_listing_amenities'     => ['type' => 'text',  'array' => true, 'default' => []],
            '_hero_image_id'         => ['type' => 'attachment', 'default' => 0],
            '_property_stack_ids'    => ['type' => 'attachment', 'array' => true, 'default' => []],
            '_property_slider_ids'   => ['type' => 'attachment', 'array' => true, 'default' => []],
        ];
    }

    public static function init() {
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_metaboxes' ] );
        add_action( 'save_post_afcglide_listing', [ __CLASS__, 'save_metabox' ], 10, 2 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'admin_assets' ] );
        add_action( 'edit_form_after_title', [ __CLASS__, 'render_description_label' ] );
    }

    public static function admin_assets( $hook ) {
        global $post;
        if ( ! in_array( $hook, [ 'post.php', 'post-new.php' ], true ) || 
             empty( $post ) || 
             $post->post_type !== 'afcglide_listing' ) {
            return;
        }

        wp_enqueue_media();
        wp_enqueue_style( 'afcglide-metabox-css', AFCG_URL . 'assets/css/admin.css', [], AFCG_VERSION );
        wp_enqueue_script( 'afcglide-metabox-js', AFCG_URL . 'assets/js/afcglide-admin.js', ['jquery'], AFCG_VERSION, true );
        
        // Pass constants to JavaScript
        wp_localize_script( 'afcglide-metabox-js', 'afcglideConfig', [
            'maxSliderImages' => self::MAX_SLIDER_IMAGES,
            'maxStackImages'  => self::MAX_STACK_IMAGES,
        ]);

        // Force Full Width Layout CSS
        echo '<style>#poststuff #post-body.columns-2 #postbox-container-1 { display:none; } #poststuff #post-body.columns-2 #postbox-container-2 { width: 100%; }</style>';
    }

    private static function get_meta( $post_id, $key ) {
        $schema = self::meta_schema()[ $key ] ?? null;
        $value  = get_post_meta( $post_id, $key, true );
        
        if ( $schema && ! empty( $schema['array'] ) ) {
            return is_array( $value ) ? $value : [];
        }
        
        return $value !== '' ? $value : ( $schema['default'] ?? '' );
    }

    public static function add_metaboxes() {
        // Clear sidebars
        $side_boxes = [
            'submitdiv', 'postimagediv', 'property_locationdiv', 
            'property_typediv', 'property_statusdiv', 'property_amenitydiv', 
            'astra_settings_meta_box'
        ];
        foreach ( $side_boxes as $box ) {
            remove_meta_box( $box, 'afcglide_listing', 'side' );
        }

        // Layout Order
        add_meta_box( 'afc_agent', '👤 Agent Information', [ __CLASS__, 'render_agent' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_details', '🏠 Property Details', [ __CLASS__, 'render_details' ], 'afcglide_listing', 'normal', 'high' );
        add_meta_box( 'afc_location', '📍 Location & GPS', [ __CLASS__, 'render_location' ], 'afcglide_listing', 'normal', 'default' );
        add_meta_box( 'afc_slider', '🖼️ Main Property Gallery Slider (Max 16)', [ __CLASS__, 'render_slider' ], 'afcglide_listing', 'normal', 'default' );
        add_meta_box( 'afc_media', '📸 Hero & 3-Photo Stack', [ __CLASS__, 'render_media' ], 'afcglide_listing', 'normal', 'default' );
        add_meta_box( 'afc_amenities', '💎 Luxury Amenities', [ __CLASS__, 'render_amenities' ], 'afcglide_listing', 'normal', 'default' );
        add_meta_box( 'afc_publish_box', '🚀 Finalize Listing', [ __CLASS__, 'render_publish' ], 'afcglide_listing', 'normal', 'low' );
    }

    public static function render_agent( $post ) {
        wp_nonce_field( 'afcglide_meta_nonce', 'afcglide_meta_nonce' );
        
        $name = self::get_meta( $post->ID, '_agent_name' );
        $phone = self::get_meta( $post->ID, '_agent_phone' );
        $whatsapp = self::get_meta( $post->ID, '_show_floating_whatsapp' );
        $photo_id = self::get_meta( $post->ID, '_agent_photo_id' );
        $photo_url = $photo_id ? wp_get_attachment_url( $photo_id ) : '';
        $placeholder = AFCG_URL . 'assets/images/agent-placeholder.png';
        ?>
        <div class="afcglide-agent-container">
            <div class="afcglide-agent-photo-wrapper">
                <div class="afcglide-preview-box">
                    <img src="<?php echo esc_url( $photo_url ?: $placeholder ); ?>" 
                         alt="<?php esc_attr_e( 'Agent Photo', 'afcglide' ); ?>" 
                         class="afcglide-agent-photo">
                </div>
                <input type="hidden" name="_agent_photo_id" id="agent_photo_id" value="<?php echo esc_attr( $photo_id ); ?>">
                <button type="button" class="button afcglide-upload-image-btn" data-target="agent_photo_id">
                    <?php esc_html_e( 'Set Agent Photo', 'afcglide' ); ?>
                </button>
                <?php if ( $photo_id ) : ?>
                    <button type="button" class="button afcglide-remove-image-btn" data-target="agent_photo_id">
                        <?php esc_html_e( 'Remove', 'afcglide' ); ?>
                    </button>
                <?php endif; ?>
            </div>
            <div class="afcglide-agent-fields">
                <input type="text" 
                       name="_agent_name" 
                       value="<?php echo esc_attr( $name ); ?>" 
                       placeholder="<?php esc_attr_e( 'Agent Full Name', 'afcglide' ); ?>" 
                       class="afcglide-agent-name-input">
                <input type="text" 
                       name="_agent_phone" 
                       value="<?php echo esc_attr( $phone ); ?>" 
                       placeholder="<?php esc_attr_e( 'Phone Number', 'afcglide' ); ?>" 
                       class="afcglide-agent-phone-input">
                <label class="afcglide-checkbox-label">
                    <input type="checkbox" 
                           name="_show_floating_whatsapp" 
                           value="1" 
                           <?php checked( $whatsapp, 1 ); ?>>
                    <?php esc_html_e( 'Enable Floating WhatsApp Button', 'afcglide' ); ?>
                </label>
            </div>
        </div>
        <?php
    }

    public static function render_slider( $post ) {
        $slider_ids = self::get_meta( $post->ID, '_property_slider_ids' );
        $count = count( $slider_ids );
        $max = self::MAX_SLIDER_IMAGES;
        ?>
        <div class="afcglide-slider-wrapper">
            <div class="afcglide-slider-header">
                <p class="description">
                    <?php esc_html_e( 'Upload high-resolution photos for the main property slider.', 'afcglide' ); ?>
                </p>
                <span id="afc-slider-count" class="afcglide-count-badge">
                    <?php echo esc_html( $count ); ?> / <?php echo esc_html( $max ); ?> Photos
                </span>
            </div>
            <div id="afc-slider-container" class="afcglide-image-grid">
                <?php foreach ( $slider_ids as $id ) : 
                    $url = wp_get_attachment_url( $id );
                    if ( ! $url ) continue;
                ?>
                    <div class="afcglide-image-item">
                        <img src="<?php echo esc_url( $url ); ?>" alt="<?php esc_attr_e( 'Property Photo', 'afcglide' ); ?>">
                        <input type="hidden" name="_property_slider_ids[]" value="<?php echo esc_attr( $id ); ?>">
                        <button type="button" 
                                class="afc-remove-slider-img" 
                                aria-label="<?php esc_attr_e( 'Remove image', 'afcglide' ); ?>">
                            &times;
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" 
                    class="button button-secondary afcglide-add-slider-images-btn" 
                    <?php disabled( $count >= $max ); ?>>
                <?php esc_html_e( 'Add Gallery Images', 'afcglide' ); ?>
            </button>
        </div>
        <?php
    }

    public static function render_details( $post ) {
        $price = self::get_meta( $post->ID, '_listing_price' );
        $type = self::get_meta( $post->ID, '_listing_property_type' );
        $beds = self::get_meta( $post->ID, '_listing_beds' );
        $baths = self::get_meta( $post->ID, '_listing_baths' );
        $sqft = self::get_meta( $post->ID, '_listing_sqft' );

        $property_types = [
            'villa'  => __( 'Villa', 'afcglide' ),
            'condo'  => __( 'Condo', 'afcglide' ),
            'house'  => __( 'House', 'afcglide' ),
            'estate' => __( 'Estate', 'afcglide' ),
        ];
        ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Price / Type', 'afcglide' ); ?></th>
                <td>
                    <input type="number" 
                           name="_listing_price" 
                           value="<?php echo esc_attr( $price ); ?>" 
                           placeholder="<?php esc_attr_e( 'Price ($)', 'afcglide' ); ?>" 
                           class="afcglide-price-input" 
                           min="0" 
                           step="1000">
                    <select name="_listing_property_type" class="afcglide-type-select">
                        <?php foreach ( $property_types as $key => $label ) : ?>
                            <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $type, $key ); ?>>
                                <?php echo esc_html( $label ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Specs (Beds/Baths/Sqft)', 'afcglide' ); ?></th>
                <td>
                    <input type="number" 
                           name="_listing_beds" 
                           value="<?php echo esc_attr( $beds ); ?>" 
                           placeholder="<?php esc_attr_e( 'Beds', 'afcglide' ); ?>" 
                           class="afcglide-beds-input" 
                           min="0" 
                           max="50">
                    <input type="number" 
                           name="_listing_baths" 
                           step="0.5" 
                           value="<?php echo esc_attr( $baths ); ?>" 
                           placeholder="<?php esc_attr_e( 'Baths', 'afcglide' ); ?>" 
                           class="afcglide-baths-input" 
                           min="0" 
                           max="50">
                    <input type="number" 
                           name="_listing_sqft" 
                           value="<?php echo esc_attr( $sqft ); ?>" 
                           placeholder="<?php esc_attr_e( 'Sq Ft', 'afcglide' ); ?>" 
                           class="afcglide-sqft-input" 
                           min="0">
                </td>
            </tr>
        </table>
        <?php
    }

    public static function render_location( $post ) {
        $address = self::get_meta( $post->ID, '_property_address' );
        $lat = self::get_meta( $post->ID, '_gps_lat' );
        $lng = self::get_meta( $post->ID, '_gps_lng' );
        ?>
        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Street Address', 'afcglide' ); ?></th>
                <td>
                    <input type="text" 
                           name="_property_address" 
                           value="<?php echo esc_attr( $address ); ?>" 
                           class="afcglide-address-input">
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'GPS Coordinates', 'afcglide' ); ?></th>
                <td>
                    <input type="text" 
                           name="_gps_lat" 
                           value="<?php echo esc_attr( $lat ); ?>" 
                           placeholder="<?php esc_attr_e( 'Latitude', 'afcglide' ); ?>" 
                           class="afcglide-lat-input" 
                           pattern="-?([0-8]?[0-9]|90)(\.[0-9]{1,10})?"
                           title="<?php esc_attr_e( 'Enter a valid latitude between -90 and 90', 'afcglide' ); ?>">
                    <input type="text" 
                           name="_gps_lng" 
                           value="<?php echo esc_attr( $lng ); ?>" 
                           placeholder="<?php esc_attr_e( 'Longitude', 'afcglide' ); ?>" 
                           class="afcglide-lng-input"
                           pattern="-?(1[0-7][0-9]|[0-9]{1,2})(\.[0-9]{1,10})?"
                           title="<?php esc_attr_e( 'Enter a valid longitude between -180 and 180', 'afcglide' ); ?>">
                </td>
            </tr>
        </table>
        <?php
    }

    public static function render_media( $post ) {
        $hero_id = self::get_meta( $post->ID, '_hero_image_id' );
        $stack_ids = self::get_meta( $post->ID, '_property_stack_ids' );
        $hero_url = $hero_id ? wp_get_attachment_url( $hero_id ) : '';
        ?>
        <div class="afcglide-hero-section">
            <h4><?php esc_html_e( '🏠 Main Hero Image', 'afcglide' ); ?></h4>
            <div class="afcglide-preview-box">
                <?php if ( $hero_url ) : ?>
                    <img src="<?php echo esc_url( $hero_url ); ?>" 
                         alt="<?php esc_attr_e( 'Hero Image', 'afcglide' ); ?>" 
                         class="afcglide-hero-preview">
                <?php endif; ?>
            </div>
            <input type="hidden" name="_hero_image_id" id="hero_image_id" value="<?php echo esc_attr( $hero_id ); ?>">
            <button type="button" class="button afcglide-upload-image-btn" data-target="hero_image_id">
                <?php esc_html_e( 'Set Hero Photo', 'afcglide' ); ?>
            </button>
            <?php if ( $hero_id ) : ?>
                <button type="button" class="button afcglide-remove-image-btn" data-target="hero_image_id">
                    <?php esc_html_e( 'Remove', 'afcglide' ); ?>
                </button>
            <?php endif; ?>
        </div>
        <div class="afcglide-stack-section">
            <h4><?php esc_html_e( '📸 3-Photo Stack', 'afcglide' ); ?></h4>
            <div id="stack-images-container" class="afcglide-stack-container">
                <?php foreach ( $stack_ids as $id ) : 
                    $url = wp_get_attachment_url( $id );
                    if ( ! $url ) continue;
                ?>
                    <div class="afcglide-stack-item">
                        <img src="<?php echo esc_url( $url ); ?>" alt="<?php esc_attr_e( 'Stack Photo', 'afcglide' ); ?>">
                        <input type="hidden" name="_property_stack_ids[]" value="<?php echo esc_attr( $id ); ?>">
                        <button type="button" 
                                class="afc-remove-stack-img" 
                                aria-label="<?php esc_attr_e( 'Remove image', 'afcglide' ); ?>">
                            &times;
                        </button>
                    </div>
                <?php endforeach; ?>
            </div>
            <button type="button" 
                    class="button afcglide-add-stack-image-btn" 
                    <?php disabled( count( $stack_ids ) >= self::MAX_STACK_IMAGES ); ?>>
                <?php esc_html_e( 'Manage Stack Photos', 'afcglide' ); ?>
            </button>
        </div>
        <?php
    }

    public static function render_amenities( $post ) {
        $selected = self::get_meta( $post->ID, '_listing_amenities' );
        $amenities = [
            'gourmet_kitchen' => __( '👨‍🍳 Gourmet Kitchen', 'afcglide' ),
            'infinity_pool'   => __( '♾️ Infinity Pool', 'afcglide' ),
            'ocean_view'      => __( '🌊 Ocean View', 'afcglide' ),
            'wine_cellar'     => __( '🍷 Wine Cellar', 'afcglide' ),
            'home_gym'        => __( '💪 Private Gym', 'afcglide' ),
            'smart_home'      => __( '📱 Smart Home', 'afcglide' ),
            'beach_front'     => __( '🏖️ Beach Front', 'afcglide' ),
            'spa_sauna'       => __( '🧖 Spa / Sauna', 'afcglide' ),
        ];
        ?>
        <div class="afcglide-amenities-grid">
            <?php foreach ( $amenities as $key => $label ) : 
                $checked = in_array( $key, $selected, true ) ? 'checked' : '';
            ?>
                <label class="afcglide-amenity-label">
                    <input type="checkbox" 
                           name="_listing_amenities[]" 
                           value="<?php echo esc_attr( $key ); ?>" 
                           <?php echo $checked; ?>>
                    <?php echo esc_html( $label ); ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?php
    }

    public static function render_publish() {
        ?>
        <div class="afcglide-publish-container">
            <input type="submit" 
                   name="publish" 
                   class="button button-primary afcglide-publish-btn" 
                   value="<?php esc_attr_e( 'PUBLISH LUXURY LISTING', 'afcglide' ); ?>">
        </div>
        <?php
    }

    public static function render_description_label() {
        global $post;
        if ( ! isset( $post ) || $post->post_type !== 'afcglide_listing' ) {
            return;
        }
        ?>
        <div class="afcglide-description-header">
            <h3><?php esc_html_e( 'Property Description', 'afcglide' ); ?></h3>
        </div>
        <?php
    }

    public static function save_metabox( $post_id, $post ) {
        // Verify nonce
        if ( ! isset( $_POST['afcglide_meta_nonce'] ) ) {
            return;
        }
        
        if ( ! wp_verify_nonce( $_POST['afcglide_meta_nonce'], 'afcglide_meta_nonce' ) ) {
            return;
        }

        // Check autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check permissions
        if ( ! current_user_can( 'edit_post', $post_id ) ) {
            return;
        }

        foreach ( self::meta_schema() as $key => $config ) {
            $value = isset( $_POST[ $key ] ) ? $_POST[ $key ] : $config['default'];
            
            // Handle arrays
            if ( ! empty( $config['array'] ) ) {
                $value = is_array( $value ) ? $value : [];
            }
            
            // Type-specific sanitization and validation
            switch ( $config['type'] ) {
                case 'int':
                    $value = is_array( $value ) ? array_map( 'intval', $value ) : intval( $value );
                    break;
                
                case 'float':
                    $value = floatval( $value );
                    break;
                
                case 'bool':
                    $value = ! empty( $value ) ? 1 : 0;
                    break;
                
                case 'phone':
                    $value = preg_replace( '/[^0-9+\-() ]/', '', sanitize_text_field( $value ) );
                    break;
                
                case 'latitude':
                    $val = floatval( $value );
                    $value = max( -90, min( 90, $val ) );
                    break;
                
                case 'longitude':
                    $val = floatval( $value );
                    $value = max( -180, min( 180, $val ) );
                    break;
                
                case 'attachment':
                    if ( is_array( $value ) ) {
                        // Validate all attachment IDs
                        $value = array_filter( $value, function( $id ) {
                            return get_post_type( intval( $id ) ) === 'attachment';
                        });
                        $value = array_map( 'intval', $value );
                        
                        // Enforce slider limit
                        if ( $key === '_property_slider_ids' && count( $value ) > self::MAX_SLIDER_IMAGES ) {
                            $value = array_slice( $value, 0, self::MAX_SLIDER_IMAGES );
                        }
                        
                        // Enforce stack limit
                        if ( $key === '_property_stack_ids' && count( $value ) > self::MAX_STACK_IMAGES ) {
                            $value = array_slice( $value, 0, self::MAX_STACK_IMAGES );
                        }
                    } else {
                        $id = intval( $value );
                        $value = ( $id && get_post_type( $id ) === 'attachment' ) ? $id : 0;
                    }
                    break;
                
                default:
                    $value = is_array( $value ) 
                        ? array_map( 'sanitize_text_field', $value ) 
                        : sanitize_text_field( $value );
            }

            update_post_meta( $post_id, $key, $value );
        }
    }
}