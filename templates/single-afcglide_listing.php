<?php
/**
 * AFCGlide Listings - Universal Premium Template v6.0
 * Clean PHP/HTML only - External CSS/JS
 * Works for all price points: $100K - $10M+
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

$post_id   = get_the_ID();
$author_id = get_post_field( 'post_author', $post_id );

// Core Property Data
$price     = get_post_meta( $post_id, '_listing_price', true );
$beds      = get_post_meta( $post_id, '_listing_beds', true );
$baths     = get_post_meta( $post_id, '_listing_baths', true );
$sqft      = get_post_meta( $post_id, '_listing_sqft', true );
$address   = get_post_meta( $post_id, '_property_address', true );
$lat       = get_post_meta( $post_id, '_gps_lat', true );
$lng       = get_post_meta( $post_id, '_gps_lng', true );
$amenities = get_post_meta( $post_id, '_listing_amenities', true ) ?: [];

// Agent Information
$agent_name     = get_post_meta( $post_id, '_agent_name_display', true ) ?: get_the_author_meta( 'display_name', $author_id );
$agent_phone    = get_post_meta( $post_id, '_agent_phone_display', true ) ?: get_user_meta( $author_id, 'agent_phone', true );
$agent_whatsapp = get_user_meta( $author_id, 'agent_whatsapp', true ) ?: $agent_phone;
$agent_photo_id = get_post_meta( $post_id, '_agent_photo_id', true );
$show_whatsapp  = get_post_meta( $post_id, '_show_floating_whatsapp', true );

// Gallery Data
$hero_id    = get_post_meta( $post_id, '_hero_image_id', true ) ?: get_post_thumbnail_id();
$stack_json = get_post_meta( $post_id, '_stack_images_json', true );
$stack_ids  = is_string( $stack_json ) ? json_decode( $stack_json, true ) : $stack_json;
$stack_ids  = is_array( $stack_ids ) ? $stack_ids : [];
$slider_ids = get_post_meta( $post_id, '_property_slider_ids', true ) ?: [];

// Company Logo
$company_logo_id = get_option( 'afcglide_company_logo_id', 0 );

// Bilingual Support
$is_spanish = ( strpos( get_locale(), 'es' ) !== false );

$amenity_labels = [
    'infinity_pool' => $is_spanish ? '♾️ Piscina Infinita' : '♾️ Infinity Pool',
    'wine_cellar' => $is_spanish ? '🍷 Bodega' : '🍷 Wine Cellar',
    'home_theater' => $is_spanish ? '🎬 Cine' : '🎬 Theater',
    'smart_home' => $is_spanish ? '📱 Casa Inteligente' : '📱 Smart Home',
    'private_gym' => $is_spanish ? '💪 Gimnasio' : '💪 Gym',
    'ocean_view' => $is_spanish ? '🌊 Vista Mar' : '🌊 Ocean View',
    'helipad' => $is_spanish ? '🚁 Helipuerto' : '🚁 Helipad',
    'gourmet_kit' => $is_spanish ? '👨‍🍳 Cocina Gourmet' : '👨‍🍳 Gourmet Kitchen',
    'spa_sauna' => $is_spanish ? '🧖 Spa' : '🧖 Spa & Sauna',
    'gated_entry' => $is_spanish ? '🛡️ Privado' : '🛡️ Gated',
    'tennis_court' => $is_spanish ? '🎾 Tenis' : '🎾 Tennis',
    'guest_house' => $is_spanish ? '🏡 Casa Huéspedes' : '🏡 Guest House',
    'elevator' => $is_spanish ? '🛗 Elevador' : '🛗 Elevator',
    'outdoor_kit' => $is_spanish ? '🔥 Cocina Exterior' : '🔥 Outdoor Kitchen',
    'beach_front' => $is_spanish ? '🏖️ Playa' : '🏖️ Beach Front',
    'solar' => $is_spanish ? '☀️ Solar' : '☀️ Solar Power',
    'staff_quarters' => $is_spanish ? '🧹 Servicio' : '🧹 Staff Quarters',
    'garage_4' => $is_spanish ? '🏎️ Garaje 4+' : '🏎️ 4+ Garage',
    'fire_pit' => $is_spanish ? '🔥 Fogata' : '🔥 Fire Pit',
    'dock' => $is_spanish ? '🛥️ Muelle' : '🛥️ Dock'
];

$whatsapp_clean = preg_replace('/[^0-9]/', '', $agent_whatsapp);

// Enqueue external CSS and JS
wp_enqueue_style( 'glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/css/glightbox.min.css', [], '3.2.0' );
wp_enqueue_style( 'afcglide-single-listing', AFCG_URL . 'assets/css/single-listing.css', [], AFCG_VERSION );
wp_enqueue_script( 'glightbox', 'https://cdn.jsdelivr.net/npm/glightbox/dist/js/glightbox.min.js', [], '3.2.0', true );
?>

<div class="afcglide-wrapper">

    <!-- Sticky Top Bar -->
    <div class="afc-top-bar">
        <div class="afc-logo-container">
            <?php if ( $company_logo_id ) : ?>
                <?php echo wp_get_attachment_image( $company_logo_id, 'medium' ); ?>
            <?php else : ?>
                <strong style="font-size: 20px; color: var(--afc-primary);">AFCGlide</strong>
            <?php endif; ?>
        </div>
        <div class="afc-top-actions">
            <button class="afc-action-btn" onclick="if(navigator.share){navigator.share({title:'<?php echo esc_js(get_the_title()); ?>',url:window.location.href})}else{alert('<?php echo esc_js( $is_spanish ? 'Copie el enlace' : 'Copy the URL' ); ?>')}">
                📤 <?php echo $is_spanish ? 'Compartir' : 'Share'; ?>
            </button>
            <button class="afc-action-btn" onclick="window.print()">
                🖨️ <?php echo $is_spanish ? 'Imprimir' : 'Print'; ?>
            </button>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="afc-hero-block">
        
        <!-- Main Hero Image -->
        <div class="afc-hero-main">
            <?php if ( $hero_id ) : ?>
                <a href="<?php echo esc_url( wp_get_attachment_url( $hero_id ) ); ?>" class="glightbox">
                    <?php echo wp_get_attachment_image( $hero_id, 'full' ); ?>
                </a>
            <?php elseif ( has_post_thumbnail() ) : ?>
                <a href="<?php echo esc_url( get_the_post_thumbnail_url( $post_id, 'full' ) ); ?>" class="glightbox">
                    <?php the_post_thumbnail( 'full' ); ?>
                </a>
            <?php endif; ?>
            <div class="afc-price-badge">
                $<?php echo number_format( (float)$price ); ?>
            </div>
        </div>

        <!-- Gallery Slider -->
        <div class="afc-hero-slider">
            <?php if ( ! empty( $slider_ids ) ) : ?>
                <?php foreach ( $slider_ids as $img_id ) : ?>
                    <div class="afc-slider-item">
                        <?php echo wp_get_attachment_image( $img_id, 'medium' ); ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 3-Photo Stack -->
        <div class="afc-hero-stack">
            <?php for ( $i = 0; $i < 3; $i++ ) : ?>
                <div class="afc-stack-item">
                    <?php if ( isset( $stack_ids[$i] ) ) : ?>
                        <a href="<?php echo esc_url( wp_get_attachment_url( $stack_ids[$i] ) ); ?>" class="glightbox">
                            <?php echo wp_get_attachment_image( $stack_ids[$i], 'medium_large' ); ?>
                        </a>
                    <?php else : ?>
                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;color:#cbd5e1;font-size:14px;">
                            <?php echo $is_spanish ? 'Foto' : 'Photo'; ?> <?php echo $i + 1; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
    </section>

    <!-- Main Content Grid -->
    <div class="afc-content-grid">
        
        <!-- Left Column: Property Details -->
        <div class="afc-main-content">
            
            <!-- Title -->
            <h1 class="afc-title"><?php the_title(); ?></h1>

            <!-- Property Specs Bar -->
            <div class="afc-specs-bar">
                <?php if ( $beds ) : ?>
                    <div class="afc-spec-item">
                        🛏️ <strong><?php echo $beds; ?></strong> <?php echo $is_spanish ? 'Habitaciones' : 'Beds'; ?>
                    </div>
                <?php endif; ?>
                <?php if ( $baths ) : ?>
                    <div class="afc-spec-item">
                        🛁 <strong><?php echo $baths; ?></strong> <?php echo $is_spanish ? 'Baños' : 'Baths'; ?>
                    </div>
                <?php endif; ?>
                <?php if ( $sqft ) : ?>
                    <div class="afc-spec-item">
                        📐 <strong><?php echo number_format( $sqft ); ?></strong> sqft
                    </div>
                <?php endif; ?>
            </div>

            <!-- Property Description -->
            <div class="afc-description">
                <?php the_content(); ?>
            </div>

            <!-- Amenities Section -->
            <?php if ( ! empty( $amenities ) ) : ?>
                <div class="afc-amenities-section">
                    <h3><?php echo $is_spanish ? 'Características' : 'Features'; ?></h3>
                    <div class="afc-amenities-grid">
                        <?php foreach ( $amenities as $amenity_slug ) : 
                            $label = isset( $amenity_labels[$amenity_slug] ) ? $amenity_labels[$amenity_slug] : ucwords( str_replace( '_', ' ', $amenity_slug ) );
                        ?>
                            <div class="afc-amenity-item">
                                <?php echo esc_html( $label ); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Location & Map Section -->
            <?php if ( $lat && $lng ) : ?>
                <div class="afc-map-section">
                    <h3><?php echo $is_spanish ? 'Ubicación' : 'Location'; ?></h3>
                    <?php if ( $address ) : ?>
                        <div class="afc-map-address">
                            📍 <?php echo esc_html( $address ); ?>
                        </div>
                    <?php endif; ?>
                    <div class="afc-map-embed">
                        <iframe 
                            width="100%" 
                            height="100%" 
                            frameborder="0" 
                            scrolling="no"
                            src="https://maps.google.com/maps?q=<?php echo esc_attr($lat); ?>,<?php echo esc_attr($lng); ?>&hl=<?php echo $is_spanish ? 'es' : 'en'; ?>&z=15&output=embed">
                        </iframe>
                    </div>
                </div>
            <?php endif; ?>

        </div>

        <!-- Right Column: Sticky Agent Card -->
        <aside class="afc-agent-card">
            
            <!-- Agent Photo -->
            <?php if ( $agent_photo_id ) : ?>
                <?php echo wp_get_attachment_image( $agent_photo_id, 'thumbnail', false, ['class' => 'afc-agent-photo'] ); ?>
            <?php endif; ?>

            <!-- Agent Name & Title -->
            <div class="afc-agent-name"><?php echo esc_html( $agent_name ); ?></div>
            <div class="afc-agent-title"><?php echo $is_spanish ? 'Agente Inmobiliario' : 'Real Estate Agent'; ?></div>

            <!-- Company Logo -->
            <?php if ( $company_logo_id ) : ?>
                <div class="afc-company-logo">
                    <?php echo wp_get_attachment_image( $company_logo_id, 'medium' ); ?>
                </div>
            <?php endif; ?>

            <!-- Contact Buttons -->
            <div class="afc-contact-buttons">
                
                <!-- WhatsApp Button -->
                <?php if ( $whatsapp_clean ) : ?>
                    <a href="https://wa.me/<?php echo $whatsapp_clean; ?>?text=<?php echo urlencode( $is_spanish ? 'Hola! Me interesa esta propiedad: ' : 'Hi! I\'m interested in this property: ' ); ?><?php echo urlencode( get_permalink() ); ?>" 
                       class="afc-btn afc-btn-whatsapp" 
                       target="_blank">
                        💬 WhatsApp
                    </a>
                <?php endif; ?>

                <!-- Phone Call Button -->
                <?php if ( $agent_phone ) : ?>
                    <a href="tel:<?php echo esc_attr( preg_replace('/[^0-9+]/', '', $agent_phone) ); ?>" 
                       class="afc-btn afc-btn-call">
                        📞 <?php echo $is_spanish ? 'Llamar' : 'Call'; ?>
                    </a>
                <?php endif; ?>

                <!-- Email Button -->
                <a href="mailto:<?php echo get_the_author_meta( 'user_email', $author_id ); ?>?subject=<?php echo urlencode( get_the_title() ); ?>" 
                   class="afc-btn afc-btn-email">
                    ✉️ Email
                </a>

            </div>

        </aside>

    </div>

</div>

<!-- Floating WhatsApp Button -->
<?php if ( $show_whatsapp && $whatsapp_clean ) : ?>
    <a href="https://wa.me/<?php echo $whatsapp_clean; ?>?text=<?php echo urlencode( $is_spanish ? 'Hola! Me interesa esta propiedad: ' : 'Hi! I\'m interested in this property: ' ); ?><?php echo urlencode( get_permalink() ); ?>" 
       class="afc-floating-whatsapp" 
       target="_blank" 
       aria-label="<?php echo $is_spanish ? 'Contactar por WhatsApp' : 'Contact via WhatsApp'; ?>">
        <svg width="32" height="32" viewBox="0 0 32 32" fill="white">
            <path d="M16 0C7.164 0 0 7.163 0 16c0 2.825.736 5.478 2.022 7.777L.696 30.696l7.25-1.903C10.135 30.264 12.978 31 16 31c8.837 0 16-7.163 16-16S24.837 0 16 0zm7.738 22.263c-.424.212-2.51 1.24-2.9 1.382-.39.14-.673.212-.957-.213-.284-.424-1.098-1.382-1.347-1.666-.248-.284-.496-.32-.92-.107-.424.212-1.79.66-3.408 2.104-1.26 1.124-2.11 2.513-2.358 2.937-.248.424-.026.653.186.864.19.19.424.496.636.744.212.248.284.424.424.708.14.284.07.532-.035.744-.107.212-.957 2.308-1.312 3.16-.346.83-.698.717-.957.73-.248.012-.532.015-.816.015-.284 0-.744-.107-1.134-.532-.39-.424-1.49-1.455-1.49-3.55s1.526-4.118 1.738-4.402c.212-.284 2.996-4.577 7.26-6.42 4.264-1.843 4.264-1.227 5.033-1.15.77.078 2.51 1.026 2.864 2.017.354.99.354 1.84.248 2.017-.107.176-.39.284-.816.496z"/>
        </svg>
    </a>
<?php endif; ?>

<?php get_footer(); ?>