<?php
/**
 * AFCGlide - Single Listing Template (Theme-compatible)
 * FULL PRO VERSION: Hero, Slider, Stack, Palette, Map, & Agent
 *
 * @package AFCGlide_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header();

// --- 1. Enqueue Assets ---
if ( defined( 'AFCG_PLUGIN_URL' ) && defined( 'AFCG_VERSION' ) ) {
    // Plugin CSS
   
   
    // Lightbox (GLightbox)
    wp_enqueue_style( 'glightbox', 'https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/css/glightbox.min.css', [], '3.2.0' );
    wp_enqueue_script( 'glightbox', 'https://cdn.jsdelivr.net/npm/glightbox@3.2.0/dist/js/glightbox.min.js', [], '3.2.0', true );
   
    // Main JS
    wp_enqueue_script( 'afcglide-single-js', AFCG_PLUGIN_URL . 'assets/js/afcglide-public.js', [ 'jquery' ], AFCG_VERSION, true );
   
    // Google Maps (Only if coordinates exist)
    $gps_lat = get_post_meta( get_the_ID(), '_gps_lat', true );
    $gps_lng = get_post_meta( get_the_ID(), '_gps_lng', true );
   
    if ( $gps_lat && $gps_lng ) {
        $options = get_option( 'afcglide_options', [] );
        $google_api_key = isset( $options['google_maps_api_key'] ) ? $options['google_maps_api_key'] : '';
       
        if ( $google_api_key ) {
            wp_enqueue_script( 'google-maps', 'https://maps.googleapis.com/maps/api/js?key=' . esc_attr( $google_api_key ), [], null, true );
        }
    }
}

/**
 * Helper: Get image URL safely
 */
if ( ! function_exists( 'afcg_get_img_url' ) ) {
    function afcg_get_img_url( $id, $size = 'large' ) {
        if ( ! $id ) return '';
        $src = wp_get_attachment_image_url( $id, $size );
        return $src ? esc_url( $src ) : '';
    }
}

global $post;
$post_id = get_the_ID();
$author_id = $post->post_author;

// --- 2. Load Listing Meta ---
$hero_id     = intval( get_post_meta( $post_id, '_hero_image', true ) );
$slider_json = get_post_meta( $post_id, '_slider_images_json', true );
$stack_ids   = [];

for ( $i = 1; $i <= 3; $i++ ) {
   $stack_ids[] = intval( get_post_meta( $post_id, '_stack_img_' . $i, true ) );
}

// Parse slider IDs
$slider_ids = [];
if ( $slider_json ) {
   $decoded = json_decode( wp_unslash( $slider_json ), true );
   if ( is_array( $decoded ) ) {
       $slider_ids = array_map( 'intval', $decoded );
   }
}

// Fallback logic for images
if ( empty( $slider_ids ) && empty( $hero_id ) ) {
   $gallery_ids = get_post_meta( $post_id, '_listing_gallery_ids', true );
   if ( $gallery_ids ) {
       $gallery_array = is_string( $gallery_ids ) ? json_decode( $gallery_ids, true ) : $gallery_ids;
       if ( is_array( $gallery_array ) ) {
           $gallery_array = array_map( 'intval', $gallery_array );
           $hero_id = array_shift( $gallery_array );
           $slider_ids = array_slice( $gallery_array, 0, 12 );
       }
   }
}

// Core Data
$price = get_post_meta( $post_id, '_listing_price', true );
$beds  = get_post_meta( $post_id, '_listing_beds', true );
$baths = get_post_meta( $post_id, '_listing_baths', true );
$sqft  = get_post_meta( $post_id, '_listing_sqft', true );

// Location
$locations = get_the_terms( $post_id, 'property_location' );
$location_text = ( ! empty( $locations ) && ! is_wp_error( $locations ) ) ? $locations[0]->name : '';

