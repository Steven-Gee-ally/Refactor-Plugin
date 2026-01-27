<?php
/**
 * AFCGlide Single Listing - Premium Bilingual Edition
 * Version 5.2 - "The Costa Rica Master"
 * Design by USA / Logic by AFCGlide
 */

if ( ! defined( 'ABSPATH' ) ) exit;

use AFCGlide\Core\Constants as C;

get_header();

// 1. DATA HARVESTING
$post_id = get_the_ID();
$price   = C::get_meta($post_id, C::META_PRICE);
$address = C::get_meta($post_id, C::META_ADDRESS);
$beds    = C::get_meta($post_id, C::META_BEDS);
$baths   = C::get_meta($post_id, C::META_BATHS);
$sqft    = C::get_meta($post_id, C::META_SQFT);
$hero_id = C::get_meta($post_id, C::META_HERO_ID);
$gallery_ids = C::get_meta($post_id, C::META_GALLERY_IDS) ?: [];

// Agent & UI Config
$agent_name  = C::get_meta($post_id, C::META_AGENT_NAME);
$agent_phone = C::get_meta($post_id, C::META_AGENT_PHONE);
$agent_photo = C::get_meta($post_id, C::META_AGENT_PHOTO);
$agent_img   = $agent_photo ? wp_get_attachment_image_url($agent_photo, 'thumbnail') : AFCG_URL . 'assets/images/placeholder-agent.png';
$wa_color    = get_option('afc_whatsapp_color', '#25D366');
$clean_phone = preg_replace('/[^0-9]/', '', $agent_phone);

// 2. BILINGUAL CONTENT SYNC
$intro     = C::get_meta($post_id, C::META_INTRO);
$narrative = C::get_meta($post_id, C::META_NARRATIVE);

// Check if TranslatePress or similar is asking for Spanish
if ( function_exists('afcglide_get_current_lang') && afcglide_get_current_lang() === 'es' ) {
    $intro_es = C::get_meta($post_id, C::META_INTRO_ES);
    $narrative_es = C::get_meta($post_id, C::META_NARRATIVE_ES);
    
    if (!empty($intro_es)) $intro = $intro_es;
    if (!empty($narrative_es)) $narrative = $narrative_es;
}

// 3. MAP / GPS LOGIC
$lat = C::get_meta($post_id, C::META_GPS_LAT);
$lng = C::get_meta($post_id, C::META_GPS_LNG);

// Build Gallery
$all_images = [];
if ($hero_id) {
    $all_images[] = [
        'url'   => wp_get_attachment_image_url($hero_id, 'full'),
        'thumb' => wp_get_attachment_image_url($hero_id, 'medium')
    ];
}
foreach ($gallery_ids as $img_id) {
    $all_images[] = [
        'url'   => wp_get_attachment_image_url($img_id, 'full'),
        'thumb' => wp_get_attachment_image_url($img_id, 'medium')
    ];
}
?>

<div class="afcglide-wrapper">
    
    <!-- CINEMATIC HERO STAGE -->
    <div class="afc-cinematic-stage">
        <div class="afcglide-hero-main">
            <img id="afc-main-view" src="<?php echo esc_url($all_images[0]['url'] ?? ''); ?>" alt="<?php the_title_attribute(); ?>">
            
            <?php if ( $price ): ?>
            <div class="afcglide-price-badge">
                $<?php echo number_format($price); ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- 4-UP FILMSTRIP -->
        <div class="afc-filmstrip-wrapper">
            <div class="afc-filmstrip-container" id="afc-filmstrip">
                <?php foreach ($all_images as $idx => $img): ?>
                    <div class="afc-filmstrip-item" onclick="afcUpdateMainView(this, '<?php echo esc_url($img['url']); ?>')">
                        <img src="<?php echo esc_url($img['thumb']); ?>" alt="Gallery image <?php echo $idx + 1; ?>">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- MAIN GRID LAYOUT -->
    <div class="afc-listing-grid">
        
        <!-- LEFT COLUMN: SPECS & CONTENT -->
        <main class="afc-main-content">
            
            <h1 class="entry-title" style="font-size: 42px; font-weight: 900; margin-bottom: 10px;"><?php the_title(); ?></h1>
            <p style="color: var(--afc-gray); font-size: 18px; margin-bottom: 30px;"><?php echo esc_html($address); ?></p>

            <div class="afcglide-specs-bar">
                <div class="afcglide-spec-item">
                    <label>BEDROOMS</label>
                    <strong><?php echo esc_html($beds); ?></strong>
                </div>
                <div class="afcglide-spec-item">
                    <label>BATHROOMS</label>
                    <strong><?php echo esc_html($baths); ?></strong>
                </div>
                <div class="afcglide-spec-item">
                    <label>SQUARE FEET</label>
                    <strong><?php echo number_format((float)$sqft); ?></strong>
                </div>
            </div>

            <div class="afc-description">
                <h3 style="font-size: 24px; font-weight: 800; margin-bottom: 20px;">Property Narrative</h3>
                <div class="afc-narrative-content">
                    <p style="font-size: 18px; color: #475569; margin-bottom: 30px; line-height: 1.8;"><?php echo esc_html($intro); ?></p>
                    <?php echo wpautop(wp_kses_post($narrative)); ?>
                </div>
            </div>

            <?php if($lat && $lng): ?>
            <div class="afc-map-block" style="margin-top: 60px;">
                <h3 style="font-size: 24px; font-weight: 800; margin-bottom: 20px;">Asset Location</h3>
                <iframe 
                    frameborder="0" 
                    src="https://maps.google.com/maps?q=<?php echo esc_attr($lat); ?>,<?php echo esc_attr($lng); ?>&z=15&output=embed"
                    style="width: 100%; height: 450px; border-radius: var(--afc-radius); filter: grayscale(0.1); border: 1px solid var(--afc-border);">
                </iframe>
            </div>
            <?php endif; ?>

        </main>

        <!-- RIGHT COLUMN: STICKY AGENT CARD -->
        <aside>
            <div class="afcglide-agent-card">
                <div class="afc-agent-photo-wrap">
                    <img src="<?php echo esc_url($agent_img); ?>" alt="<?php echo esc_attr($agent_name); ?>">
                </div>
                
                <h3 style="margin: 0 0 5px 0; font-size: 22px; font-weight: 800;"><?php echo esc_html($agent_name); ?></h3>
                <p style="margin: 0 0 25px 0; color: var(--afc-gray); font-size: 12px; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700;">Listing Agent</p>
                
                <a href="https://wa.me/<?php echo esc_attr($clean_phone); ?>" class="afc-btn-primary" style="background-color: <?php echo esc_attr($wa_color); ?>;">
                    WhatsApp Enquiry
                </a>
                
                <a href="tel:<?php echo esc_attr($clean_phone); ?>" style="display: block; text-align: center; color: var(--afc-gray); margin-top: 20px; text-decoration: none; font-weight: 600; font-size: 14px;">
                   Direct: <?php echo esc_html($agent_phone); ?>
                </a>
            </div>
        </aside>

    </div>
</div>

<script>
(function() {
    window.afcUpdateMainView = function(el, url) {
        var mainImg = document.getElementById('afc-main-view');
        mainImg.style.opacity = '0';
        setTimeout(function() {
            mainImg.src = url;
            mainImg.style.opacity = '1';
        }, 300);
        
        // Update active class on thumbnails if needed (though not strictly required with current CSS)
        // document.querySelectorAll('.afc-filmstrip-item').forEach(t => t.classList.remove('active'));
        // el.classList.add('active'); 
    };
})();
</script>

<?php get_footer(); ?>