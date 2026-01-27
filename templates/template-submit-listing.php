<?php
/**
 * AFCGlide - Professional Agent Submission Form
 * Version 4.4.0 - Full Production Master
 * NO COMPROMISE EDITION - Full Narrative & Metric Logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// 1. SYSTEM PROTOCOL - Clear the deck for a high-end interface
remove_all_actions( 'admin_notices' );
remove_all_actions( 'all_admin_notices' );
remove_action( 'wp_footer', 'astra_theme_background_updater_info' ); 
show_admin_bar( false );

use AFCGlide\Core\Constants as C;

// --- INITIALIZATION LOGIC ---
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

<div id="afcglide-submission-root">
    
    <?php if ($is_locked) : ?>
    <div class="afc-lockdown-banner">
        <div class="afc-section-number">🔒</div>
        <div class="afc-banner-content">
            <h3>GLOBAL LOCKDOWN ACTIVE</h3>
            <p>Network security protocol engaged. Submission services are temporarily suspended for maintenance.</p>
        </div>
    </div>
    <?php endif; ?>

    <header class="afc-form-header">
        <div class="afc-title-banner">
            <div class="afc-banner-icon">🚀</div>
            <div class="afc-banner-content">
                <h3><?php echo $post_id ? 'UPDATE ASSET CORE' : 'NEW ASSET DEPLOYMENT'; ?></h3>
                <p>Secure Terminal Access: <?php echo esc_html($current_user->display_name); ?></p>
            </div>
        </div>
    </header>

    <form id="afcglide-front-submission" enctype="multipart/form-data">
        <input type="hidden" name="action" value="<?php echo C::AJAX_SUBMIT; ?>">
        <input type="hidden" name="security" value="<?php echo wp_create_nonce( C::NONCE_AJAX ); ?>">
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

        <fieldset <?php echo $is_locked ? 'disabled' : ''; ?> style="border: none; padding: 0; margin: 0;">
        
            <section class="afc-form-section">
                <div class="afc-description-banner">
                    <div class="afc-section-number">1</div>
                    <div class="afc-banner-content">
                        <h3>PROPERTY NARRATIVE</h3>
                        <p>Configure the marketing storytelling for both English and Spanish markets.</p>
                    </div>
                </div>

                <div class="afc-description-wrapper">
                    <div class="afc-field">
                        <label class="afc-label">🇺🇸 Asset Title (English)</label>
                        <input type="text" name="listing_title" class="afc-title-input" value="<?php echo esc_attr($defaults['title']); ?>" placeholder="e.g. The Sapphire Estate" required>
                    </div>

                    <div class="afc-agent-selector-wrapper" style="margin-top: 25px;">
                        <div class="afc-field">
                            <label class="afc-label">🇨🇷 Título del Activo (Español)</label>
                            <input type="text" name="listing_intro_es" class="afc-input" value="<?php echo esc_attr($defaults['intro_es']); ?>" placeholder="ej. La Finca Zafiro">
                        </div>
                    </div>
                    
                    <div class="afc-field" style="margin-top: 25px;">
                        <label class="afc-label">🇺🇸 Marketing Story (English)</label>
                        <textarea name="listing_description" class="afc-input" rows="8" placeholder="Describe the lifestyle and premium features in English..."><?php echo esc_textarea($defaults['description']); ?></textarea>
                    </div>

                    <div class="afc-agent-selector-wrapper" style="margin-top: 25px; border-left: 4px solid #3b82f6;">
                        <div class="afc-field">
                            <label class="afc-label">🇨🇷 Descripción de la Propiedad (Español)</label>
                            <textarea name="listing_narrative_es" class="afc-input" rows="8" placeholder="Describa el estilo de vida y las características premium en español..."><?php echo esc_textarea($defaults['narrative_es']); ?></textarea>
                        </div>
                    </div>
                </div>
            </section>

            <section class="afc-form-section">
                <div class="afc-description-banner">
                    <div class="afc-section-number">2</div>
                    <div class="afc-banner-content">
                        <h3>CORE METRICS</h3>
                        <p>Define the commercial status, pricing, and physical dimensions of the asset.</p>
                    </div>
                </div>

                <div class="afc-metabox-content">
                    <div class="afc-media-matrix">
                        <div class="afc-field">
                            <label class="afc-label">Current Market Status</label>
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
                            <label class="afc-label">Listing Price (USD)</label>
                            <input type="number" name="listing_price" class="afc-input" value="<?php echo esc_attr($defaults['price']); ?>" placeholder="0.00" step="0.01" required>
                        </div>
                    </div>

                    <div class="afc-field" style="margin-top: 30px;">
                        <label class="afc-label">Vital Statistics</label>
                        <div class="specs-mini-grid">
                            <div class="afc-field">
                                <label style="font-size: 9px;">BEDROOMS</label>
                                <input type="number" name="listing_beds" class="afc-input" value="<?php echo esc_attr($defaults['beds']); ?>" placeholder="Beds">
                            </div>
                            <div class="afc-field">
                                <label style="font-size: 9px;">BATHROOMS</label>
                                <input type="number" name="listing_baths" class="afc-input" value="<?php echo esc_attr($defaults['baths']); ?>" placeholder="Baths" step="0.5">
                            </div>
                            <div class="afc-field">
                                <label style="font-size: 9px;">SQUARE FEET</label>
                                <input type="number" name="listing_sqft" class="afc-input" value="<?php echo esc_attr($defaults['sqft']); ?>" placeholder="Sq Ft">
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="afc-form-section">
                <div class="afc-description-banner">
                    <div class="afc-section-number">3</div>
                    <div class="afc-banner-content">
                        <h3>SIGNATURE FEATURES</h3>
                        <p>Select the elite amenities that define this listing's value proposition.</p>
                    </div>
                </div>

                <div class="afc-metabox-content">
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
                                <span style="margin-right: 10px;"><?php echo $icon; ?></span>
                                <span><?php echo esc_html($amenity); ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <section class="afc-form-section">
                <div class="afc-description-banner">
                    <div class="afc-section-number">4</div>
                    <div class="afc-banner-content">
                        <h3>MEDIA ASSETS</h3>
                        <p>High-resolution visual assets for global marketing distribution.</p>
                    </div>
                </div>

                <div class="afc-metabox-content">
                    <div class="afc-field">
                        <label class="afc-label">Primary Hero Photo</label>
                        <div class="hero-preview-box" onclick="<?php echo $is_locked ? '' : "document.getElementById('hero_file').click();"; ?>">
                            <?php if (has_post_thumbnail($post_id)) : ?>
                                <?php echo get_the_post_thumbnail($post_id, 'large', ['id' => 'hero-preview', 'style' => 'width:100%; height:100%; object-fit:cover;']); ?>
                            <?php else : ?>
                                <div id="hero-placeholder" style="text-align: center; color: #94a3b8;">
                                    <div style="font-size: 40px; margin-bottom: 10px;">🖼️</div>
                                    <div style="font-weight: 800; letter-spacing: 1px;">UPLOAD MASTER HERO IMAGE</div>
                                </div>
                                <img id="hero-preview" style="display:none; width:100%; height:100%; object-fit:cover;">
                            <?php endif; ?>
                        </div>
                        <input type="file" id="hero_file" name="hero_file" style="display:none" accept="image/*">
                    </div>
                    
                    <div class="afc-field" style="margin-top: 40px;">
                        <label class="afc-label">Gallery Collection (Max <?php echo C::MAX_GALLERY; ?>)</label>
                        <div class="afc-upload-zone" onclick="<?php echo $is_locked ? '' : "document.getElementById('gallery_files').click();"; ?>" style="text-align: center; cursor: pointer;">
                            <span style="font-size: 30px;">📸</span>
                            <p style="margin: 10px 0; font-weight: 800; color: #475569; letter-spacing: 1px;">SELECT GALLERY ASSETS</p>
                        </div>
                        <input type="file" id="gallery_files" name="gallery_files[]" style="display:none" accept="image/*" multiple>
                        
                        <div id="new-gallery-preview" style="display:none; margin-top: 25px; padding: 25px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                            <label class="afc-label" style="color: #64748b; margin-bottom: 15px;">BATCH PREVIEW:</label>
                            <div id="new-gallery-grid" class="afc-preview-grid"></div>
                        </div>
                    </div>
                </div>
            </section>

            <section class="afc-form-section">
                <div class="afc-description-banner">
                    <div class="afc-section-number">5</div>
                    <div class="afc-banner-content">
                        <h3>LOCATION DATA</h3>
                        <p>Geospatial coordinates and physical address for global mapping.</p>
                    </div>
                </div>

                <div class="afc-metabox-content">
                    <div class="afc-field" style="margin-bottom: 30px;">
                        <label class="afc-label">📍 Physical Address</label>
                        <input type="text" name="listing_address" class="afc-input" value="<?php echo esc_attr($defaults['address']); ?>" placeholder="Enter property address or sector...">
                    </div>

                    <div class="afc-agent-selector-wrapper" style="padding: 30px; border: 2px solid #e2e8f0;">
                        <label class="afc-label" style="color: #475569; font-size: 12px;">📡 GPS COORDINATES (SATELLITE SYNC)</label>
                        <div class="afc-gps-row" style="margin-top: 20px;">
                            <div class="afc-field">
                                <label style="font-size: 10px; color: #94a3b8;">LATITUDE</label>
                                <input type="text" name="gps_lat" class="afc-input" value="<?php echo esc_attr($defaults['gps_lat']); ?>" placeholder="0.000000" style="font-family: 'Courier New', monospace; font-weight: 700;">
                            </div>
                            <div class="afc-field">
                                <label style="font-size: 10px; color: #94a3b8;">LONGITUDE</label>
                                <input type="text" name="gps_lng" class="afc-input" value="<?php echo esc_attr($defaults['gps_lng']); ?>" placeholder="0.000000" style="font-family: 'Courier New', monospace; font-weight: 700;">
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </fieldset>

        <div class="afc-publish-section" style="margin-top: 50px;">
            <button type="submit" id="afc-submit-btn" class="afc-main-btn" <?php echo $is_locked ? 'disabled' : ''; ?>>
                <?php echo $is_locked ? '🔒 SYSTEM ENCRYPTION ACTIVE' : ($post_id ? 'SYNC ASSET UPDATES' : 'DEPLOY WORLD-WIDE LISTING'); ?>
            </button>
        </div>
        
        <div id="afc-feedback" style="margin-top: 30px; text-align: center; font-weight: 800; font-size: 16px;"></div>
    </form>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hero Photo Preview Protocol
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

        // Gallery Multi-Asset Preview Protocol
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
                        div.className = 'afc-preview-item';
                        const img = document.createElement('img');
                        img.src = e.target.result;
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