// --- 3. Load Agent Data (From User Profile) ---
$agent_photo_id = get_user_meta( $author_id, 'agent_photo', true );
$agent_photo    = $agent_photo_id ? wp_get_attachment_url( $agent_photo_id ) : '';
$agent_name     = get_the_author_meta( 'display_name', $author_id );
$agent_phone    = get_user_meta( $author_id, 'agent_phone', true );
$agent_company  = get_user_meta( $author_id, 'agent_company', true );

// --- 4. Colors (Palette System) ---
$primary    = get_post_meta( $post_id, '_primary_color', true );
$secondary  = get_post_meta( $post_id, '_secondary_color', true );
$background = get_post_meta( $post_id, '_background_color', true );
$text_color = get_post_meta( $post_id, '_text_color', true );

// Build Inline Styles
$palette_style = '';
if ( $primary || $secondary || $background ) {
   $styles = [];
   if ( $primary )    $styles[] = "--afc-primary: " . esc_attr( $primary );
   if ( $secondary )  $styles[] = "--afc-accent: " . esc_attr( $secondary );
   if ( $background ) $styles[] = "--afc-bg-card: " . esc_attr( $background );
   if ( $text_color ) $styles[] = "--afc-text: " . esc_attr( $text_color );
   
   if ( ! empty( $styles ) ) {
       $palette_style = 'style="' . implode( '; ', $styles ) . '"';
   }
}
?>

