<?php
/**
 * Admin Settings Page
 *
 * @package AFCGlide_Listings
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Settings {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
    }

    public static function add_settings_page() {
        add_options_page(
            __( 'AFCGlide Listings Settings', 'afcglide' ),
            __( 'AFCGlide Listings', 'afcglide' ),
            'manage_options',
            'afcglide-settings',
            [ __CLASS__, 'render_page' ]
        );
    }

    public static function register_settings() {
        register_setting( 'afcglide_settings_group', 'afcglide_options' );

        add_settings_section( 'afcglide_general_section', __( 'General Settings', 'afcglide' ), null, 'afcglide-settings' );

        add_settings_field( 'posts_per_page', __( 'Listings Per Page', 'afcglide' ), [ __CLASS__, 'render_field_posts_per_page' ], 'afcglide-settings', 'afcglide_general_section' );
        add_settings_field( 'currency_symbol', __( 'Currency Symbol', 'afcglide' ), [ __CLASS__, 'render_field_currency' ], 'afcglide-settings', 'afcglide_general_section' );
        add_settings_field( 'google_maps_api_key', __( 'Google Maps API Key', 'afcglide' ), [ __CLASS__, 'render_field_maps_key' ], 'afcglide-settings', 'afcglide_general_section' );
        add_settings_field( 'delete_on_uninstall', __( 'Delete Data on Uninstall?', 'afcglide' ), [ __CLASS__, 'render_field_delete' ], 'afcglide-settings', 'afcglide_general_section' );
    }

    public static function render_page() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'AFCGlide Listings Settings', 'afcglide' ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'afcglide_settings_group' );
                do_settings_sections( 'afcglide-settings' );
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public static function render_field_posts_per_page() {
        $options = get_option( 'afcglide_options' );
        $val = isset( $options['posts_per_page'] ) ? $options['posts_per_page'] : 6;
        echo '<input type="number" name="afcglide_options[posts_per_page]" value="' . esc_attr( $val ) . '" class="small-text">';
    }

    public static function render_field_currency() {
        $options = get_option( 'afcglide_options' );
        $val = isset( $options['currency_symbol'] ) ? $options['currency_symbol'] : '$';
        echo '<input type="text" name="afcglide_options[currency_symbol]" value="' . esc_attr( $val ) . '" class="small-text">';
    }

    public static function render_field_maps_key() {
        $options = get_option( 'afcglide_options' );
        $val = isset( $options['google_maps_api_key'] ) ? $options['google_maps_api_key'] : '';
        echo '<input type="text" name="afcglide_options[google_maps_api_key]" value="' . esc_attr( $val ) . '" class="regular-text">';
    }

    public static function render_field_delete() {
        $options = get_option( 'afcglide_options' );
        $checked = isset( $options['delete_on_uninstall'] ) ? $options['delete_on_uninstall'] : '';
        echo '<input type="checkbox" name="afcglide_options[delete_on_uninstall]" value="1" ' . checked( 1, $checked, false ) . '> <span class="description" style="color:red;">Warning: This will delete all listings when the plugin is removed.</span>';
    }
}
?>