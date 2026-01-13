<?php
/**
 * Listing Grid Template with AJAX Load More
 *
 * @package AFCGlide_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! isset( $listings_query ) || ! $listings_query->have_posts() ) {
    echo '<p class="afcglide-no-results">' . esc_html__( 'No listings found.', 'afcglide' ) . '</p>';
    return;
}

$max_pages = $listings_query->max_num_pages;
// Generate a security nonce to prevent unauthorized AJAX requests
$ajax_nonce = wp_create_nonce( 'afcglide_load_more_nonce' );
?>

<div class="afcglide-container">
    <div class="afcglide-grid-ready" id="afcglide-listings-container">
        <?php 
        while ( $listings_query->have_posts() ) : 
            $listings_query->the_post();
            
            if ( function_exists( '\AFCGlide\Listings\afcglide_get_template_part' ) ) {
                \AFCGlide\Listings\afcglide_get_template_part( 'listing-card' );
            } else {
                include plugin_dir_path( __FILE__ ) . 'listing-card.php';
            }
            
        endwhile; 
        wp_reset_postdata(); // Essential when using custom queries
        ?>
    </div>

    <?php if ( $max_pages > 1 ) : ?>
        <div class="afcglide-load-more-wrapper" style="text-align:center; margin-top: 40px;">
            <button class="afcglide-btn afcglide-load-more" 
                    id="afcglide-load-more-btn"
                    data-page="1" 
                    data-max-pages="<?php echo esc_attr( $max_pages ); ?>"
                    data-nonce="<?php echo $ajax_nonce; ?>">
                <span><?php esc_html_e( 'View More Properties', 'afcglide' ); ?></span>
                <div class="afc-loader" style="display:none;"></div> 
            </button>
        </div>
    <?php endif; ?>
</div>