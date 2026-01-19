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
        
        <!-- SECTION 1: PROPERTY DESCRIPTION -->
        <section class="afc-form-section" id="section-1">
            <h3><span class="step-num">1</span> Property Description</h3>
            <div class="afc-field full">
                <label>Property Story & Narrative</label>
                <textarea name="listing_description" rows="6" placeholder="Craft a compelling story for this luxury asset..."><?php echo esc_textarea($defaults['description']); ?></textarea>
            </div>
        </section>

        <!-- SECTION 2: PROPERTY ESSENTIALS -->
        <section class="afc-form-section" id="section-2">
            <h3><span class="step-num">2</span> Property Essentials</h3>
            <div class="afc-grid">
                <div class="afc-field full">
                    <label>Internal Asset Title</label>
                    <input type="text" name="listing_title" value="<?php echo esc_attr($defaults['title']); ?>" placeholder="e.g. Villa Aman" required>
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
        <section class="afc-form-section" id="section-5-v2">
            <h3><span class="step-num">5</span> Geospatial Identity</h3>
            <div class="afc-field full" style="margin-bottom: 25px;">
                <label>📍 Primary Physical Address</label>
                <input type="text" name="listing_address" value="<?php echo esc_attr($defaults['address']); ?>" placeholder="Enter the full street address..." style="background: #fff; border: 2px solid #e2e8f0;">
            </div>

            <div class="geospatial-coords-wrapper" style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0;">
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
                <p class="afc-help-text" style="margin-top: 15px;">Used for synchronized mapping across the global luxury asset network.</p>
            </div>
        </section>

        </fieldset>

        <button type="submit" id="afc-submit-btn" class="afc-main-btn" <?php echo $is_locked ? 'disabled' : ''; ?>>
            <?php echo $is_locked ? '🔒 SUBMISSION LOCKED' : ($post_id ? 'SYNC ASSET UPDATES' : 'PUBLISH GLOBAL LISTING'); ?>
        </button>
        
        <div id="afc-feedback"></div>
    </form>
</div>

