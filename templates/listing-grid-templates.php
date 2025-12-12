<?php
/**
 * Listing Grid Template with AJAX Load More
 *
 * @package AFCGlide_Listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Ensure we have a query to work with
if ( ! isset( $listings_query ) || ! $listings_query->have_posts() ) {
    echo '<p class="afcglide-no-results">' . esc_html__( 'No listings found.', 'afcglide' ) . '</p>';
    return;
}

$max_pages = $listings_query->max_num_pages;
?>

<div class="afcglide-container">
    <div class="afcglide-grid-ready">
        <?php 
        while ( $listings_query->have_posts() ) : 
            $listings_query->the_post();
            
            // Use the Helper to load the card (allows theme overrides)
            // Namespace: AFCGlide\Listings
            if ( function_exists( '\AFCGlide\Listings\afcglide_get_template_part' ) ) {
                \AFCGlide\Listings\afcglide_get_template_part( 'listing-card' );
            } else {
                // Fallback if helper isn't loaded
                include plugin_dir_path( __FILE__ ) . 'listing-card.php';
            }
            
        endwhile; 
        ?>
    </div>

    <?php if ( $max_pages > 1 ) : ?>
        <div class="afcglide-load-more-wrapper">
            <button class="afcglide-btn afcglide-load-more" 
                    data-page="1" 
                    data-max-pages="<?php echo esc_attr( $max_pages ); ?>"
                    data-query="<?php echo esc_attr( json_encode( $listings_query->query_vars ) ); ?>">
                <?php esc_html_e( 'Load More Listings', 'afcglide' ); ?>
            </button>
        </div>
    <?php endif; ?>
</div>