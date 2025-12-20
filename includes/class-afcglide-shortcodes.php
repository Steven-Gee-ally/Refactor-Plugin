<?php
/**
 * Shortcodes for AFCGlide Listings (clean refactor)
 *
 * This file contains the `AFCGlide_Shortcodes` class. It is intended to be
 * included by the main plugin bootstrap (`afcglide-master.php`) which should
 * call `AFCGlide_Shortcodes::init()` during plugin initialization.
 *
 * @package AFCGlide_Listings
 */

namespace AFCGlide\Listings;

defined( 'ABSPATH' ) || exit;

final class AFCGlide_Shortcodes {

    /**
     * Attach registration to init (safe place to register shortcodes)
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
    }

    /**
     * Register shortcodes used by the plugin
     */
    public static function register_shortcodes() {
        add_shortcode( 'afcglide_listings_grid', array( __CLASS__, 'render_listing_grid' ) );
        add_shortcode( 'afcglide_my_listings', array( __CLASS__, 'render_my_listings' ) );
        add_shortcode( 'afcglide_submit_listing', array( __CLASS__, 'render_submit_form' ) );
    }

    /**
     * Render the front-end submission form
     *
     * @param array $atts Shortcode attributes (unused currently)
     * @return string HTML output
     */
    public static function render_submit_form( $atts = array() ) {
        if ( ! is_user_logged_in() ) {
            $login_url = wp_login_url( get_permalink() );
            return '<div class="afcglide-notice">' . sprintf( esc_html__( 'You must be logged in. %s', 'afcglide' ), '<a href="' . esc_url( $login_url ) . '">' . esc_html__( 'Login here', 'afcglide' ) . '</a>' ) . '</div>';
        }

        ob_start();

        // Basic accessible form markup; submission handled by admin-post.php
        echo '<div class="afcglide-submit-form">';
        echo '<h3>' . esc_html__( 'Submit a New Listing', 'afcglide' ) . '</h3>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '" enctype="multipart/form-data">';
        wp_nonce_field( 'afcglide_new_listing', 'afcglide_nonce' );
        echo '<input type="hidden" name="action" value="afcglide_submit_listing">';

        echo '<p><label for="afcglide_title">' . esc_html__( 'Title', 'afcglide' ) . ' *</label><br />';
        echo '<input id="afcglide_title" name="listing_title" type="text" required class="widefat" /></p>';

        echo '<p><label for="afcglide_desc">' . esc_html__( 'Description', 'afcglide' ) . ' *</label><br />';
        echo '<textarea id="afcglide_desc" name="listing_description" rows="6" required class="widefat"></textarea></p>';

        echo '<p><label for="afcglide_price">' . esc_html__( 'Price', 'afcglide' ) . '</label><br />';
        echo '<input id="afcglide_price" name="listing_price" type="text" class="widefat" /></p>';

        echo '<p><label for="afcglide_image">' . esc_html__( 'Featured Image', 'afcglide' ) . '</label><br />';
        echo '<input id="afcglide_image" name="hero_image" type="file" accept="image/*" /></p>';

        echo '<p><button type="submit" class="button button-primary">' . esc_html__( 'Submit Listing', 'afcglide' ) . '</button></p>';
        echo '</form>';
        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Render the current user's listings
     *
     * @param array $atts Shortcode attributes (unused)
     * @return string HTML
     */
    public static function render_my_listings( $atts = array() ) {
        if ( ! is_user_logged_in() ) {
            return '<p>' . sprintf( esc_html__( 'You must be logged in. %s', 'afcglide' ), '<a href="' . esc_url( wp_login_url() ) . '">' . esc_html__( 'Login', 'afcglide' ) . '</a>' ) . '</p>';
        }

        $query = new \WP_Query( array(
            'post_type'      => 'afcglide_listing',
            'author'         => get_current_user_id(),
            'posts_per_page' => -1,
            'post_status'    => array( 'publish', 'pending', 'draft' ),
        ) );

        ob_start();
        echo '<div class="afcglide-my-listings">';
        echo '<h3>' . esc_html__( 'My Listings', 'afcglide' ) . '</h3>';

        if ( $query->have_posts() ) {
            echo '<ul class="afcglide-my-listings__list">';
            while ( $query->have_posts() ) {
                $query->the_post();
                printf( '<li><a href="%1$s">%2$s</a> <small>(%3$s)</small></li>', esc_url( get_permalink() ), esc_html( get_the_title() ), esc_html( get_post_status() ) );
            }
            echo '</ul>';
        } else {
            echo '<p>' . esc_html__( 'You have no listings yet.', 'afcglide' ) . '</p>';
        }

        wp_reset_postdata();
        echo '</div>';
        return (string) ob_get_clean();
    }

    /**
     * Render a grid of listings
     *
     * @param array $atts Shortcode attributes. Accepts 'count'.
     * @return string HTML
     */
    public static function render_listing_grid( $atts ) {
        $atts = shortcode_atts( array( 'count' => 6 ), (array) $atts, 'afcglide_listings_grid' );

        $count = absint( $atts['count'] );
        if ( 0 === $count ) {
            $count = 6;
        }

        $query = new \WP_Query( array(
            'post_type'      => 'afcglide_listing',
            'posts_per_page' => $count,
            'post_status'    => 'publish',
        ) );

        if ( ! $query->have_posts() ) {
            return '<p>' . esc_html__( 'No listings found.', 'afcglide' ) . '</p>';
        }

        ob_start();
        echo '<div class="afcglide-grid">';
        while ( $query->have_posts() ) {
            $query->the_post();
            echo '<article class="afcglide-card">';
            if ( has_post_thumbnail() ) {
                echo '<div class="afcglide-card__media">';
                the_post_thumbnail( 'medium' );
                echo '</div>';
            }
            echo '<div class="afcglide-card__body">';
            echo '<h4 class="afcglide-card__title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h4>';
            echo '</div>';
            echo '</article>';
        }
        wp_reset_postdata();
        echo '</div>';

        return (string) ob_get_clean();
    }

}
