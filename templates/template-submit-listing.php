<?php
/**
 * AFCGlide - Professional Agent Submission Form
 * Version 4.0 - Lockdown Integration + Fixed AJAX
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Check if Global Lockdown is active
$is_locked = get_option('afc_global_lockdown', '0') === '1' && ! current_user_can('manage_options');

// DATA RECALL LOGIC
$post_id = isset($_GET['post']) ? intval($_GET['post']) : 0;
$defaults = [
    'title' => '', 'price' => '', 'beds' => '', 'baths' => '', 
    'sqft' => '', 'address' => '', 'status' => 'active'
];

if ( $post_id > 0 ) {
    $post = get_post($post_id);
    if ( $post && ($post->post_author == get_current_user_id() || current_user_can('manage_options')) ) {
        $defaults['title']   = $post->post_title;
        $defaults['price']   = get_post_meta($post_id, '_listing_price', true);
        $defaults['beds']    = get_post_meta($post_id, '_listing_beds', true);
        $defaults['baths']   = get_post_meta($post_id, '_listing_baths', true);
        $defaults['sqft']    = get_post_meta($post_id, '_listing_sqft', true);
        $defaults['address'] = get_post_meta($post_id, '_listing_address', true);
        $defaults['status']  = get_post_meta($post_id, '_listing_status', true) ?: 'active';
    }
}

$current_user = wp_get_current_user();
?>

<div id="afcglide-submission-root">
    
    <?php if ($is_locked) : ?>
    <div class="afc-lockdown-banner" style="
        background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
        border: 2px solid #dc2626;
        border-radius: 12px;
        padding: 20px 30px;
        margin-bottom: 30px;
        display: flex;
        align-items: center;
        gap: 20px;
    ">
        <div style="font-size: 36px;">🔒</div>
        <div>
            <h3 style="margin: 0 0 8px 0; color: #7f1d1d; font-size: 18px; font-weight: 800;">GLOBAL LOCKDOWN ACTIVE</h3>
            <p style="margin: 0; color: #991b1b; font-size: 14px;">All listing submissions are currently frozen. This form is in read-only mode. Contact your Lead Broker to lift the lockdown.</p>
        </div>
    </div>
    <?php endif; ?>

    <div class="afc-form-header">
        <h2>🚀 <?php echo $post_id ? 'Update Global Asset' : 'Launch Global Asset'; ?></h2>
        <p>Initializing secure protocol for: <strong><?php echo esc_html($current_user->display_name); ?></strong></p>
    </div>

    <form id="afcglide-front-submission" enctype="multipart/form-data">
        <input type="hidden" name="action" value="afcglide_submit_listing">
        <input type="hidden" name="security" value="<?php echo wp_create_nonce('afc_nonce'); ?>">
        <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">

        <fieldset <?php echo $is_locked ? 'disabled' : ''; ?>>
        
        <section class="afc-form-section">
            <h3><span class="step-num">1</span> Property Essentials</h3>
            <div class="afc-grid">
                <div class="afc-field full">
                    <label>Internal Asset Title</label>
                    <input type="text" name="listing_title" value="<?php echo esc_attr($defaults['title']); ?>" placeholder="e.g. Villa Aman" required>
                </div>
                
                <div class="afc-field full">
                    <label>Market Status (Updates Scoreboard)</label>
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

        <section class="afc-form-section">
            <h3><span class="step-num">2</span> Geospatial Identity</h3>
            <div class="afc-field full">
                <label>Primary Address</label>
                <input type="text" name="listing_address" value="<?php echo esc_attr($defaults['address']); ?>" placeholder="Physical location of the asset">
            </div>
        </section>

        <section class="afc-form-section">
            <h3><span class="step-num">3</span> Property Description</h3>
            <div class="afc-field full">
                <label>Narrative & Features</label>
                <textarea name="listing_description" rows="6" placeholder="Describe this luxury property..."><?php echo esc_textarea($post_id ? get_post($post_id)->post_content : ''); ?></textarea>
            </div>
        </section>

        <section class="afc-form-section">
            <h3><span class="step-num">4</span> Curated Lifestyle</h3>
            <div class="amenities-container" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: 12px;">
                <?php 
                $options = ['Infinity Pool', 'Wine Cellar', 'Ocean View', 'Smart Home', 'Private Gym'];
                $current_amenities = (array) get_post_meta($post_id, '_listing_amenities', true);
                foreach ( $options as $amenity ) : 
                    $checked = in_array($amenity, $current_amenities) ? 'checked' : '';
                ?>
                    <label class="afc-checkbox-item" style="display: flex; align-items: center; gap: 8px; background: #f8fafc; padding: 10px; border-radius: 8px; cursor: pointer;">
                        <input type="checkbox" name="listing_amenities[]" value="<?php echo esc_attr($amenity); ?>" <?php echo $checked; ?>>
                        <span style="font-size: 12px; font-weight: 600;"><?php echo esc_html($amenity); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="afc-form-section">
            <h3><span class="step-num">5</span> Visual Command Center</h3>
            <div class="afc-media-grid" style="display: grid; grid-template-columns: 1fr; gap: 20px;">
                <div class="afc-field">
                    <label>Main Hero Asset</label>
                    <div class="hero-preview-box" onclick="<?php echo $is_locked ? '' : 'document.getElementById(\'hero_file\').click();'; ?>" style="width: 100%; aspect-ratio: 16/9; background: #f1f5f9; border-radius: 12px; border: 2px dashed #cbd5e1; display: flex; align-items: center; justify-content: center; cursor: <?php echo $is_locked ? 'not-allowed' : 'pointer'; ?>; overflow: hidden; opacity: <?php echo $is_locked ? '0.6' : '1'; ?>;">
                        <?php if (has_post_thumbnail($post_id)) : ?>
                            <?php echo get_the_post_thumbnail($post_id, 'large', ['id' => 'hero-preview', 'style' => 'width:100%; height:100%; object-fit:cover;']); ?>
                        <?php else : ?>
                            <span id="hero-placeholder" style="color: #94a3b8; font-weight: 800; font-size: 12px;">+ SET HERO PHOTO</span>
                            <img id="hero-preview" style="display:none; width:100%; height:100%; object-fit:cover;">
                        <?php endif; ?>
                    </div>
                    <input type="file" id="hero_file" name="hero_file" style="display:none" accept="image/*">
                </div>
            </div>
        </section>

        </fieldset>

        <button type="submit" id="afc-submit-btn" class="afc-main-btn" <?php echo $is_locked ? 'disabled' : ''; ?>>
            <?php echo $is_locked ? '🔒 SUBMISSION LOCKED' : ($post_id ? 'SYNC ASSET UPDATES' : 'PUBLISH GLOBAL LISTING'); ?>
        </button>
        <div id="afc-feedback" style="margin-top: 20px; text-align: center; font-weight: 700;"></div>
    </form>
</div>

<style>
.afc-form-header { 
    text-align: center; 
    margin-bottom: 30px; 
    padding: 30px;
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    border-radius: 16px;
}
.afc-form-header h2 { margin: 0 0 10px 0; font-size: 28px; font-weight: 800; color: #0c4a6e; }
.afc-form-header p { margin: 0; color: #475569; font-size: 14px; }

.afc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
.afc-field.full { grid-column: span 2; }
.afc-field label { 
    display: block; 
    margin-bottom: 8px; 
    font-weight: 700; 
    color: #1e293b; 
    font-size: 13px;
    letter-spacing: 0.3px;
}
.afc-field input, .afc-field textarea {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.2s;
}
.afc-field input:focus, .afc-field textarea:focus {
    border-color: #6366f1;
    outline: none;
}

.specs-mini-grid { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; }
.afc-form-section { 
    background: white; 
    padding: 30px; 
    border-radius: 16px; 
    margin-bottom: 25px; 
    border: 1px solid #e2e8f0;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}
.afc-form-section h3 {
    margin: 0 0 20px 0;
    font-size: 16px;
    font-weight: 800;
    color: #0f172a;
    display: flex;
    align-items: center;
    gap: 12px;
}
.step-num {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    background: #6366f1;
    color: white;
    border-radius: 50%;
    font-size: 13px;
    font-weight: 800;
}

.afc-main-btn { 
    width: 100%; 
    padding: 20px; 
    background: #6366f1; 
    color: #fff; 
    border: none; 
    border-radius: 12px; 
    font-weight: 800; 
    font-size: 15px;
    cursor: pointer; 
    margin-top: 10px; 
    transition: all 0.3s;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
}
.afc-main-btn:hover:not(:disabled) { 
    background: #4f46e5; 
    transform: translateY(-2px);
    box-shadow: 0 6px 16px rgba(99, 102, 241, 0.4);
}
.afc-main-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    box-shadow: none;
}

.afc-status-toggle { display: flex; gap: 10px; margin-top: 10px; }
.status-opt { flex: 1; cursor: pointer; }
.status-opt input { display: none; }
.status-label { 
    display: block; text-align: center; padding: 12px; border-radius: 8px; 
    background: #f1f5f9; color: #64748b; font-weight: 800; font-size: 11px;
    transition: all 0.2s ease; border: 2px solid transparent;
}
.status-opt input:checked + .status-label.active { background: #eef2ff; color: #6366f1; border-color: #6366f1; }
.status-opt input:checked + .status-label.pending { background: #fffbeb; color: #d97706; border-color: #d97706; }
.status-opt input:checked + .status-label.sold { background: #f0fdf4; color: #16a34a; border-color: #16a34a; }

fieldset { border: none; padding: 0; margin: 0; }
fieldset:disabled { opacity: 0.6; pointer-events: none; }

#afc-feedback.success { color: #16a34a; }
#afc-feedback.error { color: #dc2626; }
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