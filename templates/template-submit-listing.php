<?php
/**
 * Template Name: Submit Listing - Professional Terminal
 * Description: The 12-Section "Pastel Master" Submission Form for AFCGlide
 * Version: 5.6.0-BULLETPROOF - CSS Extracted Edition
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use AFCGlide\Core\Constants as C;

// Check for global lockdown
$lockdown = get_option('afc_global_lockdown') === '1';
$is_broker = current_user_can('manage_options');

get_header(); 
?>

<div class="afc-submission-terminal">
    <div class="afc-container">
        
        <!-- 🔒 LOCKDOWN BANNER -->
        <?php if ($lockdown && !$is_broker): ?>
        <div class="afc-lockdown-banner">
            <div class="afc-lockdown-icon">🔒</div>
            <div>
                <h3 class="afc-lockdown-title">NETWORK LOCKDOWN ACTIVE</h3>
                <p class="afc-lockdown-text">All asset submissions are currently frozen by the Managing Broker. Please contact your supervisor for authorization.</p>
            </div>
        </div>
        <?php return; // Stop rendering form
        endif; ?>

        <!-- 📊 HEADER & PROGRESS BAR -->
        <header class="afc-terminal-header">
            <h1 class="afc-terminal-title">Submit New Asset</h1>
            <div class="afc-progress-bar">
                <div class="progress-fill" id="afc-progress-fill"></div>
            </div>
        </header>

        <!-- 🎯 MAIN SUBMISSION FORM -->
        <form id="afc-submit-listing-form" enctype="multipart/form-data">
            
            <!-- SECTION 1: Basic Information -->
            <section class="afc-form-section">
                <h3 class="afc-section-title">
                    <span class="afc-step-num">1</span>
                    <i class="fas fa-info-circle"></i> Basic Information
                </h3>
                <div class="afc-field full-width">
                    <label>Property Title *</label>
                    <input type="text" name="listing_title" placeholder="e.g. Luxury Penthouse with Ocean View" required>
                </div>
                <div class="afc-field full-width">
                    <label>Property Description (English) *</label>
                    <textarea name="listing_description" rows="6" placeholder="Describe this luxury property in detail..." required></textarea>
                </div>
            </section>

            <!-- SECTION 2: Pricing Architecture -->
            <section class="afc-form-section">
                <h3 class="afc-section-title">
                    <span class="afc-step-num">2</span>
                    <i class="fas fa-tag"></i> Pricing Architecture
                </h3>
                <div class="afc-flex-row">
                    <div class="afc-field flex-2">
                        <label>Price ($) *</label>
                        <input type="number" name="listing_price" placeholder="350000" required>
                    </div>
                    <div class="afc-field flex-3">
                        <label>Address *</label>
                        <input type="text" name="listing_address" placeholder="123 Ocean Drive, Tamarindo">
                    </div>
                </div>
                <div class="afc-field full-width">
                    <label>Location / Area</label>
                    <input type="text" name="listing_location" placeholder="e.g. Tamarindo, Guanacaste">
                </div>
            </section>

            <!-- SECTION 3: Visual Assets -->
            <section class="afc-form-section">
                <h3 class="afc-section-title">
                    <span class="afc-step-num">3</span>
                    <i class="fas fa-camera"></i> Visual Assets
                </h3>
                
                <div class="afc-field full-width">
                    <label>Hero Image (Main Photo) *</label>
                    <div class="afc-media-uploader" onclick="document.getElementById('hero-file').click()">
                        <p>📸 Click to Upload Hero Image</p>
                        <input type="file" id="hero-file" name="hero_file" accept="image/*" style="display:none;">
                    </div>
                    <div id="hero-preview" class="afc-preview-grid"></div>
                </div>

                <div class="afc-field full-width">
                    <label>Gallery Images (Up to <?php echo C::MAX_GALLERY; ?>)</label>
                    <div class="afc-media-uploader" onclick="document.getElementById('gallery-files').click()">
                        <p>🖼️ Click to Upload Gallery Photos</p>
                        <input type="file" id="gallery-files" name="gallery_files[]" multiple accept="image/*" style="display:none;">
                    </div>
                    <div id="gallery-preview" class="afc-preview-grid"></div>
                </div>
            </section>

            <!-- SECTION 4: Location Intelligence -->
            <section class="afc-form-section">
                <h3 class="afc-section-title">
                    <span class="afc-step-num">4</span>
                    <i class="fas fa-map-marker-alt"></i> Location Intelligence
                </h3>
                <div class="afc-flex-row">
                    <div class="afc-field">
                        <label>Latitude (GPS)</label>
                        <input type="text" name="gps_lat" placeholder="10.3095" pattern="^-?([0-9]{1,2}|1[0-7][0-9]|180)(\.[0-9]{1,10})?$">
                    </div>
                    <div class="afc-field">
                        <label>Longitude (GPS)</label>
                        <input type="text" name="gps_lng" placeholder="-85.8419" pattern="^-?([0-9]{1,2}|1[0-7][0-9]|180)(\.[0-9]{1,10})?$">
                    </div>
                </div>
            </section>

            <!-- SECTION 5: Property Specs -->
            <section class="afc-form-section">
                <h3 class="afc-section-title">
                    <span class="afc-step-num">5</span>
                    <i class="fas fa-bed"></i> Property Specs
                </h3>
                <div class="afc-flex-row">
                    <div class="afc-field">
                        <label>Bedrooms</label>
                        <input type="number" name="listing_beds" placeholder="3" min="0">
                    </div>
                    <div class="afc-field">
                        <label>Bathrooms</label>
                        <input type="number" name="listing_baths" placeholder="2.5" min="0" step="0.5">
                    </div>
                    <div class="afc-field">
                        <label>Area (SqFt/M²)</label>
                        <input type="number" name="listing_sqft" placeholder="2500" min="0">
                    </div>
                </div>
            </section>

            <!-- SECTION 6: Amenities & Lifestyle Features -->
            <section class="afc-form-section">
                <h3 class="afc-section-title">
                    <span class="afc-step-num">6</span>
                    <i class="fas fa-check-square"></i> Amenities & Lifestyle Features
                </h3>
                <div class="afc-amenities-grid">
                    <?php 
                    $amenities_list = [
                        'Pool', 'Security', 'Gated', 'Ocean View', 'Mountain View',
                        'Air Conditioning', 'High-Speed Wi-Fi', 'Parking', 'Garden', 'Terrace',
                        'Furnished', 'Laundry Room', 'Guest House', 'Pet Friendly', 'Solar Power',
                        'Water Tank', 'Gym', 'Walking Trails', 'BBQ Area', 'Workshop'
                    ];

                    foreach ( $amenities_list as $amenity ) : ?>
                        <label class="afc-checkbox-item">
                            <input type="checkbox" name="listing_amenities[]" value="<?php echo esc_attr($amenity); ?>">
                            <span><?php echo esc_html($amenity); ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </section>

            <!-- SECTION 7: Bilingual Content (Spanish) -->
            <section class="afc-form-section">
                <h3 class="afc-section-title">
                    <span class="afc-step-num">7</span>
                    <i class="fas fa-globe"></i> Spanish Translation
                </h3>
                <div class="afc-field full-width">
                    <label>Intro Paragraph (Spanish)</label>
                    <textarea name="listing_intro_es" rows="3" placeholder="Descripción breve en español..."></textarea>
                </div>
                <div class="afc-field full-width">
                    <label>Full Description (Spanish)</label>
                    <textarea name="listing_narrative_es" rows="6" placeholder="Descripción completa en español..."></textarea>
                </div>
            </section>

            <!-- SECTION 8: Listing Status -->
            <section class="afc-form-section">
                <h3 class="afc-section-title">
                    <span class="afc-step-num">8</span>
                    <i class="fas fa-handshake"></i> Listing Status
                </h3>
                <div class="afc-status-toggle">
                    <label class="status-opt">
                        <input type="radio" name="listing_status" value="publish" checked>
                        <span class="status-label active">ACTIVE</span>
                    </label>
                    <label class="status-opt">
                        <input type="radio" name="listing_status" value="pending">
                        <span class="status-label pending">PENDING</span>
                    </label>
                    <label class="status-opt">
                        <input type="radio" name="listing_status" value="sold">
                        <span class="status-label sold">SOLD</span>
                    </label>
                </div>
            </section>

            <!-- SECTION 9: Agent Information -->
            <section class="afc-form-section">
                <h3 class="afc-section-title">
                    <span class="afc-step-num">9</span>
                    <i class="fas fa-user-tie"></i> Agent Information
                </h3>
                <div class="afc-flex-row">
                    <div class="afc-field flex-2">
                        <label>Agent Name</label>
                        <input type="text" name="agent_name" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
                    </div>
                    <div class="afc-field">
                        <label>Agent Phone</label>
                        <input type="tel" name="agent_phone" placeholder="+506 1234-5678">
                    </div>
                </div>
                <div class="afc-field full-width">
                    <label>Agent Photo (Optional)</label>
                    <input type="file" name="agent_photo_file" id="agent-photo-file" accept="image/*">
                    <div id="agent-photo-preview" class="afc-preview-zone"></div>
                </div>
                <div class="afc-field full-width">
                    <label>Brokerage Logo (Optional)</label>
                    <input type="file" name="broker_logo_file" id="broker-logo-file" accept="image/*">
                    <div id="broker-logo-preview" class="afc-preview-zone"></div>
                </div>
            </section>

            <!-- SECTION 10: Additional Files -->
            <section class="afc-form-section">
                <h3 class="afc-section-title">
                    <span class="afc-step-num">10</span>
                    <i class="fas fa-file-pdf"></i> Additional Documents
                </h3>
                <div class="afc-field full-width">
                    <label>PDF Brochure (Optional)</label>
                    <input type="file" name="pdf_file" accept=".pdf">
                </div>
            </section>

            <!-- SECTION 11: Quality Certification -->
            <section class="afc-form-section">
                <h3 class="afc-section-title">
                    <span class="afc-step-num">11</span>
                    <i class="fas fa-shield-alt"></i> Quality Review
                </h3>
                <label class="afc-checkbox-item">
                    <input type="checkbox" required>
                    <span>I certify that all images meet the 1200px minimum quality standard</span>
                </label>
            </section>

            <!-- SECTION 12: Final Transmission -->
            <section class="afc-form-section afc-submit-section">
                <h3 class="afc-section-title">
                    <span class="afc-step-num">12</span>
                    Final Transmission
                </h3>
                <button type="submit" class="afc-main-submit" id="afc-submit-btn">
                    🚀 Launch Listing to Market
                </button>
                <div id="afc-form-status" class="afc-form-status"></div>
            </section>

            <?php wp_nonce_field(C::NONCE_AJAX, 'afc_listing_nonce'); ?>
            <input type="hidden" name="action" value="<?php echo C::AJAX_SUBMIT; ?>">
        </form>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    
    // Image Preview Handlers
    $('#hero-file').on('change', function(e) {
        previewImage(e.target.files[0], '#hero-preview');
    });
    
    $('#gallery-files').on('change', function(e) {
        previewGallery(e.target.files, '#gallery-preview');
    });

    $('#agent-photo-file').on('change', function(e) {
        previewImage(e.target.files[0], '#agent-photo-preview');
    });

    $('#broker-logo-file').on('change', function(e) {
        previewImage(e.target.files[0], '#broker-logo-preview');
    });
    
    function previewImage(file, container) {
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function(e) {
            $(container).html('<div class="gallery-thumb"><img src="' + e.target.result + '"></div>');
        };
        reader.readAsDataURL(file);
    }
    
    function previewGallery(files, container) {
        $(container).empty();
        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                $(container).append('<div class="gallery-thumb"><img src="' + e.target.result + '"></div>');
            };
            reader.readAsDataURL(file);
        });
    }
    
    // Form Submission with AJAX
    $('#afc-submit-listing-form').on('submit', function(e) {
        e.preventDefault();
        
        const $btn = $('#afc-submit-btn');
        const $status = $('#afc-form-status');
        const originalText = $btn.text();
        
        // Disable button
        $btn.prop('disabled', true).text('🔄 TRANSMITTING...');
        $status.removeClass('success error').text('');
        
        // Prepare FormData
        const formData = new FormData(this);
        
        $.ajax({
            url: '<?php echo admin_url("admin-ajax.php"); ?>',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $status.addClass('success').html('✅ ' + response.data.message + '<br><a href="' + response.data.url + '">View Listing →</a>');
                    $btn.text('✓ SUCCESS').css('background', '#10b981');
                    
                    // Reset form after 3 seconds
                    setTimeout(function() {
                        window.location.href = response.data.url;
                    }, 2000);
                } else {
                    $status.addClass('error').text('❌ ' + (response.data?.message || 'Submission failed'));
                    $btn.text(originalText).prop('disabled', false);
                }
            },
            error: function() {
                $status.addClass('error').text('❌ Network error. Please try again.');
                $btn.text(originalText).prop('disabled', false);
            }
        });
    });
    
    // Progress Bar Update
    const totalSections = 12;
    let completedSections = 0;
    
    $('.afc-form-section').each(function() {
        const $section = $(this);
        $section.find('input, textarea, select').on('change', function() {
            updateProgress();
        });
    });
    
    function updateProgress() {
        let filled = 0;
        $('.afc-form-section').each(function() {
            const hasContent = $(this).find('input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), textarea').filter(function() {
                return $(this).val() !== '';
            }).length > 0;
            
            if (hasContent) filled++;
        });
        
        const percent = Math.min(100, (filled / totalSections) * 100);
        $('#afc-progress-fill').css('width', percent + '%');
    }
});
</script>

<?php get_footer(); ?>