<style>
/* Base Styles */
.afc-lockdown-banner { 
    background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    border: 2px solid #dc2626; border-radius: 12px; padding: 20px 30px;
    margin-bottom: 30px; display: flex; align-items: center; gap: 20px;
}
.afc-lockdown-banner h3 { margin: 0 0 8px 0; color: #7f1d1d; font-size: 18px; font-weight: 800; }
.afc-lockdown-banner p { margin: 0; color: #991b1b; font-size: 14px; }

.afc-form-header { 
    text-align: center; margin-bottom: 30px; padding: 30px;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-radius: 16px;
}
.afc-form-header h2 { margin: 0 0 10px 0; font-size: 28px; font-weight: 800; color: #0c4a6e; }
.afc-form-header p { margin: 0; color: #475569; font-size: 14px; }

.afc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.afc-field.full { grid-column: span 2; }
.afc-field label { 
    display: block; margin-bottom: 8px; font-weight: 700; 
    color: #1e293b; font-size: 13px; letter-spacing: 0.3px;
}
.afc-field input, .afc-field textarea {
    width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0;
    border-radius: 8px; font-size: 14px; transition: border-color 0.2s;
}
.afc-field input:focus, .afc-field textarea:focus {
    border-color: #6366f1; outline: none;
}

.afc-help-text { 
    font-size: 12px; color: #64748b; margin-top: 8px; font-style: italic; 
}

.specs-mini-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }

.afc-form-section { 
    background: white; padding: 30px; border-radius: 16px; 
    margin-bottom: 25px; border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.afc-form-section h3 {
    margin: 0 0 20px 0; font-size: 16px; font-weight: 800;
    color: #0f172a; display: flex; align-items: center; gap: 12px;
}
.step-num {
    display: inline-flex; align-items: center; justify-content: center;
    width: 28px; height: 28px; background: transparent; color: #6366f1;
    border: 2px solid #6366f1; border-radius: 50%; font-size: 13px; font-weight: 800;
}

.geospatial-coords label {
    display: block; margin-bottom: 12px; font-weight: 700; 
    color: #475569; font-size: 12px; text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Hero Upload */
.hero-preview-box { 
    width: 100%; aspect-ratio: 16/9; background: #f1f5f9; 
    border-radius: 12px; border: 2px dashed #cbd5e1; 
    display: flex; align-items: center; justify-content: center; 
    cursor: pointer; overflow: hidden; transition: all 0.3s;
}
.hero-preview-box:hover { border-color: #6366f1; background: #e0e7ff; }
#hero-placeholder { color: #94a3b8; font-weight: 800; font-size: 12px; }

/* Gallery Upload */
.gallery-upload-zone {
    width: 100%; padding: 40px; background: #fafafa;
    border: 2px dashed #cbd5e1; border-radius: 12px;
    text-align: center; cursor: pointer; transition: all 0.3s;
}
.gallery-upload-zone:hover { border-color: #6366f1; background: #f0f4ff; }
.upload-icon { font-size: 48px; margin-bottom: 10px; }
.gallery-upload-zone p { margin: 5px 0; font-weight: 700; color: #1e293b; }
.gallery-upload-zone small { color: #64748b; }

.existing-gallery-preview, .new-gallery-preview {
    margin-top: 20px; padding: 20px; background: #f8fafc;
    border-radius: 8px; border: 1px solid #e2e8f0;
}
.existing-gallery-preview h4, .new-gallery-preview h4 {
    margin: 0 0 15px 0; font-size: 13px; font-weight: 800;
    color: #475569; text-transform: uppercase; letter-spacing: 0.5px;
}
.gallery-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr));
    gap: 10px;
}
.gallery-thumb, .new-gallery-thumb {
    aspect-ratio: 1; border-radius: 8px; overflow: hidden;
    border: 2px solid #e2e8f0; background: white;
}
.gallery-thumb img, .new-gallery-thumb img {
    width: 100%; height: 100%; object-fit: cover;
}

/* Amenities */
.amenities-container { 
    display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); 
    gap: 12px; 
}
.afc-checkbox-item { 
    display: flex; align-items: center; gap: 8px; 
    background: #f8fafc; padding: 10px; border-radius: 8px; cursor: pointer;
    transition: all 0.2s;
}
.afc-checkbox-item:hover { background: #e0e7ff; }
.afc-checkbox-item .amenity-icon { font-size: 16px; margin-right: 4px; }
.afc-checkbox-item span { font-size: 11px; font-weight: 600; }

/* Status Toggle */
.afc-status-toggle { display: flex; gap: 10px; margin-top: 10px; }
.status-opt { flex: 1; cursor: pointer; }
.status-opt input { display: none; }
.status-label { 
    display: block; text-align: center; padding: 12px; border-radius: 8px; 
    background: #f1f5f9; color: #64748b; font-weight: 800; font-size: 11px;
    transition: all 0.2s ease; border: 2px solid transparent;
}
.status-opt input:checked + .status-label.active { 
    background: #eef2ff; color: #6366f1; border-color: #6366f1; 
}
.status-opt input:checked + .status-label.pending { 
    background: #fffbeb; color: #d97706; border-color: #d97706; 
}
.status-opt input:checked + .status-label.sold { 
    background: #f0fdf4; color: #16a34a; border-color: #16a34a; 
}

/* Submit Button */
.afc-main-btn { 
    width: 100%; padding: 20px; background: #bbf7d0; color: #166534; 
    border: none; border-radius: 12px; font-weight: 800; font-size: 15px;
    cursor: pointer; margin-top: 10px; transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(22, 101, 52, 0.1);
}
.afc-main-btn:hover:not(:disabled) { 
    background: #86efac; transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(22, 101, 52, 0.2);
    color: #064e3b;
}
.afc-main-btn:disabled { opacity: 0.5; cursor: not-allowed; box-shadow: none; }

/* Feedback */
#afc-feedback { 
    margin-top: 20px; text-align: center; font-weight: 700; 
    padding: 15px; border-radius: 8px;
}
#afc-feedback.success { background: #f0fdf4; color: #16a34a; }
#afc-feedback.error { background: #fef2f2; color: #dc2626; }

fieldset { border: none; padding: 0; margin: 0; }
fieldset:disabled { opacity: 0.6; pointer-events: none; }
</style>

<script>
jQuery(document).ready(function($) {
    
    // Hero Image Preview
    $('#hero_file').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#hero-preview').attr('src', e.target.result).show();
                $('#hero-placeholder').hide();
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Gallery Images Preview
    $('#gallery_files').on('change', function(e) {
        const files = e.target.files;
        if (files.length === 0) return;
        
        $('#new-gallery-preview').show();
        const $grid = $('#new-gallery-grid');
        $grid.empty();
        
        for (let i = 0; i < files.length && i < <?php echo C::MAX_GALLERY; ?>; i++) {
            const file = files[i];
            const reader = new FileReader();
            
            reader.onload = function(e) {
                const $thumb = $('<div class="new-gallery-thumb"><img src="' + e.target.result + '"></div>');
                $grid.append($thumb);
            }
            
            reader.readAsDataURL(file);
        }
        
        if (files.length > <?php echo C::MAX_GALLERY; ?>) {
            alert('Maximum <?php echo C::MAX_GALLERY; ?> images allowed. Only the first <?php echo C::MAX_GALLERY; ?> will be uploaded.');
        }
    });
    
    // Form Submission
    $('#afcglide-front-submission').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#afc-submit-btn');
        const $feedback = $('#afc-feedback');
        const originalText = $btn.text();
        
        // Disable button
        $btn.prop('disabled', true).text('⚡ PROCESSING...');
        $feedback.removeClass('success error').text('');
        
        // Create FormData
        const formData = new FormData(this);
        
        // AJAX Request
        $.ajax({
            url: '<?php echo admin_url('admin-ajax.php'); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $feedback.addClass('success').text(response.data.message);
                    $btn.text('✅ SUCCESS!');
                    
                    // Redirect after 1.5 seconds
                    setTimeout(function() {
                        if (response.data.url) {
                            window.location.href = response.data.url;
                        } else {
                            location.reload();
                        }
                    }, 1500);
                } else {
                    $feedback.addClass('error').text(response.data.message || 'Submission failed. Please try again.');
                    $btn.prop('disabled', false).text(originalText);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                $feedback.addClass('error').text('🔥 Network error. Check console and try again.');
                $btn.prop('disabled', false).text(originalText);
            }
        });
    });
});
</script>