<?php
/**
 * Listing Grid Template with AJAX Load More
 * PREFIX SYNCED: All classes match afcglide- CSS standards.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $listings_query ) || ! $listings_query->have_posts() ) {
    echo '<p class="afcglide-no-results">' . esc_html__( 'No listings found.', 'afcglide' ) . '</p>';
    return;
}

$max_pages = $listings_query->max_num_pages;
$ajax_nonce = wp_create_nonce( 'afcglide_load_more_nonce' );
?>

<div class="afcglide-grid-wrapper">
    <div class="afcglide-grid-container" id="afcglide-listings-container">
        <?php 
        while ( $listings_query->have_posts() ) : 
            $listings_query->the_post();
            
            // Look for the prefixed card template
            if ( function_exists( '\AFCGlide\Listings\afcglide_get_template_part' ) ) {
                \AFCGlide\Listings\afcglide_get_template_part( 'listing-card' );
            } else {
                // FALLBACK: Ensure the file it includes is also being reviewed
                include plugin_dir_path( __FILE__ ) . 'listing-card.php';
            }
            
        endwhile; 
        wp_reset_postdata(); 
        ?>
    </div>

    <?php if ( $max_pages > 1 ) : ?>
        <div class="afcglide-load-more-wrapper">
            <button class="afcglide-btn afcglide-btn-primary afcglide-load-more" 
                    id="afcglide-load-more-btn"
                    data-page="1" 
                    data-max-pages="<?php echo esc_attr( $max_pages ); ?>"
                    data-nonce="<?php echo $ajax_nonce; ?>">
                <span><?php esc_html_e( 'Explore More Luxury Properties', 'afcglide' ); ?></span>
                <div class="afcglide-loader" style="display:none;"></div> 
            </button>
        </div>
    <?php endif; ?>
</div>