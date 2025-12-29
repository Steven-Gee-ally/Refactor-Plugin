<?php
namespace AFCGlide\Listings\Submission;

if ( ! defined( 'ABSPATH' ) ) exit;

class Submission_Form {

    public static function init() {
        add_shortcode( 'afcglide_submission_form', [ __CLASS__, 'render_view' ] );
    }

    public static function render_view() {
        if ( ! is_user_logged_in() ) {
            return '<div class="afcglide-message error">Please log in to submit a property.</div>';
        }

        ob_start(); ?>

        <div id="afc-form-messages"></div>

        <div class="afcglide-form-wrapper afc-fade-in">
            <div class="form-header">
                <h2>List Your Property</h2>
                <p>Fill in the details below to create your luxury listing.</p>
            </div>

            <form id="afcglide-submit-property" method="POST" enctype="multipart/form-data">
                
                <?php wp_nonce_field('afcglide_ajax_nonce', 'nonce'); ?>

                <div class="form-section">
                    <h3>Property Basics</h3>
                    <div class="afcglide-form-full">
                        <label>Property Title</label>
                        <input type="text" name="property_title" placeholder="e.g. Costa Rica Luxury Villa" required>
                    </div>

                    <div class="afcglide-form-grid">
                        <div>
                            <label>Price ($)</label>
                            <input type="number" name="price" placeholder="500000">
                        </div>
                        <div>
                            <label>Bedrooms</label>
                            <input type="number" name="beds" placeholder="4">
                        </div>
                        <div>
                            <label>Bathrooms</label>
                            <input type="number" name="baths" step="0.5" placeholder="3.5">
                        </div>
                        <div>
                            <label>Listing Status</label>
                            <select name="listing_status" style="width:100%; height:42px; border-radius:4px; border:1px solid #ddd;">
                                <option value="just_listed">🆕 Just Listed</option>
                                <option value="for_sale">🏠 For Sale</option>
                                <option value="sold">💰 Sold</option>
                                <option value="reduced">📉 Price Reduced</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="afcglide-form-full">
                        <label>Description</label>
                        <textarea name="property_description" rows="5" placeholder="Describe the luxury lifestyle..."></textarea>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Luxury Amenities</h3>
                    <div class="afcglide-amenities-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 12px; background: #f8fafc; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0;">
                        <?php
                        $luxury_amenities = [
                            'gourmet_kitchen' => '👨‍🍳 Gourmet Kitchen', 'infinity_pool' => '♾️ Infinity Pool',
                            'ocean_view' => '🌊 Ocean View', 'wine_cellar' => '🍷 Wine Cellar',
                            'home_gym' => '💪 Private Gym', 'smart_home' => '📱 Smart Home Tech',
                            'outdoor_cinema' => '🎬 Outdoor Cinema', 'helipad' => '🚁 Helipad Access',
                            'gated_community' => '🛡️ Gated Community', 'guest_house' => '🏠 Guest House',
                            'solar_power' => '☀️ Solar Power', 'beach_front' => '🏖️ Beach Front',
                            'spa_sauna' => '🧖 Spa / Sauna', 'garage_3_car' => '🚗 3+ Car Garage',
                            'fire_pit' => '🔥 Luxury Fire Pit', 'concierge' => '🛎️ Concierge Service',
                            'walk_in_closet' => '👕 Walk-in Closet', 'high_ceilings' => '🏛️ High Ceilings',
                            'staff_quarters' => '🏠 Staff Quarters', 'backup_power' => '🔋 Backup Generator'
                        ];
                        foreach ( $luxury_amenities as $value => $label ) : ?>
                            <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; font-weight: 500;">
                                <input type="checkbox" name="amenities[]" value="<?php echo esc_attr( $value ); ?>"> 
                                <?php echo esc_html( $label ); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Property Media (The Roadmap)</h3>
                    
                    <div class="afcglide-form-full">
                        <label><strong>1. The Money Shot (Hero Image)</strong></label>
                        <p class="afcglide-description">The main image used in the header and grid.</p>
                        <input type="file" name="hero_image" accept="image/*">
                    </div>

                    <div class="afcglide-form-full">
                        <label><strong>2. The 3-Photo Stack</strong></label>
                        <p class="afcglide-description">Select exactly 3 photos for the side-stack display.</p>
                        <input type="file" name="stack_images[]" accept="image/*" multiple>
                    </div>

                    <div class="afcglide-form-full">
                        <label><strong>3. The Full Gallery Slider</strong></label>
                        <p class="afcglide-description">Upload all remaining photos for the lightbox slider.</p>
                        <input type="file" name="slider_images[]" accept="image/*" multiple>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Agent Branding</h3>
                    <div class="afcglide-form-grid">
                        <div>
                            <label>Agent Profile Photo</label>
                            <input type="file" name="agent_photo" accept="image/*">
                        </div>
                        <div>
                            <label>Agency Logo</label>
                            <input type="file" name="agency_logo" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="form-footer">
                    <button type="submit" class="afcglide-btn">
                        <span class="btn-text">Submit Luxury Listing</span>
                        <span class="btn-spinner"></span>
                    </button>
                </div>
            </form>
        </div>

        <?php
        return ob_get_clean();
    }
}