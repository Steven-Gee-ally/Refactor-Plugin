<?php
namespace AFCGlide\Listings;

/**
 * Admin Settings Page & Dashboard Hub
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Settings {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'add_settings_page' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_scripts' ] );
    }

    public static function add_settings_page() {
        add_menu_page(
            __( 'AFCGlide Home', 'afcglide' ),
            __( 'AFCGlide Home', 'afcglide' ),
            'manage_options',
            'afcglide-home',
            [ __CLASS__, 'render_dashboard' ], 
            'dashicons-admin-home',
            2
        );

        add_submenu_page(
            'afcglide-home',
            __( 'Dashboard', 'afcglide' ),
            __( 'Dashboard', 'afcglide' ),
            'manage_options',
            'afcglide-home',
            [ __CLASS__, 'render_dashboard' ]
        );

        add_submenu_page(
            'afcglide-home',
            __( 'Settings', 'afcglide' ),
            __( 'Settings', 'afcglide' ),
            'manage_options',
            'afcglide-settings',
            [ __CLASS__, 'render_page' ] 
        );
    }

    public static function render_dashboard() {
        ?>
        <div class="wrap afcglide-admin-wrapper">
            <h1 style="font-weight: 800; margin-bottom: 5px;">🚀 AFCGlide Command Center</h1>
            <p style="margin-bottom: 30px; color: #64748b; font-size: 16px;">Welcome back, Stevo. Here is your luxury real estate toolkit.</p>
            
            <div class="afcglide-dashboard-grid">
                
                <div class="afcglide-card" style="border-top: 5px solid #a7f3d0;">
                    <span style="font-size: 30px;">🏆</span>
                    <h2>Agent Branding</h2>
                    <p>Update your headshot, agency logo, and WhatsApp number for lead generation.</p>
                    <a href="<?php echo admin_url('profile.php'); ?>" class="button button-primary">Edit My Profile</a>
                </div>

                <div class="afcglide-card" style="border-top: 5px solid #3b82f6;">
                    <span style="font-size: 30px;">🏠</span>
                    <h2>Property Inventory</h2>
                    <p>Manage your luxury listings. Add descriptions, prices, and the "Big 8" amenities.</p>
                    <div style="display:flex; gap:10px;">
                        <a href="<?php echo admin_url('edit.php?post_type=afcglide_listing'); ?>" class="button button-primary">All Listings</a>
                        <a href="<?php echo admin_url('post-new.php?post_type=afcglide_listing'); ?>" class="button">+ Add New</a>
                    </div>
                </div>

                <div class="afcglide-card" style="border-top: 5px solid #a7f3d0;">
                    <span style="font-size: 30px;">👥</span>
                    <h2>Team Manager</h2>
                    <p>Add new agents or editors to the platform to help manage listings.</p>
                    <div style="display:flex; gap:10px;">
                        <a href="<?php echo admin_url('users.php'); ?>" class="button button-primary">View Team</a>
                        <a href="<?php echo admin_url('user-new.php'); ?>" class="button">Add Agent</a>
                    </div>
                </div>

                <div class="afcglide-card" style="border-top: 5px solid #a7f3d0;">
                    <span style="font-size: 30px;">⚙️</span>
                    <h2>Global Settings</h2>
                    <p>Configure Google Maps API, currency symbols, and listing display options.</p>
                    <a href="<?php echo admin_url('admin.php?page=afcglide-settings'); ?>" class="button button-primary">System Settings</a>
                </div>

            </div>
        </div>
        <?php
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

    public static function enqueue_admin_scripts( $hook ) {
        if ( strpos( $hook, 'afcglide' ) === false ) {
            return;
        }
        wp_enqueue_media();
        wp_enqueue_script(
            'afcglide-settings-upload',
            AFCG_URL . 'assets/js/settings-upload.js',
            [ 'jquery' ],
            AFCG_VERSION,
            true
        );
    }

    public static function register_settings() {
        register_setting( 'afcglide_settings_group', 'afcglide_options' );

        add_settings_section( 'afcglide_branding_section', __( 'Branding', 'afcglide' ), [ __CLASS__, 'render_branding_section' ], 'afcglide-settings' );
        add_settings_field( 'company_logo', __( 'Company Logo', 'afcglide' ), [ __CLASS__, 'render_field_logo' ], 'afcglide-settings', 'afcglide_branding_section' );
        add_settings_field( 'company_name', __( 'Company Name', 'afcglide' ), [ __CLASS__, 'render_field_company_name' ], 'afcglide-settings', 'afcglide_branding_section' );

        add_settings_section( 'afcglide_general_section', __( 'General Settings', 'afcglide' ), null, 'afcglide-settings' );
        add_settings_field( 'posts_per_page', __( 'Listings Per Page', 'afcglide' ), [ __CLASS__, 'render_field_posts_per_page' ], 'afcglide-settings', 'afcglide_general_section' );
        add_settings_field( 'currency_symbol', __( 'Currency Symbol', 'afcglide' ), [ __CLASS__, 'render_field_currency' ], 'afcglide-settings', 'afcglide_general_section' );
        add_settings_field( 'google_maps_api_key', __( 'Google Maps API Key', 'afcglide' ), [ __CLASS__, 'render_field_maps_key' ], 'afcglide-settings', 'afcglide_general_section' );
        add_settings_field( 'delete_on_uninstall', __( 'Delete Data on Uninstall?', 'afcglide' ), [ __CLASS__, 'render_field_delete' ], 'afcglide-settings', 'afcglide_general_section' );
    }

    public static function render_branding_section() {
        echo '<p>' . __( 'Configure global company branding.', 'afcglide' ) . '</p>';
    }

    public static function render_field_logo() {
        $options = get_option( 'afcglide_options' );
        $logo_id = isset( $options['company_logo'] ) ? $options['company_logo'] : '';
        $logo_url = $logo_id ? wp_get_attachment_image_url( $logo_id, 'medium' ) : '';
        ?>
        <div class="afcglide-logo-upload">
            <input type="hidden" id="afcglide_logo_id" name="afcglide_options[company_logo]" value="<?php echo esc_attr( $logo_id ); ?>">
            <div class="afcglide-logo-preview" style="margin-bottom: 10px;">
                <?php if ( $logo_url ): ?>
                    <img src="<?php echo esc_url( $logo_url ); ?>" style="max-width: 300px;">
                <?php endif; ?>
            </div>
            <button type="button" class="button button-secondary afcglide-upload-logo-btn">Upload Logo</button>
        </div>
        <?php
    }

    public static function render_field_company_name() {
        $options = get_option( 'afcglide_options' );
        $val = isset( $options['company_name'] ) ? $options['company_name'] : '';
        echo '<input type="text" name="afcglide_options[company_name]" value="' . esc_attr( $val ) . '" class="regular-text">';
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
        echo '<div class="afcglide-danger-zone" style="background: #fff5f5; border: 1px solid #feb2b2; padding: 15px; border-radius: 8px; display: inline-block;">';
        echo '<label><input type="checkbox" name="afcglide_options[delete_on_uninstall]" value="1" ' . checked( 1, $checked, false ) . '> <span style="font-weight: 600; color: #c53030;">Delete all data on uninstall</span></label>';
        echo '</div>';
    }
}