<div class="afcglide-single-root afcglide-container" <?php echo $palette_style; ?>>
   <main class="afcglide-single-main" role="main">

   <?php while ( have_posts() ) : the_post(); ?>

       <article id="post-<?php the_ID(); ?>" <?php post_class( 'afcglide-single-listing' ); ?>>

            <header class="afcglide-single-header">
                <h1 class="afcglide-single-title"><?php the_title(); ?></h1>

                <div class="afcglide-header-meta">
                    <?php if ( $price ) : ?>
                        <div class="afcglide-single-price">
                            <?php
                            $options = get_option( 'afcglide_options', [] );
                            $currency = isset( $options['currency_symbol'] ) ? $options['currency_symbol'] : '$';
                            echo esc_html( $currency . number_format( (float) $price ) );
                            ?>
                        </div>
                    <?php endif; ?>

                    <?php if ( $location_text ) : ?>
                        <div class="afcglide-single-location">
                            <span class="dashicons dashicons-location"></span>
                            <?php echo esc_html( $location_text ); ?>
                        </div>
                    <?php endif; ?>
                </div>
            </header>

            <section class="afcglide-hero-block" aria-labelledby="afc-hero-title">
                <div class="afcglide-hero-left">
                    <div class="afcglide-hero-main">
                        <?php if ( $hero_id ) : 
                            $hero_full  = afcg_get_img_url( $hero_id, 'full' );
                            $hero_large = afcg_get_img_url( $hero_id, 'large' );
                        ?>
                            <a href="<?php echo esc_url( $hero_full ); ?>" 
                               class="afcglide-lightbox glightbox" 
                               data-gallery="afcglide-<?php echo esc_attr( $post_id ); ?>">
                                <img src="<?php echo esc_url( $hero_large ); ?>" class="afcglide-hero-img">
                            </a>
                        <?php endif; ?>
                    </div>

                    <?php if ( ! empty( $slider_ids ) ) : ?>
                        <div class="afcglide-slider">
                            <div class="afcglide-slider-track">
                                <?php foreach ( $slider_ids as $index => $id ) :
                                    $full  = afcg_get_img_url( $id, 'full' );
                                    $thumb = afcg_get_img_url( $id, 'medium' );
                                ?>
                                    <div class="afcglide-slide-item">
                                        <a href="<?php echo esc_url( $full ); ?>" 
                                           class="afcglide-lightbox glightbox" 
                                           data-gallery="afcglide-<?php echo esc_attr( $post_id ); ?>">
                                            <img src="<?php echo esc_url( $thumb ); ?>" class="afcglide-slide-img">
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <aside class="afcglide-hero-right">
                    <?php foreach ( $stack_ids as $sid ) : 
                        if ( $sid ) :
                            $full  = afcg_get_img_url( $sid, 'full' );
                            $thumb = afcg_get_img_url( $sid, 'medium' );
                    ?>
                        <div class="afcglide-stack-item">
                            <a href="<?php echo esc_url( $full ); ?>" 
                               class="afcglide-lightbox glightbox" 
                               data-gallery="afcglide-<?php echo esc_attr( $post_id ); ?>">
                                <img src="<?php echo esc_url( $thumb ); ?>" class="afcglide-stack-img">
                            </a>
                        </div>
                    <?php endif; endforeach; ?>
                </aside>
            </section>

            <section class="afcglide-meta-section">
                <div class="afcglide-meta-grid">
                    <div class="meta-box">
                        <span class="meta-label"><?php esc_html_e('Beds', 'afcglide'); ?></span>
                        <span class="meta-value"><?php echo esc_html( $beds ); ?></span>
                    </div>
                    <div class="meta-box">
                        <span class="meta-label"><?php esc_html_e('Baths', 'afcglide'); ?></span>
                        <span class="meta-value"><?php echo esc_html( $baths ); ?></span>
                    </div>
                    <div class="meta-box">
                        <span class="meta-label"><?php esc_html_e('SqFt', 'afcglide'); ?></span>
                        <span class="meta-value"><?php echo esc_html( $sqft ); ?></span>
                    </div>
                </div>
            </section>

            <section class="afcglide-content-section">
                <h3><?php esc_html_e('Property Description', 'afcglide'); ?></h3>
                <div class="afcglide-entry-content">
                    <?php the_content(); ?>
                </div>
            </section>

            <?php if ( $gps_lat && $gps_lng ) : ?>
                <section class="afcglide-map-section">
                    <h3><?php esc_html_e('Location', 'afcglide'); ?></h3>
                    <div id="afcglide-map" style="width: 100%; height: 400px; border-radius: 8px;"></div>
                    <script>
                        function initMap() {
                            var location = { lat: <?php echo esc_js( $gps_lat ); ?>, lng: <?php echo esc_js( $gps_lng ); ?> };
                            var map = new google.maps.Map(document.getElementById('afcglide-map'), {
                                zoom: 15,
                                center: location
                            });
                            var marker = new google.maps.Marker({
                                position: location,
                                map: map
                            });
                        }
                        // Wait for Google API to load
                        if (typeof google !== 'undefined') { initMap(); }
                    </script>
                </section>
            <?php endif; ?>

            <section class="afcglide-agent-card" style="margin-top: 40px; padding: 20px; background: #f9f9f9; border-radius: 8px; display: flex; gap: 20px; align-items: center;">
                <?php if ( $agent_photo ) : ?>
                    <img src="<?php echo esc_url( $agent_photo ); ?>" alt="Agent" style="width: 80px; height: 80px; border-radius: 50%; object-fit: cover;">
                <?php endif; ?>
                <div class="agent-details">
                    <h4 style="margin: 0 0 5px;"><?php echo esc_html( $agent_name ); ?></h4>
                    <?php if ( $agent_company ) : ?>
                        <p style="margin: 0; color: #666;"><?php echo esc_html( $agent_company ); ?></p>
                    <?php endif; ?>
                    <?php if ( $agent_phone ) : ?>
                        <p style="margin: 5px 0 0;"><strong>📞 <a href="tel:<?php echo esc_attr( $agent_phone ); ?>"><?php echo esc_html( $agent_phone ); ?></a></strong></p>
                    <?php endif; ?>
                </div>
            </section>

       </article>

       <?php
       // Comments
       if ( comments_open() || get_comments_number() ) :
           ?>
           <section class="afcglide-comments-section">
               <?php comments_template(); ?>
           </section>
       <?php endif; ?>

   <?php endwhile; // End Loop ?>

   </main>
</div>

<?php get_footer(); ?>