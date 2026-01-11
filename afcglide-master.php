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
        
        // Admin Enhancements
        'includes/admin/class-afcglide-admin-menu.php',
        'includes/admin/class-afcglide-agent-protections.php',
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
        
        // Command Center: Worker Mode Logic
        add_action( 'admin_init', [ __CLASS__, 'apply_worker_mode_permissions' ] );
        
        register_activation_hook( __FILE__, [ __CLASS__, 'on_activation' ] );
        register_deactivation_hook( __FILE__, [ __CLASS__, 'on_deactivation' ] );
        
        if ( AFCG_DEBUG ) {
            add_action( 'wp_footer', [ __CLASS__, 'debug_output' ], 999 );
            add_action( 'admin_footer', [ __CLASS__, 'debug_output' ], 999 );
            add_action( 'admin_notices', [ __CLASS__, 'admin_debug_notices' ] );
        }
    }

    /**
     * WORKER MODE LOGIC:
     * This checks the Command Center switch and grants/revokes powers.
     */
    public static function apply_worker_mode_permissions() {
        $worker_mode_enabled = get_option('afc_worker_mode', 'no') === 'yes';
        $role = get_role('office_manager');

        if ( ! $role ) return;

        if ( $worker_mode_enabled ) {
            // UNLOCK: Give Office Managers the power to edit/delete other agent listings
            $role->add_cap('edit_others_afcglide_listings');
            $role->add_cap('delete_others_afcglide_listings');
            $role->add_cap('publish_afcglide_listings');
        } else {
            // LOCK: Take that power away
            $role->remove_cap('edit_others_afcglide_listings');
            $role->remove_cap('delete_others_afcglide_listings');
            $role->remove_cap('publish_afcglide_listings');
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
        if ( class_exists( __NAMESPACE__ . '\\AFCGlide_CPT_Tax' ) ) {
            AFCGlide_CPT_Tax::init();
        }
        
        foreach ( self::$core_classes as $class ) {
            if ( $class === 'AFCGlide_CPT_Tax' ) continue;
            self::init_class( $class, __NAMESPACE__ );
        }
        
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
        // 1. Setup Custom Post Types
        if ( class_exists( __NAMESPACE__ . '\\AFCGlide_CPT_Tax' ) ) {
            AFCGlide_CPT_Tax::init();
        }

        // 2. Create the "Office Manager" Role for Worker Mode
        if ( ! get_role('office_manager') ) {
            add_role( 'office_manager', 'Office Manager', [
                'read'         => true,
                'edit_posts'   => true,
                'upload_files' => true,
            ]);
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
        echo "\n\n";
    }
    
    public static function admin_debug_notices() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        if ( ! empty( self::$missing_files ) || ! empty( self::$failed_classes ) ) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>AFCGlide:</strong> Issues detected in class loading.</p></div>';
        }
    }
}

// BOOT THE ENGINE 🚀
AFCGlide_Plugin::init();