<?php
/**
 * AFCGlide Listings v3 - High-End Asset Template (Option 2)
 * Full Integration: Hero Display, Specs Bar, Amenities Grid, and Sticky Sidebar.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

// 1. DATA HARVESTING (Updated to match Metabox v4.4.2)
$post_id  = get_the_ID();
$price    = get_post_meta($post_id, '_listing_price', true);
$address  = get_post_meta($post_id, '_listing_address', true);
$hero_id  = get_post_meta($post_id, '_hero_image_id', true);
$wa_brand_color = get_option('afc_whatsapp_color', '#25D366'); // Pulls from your new Settings page

// MATCHING YOUR METABOX KEYS EXACTLY:
$beds     = get_post_meta($post_id, '_listing_beds', true); 
$baths    = get_post_meta($post_id, '_listing_baths', true); 
$sqft     = get_post_meta($post_id, '_listing_sqft', true);

// GALLERY (Using your "Shutter-Bug" Slider data)
$gallery  = get_post_meta($post_id, '_listing_gallery_ids', true) ?: [];

// AMENITIES (Matches your Section 6)
$amenities = get_post_meta($post_id, '_listing_amenities', true);

// AGENT DATA (Matches your Section 1)
$a_name   = get_post_meta($post_id, '_agent_name_display', true);
$a_phone  = get_post_meta($post_id, '_agent_phone_display', true);
$a_photo  = get_post_meta($post_id, '_agent_photo_id', true);
$a_img    = $a_photo ? wp_get_attachment_url($a_photo) : AFCG_URL . 'assets/images/placeholder-agent.png';

// CLEAN PHONE FOR LINKS (Strips () - and spaces)
$clean_phone = preg_replace('/[^0-9]/', '', $a_phone);
?>

<div class="afcglide-listing-container">
    
    <section class="afc-hero-gallery-section">
        <div class="afc-hero-main-display">
            <div class="afc-main-image-container">
                <?php if($hero_id): 
                    echo wp_get_attachment_image($hero_id, 'full', false, ['class' => 'afc-hero-image']); 
                else: ?>
                    <div class="afc-hero-placeholder"><span class="afc-placeholder-icon">🏙️</span></div>
                <?php endif; ?>
                
                <div class="afc-price-badge-overlay">
                    <span class="afc-price-amount"><?php echo $price ? '$' . number_format($price) : 'Contact for Price'; ?></span>
                </div>

                <div class="afc-photo-counter">
                    <span class="afc-camera-icon">📷</span> <?php echo count($gallery) + 1; ?> Photos
                </div>
            </div>
        </div>

        <div class="afc-thumbnail-grid">
            <?php 
            $thumbs = array_slice($gallery, 0, 4);
            foreach($thumbs as $index => $id): 
                $is_last = ($index === 3 && count($gallery) > 4);
            ?>
                <div class="afc-thumb-item">
                    <?php echo wp_get_attachment_image($id, 'medium_large', false, ['class' => 'afc-thumb-image']); ?>
                    <?php if($is_last): ?>
                        <div class="afc-view-all-overlay">+<?php echo count($gallery) - 3; ?></div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </section>

    <div class="afc-content-wrapper">
        <main class="afc-main-content">
            <div class="afc-title-section">
                <h1 class="afc-property-title"><?php the_title(); ?></h1>
                <p class="afc-property-address">📍 <?php echo esc_html($address); ?></p>
            </div>

            <div class="afc-specs-bar-modern">
                <div class="afc-spec-item">
                    <span class="afc-spec-icon">🛏️</span>
                    <div class="afc-spec-text">
                        <strong><?php echo esc_html($beds); ?></strong>
                        <small>Beds</small>
                    </div>
                </div>
                <div class="afc-spec-item">
                    <span class="afc-spec-icon">🛁</span>
                    <div class="afc-spec-text">
                        <strong><?php echo esc_html($baths); ?></strong>
                        <small>Baths</small>
                    </div>
                </div>
                <div class="afc-spec-item">
                    <span class="afc-spec-icon">📐</span>
                    <div class="afc-spec-text">
                        <strong><?php echo number_format($sqft); ?></strong>
                        <small>Sq Ft</small>
                    </div>
                </div>
            </div>

            <div class="afc-description-section">
                <h2 class="afc-section-heading">Property Narrative</h2>
                <div class="afc-description-content">
                    <?php the_content(); ?>
                </div>
            </div>

            <?php if ( ! empty( $amenities ) && is_array( $amenities ) ) : ?>
            <div class="afc-amenities-section">
                <h2 class="afc-section-heading">Premium Amenities</h2>
                <div class="afc-amenities-grid-modern">
                    <?php 
                    $amenity_icons = [
                        'Gourmet Kitchen' => '🍳', 'Infinity Pool' => '🌊', 'Ocean View' => '🌅', 'Wine Cellar' => '🍷',
                        'Private Gym' => '🏋️', 'Smart Home Tech' => '📱', 'Outdoor Cinema' => '🎬', 'Helipad Access' => '🚁',
                        'Gated Community' => '🏰', 'Guest House' => '🏠', 'Solar Power' => '☀️', 'Beach Front' => '🏖️',
                        'Spa / Sauna' => '🧖', '3+ Car Garage' => '🚗', 'Luxury Fire Pit' => '🔥', 'Concierge Service' => '🛎️',
                        'Walk-in Closet' => '👗', 'High Ceilings' => '⤴️', 'Staff Quarters' => '👨‍💼', 'Backup Generator' => '⚡'
                    ];
                    foreach ( $amenities as $amenity ) : 
                        $display_icon = isset($amenity_icons[$amenity]) ? $amenity_icons[$amenity] : '💎';
                    ?>
                        <div class="afc-amenity-item">
                            <span class="afc-amenity-icon"><?php echo $display_icon; ?></span>
                            <?php echo esc_html( $amenity ); ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </main>

        <aside class="afc-sidebar-modern">
            <div class="afc-agent-card-luxury">
                <div class="afc-agent-header">
                    <div class="afc-agent-avatar"><img src="<?php echo $a_img; ?>" class="afc-agent-photo"></div>
                    <div class="afc-agent-info">
                        <h4 class="afc-agent-name"><?php echo esc_html($a_name); ?></h4>
                        <p class="afc-agent-title">Listing Specialist</p>
                    </div>
                </div>
                <div class="afc-agent-actions">
                    <a href="tel:<?php echo $clean_phone; ?>" class="afc-btn-email">📞 Call Agent</a>
                    <a href="https://wa.me/<?php echo $clean_phone; ?>" class="afc-btn-whatsapp">💬 WhatsApp</a>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php 
$show_wa = get_post_meta(get_the_ID(), '_show_floating_whatsapp', true);

if ( $show_wa === '1' && !empty($clean_phone) ) : 
?>


<a href="https://wa.me/<?php echo $clean_phone; ?>" class="afc-whatsapp-float" target="_blank" rel="nofollow">
    <svg viewBox="0 0 32 32" class="afc-wa-icon"><path d="M16 0c-8.837 0-16 7.163-16 16 0 2.825.737 5.588 2.137 8.137l-2.137 7.863 8.1-.2.1.2c2.487 1.463 5.112 2.112 7.9 2.112 8.837 0 16-7.163 16-16s-7.163-16-16-16zm8.287 21.825c-.337.95-1.712 1.838-2.737 2.05-.688.138-1.588.25-4.6-1.013-3.862-1.612-6.362-5.538-6.55-5.8-.188-.262-1.525-2.025-1.525-3.862 0-1.838.963-2.738 1.3-3.113.337-.375.75-.463 1-.463s.5 0 .712.013c.225.013.525-.088.825.638.3.713 1.013 2.475 1.1 2.663.088.188.15.413.025.663-.125.263-.188.425-.375.65-.188.225-.412.513-.587.688-.2.2-.412.412-.175.812.238.4.1.863 2.087 2.625 1.637 1.45 3.012 1.9 3.437 2.113.425.213.675.175.925-.113.25-.288 1.075-1.25 1.362-1.688.3-.425.588-.363.988-.212.4.15 2.525 1.188 2.962 1.4.438.213.738.313.838.488.1.175.1.988-.237 1.938z" fill="currentColor"/></svg>
    <span class="afc-wa-tooltip">Chat with Agent</span>
</a>
<?php endif; ?>

<?php get_footer(); ?>