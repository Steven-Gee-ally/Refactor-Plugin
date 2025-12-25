<?php
/**
 * AFCGlide Listings - Premium Master Template (v5.5)
 * Refactored to Sync with GPS & Hero-16 Gallery
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$post_id   = get_the_ID();
$author_id = get_post_field( 'post_author', $post_id );

// 1. DATA PULL (Updated to match our EXACT Metabox keys)
$price      = get_post_meta( $post_id, '_listing_price', true );
$lat        = get_post_meta( $post_id, '_gps_lat', true ); // Sync with our GPS drawer
$lng        = get_post_meta( $post_id, '_gps_lng', true ); // Sync with our GPS drawer
$beds       = get_post_meta( $post_id, '_listing_beds', true );
$baths      = get_post_meta( $post_id, '_listing_baths', true );
$sqft       = get_post_meta( $post_id, '_listing_sqft', true );
$amenities  = get_post_meta( $post_id, '_listing_amenities', true ) ?: [];

// 2. BRANDING PULL (Pulling from User Meta)
$agent_name  = get_the_author_meta( 'display_name', $author_id );
$agent_phone = get_user_meta( $author_id, 'agent_phone', true );
$agent_photo = get_post_meta( $post_id, '_agent_photo', true ); // From our submission form
$agent_logo  = get_post_meta( $post_id, '_agency_logo', true );  // From our submission form

// 3. GALLERY PULL (Our JSON Hero-16 Drawers)
$slider_json = get_post_meta($post_id, '_slider_images_json', true);
$slider_ids  = json_decode($slider_json, true) ?: [];

$stack_json  = get_post_meta($post_id, '_stack_images_json', true);
$stack_ids   = json_decode($stack_json, true) ?: [];
?>

<div class="afcglide-single-root" style="max-width: 1400px; margin: 0 auto; padding: 0 20px; font-family: 'Inter', sans-serif;">

    <section class="afcglide-hero-block" style="display: flex; gap: 10px; margin-top: 20px; height: 600px;">
        <div class="afcglide-hero-left" style="flex: 2; position: relative; overflow: hidden; border-radius: 12px 0 0 12px;">
            <?php if ( has_post_thumbnail() ) : 
                the_post_thumbnail('full', ['style' => 'width:100%; height:100%; object-fit:cover;']); 
            endif; ?>
            <div style="position: absolute; bottom: 20px; left: 20px; background: rgba(0,0,0,0.6); color: #fff; padding: 10px 20px; border-radius: 5px; font-size: 24px; font-weight: 800;">
                $<?php echo number_format((float)$price); ?>
            </div>
        </div>

        <div class="afcglide-hero-right" style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
            <?php for($i=0; $i<3; $i++): ?>
                <div class="afcglide-stack-item" style="flex: 1; overflow: hidden; border-radius: <?php echo ($i == 0) ? '0 12px 0 0' : (($i == 2) ? '0 0 12px 0' : '0'); ?>;">
                    <?php if(isset($stack_ids[$i])) : ?>
                        <?php echo wp_get_attachment_image($stack_ids[$i], 'medium_large', false, ['style' => 'width:100%; height:100%; object-fit:cover;']); ?>
                    <?php else: ?>
                        <div style="background:#f4f4f4; height:100%; display: flex; align-items: center; justify-content: center; color: #ccc;">Photo <?php echo $i+1; ?></div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <section class="afc-branding-strip" style="display: grid; grid-template-columns: 1fr auto 1.2fr; align-items: center; background: #fff; padding: 30px; border-radius: 12px; margin: 40px 0; border: 1px solid #eee; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 20px;">
            <?php if($agent_photo): ?>
                <img src="<?php echo esc_url(wp_get_attachment_url($agent_photo)); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #f8f8f8;">
            <?php endif; ?>
            <div>
                <h3 style="margin:0; font-size: 22px; font-weight: 800;"><?php echo esc_html($agent_name); ?></h3>
                <p style="margin:0; color: #2271b1; font-weight: 600; font-size: 13px;">LISTING AGENT</p>
            </div>
        </div>

        <div class="brokerage-logo-center" style="padding: 0 40px; border-left: 1px solid #eee; border-right: 1px solid #eee;">
            <?php if($agent_logo): ?>
                <img src="<?php echo esc_url(wp_get_attachment_url($agent_logo)); ?>" style="max-height: 50px; width: auto;">
            <?php endif; ?>
        </div>

        <div style="padding-left: 40px; text-align: right;">
             <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $agent_phone); ?>" style="background: #25D366; color: #fff; padding: 12px 25px; border-radius: 8px; text-decoration: none; font-weight: 700; display: inline-block;">
                WhatsApp Agent
            </a>
        </div>
    </section>

    <div class="afc-details-grid" style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 60px; margin-bottom: 60px;">
        <div class="afc-main-text">
            <h1 style="font-size: 42px; margin-bottom: 30px;"><?php the_title(); ?></h1>
            
            <div style="display: flex; gap: 40px; margin-bottom: 40px; padding: 20px 0; border-top: 1px solid #eee; border-bottom: 1px solid #eee;">
                <div><span style="display:block; font-size:24px; font-weight:800;"><?php echo esc_html($beds); ?></span><small>Beds</small></div>
                <div><span style="display:block; font-size:24px; font-weight:800;"><?php echo esc_html($baths); ?></span><small>Baths</small></div>
                <div><span style="display:block; font-size:24px; font-weight:800;"><?php echo number_format((float)$sqft); ?></span><small>SqFt</small></div>
            </div>

            <div class="afc-description">
                <?php the_content(); ?>
            </div>
        </div>

        <div class="afc-amenities-sidebar">
            <div style="background: #fcfcfc; padding: 30px; border-radius: 12px; border: 1px solid #eee;">
                <h3 style="margin-top:0;">Amenities</h3>
                <?php foreach($amenities as $item): ?>
                    <div style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px;">
                        <span style="color:#2271b1;">✔</span> <?php echo esc_html($item); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <section class="afc-map-section" style="margin-bottom: 80px; padding-top: 40px; border-top: 1px solid #eee;">
        <h3 style="font-size: 28px; font-weight: 800; margin-bottom: 25px;">Location (GPS Coordinates)</h3>
        <div style="width: 100%; height: 450px; border-radius: 15px; overflow: hidden; border: 1px solid #ddd;">
            <iframe 
                width="100%" 
                height="100%" 
                frameborder="0" 
                src="https://maps.google.com/maps?q=<?php echo $lat; ?>,<?php echo $lng; ?>&hl=es;z=14&output=embed">
            </iframe>
        </div>
        <p style="margin-top: 15px; text-align: center;">
            <a href="https://www.google.com/maps/search/?api=1&query=<?php echo $lat; ?>,<?php echo $lng; ?>" target="_blank" style="color: #2271b1; font-weight: 700;">
                Open in Google Maps App 📍
            </a>
        </p>
    </section>

</div>

<?php get_footer(); ?>