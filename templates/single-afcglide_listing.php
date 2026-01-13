<?php
/**
 * AFCGlide Listings - Universal Premium Template v6.4
 * FIXED: Core Amenities Variable & Logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$post_id   = get_the_ID();
$author_id = get_post_field( 'post_author', $post_id );

// ==========================================
// 1. CORE PROPERTY DATA (The Engine)
// ==========================================
$price     = get_post_meta( $post_id, '_listing_price', true );
$beds      = get_post_meta( $post_id, '_listing_beds', true );
$baths     = get_post_meta( $post_id, '_listing_baths', true );
$sqft      = get_post_meta( $post_id, '_listing_sqft', true );
$address   = get_post_meta( $post_id, '_property_address', true );
$lat       = get_post_meta( $post_id, '_gps_lat', true );
$lng       = get_post_meta( $post_id, '_gps_lng', true );

// This is the line we were missing!
$amenities = get_post_meta( $post_id, '_listing_amenities', true ) ?: [];

// ==========================================
// 2. MEDIA & AGENT DATA
// ==========================================
$hero_id    = get_post_meta( $post_id, '_hero_image_id', true ) ?: get_post_thumbnail_id();
$stack_json = get_post_meta( $post_id, '_stack_images_json', true );
$stack_ids  = is_string( $stack_json ) ? json_decode( $stack_json, true ) : [];
$is_spanish = ( strpos( get_locale(), 'es' ) !== false );

$agent_name     = get_post_meta( $post_id, '_agent_name_display', true ) ?: get_the_author_meta( 'display_name', $author_id );
$whatsapp_raw   = get_post_meta( $post_id, '_agent_phone_display', true ) ?: get_user_meta( $author_id, 'agent_phone', true );
$whatsapp_clean = preg_replace('/[^0-9]/', '', $whatsapp_raw);
?>

<div class="afcglide-wrapper">

    <section class="afc-hero-block">
        <div class="afc-hero-main">
            <?php if ( $hero_id ) : ?>
                <?php echo wp_get_attachment_image( $hero_id, 'full' ); ?>
            <?php endif; ?>
            <div class="afc-price-badge">$<?php echo number_format( (float)$price ); ?></div>
        </div>

        <div class="afc-hero-stack">
            <?php for ( $i = 0; $i < 3; $i++ ) : ?>
                <div class="afc-stack-item">
                    <?php if ( isset( $stack_ids[$i] ) ) : ?>
                        <?php echo wp_get_attachment_image( $stack_ids[$i], 'medium_large' ); ?>
                    <?php else : ?>
                        <div class="afc-stack-placeholder">Photo <?php echo $i + 1; ?></div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <div class="afc-content-grid">
        <div class="afc-main-content">
            <h1 class="afc-title"><?php the_title(); ?></h1>
            <p class="afc-map-address">📍 <?php echo esc_html($address); ?></p>

            <div class="afc-specs-bar">
                <div class="afc-spec-item">🛏️ <strong><?php echo $beds; ?></strong> <?php echo $is_spanish ? 'Hab' : 'Beds'; ?></div>
                <div class="afc-spec-item">🛁 <strong><?php echo $baths; ?></strong> <?php echo $is_spanish ? 'Baños' : 'Baths'; ?></div>
                <div class="afc-spec-item">📐 <strong><?php echo number_format($sqft); ?></strong> sqft</div>
            </div>

            <div class="afc-description">
                <?php the_content(); ?>
            </div>

            <?php if ( ! empty( $amenities ) && is_array( $amenities ) ) : ?>
                <div class="afc-amenities-section">
                    <h3><?php echo $is_spanish ? 'Amenidades' : 'Amenities'; ?></h3>
                    <div class="afc-amenities-grid">
                        <?php foreach ( $amenities as $amenity ) : ?>
                            <div class="afc-amenity-item">
                                <span class="check">✓</span> <?php echo esc_html( $amenity ); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ( $lat && $lng ) : ?>
                <div class="afc-map-section">
                    <h3><?php echo $is_spanish ? 'Ubicación' : 'Location'; ?></h3>
                    <div class="afc-map-embed">
                        <iframe 
                            width="100%" 
                            height="450" 
                            style="border:0; border-radius:16px;" 
                            loading="lazy" 
                            src="https://maps.google.com/maps?q=<?php echo $lat; ?>,<?php echo $lng; ?>&z=15&output=embed">
                        </iframe>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <aside class="afc-sidebar">
            <div class="afc-agent-card">
                <div class="afc-agent-name"><?php echo esc_html( $agent_name ); ?></div>
                <div class="afc-contact-buttons">
                    <a href="https://wa.me/<?php echo $whatsapp_clean; ?>" class="afc-btn afc-btn-whatsapp" target="_blank">💬 WhatsApp</a>
                    <a href="mailto:<?php echo get_the_author_meta( 'user_email', $author_id ); ?>" class="afc-btn afc-btn-email">✉️ Email</a>
                </div>
            </div>
        </aside>
    </div>
</div>

<?php get_footer(); ?>