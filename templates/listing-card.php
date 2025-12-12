<?php
/**
 * Template for displaying a single listing card in the grid.
 * Updated with STATUS RIBBONS.
 *
 * @package AFCGlide_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();

// 1. Meta Data
$price       = get_post_meta( $post_id, '_listing_price', true );
$beds        = get_post_meta( $post_id, '_listing_beds', true );
$baths       = get_post_meta( $post_id, '_listing_baths', true );
$sqft        = get_post_meta( $post_id, '_listing_sqft', true );
$is_featured = get_post_meta( $post_id, '_listing_is_featured', true );

// 2a. Location
$locations = get_the_terms( $post_id, 'property_location' );
$location_text = ( ! empty( $locations ) && ! is_wp_error( $locations ) ) ? $locations[0]->name : '';

// 2b. Status (NEW! The Ribbon Logic)
$statuses = get_the_terms( $post_id, 'property_status' );
$status_obj = ( ! empty( $statuses ) && ! is_wp_error( $statuses ) ) ? $statuses[0] : null;
$status_name = $status_obj ? $status_obj->name : '';
$status_slug = $status_obj ? $status_obj->slug : '';

// 3. Image
$image_url = AFCG_PLUGIN_URL . 'assets/images/placeholder.jpg';
if ( has_post_thumbnail( $post_id ) ) {
    $thumb_id = get_post_thumbnail_id( $post_id );
    $image_data = wp_get_attachment_image_src( $thumb_id, 'medium_large' );
    if ( $image_data ) $image_url = $image_data[0];
}
?>

<article class="afcglide-listing-card">
    
    <div class="afcglide-card-media">
        <a href="<?php the_permalink(); ?>">
            <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo esc_attr( get_the_title() ); ?>" loading="lazy">
        </a>
        
        <?php if ( $is_featured === '1' ) : ?>
            <span class="afcglide-featured-badge"><?php esc_html_e( 'Featured', 'afcglide' ); ?></span>
        <?php endif; ?>

        <?php if ( $status_name ) : ?>
            <span class="afcglide-status-badge status-<?php echo esc_attr( $status_slug ); ?>">
                <?php echo esc_html( $status_name ); ?>
            </span>
        <?php endif; ?>
    </div>
    
    <div class="afcglide-card-body">
        <h3 class="afcglide-card-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h3>
        
        <?php if ( $location_text ) : ?>
            <div class="afcglide-card-location">
                📍 <?php echo esc_html( $location_text ); ?>
            </div>
        <?php endif; ?>
        
        <?php if ( $price ) : ?>
            <div class="afcglide-card-price">
                <?php 
                $currency = get_option( 'afcglide_options' )['currency_symbol'] ?? '$';
                echo esc_html( $currency . number_format( (float) $price ) ); 
                ?>
            </div>
        <?php endif; ?>
        
        <div class="afcglide-card-meta">
            <?php if ( $beds ) : ?><span>🛏️ <?php echo esc_html( $beds ); ?></span><?php endif; ?>
            <?php if ( $baths ) : ?><span>🚿 <?php echo esc_html( $baths ); ?></span><?php endif; ?>
            <?php if ( $sqft ) : ?><span>📐 <?php echo esc_html( number_format( (int) $sqft ) ); ?></span><?php endif; ?>
        </div>
    </div>
    
</article>