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
        // Logic: Handle Saving the Global Agent
        if ( isset($_POST['afc_save_agent_choice']) ) {
            update_option('afc_global_agent_id', sanitize_text_field($_POST['afc_agent_id']));
            echo '<div class="notice notice-success is-dismissible" style="border:none; background:#f0fdf4; color:#166534; border-left:4px solid #4ade80;"><p><strong>Success:</strong> Global Agent synchronization complete.</p></div>';
        }

        $agents = get_users(['role__in' => ['administrator', 'author', 'editor']]);
        $current_agent_id = get_option('afc_global_agent_id', '');
        $current_agent = get_userdata($current_agent_id);
        ?>
        
        <div class="wrap" style="max-width: 1000px; margin: 40px auto; font-family: -apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;">
            
            <div style="margin-bottom: 40px;">
                <h1 style="font-size: 32px; font-weight: 300; color: #1e293b; margin: 0;">AFCGlide <span style="font-weight: 700;">Home</span></h1>
                <p style="color: #94a3b8; font-size: 16px; margin-top: 5px;">Luxury Real Estate Management Engine v3</p>
            </div>

            <div style="background: #ffffff; border: 1px solid #f1f5f9; padding: 30px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); display: flex; align-items: center; justify-content: space-between; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div style="height: 60px; width: 60px; background: #eff6ff; color: #3b82f6; border-radius: 16px; display: flex; align-items: center; justify-content: center; font-size: 24px;">
                        <?php echo $current_agent ? '✨' : '👤'; ?>
                    </div>
                    <div>
                        <p style="margin: 0; font-size: 11px; text-transform: uppercase; color: #94a3b8; font-weight: 700; letter-spacing: 1px;">Primary Global Agent</p>
                        <h3 style="margin: 0; font-size: 20px; color: #334155; font-weight: 600;">
                            <?php echo $current_agent ? esc_html($current_agent->display_name) : 'Awaiting Connection...'; ?>
                        </h3>
                    </div>
                </div>

                <form method="post" style="display: flex; gap: 12px;">
                    <select name="afc_agent_id" style="padding: 12px 15px; border-radius: 12px; border: 1px solid #e2e8f0; background: #f8fafc; color: #475569; min-width: 220px; font-size: 14px;">
                        <option value="">Choose Agent Profile...</option>
                        <?php foreach ( $agents as $agent ) : ?>
                            <option value="<?php echo $agent->ID; ?>" <?php selected($current_agent_id, $agent->ID); ?>>
                                <?php echo esc_html( $agent->display_name ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" name="afc_save_agent_choice" style="background: #3b82f6; color: #fff; border: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; cursor: pointer;">Sync</button>
                </form>
            </div>

            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px;">
                
                <a href="<?php echo admin_url('edit.php?post_type=afcglide_listing'); ?>" style="text-decoration: none; background: #fff; padding: 30px; border-radius: 20px; border: 1px solid #f1f5f9; transition: all 0.3s ease;">
                    <div style="background: #fdf2f8; width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; font-size: 20px;">🏠</div>
                    <h4 style="margin: 0 0 10px 0; color: #334155; font-size: 17px;">Inventory</h4>
                    <p style="margin: 0; color: #94a3b8; font-size: 13px; line-height: 1.6;">Manage property details and luxury amenities.</p>
                </a>

                <a href="<?php echo admin_url('profile.php'); ?>" style="text-decoration: none; background: #fff; padding: 30px; border-radius: 20px; border: 1px solid #f1f5f9; transition: all 0.3s ease;">
                    <div style="background: #f0fdf4; width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; font-size: 20px;">🤳</div>
                    <h4 style="margin: 0 0 10px 0; color: #334155; font-size: 17px;">Agent Branding</h4>
                    <p style="margin: 0; color: #94a3b8; font-size: 13px; line-height: 1.6;">Edit headshots, bios, and social connections.</p>
                </a>

                <a href="<?php echo admin_url('admin.php?page=afcglide-settings'); ?>" style="text-decoration: none; background: #fff; padding: 30px; border-radius: 20px; border: 1px solid #f1f5f9; transition: all 0.3s ease;">
                    <div style="background: #fff7ed; width: 45px; height: 45px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; font-size: 20px;">⚙️</div>
                    <h4 style="margin: 0 0 10px 0; color: #334155; font-size: 17px;">Global Config</h4>
                    <p style="margin: 0; color: #94a3b8; font-size: 13px; line-height: 1.6;">API keys, currency symbols, and branding logos.</p>
                </a>

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