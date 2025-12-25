<?php
namespace AFCGlide\Listings;

/**
 * Plugin Name: AFCGlide Listings
 * Description: Real Estate Listings - Full Build (Optimized v3.6)
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
    
    /**
     * Files to load (relative to plugin root)
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
        'submission/class-submission-files.php',
        // NOTE: class-submission-form.php removed - consolidated into AFCGlide_Shortcodes
    ];
    
    /**
     * Core classes to initialize
     */
    private static $core_classes = [
        'AFCGlide_CPT_Tax',
        'AFCGlide_Metaboxes',
        'AFCGlide_Shortcodes',      // Handles ALL shortcodes (login, register, submit, grid)
        'AFCGlide_Public',
        'AFCGlide_Settings',
        'AFCGlide_Ajax_Handler',
        'AFCGlide_Block_Manager',
        'AFCGlide_Admin_Assets',
        'AFCGlide_User_Profile',
        'AFCGlide_Templates',
    ];
    
    /**
     * Submission classes to initialize
     */
    private static $submission_classes = [
        'Submission_Auth',
        'Submission_Listing',
        'Submission_Files',
        // NOTE: Submission_Form removed - now handled by AFCGlide_Shortcodes
    ];
    
    /**
     * Missing files tracker
     */
    private static $missing_files = [];
    
    /**
     * Failed class initializations
     */
    private static $failed_classes = [];
    
    /**
     * Shortcodes that should be registered
     */
    private static $expected_shortcodes = [
        'afcglide_login',
        'afcglide_register',
        'afcglide_submit_listing',
        'afcglide_listings_grid',
    ];

    /**
     * Initialize the plugin
     */
    public static function init() {
        // Load all required files
        self::load_files();
        
        // Hook into WordPress init
        add_action( 'init', [ __CLASS__, 'initialize_classes' ], 5 );
        
        // Setup activation/deactivation hooks
        register_activation_hook( __FILE__, [ __CLASS__, 'on_activation' ] );
        register_deactivation_hook( __FILE__, [ __CLASS__, 'on_deactivation' ] );
        
        // Debug information (admin only)
        if ( AFCG_DEBUG ) {
            add_action( 'wp_footer', [ __CLASS__, 'debug_output' ], 999 );
            add_action( 'admin_footer', [ __CLASS__, 'debug_output' ], 999 );
            add_action( 'admin_notices', [ __CLASS__, 'admin_debug_notices' ] );
        }
    }
    
    /**
     * Load all plugin files
     */
    private static function load_files() {
        foreach ( self::$workers as $worker ) {
            $file = AFCG_PATH . $worker;
            
            if ( file_exists( $file ) ) {
                require_once $file;
            } else {
                self::$missing_files[] = $worker;
                
                // Log the error
                if ( AFCG_DEBUG ) {
                    error_log( sprintf(
                        '[AFCGlide v%s] Missing file: %s (Expected at: %s)',
                        AFCG_VERSION,
                        $worker,
                        $file
                    ) );
                }
            }
        }
        
        // Log summary
        if ( AFCG_DEBUG && ! empty( self::$missing_files ) ) {
            error_log( sprintf(
                '[AFCGlide v%s] Total missing files: %d',
                AFCG_VERSION,
                count( self::$missing_files )
            ) );
        }
    }
    
    /**
     * Initialize all plugin classes
     */
    public static function initialize_classes() {
        // Initialize core classes
        foreach ( self::$core_classes as $class ) {
            self::init_class( $class, __NAMESPACE__ );
        }
        
        // Initialize submission classes (different namespace)
        foreach ( self::$submission_classes as $class ) {
            self::init_class( $class, __NAMESPACE__ . '\\Submission' );
        }
        
        // Log initialization summary
        if ( AFCG_DEBUG ) {
            $total_classes = count( self::$core_classes ) + count( self::$submission_classes );
            $failed_count = count( self::$failed_classes );
            $success_count = $total_classes - $failed_count;
            
            error_log( sprintf(
                '[AFCGlide v%s] Class initialization: %d/%d successful, %d failed',
                AFCG_VERSION,
                $success_count,
                $total_classes,
                $failed_count
            ) );
        }
    }
    
    /**
     * Initialize a single class
     * 
     * @param string $class Class name
     * @param string $namespace Full namespace
     */
    private static function init_class( $class, $namespace ) {
        $full_class = $namespace . '\\' . $class;
        
        if ( class_exists( $full_class ) ) {
            if ( method_exists( $full_class, 'init' ) ) {
                try {
                    $full_class::init();
                    
                    if ( AFCG_DEBUG ) {
                        error_log( sprintf(
                            '[AFCGlide v%s] ✓ Initialized: %s',
                            AFCG_VERSION,
                            $full_class
                        ) );
                    }
                } catch ( \Exception $e ) {
                    self::$failed_classes[] = [
                        'class'  => $full_class,
                        'reason' => 'Exception: ' . $e->getMessage()
                    ];
                    
                    error_log( sprintf(
                        '[AFCGlide v%s] ✗ Exception initializing %s: %s',
                        AFCG_VERSION,
                        $full_class,
                        $e->getMessage()
                    ) );
                }
            } else {
                self::$failed_classes[] = [
                    'class'  => $full_class,
                    'reason' => 'Missing init() method'
                ];
                
                if ( AFCG_DEBUG ) {
                    error_log( sprintf(
                        '[AFCGlide v%s] ✗ Class %s exists but has no init() method',
                        AFCG_VERSION,
                        $full_class
                    ) );
                }
            }
        } else {
            self::$failed_classes[] = [
                'class'  => $full_class,
                'reason' => 'Class not found'
            ];
            
            if ( AFCG_DEBUG ) {
                error_log( sprintf(
                    '[AFCGlide v%s] ✗ Class not found: %s',
                    AFCG_VERSION,
                    $full_class
                ) );
            }
        }
    }
    
    /**
     * Plugin activation
     */
    public static function on_activation() {
        // Initialize classes that register CPTs/taxonomies
        if ( class_exists( __NAMESPACE__ . '\\AFCGlide_CPT_Tax' ) ) {
            AFCGlide_CPT_Tax::init();
        }
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Set activation timestamp
        update_option( 'afcglide_activated_time', time() );
        update_option( 'afcglide_version', AFCG_VERSION );
        
        if ( AFCG_DEBUG ) {
            error_log( sprintf(
                '[AFCGlide v%s] Plugin activated at %s',
                AFCG_VERSION,
                current_time( 'mysql' )
            ) );
        }
    }
    
    /**
     * Plugin deactivation
     */
    public static function on_deactivation() {
        flush_rewrite_rules();
        
        if ( AFCG_DEBUG ) {
            error_log( sprintf(
                '[AFCGlide v%s] Plugin deactivated at %s',
                AFCG_VERSION,
                current_time( 'mysql' )
            ) );
        }
    }
    
    /**
     * Output debug information in footer (admin only)
     */
    public static function debug_output() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        echo "\n<!-- ================================================ -->\n";
        echo "<!-- AFCGlide Debug Information v" . AFCG_VERSION . " -->\n";
        echo "<!-- ================================================ -->\n";
        
        // Shortcode Status
        echo "<!-- SHORTCODES REGISTERED: -->\n";
        $all_registered = true;
        foreach ( self::$expected_shortcodes as $shortcode ) {
            $exists = shortcode_exists( $shortcode );
            $status = $exists ? '✓ YES' : '✗ NO';
            echo "<!--   {$shortcode}: {$status} -->\n";
            if ( ! $exists ) $all_registered = false;
        }
        
        if ( $all_registered ) {
            echo "<!-- ✓ All shortcodes registered successfully -->\n";
        } else {
            echo "<!-- ✗ MISSING SHORTCODES DETECTED! -->\n";
        }
        
        // Missing Files
        if ( ! empty( self::$missing_files ) ) {
            echo "<!-- MISSING FILES: " . count( self::$missing_files ) . " -->\n";
            foreach ( self::$missing_files as $file ) {
                echo "<!--   ✗ " . esc_html( $file ) . " -->\n";
            }
        } else {
            echo "<!-- ✓ All files loaded successfully -->\n";
        }
        
        // Failed Classes
        if ( ! empty( self::$failed_classes ) ) {
            echo "<!-- FAILED CLASS INITIALIZATIONS: " . count( self::$failed_classes ) . " -->\n";
            foreach ( self::$failed_classes as $failure ) {
                echo "<!--   ✗ " . esc_html( $failure['class'] ) . " -->\n";
                echo "<!--      Reason: " . esc_html( $failure['reason'] ) . " -->\n";
            }
        } else {
            echo "<!-- ✓ All classes initialized successfully -->\n";
        }
        
        // General Info
        echo "<!-- Plugin Path: " . AFCG_PATH . " -->\n";
        echo "<!-- Plugin URL: " . AFCG_URL . " -->\n";
        echo "<!-- WordPress Version: " . get_bloginfo( 'version' ) . " -->\n";
        echo "<!-- PHP Version: " . PHP_VERSION . " -->\n";
        
        echo "<!-- ================================================ -->\n";
        echo "<!-- End AFCGlide Debug -->\n\n";
    }
    
    /**
     * Show admin notices for critical errors
     */
    public static function admin_debug_notices() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }
        
        $has_errors = false;
        $messages = [];
        
        // Check for missing files
        if ( ! empty( self::$missing_files ) ) {
            $has_errors = true;
            $messages[] = sprintf(
                '<strong>Missing Files:</strong> %d file(s) not found. Check error log for details.',
                count( self::$missing_files )
            );
        }
        
        // Check for failed classes
        if ( ! empty( self::$failed_classes ) ) {
            $has_errors = true;
            $messages[] = sprintf(
                '<strong>Failed Initializations:</strong> %d class(es) failed to initialize. Check error log for details.',
                count( self::$failed_classes )
            );
        }
        
        // Check for missing shortcodes (only after init has run)
        if ( did_action( 'init' ) ) {
            $missing_shortcodes = [];
            foreach ( self::$expected_shortcodes as $shortcode ) {
                if ( ! shortcode_exists( $shortcode ) ) {
                    $missing_shortcodes[] = $shortcode;
                }
            }
            
            if ( ! empty( $missing_shortcodes ) ) {
                $has_errors = true;
                $messages[] = sprintf(
                    '<strong>Missing Shortcodes:</strong> %s',
                    implode( ', ', $missing_shortcodes )
                );
            }
        }
        
        // Display consolidated error notice
        if ( $has_errors ) {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>AFCGlide v' . AFCG_VERSION . ' - Critical Issues Detected:</strong></p>';
            echo '<ul style="list-style: disc; padding-left: 20px;">';
            foreach ( $messages as $message ) {
                echo '<li>' . $message . '</li>';
            }
            echo '</ul>';
            echo '<p><em>View page source or check <code>wp-content/debug.log</code> for detailed information.</em></p>';
            echo '</div>';
        }
    }
}

// Boot the plugin
AFCGlide_Plugin::init();