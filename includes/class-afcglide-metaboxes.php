<?php
namespace AFCGlide\Listings;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Metaboxes v5.1.0 - BULLETPROOF EDITION
 * Handles the administrative interface for luxury listings.
 * NOW WITH: Spanish Translation Fields + Property Type + Full AJAX Handler Compatibility
 *
 * @package AFCGlide\Listings
 */
class AFCGlide_Metaboxes {

    public static function init() {
        add_action( 'init', [ __CLASS__, 'register_post_content_support' ] );
        add_action( 'add_meta_boxes', [ __CLASS__, 'add_metaboxes' ] );
        add_action( 'save_post', [ __CLASS__, 'save_metaboxes' ], 10, 2 );
        add_action( 'admin_notices', [ __CLASS__, 'render_admin_notices' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_assets' ] );
    }

    /**
     * Enqueue Admin Assets
     */
    public static function enqueue_admin_assets( $hook ) {
        global $post;
        if ( $hook !== 'post-new.php' && $hook !== 'post.php' ) return;
        if ( ! $post || get_post_type( $post ) !== C::POST_TYPE ) return;

        wp_enqueue_media();
        wp_enqueue_style( 'afc-admin-ui', plugin_dir_url(__FILE__) . '../assets/css/admin-submission.css', [], '5.1.0' );
        wp_enqueue_script( 'afc-metaboxes-js', plugin_dir_url(__FILE__) . '../assets/js/afc-metaboxes.js', ['jquery', 'jquery-ui-sortable'], '5.1.0', true );
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

        $screen = C::POST_TYPE;
        
        // 1. Pricing Architecture
        add_meta_box( 
            'afc_pricing', 
            '💰 1. Pricing Architecture', 
            [ __CLASS__, 'render_pricing_metabox' ], 
            $screen, 
            'normal', 
            'high' 
        );
        
        // 2. Property Headline
        add_meta_box( 
            'afc_intro', 
            '📝 2. Property Headline', 
            [ __CLASS__, 'render_intro_metabox' ], 
            $screen, 
            'normal', 
            'high' 
        );
        
        // 3. Property Narrative (Enhanced with Spanish)
        add_meta_box( 
            'afc_description', 
            '📖 3. Property Narrative (Bilingual)', 
            [ __CLASS__, 'render_description_metabox' ], 
            $screen, 
            'normal', 
            'high' 
        );
        
        // 4. Property Specifications (Enhanced with Type)
        add_meta_box( 
            'afc_details', 
            '🏠 4. Property Specifications', 
            [ __CLASS__, 'render_details_metabox' ], 
            $screen, 
            'normal', 
            'high' 
        );
        
        // 5. Visual Command Center (Hero)
        add_meta_box( 
            'afc_media_hub', 
            '📸 5. Visual Command Center', 
            [ __CLASS__, 'render_media_metabox' ], 
            $screen, 
            'normal', 
            'high' 
        );
        
        // 6. Property Gallery Slider
        add_meta_box( 
            'afc_slider', 
            '🖼️ 6. Property Gallery Slider', 
            [ __CLASS__, 'render_gallery_metabox' ], 
            $screen, 
            'normal', 
            'high' 
        );
        
        // 7. Location & GPS
        add_meta_box( 
            'afc_location_v2', 
            '📍 7. Location & GPS', 
            [ __CLASS__, 'render_location_metabox' ], 
            $screen, 
            'normal', 
            'high' 
        );
        
        // 8. Property Features
        add_meta_box( 
            'afc_amenities', 
            '💎 8. Property Features', 
            [ __CLASS__, 'render_amenities_metabox' ], 
            $screen, 
            'normal', 
            'high' 
        );
        
        // 9. Agent Branding
        add_meta_box( 
            'afc_agent', 
            '👤 9. Agent Branding', 
            [ __CLASS__, 'render_agent_metabox' ], 
            $screen, 
            'normal', 
            'high' 
        );
        
        // 10. Intelligence & Files
        add_meta_box( 
            'afc_intelligence', 
            '📊 10. Asset Intelligence & Files', 
            [ __CLASS__, 'render_intelligence_metabox' ], 
            $screen, 
            'normal', 
            'high' 
        );

        // 11. Global Broadcast Control
        add_meta_box( 
            'afc_publish_box', 
            '🚀 11. Global Broadcast Control', 
            [ __CLASS__, 'render_publish_metabox' ], 
            $screen, 
            'normal', 
            'high' 
        );
    }

    /**
     * Section 1: Pricing Architecture
     */
    public static function render_pricing_metabox( $post ) {
        $price = C::get_meta( $post->ID, C::META_PRICE );
        ?>
        <div class="afc-metabox-content">
            <div class="afc-field">
                <label class="afc-label">Listing Price (USD)</label>
                <div style="position: relative;">
                    <span style="position: absolute; left: 15px; top: 12px; font-weight: 900; color: #059669; font-size: 16px;">$</span>
                    <input type="text" name="_listing_price" value="<?php echo esc_attr( $price ); ?>" class="afc-input" placeholder="e.g. 5,000,000" style="padding-left: 35px; font-size: 18px; font-weight: 900; color: #059669;">
                </div>
                <p class="afc-help-text">Enter the listing price in USD.</p>
            </div>
        </div>
        <?php
    }

    /**
     * Section 2: Property Headline
     */
    public static function render_intro_metabox( $post ) {
        $intro = C::get_meta( $post->ID, C::META_INTRO );
        ?>
        <div class="afc-metabox-content">
            <div class="afc-field">
                <label class="afc-label">Property Headline (English)</label>
                <input type="text" name="_listing_intro_text" value="<?php echo esc_attr( $intro ); ?>" class="afc-input" placeholder="e.g. Stunning Modern Villa in the Hills" style="font-size: 16px; font-weight: bold;">
                <p class="afc-help-text">A captivating one-line header for the listing.</p>
            </div>
        </div>
        <?php
    }

    /**
     * Section 3: Property Narrative (ENHANCED - Now with Spanish!)
     */
    public static function render_description_metabox( $post ) {
        $narrative = C::get_meta( $post->ID, C::META_NARRATIVE );
        $intro_es = C::get_meta( $post->ID, C::META_INTRO_ES );
        $narrative_es = C::get_meta( $post->ID, C::META_NARRATIVE_ES );
        ?>
        <div class="afc-metabox-content">
            
            <!-- ENGLISH SECTION -->
            <div style="background: #f0f9ff; padding: 20px; border-radius: 12px; margin-bottom: 30px; border-left: 4px solid #3b82f6;">
                <h4 style="margin: 0 0 15px 0; color: #1e40af; font-size: 14px; font-weight: 800;">🇺🇸 ENGLISH VERSION</h4>
                <label class="afc-label">Full Property Description</label>
                <?php 
                wp_editor( $narrative, '_listing_narrative', [
                    'textarea_name' => '_listing_narrative',
                    'textarea_rows' => 10,
                    'media_buttons' => false,
                    'teeny'         => false,
                    'tinymce'       => [
                        'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink',
                    ],
                ]);
                ?>
                <p class="afc-help-text" style="margin-top: 10px;">Describe the lifestyle and luxury of this property in English.</p>
            </div>

            <!-- SPANISH SECTION (NEW!) -->
            <div style="background: #fff7ed; padding: 20px; border-radius: 12px; border-left: 4px solid #f97316;">
                <h4 style="margin: 0 0 15px 0; color: #9a3412; font-size: 14px; font-weight: 800;">🇪🇸 SPANISH VERSION (Opcional)</h4>
                
                <div class="afc-field">
                    <label class="afc-label">Headline en Español</label>
                    <input type="text" name="_listing_intro_es" value="<?php echo esc_attr( $intro_es ); ?>" class="afc-input" placeholder="e.g. Villa Moderna Impresionante en las Colinas">
                </div>

                <div class="afc-field" style="margin-top: 20px;">
                    <label class="afc-label">Descripción Completa en Español</label>
                    <?php 
                    wp_editor( $narrative_es, '_listing_narrative_es', [
                        'textarea_name' => '_listing_narrative_es',
                        'textarea_rows' => 8,
                        'media_buttons' => false,
                        'teeny'         => false,
                        'tinymce'       => [
                            'toolbar1' => 'bold,italic,underline,bullist,numlist,link,unlink',
                        ],
                    ]);
                    ?>
                </div>
                <p class="afc-help-text" style="margin-top: 10px;">Spanish translation will be shown when the site is in Spanish mode.</p>
            </div>

        </div>
        <?php
    }

    /**
     * Section 4: Property Specifications (ENHANCED - Now with Property Type!)
     */
    public static function render_details_metabox( $post ) {
        $beds  = C::get_meta( $post->ID, C::META_BEDS );
        $baths = C::get_meta( $post->ID, C::META_BATHS );
        $sqft  = C::get_meta( $post->ID, C::META_SQFT );
        $type  = C::get_meta( $post->ID, C::META_TYPE );
        ?>
        <div class="afc-metabox-content">
            <div class="afc-details-grid">
                <div class="afc-field">
                    <label class="afc-label">Property Type</label>
                    <select name="_listing_type" class="afc-select" style="height: 50px; font-weight: 700;">
                        <option value="">Select Type</option>
                        <option value="residential" <?php selected( $type, 'residential' ); ?>>🏡 Residential</option>
                        <option value="commercial" <?php selected( $type, 'commercial' ); ?>>🏢 Commercial</option>
                        <option value="land" <?php selected( $type, 'land' ); ?>>🌳 Land</option>
                        <option value="condo" <?php selected( $type, 'condo' ); ?>>🏘️ Condo</option>
                        <option value="vacation" <?php selected( $type, 'vacation' ); ?>>🏖️ Vacation Rental</option>
                    </select>
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
     * Section 5: Visual Command Center (Hero)
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
     * Section 6: Property Gallery
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
     * Section 7: Location & GPS
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
            
            <div class="afc-gps-row">
                <div class="afc-field">
                    <label class="afc-label">Latitude</label>
                    <input type="text" name="_gps_lat" value="<?php echo esc_attr( $lat ); ?>" class="afc-input" placeholder="0.000000">
                </div>
                <div class="afc-field">
                    <label class="afc-label">Longitude</label>
                    <input type="text" name="_gps_lng" value="<?php echo esc_attr( $lng ); ?>" class="afc-input" placeholder="0.000000">
                </div>
            </div>
            <p class="afc-help-text" style="margin-top: 15px;">Synchronized with the global luxury network geospatial database.</p>
        </div>
        <?php
    }

    /**
     * Section 8: Property Features
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
     * Section 9: Agent Branding
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
                        $u_phone = get_user_meta( $user->ID, C::USER_PHONE, true );
                        $u_photo = get_user_meta( $user->ID, C::USER_PHOTO, true );
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
                    <p style="text-align:center; font-size:10px; color:#94a3b8; margin-top:5px;">AGENT</p>
                </div>

                <!-- NEW: BROKERAGE LOGO -->
                <div class="afc-agent-photo-wrapper" style="margin-left: 10px;">
                    <?php 
                    $broker_logo = \AFCGlide\Core\Constants::get_meta( $post->ID, \AFCGlide\Core\Constants::META_BROKER_LOGO ); 
                    $broker_logo_url = $broker_logo ? wp_get_attachment_image_url( $broker_logo, 'thumbnail' ) : AFCG_URL . 'assets/images/placeholder.png';
                    ?>
                    <div class="afc-agent-preview" style="border-radius: 6px !important;">
                        <img src="<?php echo esc_url( $broker_logo_url ); ?>" id="broker-logo-img" alt="" style="object-fit: contain !important;">
                    </div>
                    <input type="hidden" name="_listing_broker_logo" id="_listing_broker_logo" value="<?php echo esc_attr( $broker_logo ); ?>">
                    <button type="button" class="button afcglide-upload-logo-btn">Brand Logo</button>
                    <p style="text-align:center; font-size:10px; color:#94a3b8; margin-top:5px;">BROKERAGE</p>
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
     * Section 10: Intelligence & Files
     */
    public static function render_intelligence_metabox( $post ) {
        $pdf_id  = C::get_meta( $post->ID, C::META_PDF_ID );
        $showing = C::get_meta( $post->ID, C::META_OPEN_HOUSE );
        $stats   = intval( C::get_meta( $post->ID, C::META_VIEWS ) );
        
        $pdf_name = $pdf_id ? basename( get_attached_file( $pdf_id ) ) : 'No document attached';
        ?>
        <div class="afc-metabox-content">
            
            <!-- Document Upload -->
            <div class="afc-field">
                <label class="afc-label">📄 Asset Brochure / Floorplan (PDF)</label>
                <div style="display: flex; align-items: center; gap: 15px; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0;">
                    <input type="hidden" name="_listing_pdf_id" id="_listing_pdf_id" value="<?php echo esc_attr( $pdf_id ); ?>">
                    <div style="flex: 1;">
                        <span id="pdf-filename" style="font-weight:700; color:#1e293b; display:block; margin-bottom:4px;"><?php echo esc_html( $pdf_name ); ?></span>
                        <span id="pdf-status" style="font-size:11px; color:#64748b;"><?php echo $pdf_id ? "ID: $pdf_id" : "Ready to upload"; ?></span>
                    </div>
                    <button type="button" class="button afc-pdf-upload-btn">Select PDF</button>
                </div>
            </div>

            <!-- Showing Schedule -->
            <div class="afc-field">
                <label class="afc-label">🗓️ Private Showing / Open House Schedule</label>
                <textarea name="_listing_showing_schedule" class="afc-input" rows="3" placeholder="e.g. Sunday 2 PM - 4 PM"><?php echo esc_textarea( $showing ); ?></textarea>
                <p class="afc-help-text">Visible to agents and logged-in users only.</p>
            </div>

            <!-- Stats -->
            <div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #f1f5f9;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <span style="font-size: 18px;">📈</span>
                    <div>
                        <span style="font-size: 11px; font-weight: 800; color: #94a3b8; text-transform: uppercase;">Asset Views</span>
                        <strong style="font-size: 16px; color: #10b981; display:block;"><?php echo number_format($stats); ?></strong>
                    </div>
                </div>
            </div>

        </div>
        <?php
    }

    /**
     * Section 11: Publish Control
     */
    public static function render_publish_metabox( $post ) {
        $status = $post->post_status;
        $statuses = [
            'publish' => '🟢 ACTIVE (Live on Market)',
            'pending' => '🟡 PENDING (Under Contract)',
            'sold'    => '🔴 SOLD (Transaction Closed)',
            'draft'   => '⚪ DRAFT (Private)',
        ];
        ?>
        <div class="afc-metabox-content">
            <div class="afc-field" style="margin-bottom: 30px;">
                <label class="afc-label">Current Market Status</label>
                <select name="_listing_market_status" class="afc-select" style="font-weight: 900; height: 50px; font-size: 14px;">
                    <?php foreach ( $statuses as $val => $label ) : ?>
                        <option value="<?php echo esc_attr($val); ?>" <?php selected($status, $val); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="afc-help-text">Update this when the property moves from Active to Pending or Sold.</p>
            </div>

            <div class="afc-publish-section" style="border-top: 1px solid #eee; padding-top: 25px;">
                <button type="submit" name="publish" id="publish" class="button button-primary button-large afc-publish-btn" style="height: 60px !important; font-size: 16px !important; width: 100%; background: #10b981 !important; color: #ffffff !important; border: none !important; font-weight: 800;">
                    🚀 BROADCAST GLOBAL UPDATES
                </button>
                <p style="text-align: center; font-size: 11px; color: #94a3b8; margin-top: 15px; font-weight: 700;">
                    Your updates will be synced across the global infrastructure immediately.
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Save logic (ENHANCED - Now saves Spanish + Property Type!)
     */
    public static function save_metaboxes( $post_id, $post ) {
        if ( ! isset( $_POST['afcglide_nonce'] ) ) return;
        
        if ( ! wp_verify_nonce( $_POST['afcglide_nonce'], C::NONCE_META ) ) return;

        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;

        // Meta Map (ENHANCED with new fields!)
        $meta_fields = [
            '_listing_intro_text'       => C::META_INTRO,
            '_listing_intro_es'         => C::META_INTRO_ES,      // NEW!
            '_listing_narrative_es'     => C::META_NARRATIVE_ES,  // NEW!
            '_listing_type'             => C::META_TYPE,          // NEW!
            '_agent_name_display'       => C::META_AGENT_NAME,
            '_listing_agent_phone'      => C::META_AGENT_PHONE,
            '_listing_agent_photo'      => C::META_AGENT_PHOTO,
            '_listing_broker_logo'      => C::META_BROKER_LOGO,   // NEW!
            '_listing_show_wa'          => C::META_SHOW_WA,
            '_listing_video_url'        => '_listing_video_url', // TODO: Constantize later
            '_listing_hero_id'          => C::META_HERO_ID,
            '_listing_price'            => C::META_PRICE,
            '_listing_beds'             => C::META_BEDS,
            '_listing_baths'            => C::META_BATHS,
            '_listing_sqft'             => C::META_SQFT,
            '_listing_address'          => C::META_ADDRESS,
            '_gps_lat'                  => C::META_GPS_LAT,
            '_gps_lng'                  => C::META_GPS_LNG,
            '_listing_pdf_id'           => C::META_PDF_ID,
            '_listing_showing_schedule' => C::META_OPEN_HOUSE,
            '_listing_narrative'        => C::META_NARRATIVE,
        ];

        foreach ( $meta_fields as $form_key => $meta_key ) {
            if ( isset( $_POST[$form_key] ) ) {
                // Use wp_kses_post for narrative fields to preserve HTML from wp_editor
                if ( $form_key === '_listing_narrative' || $form_key === '_listing_narrative_es' ) {
                    $value = wp_kses_post( $_POST[$form_key] );
                } else {
                    $value = sanitize_text_field( $_POST[$form_key] );
                }
                
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

        // Market Status Sync (WordPress Core Level)
        if ( isset( $_POST['_listing_market_status'] ) ) {
            $new_status = sanitize_text_field( $_POST['_listing_market_status'] );
            
            // Also save to META_STATUS for AJAX handler compatibility
            C::update_meta( $post_id, C::META_STATUS, $new_status );
            
            if ( $new_status !== $post->post_status ) {
                // Remove the action to avoid infinite loops
                remove_action( 'save_post', [ __CLASS__, 'save_metaboxes' ] );
                wp_update_post( [
                    'ID'          => $post_id,
                    'post_status' => $new_status
                ]);

                // If marked as SOLD, add a special flag for the notice
                if ( $new_status === 'sold' ) {
                    add_filter( 'redirect_post_location', function( $location ) {
                        return add_query_arg( 'afc_sold_success', '1', $location );
                    });
                }

                add_action( 'save_post', [ __CLASS__, 'save_metaboxes' ], 10, 2 );
            }
        }
    }

    /**
     * Admin Notices
     */
    public static function render_admin_notices() {
        global $pagenow, $post;
        
        if ( $pagenow === 'post.php' && isset( $_GET['message'] ) && $_GET['message'] == '6' && $post && get_post_type( $post ) === C::POST_TYPE ) {
            ?>
            <div class="notice notice-success is-dismissible afc-success-portal">
                <p>🚀 <strong>GLOBAL BROADCAST SUCCESSFUL:</strong> Listing is now live on the luxury network. <a href="<?php echo get_permalink( $post->ID ); ?>" target="_blank">View Live Asset &rarr;</a></p>
            </div>
            <?php
        }

        if ( isset( $_GET['afc_sold_success'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible afc-success-portal" style="border-left-color: #ef4444 !important; background: #fff1f2 !important;">
                <p>🎊 <strong>CONGRATULATIONS ON THE SALE!</strong> This asset has been successfully marked as <strong>SOLD</strong>. Career volume updated. 🥂</p>
            </div>
            <?php
        }
    }
}