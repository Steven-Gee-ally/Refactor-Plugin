<?php
/**
 * Handles Shortcodes: Grid and Slider
 *
 * @package AFCGlide_Listings
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Shortcodes {

    public static function init() {
        add_shortcode( 'afcglide_listings', [ __CLASS__, 'render_grid' ] );
        add_shortcode( 'afcglide_slider', [ __CLASS__, 'render_slider' ] );
    }

    /**
     * Render Grid [afcglide_listings]
     */
    public static function render_grid( $atts ) {
        $atts = shortcode_atts( [
            'posts_per_page' => 6,
            'featured'       => '',
        ], $atts, 'afcglide_listings' );

        $args = self::get_query_args( $atts );
        $query = new \WP_Query( $args );

        ob_start();
        
        // Wrapper for JS targeting
        echo '<div class="afcglide-container">';
        echo '<div class="afcglide-grid-ready">'; // This class is vital for AJAX append

        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                self::safe_load_template();
            }
        } else {
            echo '<p>' . esc_html__( 'No listings found.', 'afcglide' ) . '</p>';
        }

        echo '</div>'; // End grid

        // Load More Button
        if ( $query->max_num_pages > 1 ) {
            echo '<div class="afcglide-load-more-wrapper" style="text-align:center; margin-top:20px;">';
            // We use htmlspecialchars to safely pass the query args to JS
            echo '<button class="button afcglide-load-more" data-page="1" data-max-pages="' . esc_attr( $query->max_num_pages ) . '" data-query="' . esc_attr( json_encode($args) ) . '">' . esc_html__( 'Load More Listings', 'afcglide' ) . '</button>';
            echo '</div>';
        }
        echo '</div>'; // End container

        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Render Slider [afcglide_slider]
     */
    public static function render_slider( $atts ) {
        $atts = shortcode_atts( [
            'posts_per_page' => 9,
            'featured'       => 'true', // Default to featured for slider
        ], $atts, 'afcglide_slider' );

        $args = self::get_query_args( $atts );
        $query = new \WP_Query( $args );

        ob_start();
        
        if ( $query->have_posts() ) {
            echo '<div class="afcglide-card-slider-wrapper"><div class="afcglide-card-slider">';
            while ( $query->have_posts() ) {
                $query->the_post();
                echo '<div class="afcglide-slide">';
                self::safe_load_template();
                echo '</div>';
            }
            echo '</div></div>';
        }

        wp_reset_postdata();
        return ob_get_clean();
    }

    /**
     * Helper: Safe Template Loader
     * Prevents crash if template file is missing
     */
    private static function safe_load_template() {
        $template_path = AFCG_PLUGIN_DIR . 'templates/listing-card.php';
        
        if ( file_exists( $template_path ) ) {
            include $template_path;
        } else {
            // Fallback Layout (In case file is missing)
            echo '<div class="afcglide-card-fallback" style="border:1px solid #ddd; padding:15px; margin-bottom:15px;">';
            if ( has_post_thumbnail() ) { the_post_thumbnail('medium'); }
            echo '<h3><a href="' . get_permalink() . '">' . get_the_title() . '</a></h3>';
            echo '<p>' . get_the_excerpt() . '</p>';
            echo '</div>';
        }
    }

    /**
     * Shared Query Logic
     */
    private static function get_query_args( $atts ) {
        $args = [
            'post_type'      => 'afcglide_listing',
            'post_status'    => 'publish',
            'posts_per_page' => absint( $atts['posts_per_page'] ),
        ];

        // FIXED: Meta key changed from '_listing_is_featured' to '_is_featured' to match Metaboxes
        if ( ! empty( $atts['featured'] ) && 'true' === $atts['featured'] ) {
            $args['meta_query'] = [ 
                [ 
                    'key'     => '_is_featured', // Matches File #5
                    'value'   => '1', 
                    'compare' => '=' 
                ] 
            ];
        }

        return $args;
    }
}
?>