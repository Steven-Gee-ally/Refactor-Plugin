<?php
/**
 * AFCGlide - Professional Agent Submission Form
 * Version 4.3.1 - Master Production Edition
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. Clean Protocol - Vanish Astra/WP Junk
remove_all_actions( 'admin_notices' );
remove_all_actions( 'all_admin_notices' );
remove_action( 'wp_footer', 'astra_theme_background_updater_info' ); 
show_admin_bar( false );

use AFCGlide\Core\Constants as C;

// --- LOGIC ---
$is_locked = C::get_option( C::OPT_GLOBAL_LOCKDOWN ) === '1' && ! current_user_can( C::CAP_MANAGE );
$post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
$defaults = [
    'title' => '', 'price' => '', 'beds' => '', 'baths' => '', 
    'sqft' => '', 'address' => '', 'status' => 'active', 'description' => '',
    'gps_lat' => '', 'gps_lng' => '',
    'intro_es' => '', 'narrative_es' => ''
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
        $defaults['intro_es']      = C::get_meta($post_id, C::META_INTRO_ES);
        $defaults['narrative_es']  = C::get_meta($post_id, C::META_NARRATIVE_ES);
    }
}

$current_user = wp_get_current_user();
$existing_gallery = $post_id ? C::get_meta($post_id, C::META_GALLERY_IDS) : [];
$existing_amenities = $post_id ? (array) C::get_meta($post_id, C::META_AMENITIES) : [];
?>

<style>
    .notice, .updated, .error, .astra-notice, #wpadminbar { display: none !important; }
    html { margin-top: 0 !important; }
    #afcglide-submission-root { max-width: 1000px; margin: 0 auto; padding: 40px 20px; font-family: 'Inter', sans-serif; background: #fcfcfc; }
    .afc-form-section { background: #fff; border-radius: 12px; padding: 30px; margin-bottom: 30px; border: 1px solid #eef2f6; box-shadow: 0 4px 15px rgba(0,0,0,0.03); }
    .afc-field label { display: block; font-weight: 700; color: #1e293b; margin-bottom: 8px; text-transform: uppercase; font-size: 11px; letter-spacing: 1px; }
    .specs-mini-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; }
    .amenities-container { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; }
    .afc-checkbox-item { display: flex; align-items: center; background: #f8fafc; padding: 10px; border-radius: 8px; cursor: pointer; border: 1px solid transparent; transition: 0.2s; }
    .afc-checkbox-item:hover { border-color: #3b82f6; background: #eff6ff; }
    .hero-preview-box { width: 100%; height: 350px; background: #f1f5f9; border: 2px dashed #cbd5e1; border-radius: 12px; display: flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden; position: relative; }
    .afc-status-toggle { display: flex; gap: 10px; }
    .status-label { padding: 10px 20px; border-radius: 8px; font-weight: 800; font-size: 12px; cursor: pointer; border: 2px solid #e2e8f0; color: #94a3b8; transition: 0.2s; }
    input[type="radio"]:checked + .status-label.active { color: #10b981; background: #ecfdf5; border-color: #10b981; }
    input[type="radio"]:checked + .status-label.pending { color: #f59e0b; background: #fffbeb; border-color: #f59e0b; }
    input[type="radio"]:checked + .status-label.sold { color: #ef4444; background: #fef2f2; border-color: #ef4444; }
    .afc-main-btn { width: 100%; padding: 22px; background: #0f172a; color: #fff; border: none; border-radius: 12px; font-weight: 800; font-size: 18px; cursor: pointer; transition: 0.3s; letter-spacing: 1px; }
    .afc-main-btn:hover:not(:disabled) { background: #1e293b; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .afc-main-btn:disabled { opacity: 0.5; cursor: not-allowed; }
</style>

<div id="afcglide-submission-root">
    
    <?php if ($is_locked) : ?>
    <div class="afc-lockdown-banner" style="background: #fef2f2; border: 2px solid #ef4444; padding: 25px; border-radius: 12px; display: flex; gap: 20px; align-items: center; margin-bottom: 40px;">
        <span style="font-size: 40px;">🔒</span>
        <div>
            <h3 style="margin: 0; color: #991b1b; font-weight: 900;">GLOBAL LOCKDOWN ACTIVE</h3>
            <p style="margin: 5px 0 0; color: #b91c1c;">Network security protocol engaged. Submission services are temporarily suspended.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="afc-form-header" style="margin-bottom: 50px; text-align: center;">
        <h2 style="font-size: 32px; font-weight: 900; color: #0f172a; margin-bottom: 10px;">🚀 <?php echo $post_id ? 'Update Asset' : 'New Listing'; ?></h2>
        <div style="display: inline-block; padding: 6px 15px; background: #f1f5f9; border-radius: 20px; color: #64748b; font-size: 13px; font-weight: 600;">
            Secure Terminal: <?php echo esc_html($current_user->display_name); ?>
        </div>
    </div>

    <form id="afcglide-front-submission" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo C::AJAX_SUBMIT; ?>">
        <input type="hidden" name="security" value="<?php echo wp_create_nonce( C::NONCE_AJAX ); ?>">
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

        <fieldset <?php echo $is_locked ? 'disabled' : ''; ?> style="border: none; padding: 0; margin: 0;">
        
            <section class="afc-form-section">
                <h3><span style="color: #3b82f6;">1</span> Property Narrative</h3>
                
                <div class="afc-field" style="margin-bottom: 25px;">
                    <label>🇺🇸 Asset Title (English)</label>
                    <input type="text" name="listing_title" value="<?php echo esc_attr($defaults['title']); ?>" placeholder="e.g. The Sapphire Estate" required style="width: 100%; border: 2px solid #e2e8f0; border-radius: 8px; padding: 12px; font-size: 16px;">
                </div>

                <div style="background: #f8fafc; padding: 20px; border-radius: 10px; border-left: 4px solid #10b981; margin-bottom: 25px;">
                    <div class="afc-field">
                        <label>🇨🇷 Título del Activo (Español)</label>
                        <input type="text" name="listing_intro_es" value="<?php echo esc_attr($defaults['intro_es']); ?>" placeholder="ej. La Finca Zafiro" style="width: 100%; border: 2px solid #cbd5e1; border-radius: 8px; padding: 12px; font-size: 16px;">
                    </div>
                </div>
                
                <div class="afc-field" style="margin-bottom: 25px;">
                    <label>🇺🇸 Marketing Story (English)</label>
                    <textarea name="listing_description" rows="5" placeholder="Describe the lifestyle in English..." style="width: 100%; border: 2px solid #e2e8f0; border-radius: 8px; padding: 15px; font-size: 15px;"><?php echo esc_textarea($defaults['description']); ?></textarea>
                </div>

                <div style="background: #f8fafc; padding: 20px; border-radius: 10px; border-left: 4px solid #3b82f6;">
                    <div class="afc-field">
                        <label>🇨🇷 Descripción de la Propiedad (Español)</label>
                        <textarea name="listing_narrative_es" rows="5" placeholder="Describa el estilo de vida en español..." style="width: 100%; border: 2px solid #cbd5e1; border-radius: 8px; padding: 15px; font-size: 15px;"><?php echo esc_textarea($defaults['narrative_es']); ?></textarea>
                    </div>
                </div>
            </section>

            <section class="afc-form-section">
                <h3><span style="color: #3b82f6;">2</span> Core Metrics</h3>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                    <div class="afc-field">
                        <label>Current Status</label>
                        <div class="afc-status-toggle">
                            <?php foreach (['active' => 'ACTIVE', 'pending' => 'PENDING', 'sold' => 'SOLD'] as $val => $lab) : ?>
                            <label>
                                <input type="radio" name="listing_status" value="<?php echo $val; ?>" <?php checked($defaults['status'], $val); ?> style="display:none;">
                                <span class="status-label <?php echo $val; ?>"><?php echo $lab; ?></span>
                            </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="afc-field">
                        <label>Listing Price (USD)</label>
                        <input type="number" name="listing_price" value="<?php echo esc_attr($defaults['price']); ?>" placeholder="0.00" step="0.01" required style="width: 100%; border: 2px solid #e2e8f0; border-radius: 8px; padding: 12px;">
                    </div>
                </div>

                <div class="afc-field" style="margin-top: 25px;">
                    <label>Vital Statistics</label>
                    <div class="specs-mini-grid">
                        <input type="number" name="listing_beds" value="<?php echo esc_attr($defaults['beds']); ?>" placeholder="Beds" style="border: 2px solid #e2e8f0; border-radius: 8px; padding: 12px;">
                        <input type="number" name="listing_baths" value="<?php echo esc_attr($defaults['baths']); ?>" placeholder="Baths" step="0.5" style="border: 2px solid #e2e8f0; border-radius: 8px; padding: 12px;">
                        <input type="number" name="listing_sqft" value="<?php echo esc_attr($defaults['sqft']); ?>" placeholder="Sq Ft" style="border: 2px solid #e2e8f0; border-radius: 8px; padding: 12px;">
                    </div>
                </div>
            </section>

            <section class="afc-form-section">
                <h3><span style="color: #3b82f6;">3</span> Signature Features</h3>
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
                            <input type="checkbox" name="listing_amenities[]" value="<?php echo esc_attr($amenity); ?>" <?php echo $checked; ?> style="margin-right: 12px;">
                            <span style="margin-right: 8px;"><?php echo $icon; ?></span>
                            <span style="font-size: 13px; color: #475569; font-weight: 700;"><?php echo esc_html($amenity); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <section class="afc-form-section">
                <h3><span style="color: #3b82f6;">4</span> Media Assets</h3>
                <div class="afc-field">
                    <label>Primary Hero Photo</label>
                    <div class="hero-preview-box" onclick="<?php echo $is_locked ? '' : "document.getElementById('hero_file').click();"; ?>">
                        <?php if (has_post_thumbnail($post_id)) : ?>
                            <?php echo get_the_post_thumbnail($post_id, 'large', ['id' => 'hero-preview', 'style' => 'width:100%; height:100%; object-fit:cover;']); ?>
                        <?php else : ?>
                            <div id="hero-placeholder" style="text-align: center; color: #94a3b8;">
                                <div style="font-size: 40px; margin-bottom: 10px;">🖼️</div>
                                <div style="font-weight: 800;">CLICK TO UPLOAD HERO</div>
                            </div>
                            <img id="hero-preview" style="display:none; width:100%; height:100%; object-fit:cover;">
                        <?php endif; ?>
                    </div>
                    <input type="file" id="hero_file" name="hero_file" style="display:none" accept="image/*">
                </div>
                
                <div class="afc-field" style="margin-top: 30px;">
                    <label>Gallery Collection (Max <?php echo C::MAX_GALLERY; ?>)</label>
                    <div style="background: #f8fafc; border: 2px dashed #cbd5e1; border-radius: 12px; padding: 40px; text-align: center; cursor: pointer;" onclick="<?php echo $is_locked ? '' : "document.getElementById('gallery_files').click();"; ?>">
                        <span style="font-size: 30px;">📸</span>
                        <p style="margin: 10px 0; font-weight: 800; color: #475569;">ATTACH GALLERY IMAGES</p>
                    </div>
                    <input type="file" id="gallery_files" name="gallery_files[]" style="display:none" accept="image/*" multiple>
                    
                    <div id="new-gallery-preview" style="display:none; margin-top: 25px; padding: 20px; background: #f1f5f9; border-radius: 12px;">
                        <label style="font-size: 10px; color: #64748b; margin-bottom: 15px;">PREVIEWING NEW BATCH:</label>
                        <div id="new-gallery-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(80px, 1fr)); gap: 15px;"></div>
                    </div>
                </div>
            </section>

            <section class="afc-form-section">
                <h3><span style="color: #3b82f6;">5</span> Location Data</h3>
                <div class="afc-field" style="margin-bottom: 25px;">
                    <label>📍 Physical Address</label>
                    <input type="text" name="listing_address" value="<?php echo esc_attr($defaults['address']); ?>" placeholder="Enter property address..." style="width: 100%; border: 2px solid #e2e8f0; border-radius: 8px; padding: 12px;">
                </div>

                <div style="background: #f8fafc; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <label style="font-weight: 800; color: #475569; font-size: 11px; text-transform: uppercase;">📡 Network GPS Coordinates</label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
                        <div class="afc-field">
                            <label style="font-size: 10px;">LATITUDE</label>
                            <input type="text" name="gps_lat" value="<?php echo esc_attr($defaults['gps_lat']); ?>" placeholder="0.0000" style="width: 100%; font-family: monospace; border: 2px solid #cbd5e1; border-radius: 6px; padding: 10px;">
                        </div>
                        <div class="afc-field">
                            <label style="font-size: 10px;">LONGITUDE</label>
                            <input type="text" name="gps_lng" value="<?php echo esc_attr($defaults['gps_lng']); ?>" placeholder="0.0000" style="width: 100%; font-family: monospace; border: 2px solid #cbd5e1; border-radius: 6px; padding: 10px;">
                        </div>
                    </div>
                </div>
            </section>

        </fieldset>

        <button type="submit" id="afc-submit-btn" class="afc-main-btn" <?php echo $is_locked ? 'disabled' : ''; ?>>
            <?php echo $is_locked ? '🔒 SYSTEM LOCKED' : ($post_id ? 'SYNC ASSET CHANGES' : 'DEPLOY GLOBAL LISTING'); ?>
        </button>
        
        <div id="afc-feedback" style="margin-top: 25px; text-align: center; font-weight: 800; min-height: 24px;"></div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        
        // 1. Hero Preview Logic
        const heroInput = document.getElementById('hero_file');
        const heroPreview = document.getElementById('hero-preview');
        const heroPlaceholder = document.getElementById('hero-placeholder');

        if (heroInput) {
            heroInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        heroPreview.src = e.target.result;
                        heroPreview.style.display = 'block';
                        if (heroPlaceholder) heroPlaceholder.style.display = 'none';
                    }
                    reader.readAsDataURL(file);
                }
            });
        }

        // 2. Multi-Gallery Preview Logic
        const galleryInput = document.getElementById('gallery_files');
        const galleryGrid = document.getElementById('new-gallery-grid');
        const galleryContainer = document.getElementById('new-gallery-preview');

        if (galleryInput) {
            galleryInput.addEventListener('change', function() {
                galleryGrid.innerHTML = ''; 
                galleryContainer.style.display = 'block';

                Array.from(this.files).forEach(file => {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        const div = document.createElement('div');
                        div.style.cssText = 'width: 100%; aspect-ratio: 1/1; border-radius: 8px; overflow: hidden; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.1);';
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.style.cssText = 'width: 100%; height: 100%; object-fit: cover;';
                        
                        div.appendChild(img);
                        galleryGrid.appendChild(div);
                    }
                    reader.readAsDataURL(file);
                });
            });
        }
    });
    </script>
</div>