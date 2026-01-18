<?php
/**
 * AFCGlide Individual Listing Card
 * Vision: High-End Minimalist UI
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();

// 1. DATA HARVESTING
$price   = get_post_meta( $post_id, '_listing_price', true );
$beds    = get_post_meta( $post_id, '_listing_beds', true );
$baths   = get_post_meta( $post_id, '_listing_baths', true );
$sqft    = get_post_meta( $post_id, '_listing_sqft', true );

// 2. FORMATTING
$display_price = ( ! empty($price) ) ? '$' . number_format( (float)$price ) : 'Contact for Price';
?>

<div class="afcglide-card">
    <div class="afcglide-card-image">
        <a href="<?php the_permalink(); ?>">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'large' ); ?>
            <?php else : ?>
                <img src="<?php echo esc_url( AFCG_URL . 'assets/images/placeholder-listings.svg' ); ?>" alt="Placeholder">
            <?php endif; ?>
        </a>
        <div class="afcglide-price-tag">
            <?php echo esc_html( $display_price ); ?>
        </div>
    </div>

    <div class="afcglide-card-content">
        <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
        
        <div class="afcglide-meta-specs">
            <div class="afcglide-spec-item">
                <span>🛏️</span> <strong><?php echo esc_html( $beds ?: '0' ); ?></strong>
            </div>
            <div class="afcglide-spec-item">
                <span>🛁</span> <strong><?php echo esc_html( $baths ?: '0' ); ?></strong>
            </div>
            <div class="afcglide-spec-item">
                <span>📐</span> <strong><?php echo number_format( (float)$sqft ); ?></strong> <small>sqft</small>
            </div>
        </div>
    </div>
</div>