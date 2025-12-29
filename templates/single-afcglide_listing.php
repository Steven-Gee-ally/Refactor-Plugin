<?php
/**
 * AFCGlide Listings - Premium Master Template (v5.7)
 * Fixed Meta Keys & Bilingual Logic
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$post_id   = get_the_ID();
$author_id = get_post_field( 'post_author', $post_id );

// 1. PRIMARY DATA PULL
$price      = get_post_meta( $post_id, '_listing_price', true );
$lat        = get_post_meta( $post_id, '_gps_lat', true );
$lng        = get_post_meta( $post_id, '_gps_lng', true );
$beds       = get_post_meta( $post_id, '_listing_beds', true );
$baths      = get_post_meta( $post_id, '_listing_baths', true );
$sqft       = get_post_meta( $post_id, '_listing_sqft', true );
$amenities  = get_post_meta( $post_id, '_listing_amenities', true ) ?: [];

// 2. BRANDING PULL - MATCHING DATABASE KEYS
$agent_name  = get_the_author_meta( 'display_name', $author_id );
$agent_phone = get_user_meta( $author_id, 'agent_phone', true );
$agent_photo = get_post_meta( $post_id, '_agent_photo_id', true ); 
$agent_logo  = get_post_meta( $post_id, '_agency_logo_id', true ); 

// 3. GALLERY PULL - ARRAY OF IDs
$stack_ids   = get_post_meta($post_id, '_property_stack_ids', true) ?: [];

// 4. BILINGUAL LOGIC
$current_lang = get_locale(); 
$is_spanish = (strpos($current_lang, 'es') !== false);

$amenity_labels = [
    'ocean_view'      => $is_spanish ? '🌊 Vista al Mar' : '🌊 Ocean View',
    'beach_front'     => $is_spanish ? '🏖️ Frente a la Playa' : '🏖️ Beach Front',
    'mountain_view'   => $is_spanish ? '⛰️ Vista a la Montaña' : '⛰️ Mountain View',
    'jungle_setting'  => $is_spanish ? '🐒 Entorno de Selva' : '🐒 Jungle Setting',
    'infinity_pool'   => $is_spanish ? '♾️ Piscina Infinita' : '♾️ Infinity Pool',
    'gourmet_kitchen' => $is_spanish ? '👨‍🍳 Cocina de Chef' : '👨‍🍳 Gourmet Kitchen',
    'wine_cellar'     => $is_spanish ? '🍷 Bodega de Vinos' : '🍷 Wine Cellar',
    'home_gym'        => $is_spanish ? '💪 Gimnasio Privado' : '💪 Private Gym',
    'spa_sauna'       => $is_spanish ? '🧖 Spa y Sauna' : '🧖 Spa / Sauna',
    'home_cinema'     => $is_spanish ? '🎬 Cine en Casa' : '🎬 Home Cinema',
    'high_speed_fiber' => $is_spanish ? '📶 Fibra Óptica' : '📶 High-Speed Fiber',
    'solar_power'     => $is_spanish ? '☀️ Energía Solar' : '☀️ Solar Power',
    'backup_power'    => $is_spanish ? '🔋 Planta Eléctrica' : '🔋 Backup Generator',
    'gated_community' => $is_spanish ? '🛡️ Comunidad Privada' : '🛡️ Gated Community',
    'security_24_7'   => $is_spanish ? '👮 Seguridad 24/7' : '👮 24/7 Security',
    'helipad'         => $is_spanish ? '🚁 Helipuerto' : '🚁 Helipad Access',
    'guest_house'     => $is_spanish ? '🏠 Casa de Huéspedes' : '🏠 Guest House', // FIXED TYPO HERE
    'outdoor_bbq'     => $is_spanish ? '🍖 Rancho de BBQ' : '🍖 Outdoor BBQ',
    'high_ceilings'   => $is_spanish ? '🏛️ Cielos Rasos Altos' : '🏛️ High Ceilings',
    'garage_3_car'    => $is_spanish ? '🚗 Garaje para 3+ Carros' : '🚗 3+ Car Garage'
];
?>

<div class="afcglide-single-root" style="max-width: 1400px; margin: 0 auto; padding: 0 20px; font-family: 'Inter', sans-serif;">

    <section class="afcglide-hero-block" style="display: flex; gap: 10px; margin-top: 20px; height: 600px;">
        <div class="afcglide-hero-left" style="flex: 2; position: relative; overflow: hidden; border-radius: 12px 0 0 12px;">
            <?php if ( has_post_thumbnail() ) : 
                the_post_thumbnail('full', ['style' => 'width:100%; height:100%; object-fit:cover;']); 
            endif; ?>
            <div style="position: absolute; bottom: 20px; left: 20px; background: rgba(0,0,0,0.8); color: #fff; padding: 10px 20px; border-radius: 5px; font-size: 24px; font-weight: 800;">
                $<?php echo number_format((float)$price); ?>
            </div>
        </div>

        <div class="afcglide-hero-right" style="flex: 1; display: flex; flex-direction: column; gap: 10px;">
            <?php for($i=0; $i<3; $i++): ?>
                <div class="afcglide-stack-item" style="flex: 1; overflow: hidden; background: #f1f5f9; border-radius: <?php echo ($i == 0) ? '0 12px 0 0' : (($i == 2) ? '0 0 12px 0' : '0'); ?>;">
                    <?php if(!empty($stack_ids[$i])) : ?>
                        <?php echo wp_get_attachment_image($stack_ids[$i], 'medium_large', false, ['style' => 'width:100%; height:100%; object-fit:cover;']); ?>
                    <?php else: ?>
                        <div style="height:100%; display: flex; align-items: center; justify-content: center; color: #cbd5e1; font-size: 12px;">PHOTO <?php echo $i+1; ?></div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <section class="afc-branding-strip" style="display: grid; grid-template-columns: 1fr auto 1.2fr; align-items: center; background: #fff; padding: 30px; border-radius: 12px; margin: 40px 0; border: 1px solid #e2e8f0; box-shadow: 0 10px 30px rgba(0,0,0,0.05);">
        <div style="display: flex; align-items: center; gap: 20px;">
            <?php if($agent_photo): ?>
                <img src="<?php echo esc_url(wp_get_attachment_url($agent_photo)); ?>" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover; border: 3px solid #f8f8f8;">
            <?php endif; ?>
            <div>
                <h3 style="margin:0; font-size: 22px; font-weight: 800;"><?php echo esc_html($agent_name); ?></h3>
                <p style="margin:0; color: #4f46e5; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 1px;">LISTING AGENT</p>
            </div>
        </div>

        <div class="brokerage-logo-center" style="padding: 0 40px; border-left: 1px solid #eee; border-right: 1px solid #eee;">
            <?php if($agent_logo): ?>
                <img src="<?php echo esc_url(wp_get_attachment_url($agent_logo)); ?>" style="max-height: 50px; width: auto;">
            <?php endif; ?>
        </div>

        <div style="padding-left: 40px; text-align: right;">
             <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $agent_phone); ?>" style="background: #25D366; color: #fff; padding: 14px 28px; border-radius: 8px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 10px;">
                <span>📱</span> WhatsApp Agent
            </a>
        </div>
    </section>

    <div class="afc-details-grid" style="display: grid; grid-template-columns: 1.6fr 1fr; gap: 60px; margin-bottom: 60px;">
        <div class="afc-main-text">
            <h1 style="font-size: 42px; margin-bottom: 30px; color: #1e293b;"><?php the_title(); ?></h1>
            <div class="afc-description" style="line-height: 1.8; color: #334155; font-size: 17px;">
                <?php the_content(); ?>
            </div>
        </div>

        <div class="afc-amenities-sidebar">
            <div style="background: #f8fafc; padding: 30px; border-radius: 12px; border: 1px solid #e2e8f0;">
                <h3 style="margin-top:0; font-size: 20px; font-weight: 800; color: #1e293b; margin-bottom: 20px;">Features</h3>
                <?php 
                if ( ! empty( $amenities ) && is_array( $amenities ) ) :
                    foreach ( $amenities as $slug ) : 
                        $display_text = isset($amenity_labels[$slug]) ? $amenity_labels[$slug] : ucwords(str_replace('_', ' ', $slug));
                        ?>
                        <div style="margin-bottom: 14px; display: flex; align-items: center; gap: 12px; font-weight: 500; color: #334155;">
                            <span style="color: #4f46e5; font-size: 12px;">✦</span> <?php echo esc_html($display_text); ?>
                        </div>
                    <?php endforeach; 
                endif; ?>
            </div>
        </div>
    </div>

    <section class="afc-map-section" style="margin-bottom: 80px; padding-top: 40px; border-top: 1px solid #f1f5f9;">
        <div style="width: 100%; height: 450px; border-radius: 15px; overflow: hidden; border: 1px solid #e2e8f0;">
            <iframe 
                width="100%" height="100%" frameborder="0" 
                src="https://maps.google.com/maps?q=<?php echo $lat; ?>,<?php echo $lng; ?>&hl=es&z=15&output=embed">
            </iframe>
        </div>
    </section>

</div>

<?php get_footer(); ?>