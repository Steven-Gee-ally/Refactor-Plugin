<?php
namespace AFCGlide\Listings;

/**
 * Plugin Name: AFCGlide Listings
 * Description: Real Estate Listings - Full Build (Optimized v3.6.7 - Agent-Proof Edition)
 * Version: 3.6.7-AGENT-PROOF
 * Author: Stevo
 * Text Domain: afcglide-listings
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Define Plugin Constants
 */
define( 'AFCG_VERSION', '3.6.26' );
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
    
        'includes/class-afcglide-templates.php',
        'includes/class-afcglide-block-manager.php',
        'includes/class-afcglide-public.php',
        'includes/class-afcglide-ajax-handler.php',
        'includes/class-afcglide-user-profile.php',
        'includes/class-afcglide-shortcodes.php',
        
        // Admin Enhancements
        'includes/admin/class-afcglide-admin-menu.php',
        'includes/admin/class-afcglide-agent-protection.php',
    ];
    
    private static $core_classes = [
        'AFCGlide_CPT_Tax',
        'AFCGlide_Metaboxes',
        'AFCGlide_Shortcodes',
        'AFCGlide_Public',
        //'AFCGlide_Settings',//
        'AFCGlide_Ajax_Handler',
        'AFCGlide_Block_Manager',
        'AFCGlide_User_Profile',
        'AFCGlide_Templates', 
    ];
    
    private static $missing_files = [];
    private static $failed_classes = [];

    public static function init() {
        self::load_files();
        add_action( 'init', [ __CLASS__, 'initialize_classes' ], 5 );
        add_action( 'init', [ __CLASS__, 'register_listing_meta' ] );

        // Enqueue Scripts (Public and Admin)
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_public_assets' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_admin_command_center_assets' ] );
        
        // Command Center: Worker Mode Logic
        add_action( 'admin_init', [ __CLASS__, 'apply_worker_mode_permissions' ] );
        
        register_activation_hook( __FILE__, [ __CLASS__, 'on_activation' ] );
        register_deactivation_hook( __FILE__, [ __CLASS__, 'on_deactivation' ] );
        
        if ( AFCG_DEBUG ) {
            add_action( 'admin_notices', [ __CLASS__, 'admin_debug_notices' ] );
        }
    }

    /**
     * ENQUEUE ADMIN SCRIPTS
     */
    public static function enqueue_admin_command_center_assets( $hook ) {
        $screen = get_current_screen();
        
        if ( strpos( $hook, 'afcglide' ) !== false || 
             ( $screen && $screen->post_type === 'afcglide_listing' ) ||
             in_array( $hook, ['profile.php', 'user-edit.php'] )
           ) {
            
            wp_enqueue_media(); 
            wp_enqueue_style( 'afcglide-primary-font', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', [], null );
            wp_enqueue_style( 'afcglide-admin-css', AFCG_URL . 'assets/css/admin.css', [], AFCG_VERSION );
            wp_enqueue_style( 'afcglide-master-styles', AFCG_URL . 'assets/css/afcglide-styles.css', [], AFCG_VERSION );
            wp_enqueue_script( 'afcglide-admin-js', AFCG_URL . 'assets/js/afcglide-admin.js', [ 'jquery', 'jquery-ui-sortable' ], AFCG_VERSION, true );

            wp_localize_script( 'afcglide-admin-js', 'afc_vars', [
                'ajax_url'       => admin_url( 'admin-ajax.php' ),
                'lockdown_nonce' => wp_create_nonce( 'afc_lockdown_nonce' )
            ] );
        }
    }

    public static function enqueue_public_assets() {
        wp_enqueue_style( 'afcglide-public-styles', AFCG_URL . 'assets/css/afcglide-styles.css', [], AFCG_VERSION );
        wp_enqueue_style( 'afcglide-shortcodes', AFCG_URL . 'assets/css/shortcodes.css', [ 'afcglide-public-styles' ], AFCG_VERSION );
        wp_enqueue_script( 'afcglide-public-js', AFCG_URL . 'assets/js/afcglide-public.js', [ 'jquery' ], AFCG_VERSION, true );
        wp_enqueue_script( 'afcglide-submission-js', AFCG_URL . 'assets/js/afcglide-submission.js', [ 'jquery' ], AFCG_VERSION, true );

        wp_localize_script( 'afcglide-public-js', 'afcglide_ajax_object', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'afcglide_ajax_nonce' ),
            'strings'  => [
                'loading' => __( 'Processing Luxury Command...', 'afcglide' ),
                'success' => __( 'Success!', 'afcglide' ),
                'error'   => __( 'Error. Please try again.', 'afcglide' )
            ]
        ]);

        wp_localize_script( 'afcglide-submission-js', 'afc_ajax_obj', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'afcglide_ajax_nonce' ),
            'messages' => [
                'uploading' => __( 'Uploading Luxury Gallery (up to 16 photos)...', 'afcglide' ),
                'success'   => __( '🚀 Success! Your listing is live.', 'afcglide' ),
                'error'     => __( '📸 Error: Please upload at least 4 photos.', 'afcglide' )
            ]
        ]);
    }

    public static function apply_worker_mode_permissions() {
        $worker_mode_enabled = get_option('afc_worker_mode', 'no') === 'yes';
        $role = get_role('office_manager');
        if ( ! $role ) return;

        if ( $worker_mode_enabled ) {
            $role->add_cap('edit_others_afcglide_listings');
            $role->add_cap('delete_others_afcglide_listings');
            $role->add_cap('publish_afcglide_listings');
        } else {
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
        if ( class_exists( 'AFCGlide\\Admin\\AFCGlide_Admin_Menu' ) ) {
            \AFCGlide\Admin\AFCGlide_Admin_Menu::init();
        }
        if ( class_exists( 'AFCGlide\\Admin\\AFCGlide_Admin_Protection' ) ) {
            \AFCGlide\Admin\AFCGlide_Admin_Protection::init();
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
    
    public static function admin_debug_notices() {
        if ( ! current_user_can( 'manage_options' ) ) return;
        if ( ! empty( self::$missing_files ) || ! empty( self::$failed_classes ) ) {
            echo '<div class="notice notice-error is-dismissible"><p><strong>AFCGlide:</strong> Issues detected in class loading.</p></div>';
        }
    }

    /**
     * REGISTER PROTECTED META KEYS
     * This is the "Brain" that manages your luxury data points.
     */
    public static function register_listing_meta() {
        $meta_keys = [
            '_listing_price'    => 'number',
            '_listing_beds'     => 'string',
            '_listing_baths'    => 'string',
            '_listing_sqft'     => 'number',
            '_property_address' => 'string',
            '_gps_lat'          => 'string',
            '_gps_lng'          => 'string',
            '_listing_amenities'=> 'array', 
        ];

        foreach ( $meta_keys as $key => $type ) {
            register_post_meta( 'afcglide_listing', $key, [
                'show_in_rest' => true,
                'single'       => true,
                'type'         => $type,
            ]);
        }
    }
}

// BOOT THE ENGINE 🚀
AFCGlide_Plugin::init();