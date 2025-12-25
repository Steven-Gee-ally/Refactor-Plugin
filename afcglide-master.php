<?php
namespace AFCGlide\Listings;

/**
 * Plugin Name: AFCGlide Listings
 * Description: Real Estate Listings - Full Build (v3.7 Master)
 * Version: 3.7.0-MASTER
 * Author: Stevo
 * Text Domain: afcglide-listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// Define Constants
define( 'AFCG_VERSION', '3.7.0' );
define( 'AFCG_PATH', plugin_dir_path( __FILE__ ) );
define( 'AFCG_URL', plugin_dir_url( __FILE__ ) );
define( 'AFCG_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Main Plugin Bootstrap Class
 */
class AFCGlide_Plugin {
    
    /**
     * Files to load (Physical paths)
     */
    private static $workers = [
        // Helpers
        'includes/helpers/class-validator.php',
        'includes/helpers/class-sanitizer.php',
        'includes/helpers/class-message-helper.php',
        'includes/helpers/class-upload-helper.php',
        'includes/helpers/helpers.php',
        
        // Core Classes
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
        
        // Submission Logic
        'submission/class-submission-auth.php',
        'submission/class-submission-listing.php',
        'submission/class-submission-files.php'
    ];
    
    /**
     * Classes to initialize (Namespace-ready)
     */
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

    private static $submission_classes = [
        'Submission_Auth',
        'Submission_Listing',
        'Submission_Files',
    ];

    /**
     * Initialize the plugin
     */
    public static function init() {
        self::load_files();
        
        // Boot modules on plugins_loaded to ensure the metaboxes register correctly
        add_action( 'plugins_loaded', [ __CLASS__, 'initialize_classes' ] );
        
        register_activation_hook( __FILE__, [ __CLASS__, 'on_activation' ] );
        register_deactivation_hook( __FILE__, [ __CLASS__, 'on_deactivation' ] );
    }
    
    private static function load_files() {
        foreach ( self::$workers as $worker ) {
            $file = AFCG_PATH . $worker;
            if ( file_exists( $file ) ) {
                require_once $file;
            }
        }
    }
    
    public static function initialize_classes() {
        // Init Core
        foreach ( self::$core_classes as $class ) {
            $full_class = __NAMESPACE__ . '\\' . $class;
            if ( class_exists( $full_class ) && method_exists( $full_class, 'init' ) ) {
                $full_class::init();
            }
        }
        
        // Init Submission (Sub-namespace)
        foreach ( self::$submission_classes as $class ) {
            $full_class = __NAMESPACE__ . '\\Submission\\' . $class;
            if ( class_exists( $full_class ) && method_exists( $full_class, 'init' ) ) {
                $full_class::init();
            }
        }
    }
    
    public static function on_activation() {
        if ( class_exists( __NAMESPACE__ . '\\AFCGlide_CPT_Tax' ) ) {
            \AFCGlide\Listings\AFCGlide_CPT_Tax::init();
        }
        flush_rewrite_rules();
    }
    
    public static function on_deactivation() {
        flush_rewrite_rules();
    }
}

// Start Engine
AFCGlide_Plugin::init();