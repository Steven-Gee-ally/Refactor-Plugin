<?php
namespace AFCGlide\Admin; 

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Dashboard - The Executive Hub
 * Version 5.0.1 - Matrix Overhaul Optimized for External CSS
 */
class AFCGlide_Dashboard {
    
    public static function init() {
        // Note: admin_menu registration is handled by AFCGlide_Ajax_Handler
        add_action( 'admin_init', [ __CLASS__, 'handle_protocol_execution' ] );
        add_action( 'admin_init', [ __CLASS__, 'handle_agent_creation' ] );
        add_action( 'admin_init', [ __CLASS__, 'register_backbone_settings' ] );
        add_action( 'admin_notices', [ __CLASS__, 'check_homepage_configuration' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_dashboard_assets' ] );
    }

    public static function enqueue_dashboard_assets($hook) {
        if ( strpos($hook, 'afcglide-dashboard') === false ) return;
        wp_enqueue_style( 'afcglide-dashboard-css', plugins_url( 'assets/css/afcglide-dashboard.css', dirname(__FILE__) ) );
    }

    public static function register_backbone_settings() {
        $settings = [
            'afc_agent_name', 'afc_agent_phone_display', 'afc_primary_color', 
            'afc_whatsapp_color', 'afc_brokerage_address', 'afc_license_number', 
            'afc_quality_gatekeeper', 'afc_global_lockdown', 'afc_identity_shield', 
            'afc_whatsapp_global', 'afc_system_label'
        ];
        foreach ($settings as $setting) {
            register_setting( 'afcglide_settings_group', $setting );
        }
    }

    public static function register_welcome_page() {
        $system_label = get_option('afc_system_label', 'AFCGlide');
        add_menu_page($system_label . ' Hub', $system_label, 'read', 'afcglide-dashboard', [ __CLASS__, 'render_welcome_screen' ], 'dashicons-dashboard', 5.9);
        add_submenu_page('afcglide-dashboard', 'System Manual', '📘 System Manual', 'read', 'afcglide-manual', [ __CLASS__, 'render_manual_page' ]);
    }

    public static function render_welcome_screen() {
        $is_broker = current_user_can('manage_options');
        $display_name = strtoupper(wp_get_current_user()->display_name);
        $system_label = get_option('afc_system_label', 'AFCGlide');
        ?>
        <div class="afc-control-center">
            <div class="afc-hub-brand">
                <div class="afc-main-logo">
                    <div class="afc-logo-icon-wrap"><span class="dashicons dashicons-admin-site"></span></div>
                    <div class="afc-logo-text">
                        <strong><?php echo esc_html($system_label); ?></strong>
                        <span>Global Infrastructure</span>
                    </div>
                </div>
            </div>

            <div class="afc-top-bar">
                <div class="afc-top-bar-section">Operator: <span><?php echo esc_html($display_name); ?></span></div>
                <div class="afc-top-bar-section">Node: <span>AFCG-PRO-v5.0</span></div>
                <div class="afc-focus-wrap">
                    <span>SECURITY SHIELD</span>
                    <label class="afc-switch">
                        <input type="checkbox" <?php checked(get_option('afc_identity_shield'), '1'); ?> disabled>
                        <span class="switch-slider"></span>
                    </label>
                </div>
            </div>

            <div class="afc-metrics-grid">
                <div class="afc-metric-card metric-blue">
                    <div class="metric-label">Live Assets</div>
                    <div class="metric-value"><?php echo self::get_listing_count('publish'); ?></div>
                </div>
                <div class="afc-metric-card metric-sky">
                    <div class="metric-label">Under Contract</div>
                    <div class="metric-value"><?php echo self::get_listing_count('pending'); ?></div>
                </div>
                <div class="afc-metric-card metric-slate">
                    <div class="metric-label">Portfolio Volume</div>
                    <div class="metric-value">$<?php echo number_format(self::calculate_total_volume()); ?></div>
                </div>
            </div>

            <?php if ($is_broker) : ?>
            <div class="afc-broker-matrix">
                <div class="afc-matrix-card afc-header-yellow">
                    <h2><span class="dashicons dashicons-admin-settings"></span> System Backbone</h2>
                    <form method="post" action="options.php">
                        <?php settings_fields( 'afcglide_settings_group' ); ?>
                        <div class="afc-backbone-grid">
                            <div class="afc-backbone-item">
                                <label>System White Label</label>
                                <input type="text" name="afc_system_label" value="<?php echo esc_attr($system_label); ?>">
                            </div>
                            <div class="afc-backbone-item">
                                <label>WhatsApp Color</label>
                                <input type="color" name="afc_whatsapp_color" value="<?php echo esc_attr(get_option('afc_whatsapp_color', '#25d366')); ?>">
                            </div>
                            <div class="afc-toggle-row">
                                <div class="afc-toggle-item">
                                    <label class="afc-switch">
                                        <input type="checkbox" name="afc_quality_gatekeeper" value="1" <?php checked(get_option('afc_quality_gatekeeper'), '1'); ?>>
                                        <span class="switch-slider"></span>
                                    </label>
                                    <span>Gatekeeper</span>
                                </div>
                            </div>
                        </div>
                        <div class="afc-matrix-footer">
                            <button type="submit" class="afc-vogue-btn">Update Backbone</button>
                        </div>
                    </form>
                </div>

                <div class="afc-matrix-card afc-header-orange">
                    <h2><span class="dashicons dashicons-cloud"></span> System Initializer</h2>
                    <p style="font-size: 12px; color: #64748b;">Awaiting Deployment: Core platform pages can be auto-generated with a single sync.</p>
                    <button class="afc-vogue-btn afc-initialize-btn">Initialize Core Pages</button>
                </div>
            </div>

            <div class="afc-performance-grid">
                <div class="afc-section afc-header-red">
                    <div class="afc-section-header">
                        <div class="afc-pulse"></div>
                        <h2>System Heartbeat</h2>
                    </div>
                    <?php self::render_activity_stream(); ?>
                </div>

                <div class="afc-section">
                    <div class="afc-section-header">
                        <h2>Team Performance Roster</h2>
                    </div>
                    <?php self::render_team_roster(); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }

    public static function render_activity_stream() {
        $recent = get_posts(['post_type' => C::POST_TYPE, 'posts_per_page' => 5]);
        if (empty($recent)) {
            echo '<div class="afc-activity-empty">Listening for global activity...</div>';
            return;
        }
        foreach ($recent as $post) {
            echo '<div class="afc-activity-item">';
            echo '<div><span class="afc-activity-status">UPDATED</span><strong>'.esc_html($post->post_title).'</strong></div>';
            echo '<div class="afc-activity-time">'.human_time_diff(get_the_modified_time('U', $post->ID)).' ago</div>';
            echo '</div>';
        }
    }

    public static function render_team_roster() {
        $agents = get_users(['role__in' => ['administrator', 'listing_agent']]);
        echo '<table class="afc-team-table"><thead><tr><th>Operator</th><th>Units</th><th>Volume</th></tr></thead><tbody>';
        foreach ($agents as $agent) {
            echo '<tr><td>'.esc_html($agent->display_name).'</td><td>0</td><td>$0</td></tr>';
        }
        echo '</tbody></table>';
    }

    private static function get_listing_count($status) {
        $count = wp_count_posts(C::POST_TYPE);
        return $count->$status ?? 0;
    }

    private static function calculate_total_volume() {
        return 0; // Placeholder for logic
    }

    public static function render_manual_page() {
        // Implementation of System Manual using .afc-manual-container
    }

    public static function handle_protocol_execution() { /* Logic */ }
    public static function handle_agent_creation() { /* Logic */ }
    public static function check_homepage_configuration() { /* Logic */ }
}

AFCGlide_Dashboard::init();