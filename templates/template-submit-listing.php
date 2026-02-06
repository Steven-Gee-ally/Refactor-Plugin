<?php
/**
 * AFCGlide - Professional Agent Submission Form
 * Version 4.0.0 - Production Ready
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use AFCGlide\Core\Constants as C;

// Check if Global Lockdown is active
$is_locked = C::get_option( C::OPT_GLOBAL_LOCKDOWN ) === '1' && ! current_user_can( C::CAP_MANAGE );

// DATA RECALL LOGIC
$post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
$defaults = [
    'title' => '', 'price' => '', 'beds' => '', 'baths' => '', 
    'sqft' => '', 'address' => '', 'status' => 'active', 'description' => '',
    'gps_lat' => '', 'gps_lng' => ''
];

if ( $post_id > 0 ) {
    $post = get_post($post_id);
    if ( $post && ($post->post_author == get_current_user_id() || current_user_can( C::CAP_MANAGE )) ) {
        $defaults['title']       = $post->post_title;
        $defaults['description'] = $post->post_content;
        $defaults['price']       = C::get_meta($post_id, C::META_PRICE);
        $defaults['beds']        = C::get_meta($post_id, C::META_BEDS);
        $defaults['baths']       = C::get_meta($post_id, C::META_BATHS);
        $defaults['sqft']        = C::get_meta($post_id, C::META_SQFT);
        $defaults['address']     = C::get_meta($post_id, C::META_ADDRESS);
        $defaults['status']      = C::get_meta($post_id, C::META_STATUS) ?: 'active';
        $defaults['gps_lat']     = C::get_meta($post_id, C::META_GPS_LAT);
        $defaults['gps_lng']     = C::get_meta($post_id, C::META_GPS_LNG);
    }
}

$current_user = wp_get_current_user();
$existing_gallery = $post_id ? C::get_meta($post_id, C::META_GALLERY_IDS) : [];
$existing_amenities = $post_id ? (array) C::get_meta($post_id, C::META_AMENITIES) : [];
?>

<div id="afcglide-submission-root">
    
    <?php if ($is_locked) : ?>
    <div class="afc-lockdown-banner">
        <div style="font-size: 36px;">🔒</div>
        <div>
            <h3>GLOBAL LOCKDOWN ACTIVE</h3>
            <p>All listing submissions are currently frozen. This form is in read-only mode. Contact your Lead Broker to lift the lockdown.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="afc-form-header">
        <h2>🚀 <?php echo $post_id ? 'Update Global Asset' : 'Launch Global Asset'; ?></h2>
        <p>Initializing secure protocol for: <strong><?php echo esc_html($current_user->display_name); ?></strong></p>
    </div>

    <form id="afcglide-front-submission" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo C::AJAX_SUBMIT; ?>">
        <input type="hidden" name="security" value="<?php echo wp_create_nonce( C::NONCE_AJAX ); ?>">
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

        <fieldset <?php echo $is_locked ? 'disabled' : ''; ?>>
        
        <!-- SECTION 1: PROPERTY NARRATIVE -->
        <section class="afc-form-section" id="section-1">
            <h3><span class="step-num">1</span> Property Narrative</h3>
            
            <div style="background: #eff6ff; padding: 25px; border-radius: 12px; border-left: 5px solid #3b82f6; margin-bottom: 25px;">
                <h4 style="margin: 0 0 15px 0; color: #1e40af; font-size: 14px; font-weight: 800;">🇺🇸 ENGLISH VERSION</h4>
                <div class="afc-field full" style="margin-bottom: 20px;">
                    <label>Public Asset Headline (English)</label>
                    <input type="text" name="listing_headline" value="<?php echo esc_attr(C::get_meta($post_id, C::META_INTRO)); ?>" placeholder="e.g. Stunning Oceanfront Estate with Private Cove">
                </div>
                <div class="afc-field full">
                    <label>Full Narrative Story (English)</label>
                    <textarea name="listing_description" rows="6" placeholder="Craft a compelling story for this luxury asset..."><?php echo esc_textarea($defaults['description']); ?></textarea>
                </div>
            </div>

            <div style="background: #fff7ed; padding: 25px; border-radius: 12px; border-left: 5px solid #f97316;">
                <h4 style="margin: 0 0 15px 0; color: #9a3412; font-size: 14px; font-weight: 800;">🇪🇸 SPANISH VERSION (Optional)</h4>
                <div class="afc-field full" style="margin-bottom: 20px;">
                    <label>Headline en Español</label>
                    <input type="text" name="listing_headline_es" value="<?php echo esc_attr(C::get_meta($post_id, C::META_INTRO_ES)); ?>" placeholder="e.g. Villa Impresionante frente al Mar">
                </div>
                <div class="afc-field full">
                    <label>Historia Narrativa en Español</label>
                    <textarea name="listing_description_es" rows="6" placeholder="Escriba la historia de esta propiedad en español..."><?php echo esc_textarea(C::get_meta($post_id, C::META_NARRATIVE_ES)); ?></textarea>
                </div>
            </div>
        </section>

        <!-- SECTION 2: PROPERTY ESSENTIALS -->
        <section class="afc-form-section" id="section-2">
            <h3><span class="step-num">2</span> Property Essentials</h3>
            <div class="afc-grid">
                <div class="afc-field">
                    <label>Internal Asset Title</label>
                    <input type="text" name="listing_title" value="<?php echo esc_attr($defaults['title']); ?>" placeholder="e.g. Villa Aman" required>
                </div>

                <div class="afc-field">
                    <label>Property Type</label>
                    <?php 
                    $property_type = C::get_meta($post_id, C::META_TYPE);
                    $types = [
                        'residential' => '🏡 Residential',
                        'commercial'  => '🏢 Commercial',
                        'land'        => '🌳 Land',
                        'condo'       => '🏘️ Condo',
                        'vacation'    => '🏖️ Vacation'
                    ];
                    ?>
                    <select name="listing_type" class="afc-select" style="height: 50px; font-weight: 700; width: 100%; border: 2px solid #e2e8f0; border-radius: 8px;">
                        <option value="">Select Type</option>
                        <?php foreach ( $types as $val => $label ) : ?>
                            <option value="<?php echo $val; ?>" <?php selected($property_type, $val); ?>><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="afc-field full">
                    <label>Market Status</label>
                    <div class="afc-status-toggle">
                        <label class="status-opt">
                            <input type="radio" name="listing_status" value="active" <?php checked($defaults['status'], 'active'); ?>>
                            <span class="status-label active">ACTIVE</span>
                        </label>
                        <label class="status-opt">
                            <input type="radio" name="listing_status" value="pending" <?php checked($defaults['status'], 'pending'); ?>>
                            <span class="status-label pending">PENDING</span>
                        </label>
                        <label class="status-opt">
                            <input type="radio" name="listing_status" value="sold" <?php checked($defaults['status'], 'sold'); ?>>
                            <span class="status-label sold">SOLD</span>
                        </label>
                    </div>
                </div>

                <div class="afc-field">
                    <label>Target Price (USD)</label>
                    <input type="number" name="listing_price" value="<?php echo esc_attr($defaults['price']); ?>" placeholder="0.00" step="0.01" required>
                </div>
                
                <div class="afc-field">
                    <label>Vital Specs</label>
                    <div class="specs-mini-grid">
                        <input type="number" name="listing_beds" value="<?php echo esc_attr($defaults['beds']); ?>" placeholder="Beds">
                        <input type="number" name="listing_baths" value="<?php echo esc_attr($defaults['baths']); ?>" placeholder="Baths" step="0.5">
                        <input type="number" name="listing_sqft" value="<?php echo esc_attr($defaults['sqft']); ?>" placeholder="Sq Ft">
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION 3: AMENITIES -->
        <section class="afc-form-section" id="section-3">
            <h3><span class="step-num">3</span> Curated Lifestyle</h3>
            <div class="amenities-container">
                <?php 
                $amenity_options = [
                    'Gourmet Kitchen' => '🍳', 'Infinity Pool' => '🌊', 'Ocean View' => '🌅', 'Wine Cellar' => '🍷',
                    'Private Gym' => '🏋️', 'Smart Home Tech' => '📱', 'Outdoor Cinema' => '🎬', 'Helipad Access' => '🚁',
                    'Gated Community' => '🏰', 'Guest House' => '🏠', 'Solar Power' => '☀️', 'Beach Front' => '🏖️',
                    'Spa / Sauna' => '🧖', '3+ Car Garage' => '🚗', 'Luxury Fire Pit' => '🔥', 'Concierge Service' => '🛎️',
                    'Walk-in Closet' => '👗', 'High Ceilings' => '⤴️', 'Staff Quarters' => '👨‍💼', 'Backup Generator' => '⚡'
                ];
                foreach ( $amenity_options as $amenity => $icon ) : 
                    $checked = in_array($amenity, $existing_amenities) ? 'checked' : '';
                ?>
                    <label class="afc-checkbox-item">
                        <input type="checkbox" name="listing_amenities[]" value="<?php echo esc_attr($amenity); ?>" <?php echo $checked; ?>>
                        <span class="amenity-icon"><?php echo $icon; ?></span>
                        <span><?php echo esc_html($amenity); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- SECTION 4: VISUAL COMMAND CENTER -->
        <section class="afc-form-section" id="section-4">
            <h3><span class="step-num">4</span> Visual Command Center</h3>
            
            <div class="afc-grid">
                <!-- Hero Image -->
                <div class="afc-field full">
                    <label>Main Hero Asset (Required)</label>
                    <div class="hero-preview-box" onclick="<?php echo $is_locked ? '' : 'document.getElementById(\'hero_file\').click();'; ?>">
                        <?php if (has_post_thumbnail($post_id)) : ?>
                            <?php echo get_the_post_thumbnail($post_id, 'large', ['id' => 'hero-preview', 'style' => 'width:100%; height:100%; object-fit:cover;']); ?>
                        <?php else : ?>
                            <span id="hero-placeholder">+ SET HERO PHOTO</span>
                            <img id="hero-preview" style="display:none; width:100%; height:100%; object-fit:cover;">
                        <?php endif; ?>
                    </div>
                    <input type="file" id="hero_file" name="hero_file" style="display:none" accept="image/*">
                    <p class="afc-help-text">Click to upload. Minimum width: <?php echo C::MIN_IMAGE_WIDTH; ?>px</p>
                </div>

                <!-- PDF Brochure -->
                <div class="afc-field full" style="margin-top: 20px;">
                    <label>Asset Brochure / Floorplan (Optional PDF)</label>
                    <div style="background: #f8fafc; border: 2px dashed #cbd5e1; padding: 20px; border-radius: 12px; text-align: center; cursor: pointer;" onclick="document.getElementById('pdf_file').click();">
                        <span id="pdf-status" style="font-size: 24px; display: block; margin-bottom: 8px;">📄</span>
                        <span id="pdf-filename" style="font-weight: 700; color: #475569;">
                            <?php 
                            $pdf_id = C::get_meta($post_id, C::META_PDF_ID);
                            echo $pdf_id ? basename(get_attached_file($pdf_id)) : 'Click to upload floorplan or brochure (PDF)';
                            ?>
                        </span>
                    </div>
                    <input type="file" id="pdf_file" name="pdf_file" style="display:none" accept="application/pdf">
                </div>
            </div>
            
            <!-- Gallery Images -->
            <div class="afc-field full" style="margin-top: 30px;">
                <label>Property Gallery (Optional - Max <?php echo C::MAX_GALLERY; ?> photos)</label>
                <div class="gallery-upload-zone" onclick="<?php echo $is_locked ? '' : 'document.getElementById(\'gallery_files\').click();'; ?>">
                    <div class="upload-icon">📸</div>
                    <p>Click to upload gallery images</p>
                    <small>Select multiple files at once</small>
                </div>
                <input type="file" id="gallery_files" name="gallery_files[]" style="display:none" accept="image/*" multiple>
                
                <?php if (!empty($existing_gallery) && is_array($existing_gallery)) : ?>
                <div class="existing-gallery-preview">
                    <h4>Current Gallery (<?php echo count($existing_gallery); ?> photos)</h4>
                    <div class="gallery-grid">
                        <?php foreach ($existing_gallery as $img_id) : ?>
                            <div class="gallery-thumb">
                                <?php echo wp_get_attachment_image($img_id, 'thumbnail'); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <div id="new-gallery-preview" class="new-gallery-preview" style="display:none;">
                    <h4>New Uploads</h4>
                    <div class="gallery-grid" id="new-gallery-grid"></div>
                </div>
            </div>
        </section>

        <!-- SECTION 5: PROPERTY LOCATION & GPS -->
        <section class="afc-form-section" id="section-5">
            <h3><span class="step-num">5</span> Geospatial Identity</h3>
            <div class="afc-field full" style="margin-bottom: 25px;">
                <label>📍 Primary Physical Address</label>
                <input type="text" name="listing_address" value="<?php echo esc_attr($defaults['address']); ?>" placeholder="Enter the full street address..." style="background: #fff; border: 2px solid #e2e8f0;">
            </div>

            <div class="geospatial-coords-wrapper" style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 25px;">
                <label style="display: block; margin-bottom: 15px; font-weight: 800; color: #475569; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">📡 Precision Network Coordinates</label>
                <div class="afc-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                    <div class="afc-field">
                        <label style="display: block; font-size: 10px; color: #64748b;">LATITUDE</label>
                        <input type="text" name="gps_lat" value="<?php echo esc_attr($defaults['gps_lat']); ?>" placeholder="0.0000" style="width: 100%; font-family: monospace; background: #fff; border: 2px solid #e2e8f0;">
                    </div>
                    <div class="afc-field">
                        <label style="display: block; font-size: 10px; color: #64748b;">LONGITUDE</label>
                        <input type="text" name="gps_lng" value="<?php echo esc_attr($defaults['gps_lng']); ?>" placeholder="0.0000" style="width: 100%; font-family: monospace; background: #fff; border: 2px solid #e2e8f0;">
                    </div>
                </div>
            </div>

            <div class="afc-field full">
                <label>🗓️ Showing / Open House Schedule</label>
                <textarea name="listing_showing" rows="2" placeholder="e.g. By appointment only / Sunday 2-4 PM"><?php echo esc_textarea(C::get_meta($post_id, C::META_OPEN_HOUSE)); ?></textarea>
            </div>
        </section>

        <!-- SECTION 6: AGENT BRANDING (CENTER ALIGNED) -->
        <section class="afc-form-section" id="section-6">
            <h3><span class="step-num">6</span> Agent Branding</h3>
            
            <div style="display: flex; align-items: center; justify-content: center; gap: 40px; padding: 30px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                
                <!-- Agent Photo (Center-Left) -->
                <div style="flex: 0 0 auto;">
                    <div class="afc-field">
                        <label style="display: block; text-align: center; margin-bottom: 10px; font-size: 10px; color: #64748b; font-weight: 800;">AGENT PHOTO</label>
                        <?php 
                        $agent_photo_id = C::get_meta($post_id, C::META_AGENT_PHOTO);
                        $photo_url = $agent_photo_id ? wp_get_attachment_image_url($agent_photo_id, 'thumbnail') : '';
                        ?>
                        <div class="agent-photo-preview" onclick="<?php echo $is_locked ? '' : 'document.getElementById(\'agent_photo_file\').click();'; ?>" style="width: 120px; height: 120px; border-radius: 50%; overflow: hidden; border: 3px solid #e2e8f0; cursor: pointer; background: #fff; display: flex; align-items: center; justify-content: center;">
                            <?php if ($photo_url) : ?>
                                <img src="<?php echo $photo_url; ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else : ?>
                                <span style="font-size: 48px; color: #cbd5e1;">👤</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="agent_photo_file" name="agent_photo_file" style="display:none" accept="image/*">
                    </div>
                </div>

                <!-- Agent Info (Center) -->
                <div style="flex: 1; text-align: center;">
                    <div class="afc-field" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-size: 10px; color: #64748b; font-weight: 800;">AGENT NAME</label>
                        <input type="text" name="agent_name" value="<?php echo esc_attr(C::get_meta($post_id, C::META_AGENT_NAME) ?: $current_user->display_name); ?>" placeholder="Your Name" style="width: 100%; max-width: 300px; text-align: center; margin: 0 auto; background: #fff; border: 2px solid #e2e8f0; font-weight: 600; padding: 12px; border-radius: 8px;">
                    </div>
                    <div class="afc-field" style="margin-bottom: 20px;">
                        <label style="display: block; margin-bottom: 8px; font-size: 10px; color: #64748b; font-weight: 800;">DIRECT CONTACT</label>
                        <input type="tel" name="agent_phone" value="<?php echo esc_attr(C::get_meta($post_id, C::META_AGENT_PHONE)); ?>" placeholder="+506 1234-5678" style="width: 100%; max-width: 300px; text-align: center; margin: 0 auto; background: #fff; border: 2px solid #e2e8f0; font-family: monospace; padding: 12px; border-radius: 8px;">
                    </div>
                    <div class="afc-field">
                        <label style="display: block; margin-bottom: 8px; font-size: 10px; color: #64748b; font-weight: 800;">WHATSAPP (FLOATING BUTTON)</label>
                        <input type="tel" name="agent_whatsapp" value="<?php echo esc_attr(C::get_meta($post_id, C::META_AGENT_WHATSAPP)); ?>" placeholder="+506 8765-4321" style="width: 100%; max-width: 300px; text-align: center; margin: 0 auto; background: #fff; border: 2px solid #e2e8f0; font-family: monospace; padding: 12px; border-radius: 8px;">
                    </div>
                </div>

                <!-- Brokerage Logo (Center-Right) -->
                <div style="flex: 0 0 auto;">
                    <div class="afc-field">
                        <label style="display: block; text-align: center; margin-bottom: 10px; font-size: 10px; color: #64748b; font-weight: 800;">BROKER LOGO</label>
                        <?php 
                        $logo_id = C::get_meta($post_id, C::META_BROKER_LOGO);
                        $logo_url = $logo_id ? wp_get_attachment_image_url($logo_id, 'thumbnail') : '';
                        ?>
                        <div class="broker-logo-preview" onclick="<?php echo $is_locked ? '' : 'document.getElementById(\'broker_logo_file\').click();'; ?>" style="width: 120px; height: 120px; border-radius: 8px; overflow: hidden; border: 2px dashed #cbd5e1; cursor: pointer; background: #fff; display: flex; align-items: center; justify-content: center;">
                            <?php if ($logo_url) : ?>
                                <img src="<?php echo $logo_url; ?>" style="max-width: 100%; max-height: 100%; object-fit: contain;">
                            <?php else : ?>
                                <span style="font-size: 14px; color: #94a3b8; font-weight: 700;">🏢 LOGO</span>
                            <?php endif; ?>
                        </div>
                        <input type="file" id="broker_logo_file" name="broker_logo_file" style="display:none" accept="image/*">
                    </div>
                </div>

            </div>
        </section>

        </fieldset>

        <button type="submit" id="afc-submit-btn" class="afc-main-btn" <?php echo $is_locked ? 'disabled' : ''; ?>>
            <?php echo $is_locked ? '🔒 SUBMISSION LOCKED' : ($post_id ? 'SYNC ASSET UPDATES' : 'PUBLISH GLOBAL LISTING'); ?>
        </button>
        
        <div id="afc-feedback"></div>
    </form>
</div>