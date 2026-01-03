<?php
namespace AFCGlide\Listings;

/**
 * Plugin Name: AFCGlide Listings
 * Description: Real Estate Listings - Full Build (Optimized v3.6.7 - Agent-Proof Edition)
 * Version: 3.6.7-AGENT-PROOF
 * Author: Stevo
 * Text Domain: afcglide-listings
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Define Plugin Constants
 */
define( 'AFCG_VERSION', '3.6.7' );
define( 'AFCG_PATH', plugin_dir_path( __FILE__ ) );
define( 'AFCG_URL', plugin_dir_url( __FILE__ ) );
define( 'AFCG_BASENAME', plugin_basename( __FILE__ ) );
define( 'AFCG_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );

/**
 * Main Plugin Bootstrap Class
 */
class AFCGlide_Plugin {
    
    private static $workers = [
        // Helper Classes
        'includes/helpers/class-validator.php',
        'includes/helpers/class-sanitizer.php',
        'includes/helpers/class-message-helper.php',
        'includes/helpers/class-upload-helper.php',
        'includes/helpers/helpers.php',
        
        // Core Functionality
        'includes/class-cpt-tax.php',
        'includes/class-afcglide-metaboxes.php',
        'includes/class-afcglide-settings.php',
        'includes/class-afcglide-templates.php',
        'includes/class-afcglide-block-manager.php',
        'includes/class-afcglide-admin-assets.php',
        'includes/class-afcglide-public.php',
        'includes/class-afcglide-ajax-handler.php',
        'includes/class-afcglide-user-profile.php',
        'includes/class-afcglide-shortcodes.php',
        
        // Admin Enhancements (NEW)
        'includes/admin/class-afcglide-admin-menu.php',        // Clean admin interface
        'includes/admin/class-afcglide-agent-protections.php', // Delete protection & duplicate
    ];
    
    private static $core_classes = [
        'AFCGlide_CPT_Tax',
        'AFCGlide_Metaboxes',
        'AFCGlide_Shortcodes',
        'AFCGlide_Public',
        'AFCGlide_Settings',
        'AFCGlide_Ajax_Handler',
        'AFCGlide_Block_Manager',
        'AFCGlide_Admin_Assets',
        'AFCGlide_User_Profile',
        'AFCGlide_Templates', 
    ];
    
    private static $missing_files = [];
    private static $failed_classes = [];

    public static function init() {
        self::load_files();
        add_action( 'init', [ __CLASS__, 'initialize_classes' ], 5 );
        
        register_activation_hook( __FILE__, [ __CLASS__, 'on_activation' ] );
        register_deactivation_hook( __FILE__, [ __CLASS__, 'on_deactivation' ] );
        
        if ( AFCG_DEBUG ) {
            add_action( 'wp_footer', [ __CLASS__, 'debug_output' ], 999 );
            add_action( 'admin_footer', [ __CLASS__, 'debug_output' ], 999 );
            add_action( 'admin_notices', [ __CLASS__, 'admin_debug_notices' ] );
        }
    }

    private static function load_files() {
        foreach ( self::$workers as $worker ) {
            $file = AFCG_PATH . $worker;
            if ( file_exists( $file ) ) {
                require_once $file;
            } else {
                self::$missing_files[] = $worker;
            }
        }
    }
    
    public static function initialize_classes() {
        // Initialize CPT/Tax first (creates post type)
        if ( class_exists( __NAMESPACE__ . '\\AFCGlide_CPT_Tax' ) ) {
            AFCGlide_CPT_Tax::init();
        }
        
        // Initialize core listing classes
        foreach ( self::$core_classes as $class ) {
            if ( $class === 'AFCGlide_CPT_Tax' ) continue;
            self::init_class( $class, __NAMESPACE__ );
        }
        
        // Initialize Admin Menu Customizer (different namespace)
        if ( class_exists( 'AFCGlide\\Admin\\AFCGlide_Admin_Menu' ) ) {
            \AFCGlide\Admin\AFCGlide_Admin_Menu::init();
        }
        
        // Initialize Agent Protections (different namespace) - NEW
        if ( class_exists( 'AFCGlide\\Admin\\AFCGlide_Agent_Protections' ) ) {
            \AFCGlide\Admin\AFCGlide_Agent_Protections::init();
        }
    }
    
    private static function init_class( $class, $namespace ) {
        $full_class = $namespace . '\\' . $class;
        if ( class_exists( $full_class ) && method_exists( $full_class, 'init' ) ) {
            try {
                $full_class::init();
            } catch ( \Exception $e ) {
                self::$failed_classes[] = ['class' => $full_class, 'reason' => $e->getMessage()];
            }
        }
    }
    
    public static function on_activation() {
        if ( class_exists( __NAMESPACE__ . '\\AFCGlide_CPT_Tax' ) ) {
            AFCGlide_CPT_Tax::init();
        }
        flush_rewrite_rules();
        update_option( 'afcglide_activated_time', time() );
        update_option( 'afcglide_version', AFCG_VERSION );
    }
    
    public static function on_deactivation() {
        flush_rewrite_rules();
    }
    
    public static function debug_output() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        echo "\n<!-- AFCGlide Debug: Plugin loaded successfully -->\n";
    }
    
    public static function admin_debug_notices() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        if ( ! empty( self::$missing_files ) || ! empty( self::$failed_classes ) ) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>AFCGlide:</strong> Issues detected in class loading. Check debug logs.</p></div>';
        }
    }
}

// BOOT THE ENGINE 🚀
AFCGlide_Plugin::init();