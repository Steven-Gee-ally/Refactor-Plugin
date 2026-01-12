<?php
/**
 * AFCGlide - Professional Agent Submission Form
 * Template: Best in the World Luxury Interface
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Get current user for auto-fill
$current_user = wp_get_current_user();
?>

<div id="afcglide-submission-root" class="afc-form-wrapper">
    <div class="afc-form-header">
        <h2>🚀 New Property Submission</h2>
        <p>Complete the details below to launch your listing.</p>
    </div>

    <form id="afcglide-front-submission" enctype="multipart/form-data">
        
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
                    <label>Property Type</label>
                    <select name="property_type">
                        <option value="residential">Residential</option>
                        <option value="commercial">Commercial</option>
                        <option value="land">Land/Lot</option>
                    </select>
                </div>
            </div>
        </section>

        <section class="afc-form-section">
            <h3><span class="step-num">2</span> Luxury Media Gallery</h3>
            <p class="field-hint">Upload exactly 16 high-resolution photos (Min 1200px wide).</p>
            
            <div id="afc-upload-dropzone" class="afc-dropzone">
                <div class="dz-message">
                    <span class="dz-icon">📸</span>
                    <p>Drag & Drop Photos or Click to Upload</p>
                    <input type="file" name="property_photos[]" id="afc_photos" multiple accept="image/*" style="display:none;">
                    <button type="button" class="afc-upload-trigger">Select Photos</button>
                </div>
            </div>
            <div id="afc-preview-grid" class="afc-preview-grid"></div>
        </section>

        <section class="afc-form-section">
            <h3><span class="step-num">3</span> Agent Information</h3>
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