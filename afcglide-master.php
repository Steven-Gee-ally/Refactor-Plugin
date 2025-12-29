<?php
namespace AFCGlide\Listings;

/**
 * Plugin Name: AFCGlide Listings
 * Description: Real Estate Listings - Full Build (Optimized v3.6.6)
 * Version: 3.6.6-STEVO-LIVE
 * Author: Stevo
 * Text Domain: afcglide-listings
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Define Plugin Constants
 */
define( 'AFCG_VERSION', '3.6.6' );
define( 'AFCG_PATH', plugin_dir_path( __FILE__ ) );
define( 'AFCG_URL', plugin_dir_url( __FILE__ ) );
define( 'AFCG_BASENAME', plugin_basename( __FILE__ ) );
define( 'AFCG_DEBUG', defined( 'WP_DEBUG' ) && WP_DEBUG );

/**
 * Main Plugin Bootstrap Class
 */
class AFCGlide_Plugin {
    
    private static $workers = [
        'includes/helpers/class-validator.php',
        'includes/helpers/class-sanitizer.php',
        'includes/helpers/class-message-helper.php',
        'includes/helpers/class-upload-helper.php',
        'includes/helpers/helpers.php',
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
        'submission/class-submission-auth.php',
        'submission/class-submission-listing.php',
        'submission/class-submission-files.php',
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
        'AFCGlide_Templates', // This class now handles the Astra "Lock"
    ];
    
    private static $submission_classes = [
        'Submission_Auth',
        'Submission_Listing',
        'Submission_Files',
    ];
    
    private static $missing_files = [];
    private static $failed_classes = [];

    public static function init() {
        self::load_files();
        add_action( 'init', [ __CLASS__, 'initialize_classes' ], 5 );

        // --- STEVO'S ADDITION: CLEAN THE SIDEBAR ---
        add_action( 'admin_menu', [ __CLASS__, 'clean_admin_menu' ], 999 );
        
        register_activation_hook( __FILE__, [ __CLASS__, 'on_activation' ] );
        register_deactivation_hook( __FILE__, [ __CLASS__, 'on_deactivation' ] );
        
        if ( AFCG_DEBUG ) {
            add_action( 'wp_footer', [ __CLASS__, 'debug_output' ], 999 );
            add_action( 'admin_footer', [ __CLASS__, 'debug_output' ], 999 );
            add_action( 'admin_notices', [ __CLASS__, 'admin_debug_notices' ] );
        }
    }

    /**
     * Clean the Listings Menu
     * Moves control away from default WP UI
     */
    public static function clean_admin_menu() {
        remove_submenu_page( 'edit.php?post_type=afcglide_listing', 'post-new.php?post_type=afcglide_listing' );
        remove_submenu_page( 'edit.php?post_type=afcglide_listing', 'edit-tags.php?taxonomy=amenity&post_type=afcglide_listing' );
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
        if ( class_exists( __NAMESPACE__ . '\\AFCGlide_CPT_Tax' ) ) {
            AFCGlide_CPT_Tax::init();
        }
        foreach ( self::$core_classes as $class ) {
            if ( $class === 'AFCGlide_CPT_Tax' ) continue;
            self::init_class( $class, __NAMESPACE__ );
        }
        foreach ( self::$submission_classes as $class ) {
            self::init_class( $class, __NAMESPACE__ . '\\Submission' );
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
        echo "\n\n\n\n";
    }
    
    public static function admin_debug_notices() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        if ( ! empty( self::$missing_files ) || ! empty( self::$failed_classes ) ) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>AFCGlide:</strong> Issues detected in class loading. Check debug logs.</p></div>';
        }
    }
}

/**
 * 1. BOOT THE SYSTEM
 * All layout control is now handled within the AFCGlide_Templates class.
 */
AFCGlide_Plugin::init();