<?php
/**
 * Plugin Name:       AFCGlide Listings
 * Plugin URI:        https://example.com/afcglide-listings
 * Description:       Professional real estate listings plugin with custom post types, frontend submission, and advanced features.
 * Version:           2.3.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Stevo
 * Author URI:        https://example.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       afcglide
 * Domain Path:       /languages
 *
 * @package AFCGlide\Listings
 */

namespace AFCGlide\Listings;

if (!defined('ABSPATH')) exit;

final class AFCGlide_Listings_Plugin {

    const VERSION = '2.3.0';

    private static ?self $instance = null;
    private string $plugin_dir;
    private string $plugin_url;
    private array $loaded_classes = [];

    public static function instance(): self {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->plugin_dir = plugin_dir_path(__FILE__);
        $this->plugin_url = plugin_dir_url(__FILE__);

        if (!defined('AFCG_VERSION')) define('AFCG_VERSION', self::VERSION);
        if (!defined('AFCG_PLUGIN_DIR')) define('AFCG_PLUGIN_DIR', $this->plugin_dir);
        if (!defined('AFCG_PLUGIN_URL')) define('AFCG_PLUGIN_URL', $this->plugin_url);
        if (!defined('AFCG_PLUGIN_FILE')) define('AFCG_PLUGIN_FILE', __FILE__);
        if (!defined('AFCG_PLUGIN_BASENAME')) define('AFCG_PLUGIN_BASENAME', plugin_basename(__FILE__));

        $this->load_dependencies();
        $this->register_hooks();
    }

    private function load_dependencies(): void {
        $files = [
            'includes/class-afcglide-listings-activator.php',
            'includes/class-afcglide-listings-deactivator.php',
            'includes/class-cpt-tax.php',
            'includes/class-afcglide-metaboxes.php',
            'includes/class-afcglide-settings.php',
            'includes/class-afcglide-submission-handler.php',
            'includes/class-afcglide-submission-form.php',
            'includes/class-afcglide-public.php',
            'includes/class-afcglide-templates.php',
            'includes/user/class-afcglide-user-profile.php',
            'includes/class-afcglide-admin.php',
        ];

        foreach ($files as $file) {
            $path = $this->plugin_dir . $file;
            if (file_exists($path)) {
                require_once $path;
                $this->loaded_classes[] = $file;
            }
        }
    }

    private function register_hooks(): void {
        register_activation_hook(__FILE__, [$this, 'plugin_activate']);
        register_deactivation_hook(__FILE__, [__NAMESPACE__ . '\AFCGlide_Listings_Deactivator', 'deactivate']);

        add_action('plugins_loaded', [$this, 'init_plugin'], 10);
        add_action('init', [$this, 'load_textdomain']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function plugin_activate() {
        if (class_exists(__NAMESPACE__ . '\AFCGlide_Listings_Activator')) {
            AFCGlide_Listings_Activator::activate();
        }
    }

    public function init_plugin(): void {
        $components = [
            'AFCGlide_CPT_Tax',
            'AFCGlide_Admin',
            'AFCGlide_Metaboxes',
            'AFCGlide_Settings',
            'AFCGlide_Submission_Handler',
            'AFCGlide_Submission_Form',
            'AFCGlide_Public',
            'AFCGlide_Templates',
            'AFCGlide_User_Profile',
        ];

        foreach ($components as $component) {
            $class = __NAMESPACE__ . '\\' . $component;
            if (class_exists($class) && method_exists($class, 'init')) {
                $class::init();
            }
        }
    }

    public function load_textdomain(): void {
        load_plugin_textdomain('afcglide', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function enqueue_public_assets() {
        // placeholder
    }

    public function enqueue_admin_assets() {
        // placeholder
    }

    private function __clone() {}
    public function __wakeup() { throw new \Exception('Cannot unserialize singleton'); }
}

function afcglide_listings(): AFCGlide_Listings_Plugin {
    return AFCGlide_Listings_Plugin::instance();
}

afcglide_listings();
