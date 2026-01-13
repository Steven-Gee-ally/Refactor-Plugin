<?php
/**
 * AFCGlide - Professional Agent Submission Form
 * Version 3.2 - Meticulous Multi-Step Edition
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$current_user = wp_get_current_user();
?>

<div id="afcglide-submission-root" class="afc-form-wrapper">
    <div class="afc-form-header">
        <h2>🚀 New Property Submission</h2>
        <p>Complete the details below to launch your luxury listing.</p>
    </div>

    <form id="afcglide-front-submission" enctype="multipart/form-data">
        
        <?php wp_nonce_field( 'afcglide_ajax_nonce', 'nonce' ); ?>

        <section class="afc-form-section">
            <h3><span class="step-num">1</span> Property Essentials</h3>
            <div class="afc-grid">
                <div class="afc-field full">
                    <label>Property Title</label>
                    <input type="text" name="listing_title" placeholder="e.g. Luxury Penthouse with Ocean View" required>
                </div>
                <div class="afc-field">
                    <label>Price ($)</label>
                    <input type="number" name="listing_price" placeholder="2500000" required>
                </div>
                <div class="afc-field">
                    <label>Specs (Beds / Baths / SqFt)</label>
                    <div class="afc-subgrid" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                        <input type="number" name="listing_beds" placeholder="Beds">
                        <input type="number" name="listing_baths" placeholder="Baths">
                        <input type="number" name="listing_sqft" placeholder="Sq Ft">
                    </div>
                </div>
            </div>
        </section>

        <section class="afc-form-section">
            <h3><span class="step-num">2</span> Location Details</h3>
            <div class="afc-grid">
                <div class="afc-field full">
                    <label>Physical Address</label>
                    <input type="text" name="property_address" placeholder="123 Luxury Way, Beverly Hills...">
                </div>
                <div class="afc-field">
                    <label>GPS Latitude</label>
                    <input type="text" name="gps_lat" placeholder="e.g. 34.0736">
                </div>
                <div class="afc-field">
                    <label>GPS Longitude</label>
                    <input type="text" name="gps_lng" placeholder="e.g. -118.4004">
                </div>
            </div>
        </section>

        <section class="afc-form-section">
            <h3><span class="step-num">3</span> Luxury Amenities</h3>
            <p class="field-hint">Select all features included with this property.</p>
            <div class="afc-amenities-selector-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 10px;">
                <?php 
                $luxury_amenities = ['Swimming Pool', 'Fitness Center', 'Security 24/7', 'Gated Community', 'Ocean View', 'Mountain View', 'Smart Home', 'Private Garden', 'Guest House', 'Helipad'];
                foreach ( $luxury_amenities as $amenity ) : ?>
                    <label class="afc-checkbox-item" style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="amenities[]" value="<?php echo esc_attr($amenity); ?>">
                        <span class="checkbox-label"><?php echo esc_html($amenity); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="afc-form-section">
            <h3><span class="step-num">4</span> Luxury Media Gallery</h3>
            <p class="field-hint">Upload exactly 16 high-resolution photos (Min 1200px wide).</p>
            
            <div id="afc-upload-dropzone" class="afc-dropzone">
                <div class="dz-message">
                    <span class="dz-icon">📸</span>
                    <p>Drag & Drop Photos or Click to Upload</p>
                    <input type="file" name="afc_photos[]" id="afc_photos" multiple accept="image/*" style="display:none;">
                    <button type="button" class="afc-upload-trigger">Select Photos</button>
                </div>
            </div>
            <div id="afc-preview-grid" class="afc-preview-grid"></div>
        </section>

        <section class="afc-form-section">
            <h3><span class="step-num">5</span> Agent Information</h3>
            <div class="afc-grid">
                <div class="afc-field">
                    <label>Agent Name</label>
                    <input type="text" name="agent_name" value="<?php echo esc_attr($current_user->display_name); ?>" readonly>
                </div>
                <div class="afc-field">
                    <label>Contact Phone</label>
                    <input type="text" name="agent_phone" placeholder="Your direct line" required>
                </div>
            </div>
        </section>

        <div class="afc-form-footer">
            <div id="afc-form-status"></div>
            <button type="submit" id="submit-listing-btn" class="afc-main-btn">
                <span class="btn-text">Publish Listing</span>
                <div class="afc-loader" style="display:none;"></div>
            </button>
        </div>
    </form>
</div>