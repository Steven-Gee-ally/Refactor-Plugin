<?php
/**
 * AFCGlide Individual Listing Card
 * Version 4.3 - Synergy Integrated Edition
 * Vision: High-End Minimalist UI with Live Analytics
 */

if ( ! defined( 'ABSPATH' ) ) exit;

$post_id = get_the_ID();

// 1. DATA HARVESTING (World-Class Precision)
$price   = get_post_meta( $post_id, \AFCGlide\Core\Constants::META_PRICE, true );
$beds    = get_post_meta( $post_id, \AFCGlide\Core\Constants::META_BEDS, true );
$baths   = get_post_meta( $post_id, \AFCGlide\Core\Constants::META_BATHS, true );
$sqft    = get_post_meta( $post_id, \AFCGlide\Core\Constants::META_SQFT, true );
$address = get_post_meta( $post_id, \AFCGlide\Core\Constants::META_ADDRESS, true );

// Live Synergy Analytics from our tracker
$views   = get_post_meta( $post_id, '_listing_views_count', true ) ?: 0;

// 2. FORMATTING
$display_price = ( ! empty($price) ) ? '$' . number_format( (float)$price ) : 'Price Upon Request';
$display_sqft  = ( ! empty($sqft) ) ? number_format( (float)$sqft ) . ' <small>SQFT</small>' : '--';
?>

<div class="afcglide-card">
    <div class="afcglide-card-image">
        <a href="<?php echo esc_url( get_permalink() ); ?>" class="afc-image-link">
            <?php if ( has_post_thumbnail() ) : ?>
                <?php the_post_thumbnail( 'large', ['class' => 'afc-main-thumb'] ); ?>
            <?php else : ?>
                <img src="<?php echo esc_url( AFCG_URL . 'assets/images/placeholder-listings.svg' ); ?>" alt="Asset Pending">
            <?php endif; ?>
            
            <div class="afc-image-overlay"></div>
        </a>

        <div class="afcglide-price-tag">
            <span><?php echo esc_html( $display_price ); ?></span>
        </div>

        <div class="afc-analytics-badge">
            <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
            <?php echo number_format($views); ?> VIEWS
        </div>
    </div>

    <div class="afcglide-card-content">
        <div class="afc-card-header">
            <p class="afc-card-location">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z"/></svg>
                <?php echo esc_html( $address ?: 'Location Confidential' ); ?>
            </p>
            <h3><a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a></h3>
        </div>
        
        <div class="afcglide-meta-specs">
            <div class="afcglide-spec-item">
                <span class="afc-spec-label">BEDS</span>
                <strong><?php echo esc_html( $beds ?: '0' ); ?></strong>
            </div>
            <div class="afcglide-spec-item">
                <span class="afc-spec-label">BATHS</span>
                <strong><?php echo esc_html( $baths ?: '0' ); ?></strong>
            </div>
            <div class="afcglide-spec-item">
                <span class="afc-spec-label">AREA</span>
                <strong><?php echo $display_sqft; ?></strong>
            </div>
        </div>
        
        <div class="afc-card-footer">
            <a href="<?php echo esc_url( get_permalink() ); ?>" class="afc-view-btn">EXPLORE PROPERTY</a>
        </div>
    </div>
</div>