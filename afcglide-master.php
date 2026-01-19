<?php
/**
 * Plugin Name: AFCGlide Listings v4 - Production Ready
 * Description: High-end Real Estate Asset Management System
 * Version: 4.0.0
 * Author: AFCGlide
 * Text Domain: afcglide
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. DIRECTORY CONSTANTS
 */
define( 'AFCG_VERSION', '4.0.0' );
define( 'AFCG_PATH', plugin_dir_path( __FILE__ ) );
define( 'AFCG_URL', plugin_dir_url( __FILE__ ) );

/**
 * 2. AUTOLOADER - Load Constants First
 */
require_once AFCG_PATH . 'includes/class-afcglide-constants.php';

/**
 * 3. LOAD CORE FILES (Not Initialize Yet)
 */
$core_classes = [
    'includes/class-cpt-tax.php',
    'includes/class-afcglide-dashboard.php',
    'includes/class-afcglide-settings.php',
    'includes/class-afcglide-metaboxes.php',
    'includes/class-afcglide-ajax-handler.php',
    'includes/class-afcglide-shortcodes.php',
    'includes/class-afcglide-table.php',
    'includes/class-afcglide-user-profile.php',
    'includes/class-afcglide-public.php',
    'includes/class-afcglide-admin-ui.php',
    'includes/class-afcglide-block-manager.php',
    'includes/class-afcglide-identity-shield.php',
];

foreach ( $core_classes as $file ) {
    $path = AFCG_PATH . $file;
    if ( file_exists( $path ) ) {
        require_once $path;
    } else {
        error_log( "AFCGlide Error: Missing file - {$file}" );
    }
}

/**
 * 4. INITIALIZE ON 'init' HOOK (Correct Timing for CPT)
 */
add_action( 'init', 'afcglide_register_cpt', 0 );

function afcglide_register_cpt() {
    if ( class_exists( '\AFCGlide\Listings\AFCGlide_CPT_Tax' ) ) {
        \AFCGlide\Listings\AFCGlide_CPT_Tax::init();
    }
}

/**
 * 5. INITIALIZE ADMIN COMPONENTS
 */
add_action( 'init', 'afcglide_init_admin', 10 );

function afcglide_init_admin() {
    
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Admin_UI' ) ) {
        \AFCGlide\Admin\AFCGlide_Admin_UI::init();
    }

    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Identity_Shield' ) ) {
        \AFCGlide\Admin\AFCGlide_Identity_Shield::init();
    }
    
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Dashboard' ) ) {
        \AFCGlide\Admin\AFCGlide_Dashboard::init();
    }
    
    if ( class_exists( '\AFCGlide\Listings\AFCGlide_Metaboxes' ) ) {
        \AFCGlide\Listings\AFCGlide_Metaboxes::init();
    }
    
    if ( class_exists( '\AFCGlide\Listings\AFCGlide_Ajax_Handler' ) ) {
        \AFCGlide\Listings\AFCGlide_Ajax_Handler::init();
    }
    
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Shortcodes' ) ) {
        \AFCGlide\Admin\AFCGlide_Shortcodes::init();
    }
    
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Table' ) ) {
        \AFCGlide\Admin\AFCGlide_Table::init();
    }
    
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_User_Profile' ) ) {
        \AFCGlide\Admin\AFCGlide_User_Profile::init();
    }
    
    if ( class_exists( '\AFCGlide\Listings\AFCGlide_Public' ) ) {
        \AFCGlide\Listings\AFCGlide_Public::init();
    }
    
    if ( class_exists( '\AFCGlide\Listings\AFCGlide_Block_Manager' ) ) {
        \AFCGlide\Listings\AFCGlide_Block_Manager::init();
    }
}

/**
 * 6. ASSET LOADING
 */
add_action( 'wp_enqueue_scripts', 'afcglide_frontend_assets' );
add_action( 'admin_enqueue_scripts', 'afcglide_admin_assets' );

function afcglide_frontend_assets() {
    if ( is_singular( \AFCGlide\Core\Constants::POST_TYPE ) ) {
        wp_enqueue_style( 
            'afc-single-listing', 
            AFCG_URL . 'assets/css/afcglide-single-listing.css', 
            [], 
            AFCG_VERSION 
        );

        // Dynamic WhatsApp Color
        $wa_color = get_option('afc_whatsapp_color', '#25D366');
        $custom_css = "
            .afc-whatsapp-float { background-color: {$wa_color} !important; }
            @keyframes afc-pulse {
                0% { box-shadow: 0 0 0 0 {$wa_color}b3; } 
                70% { box-shadow: 0 0 0 15px {$wa_color}00; } 
                100% { box-shadow: 0 0 0 0 {$wa_color}00; }
            }
        ";
        wp_add_inline_style( 'afc-single-listing', $custom_css );
    }

    // Submission Form Assets (Check for shortcode or specific page logic)
    global $post;
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'afcglide_submission_form' ) ) {
        wp_enqueue_style( 
            'afc-submission-css', 
            AFCG_URL . 'assets/css/afcglide-frontend-submission.css', 
            [], 
            AFCG_VERSION 
        );

        wp_enqueue_script( 
            'afc-submission-js', 
            AFCG_URL . 'assets/js/afcglide-submission.js', 
            ['jquery'], 
            AFCG_VERSION, 
            true 
        );

        wp_localize_script( 'afc-submission-js', 'afc_vars', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce( \AFCGlide\Core\Constants::NONCE_AJAX ),
        ]);
    }
}

function afcglide_admin_assets( $hook ) {
    global $post_type;
    
    $is_afc_page = ( 
        \AFCGlide\Core\Constants::POST_TYPE === $post_type || 
        ( isset($_GET['page']) && strpos($_GET['page'], 'afcglide') !== false ) 
    );
    
    if ( ! $is_afc_page ) return;
    
    wp_enqueue_media();
    wp_enqueue_script( 'jquery-ui-sortable' );
    
    wp_enqueue_style( 
        'afc-admin-styles', 
        AFCG_URL . 'assets/css/afcglide-admin.css', 
        [], 
        AFCG_VERSION 
    );
    
    wp_enqueue_script( 
        'afc-admin-js', 
        AFCG_URL . 'assets/js/afcglide-admin.js', 
        ['jquery', 'jquery-ui-sortable'], 
        AFCG_VERSION, 
        true 
    );
    
    wp_localize_script( 'afc-admin-js', 'afc_vars', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce( \AFCGlide\Core\Constants::NONCE_AJAX ),
    ]);

    // Dashboard Specific CSS
    if ( isset($_GET['page']) && $_GET['page'] === 'afcglide-dashboard' ) {
        wp_enqueue_style( 
            'afc-dashboard-css', 
            AFCG_URL . 'assets/css/afcglide-dashboard.css', 
            [], 
            AFCG_VERSION 
        );
    }
}

/**
 * 7. SINGLE LISTING TEMPLATE OVERRIDE
 */
add_filter( 'single_template', 'afcglide_single_template' );

function afcglide_single_template( $template ) {
    if ( is_singular( \AFCGlide\Core\Constants::POST_TYPE ) ) {
        $plugin_template = AFCG_PATH . 'templates/single-afcglide_listing.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }
    return $template;
}

/**
 * 8. GLOBAL FLOATING WHATSAPP
 */
add_action( 'wp_footer', 'afcglide_global_whatsapp' );

function afcglide_global_whatsapp() {
    
    // Don't show on listing pages (they have their own button)
    if ( is_singular( \AFCGlide\Core\Constants::POST_TYPE ) ) return;
    
    // Check if global WhatsApp is enabled
    if ( \AFCGlide\Core\Constants::get_option( \AFCGlide\Core\Constants::OPT_WA_GLOBAL ) !== '1' ) return;
    
    $global_phone = \AFCGlide\Core\Constants::get_option( \AFCGlide\Core\Constants::OPT_AGENT_PHONE );
    $wa_color     = \AFCGlide\Core\Constants::get_option( \AFCGlide\Core\Constants::OPT_WA_COLOR, '#25D366' );
    
    if ( empty($global_phone) ) return;
    
    $clean_phone = preg_replace('/[^0-9]/', '', $global_phone);
    ?>
    <style>
        .afc-global-wa {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background-color: <?php echo esc_attr($wa_color); ?>;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
            z-index: 9999;
            transition: all 0.3s ease;
            animation: afc-pulse-global 2s infinite;
        }
        .afc-global-wa:hover { transform: scale(1.1); color: white; }
        .afc-global-wa svg { width: 32px; height: 32px; fill: currentColor; }
        @keyframes afc-pulse-global {
            0% { box-shadow: 0 0 0 0 <?php echo esc_attr($wa_color); ?>b3; }
            70% { box-shadow: 0 0 0 15px <?php echo esc_attr($wa_color); ?>00; }
            100% { box-shadow: 0 0 0 0 <?php echo esc_attr($wa_color); ?>00; }
        }
    </style>
    <a href="https://wa.me/<?php echo $clean_phone; ?>" class="afc-global-wa" target="_blank" rel="nofollow">
        <svg viewBox="0 0 32 32"><path d="M16 0c-8.837 0-16 7.163-16 16 0 2.825.737 5.588 2.137 8.137l-2.137 7.863 8.1-.2.1.2c2.487 1.463 5.112 2.112 7.9 2.112 8.837 0 16-7.163 16-16s-7.163-16-16-16zm8.287 21.825c-.337.95-1.712 1.838-2.737 2.05-.688.138-1.588.25-4.6-1.013-3.862-1.612-6.362-5.538-6.55-5.8-.188-.262-1.525-2.025-1.525-3.862 0-1.838.963-2.738 1.3-3.113.337-.375.75-.463 1-.463s.5 0 .712.013c.225.013.525-.088.825.638.3.713 1.013 2.475 1.1 2.663.088.188.15.413.025.663-.125.263-.188.425-.375.65-.188.225-.412.513-.587.688-.2.2-.412.412-.175.812.238.4.1.863 2.087 2.625 1.637 1.45 3.012 1.9 3.437 2.113.425.213.675.175.925-.113.25-.288 1.075-1.25 1.362-1.688.3-.425.588-.363.988-.212.4.15 2.525 1.188 2.962 1.4.438.213.738.313.838.488.1.175.1.988-.237 1.938z"/></svg>
    </a>
    <?php
}

/**
 * 9. ACTIVATION HOOK
 */
register_activation_hook( __FILE__, 'afcglide_activate' );

function afcglide_activate() {
    // Load CPT class if not loaded
    if ( ! class_exists( '\AFCGlide\Listings\AFCGlide_CPT_Tax' ) ) {
        require_once AFCG_PATH . 'includes/class-cpt-tax.php';
    }
    
    // Register CPT and Taxonomies
    \AFCGlide\Listings\AFCGlide_CPT_Tax::register_post_type();
    \AFCGlide\Listings\AFCGlide_CPT_Tax::register_taxonomies();
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Set default options if not exist
    $defaults = [
        \AFCGlide\Core\Constants::OPT_PRIMARY_COLOR  => '#10b981',
        \AFCGlide\Core\Constants::OPT_WA_COLOR       => '#25D366',
        \AFCGlide\Core\Constants::OPT_QUALITY_GATE   => '1',
        \AFCGlide\Core\Constants::OPT_ADMIN_LOCKDOWN => '0',
        \AFCGlide\Core\Constants::OPT_WA_GLOBAL      => '0',
    ];
    
    // Initialize Roles
    afcglide_init_roles();

    foreach ( $defaults as $key => $value ) {
        if ( false === get_option( $key ) ) {
            add_option( $key, $value );
        }
    }
}

/**
 * 10. ROLE INITIALIZATION
 */
function afcglide_init_roles() {
    // 1. Managing Broker (Full Access)
    add_role( 'managing_broker', 'Managing Broker', [
        'read'                        => true,
        'manage_options'              => true,
        'upload_files'                => true,
        'edit_afc_listing'            => true,
        'read_afc_listing'            => true,
        'delete_afc_listing'          => true,
        'edit_afc_listings'           => true,
        'edit_others_afc_listings'    => true,
        'publish_afc_listings'        => true,
        'read_private_afc_listings'   => true,
        'delete_afc_listings'         => true,
        'delete_private_afc_listings' => true,
        'delete_published_afc_listings'=> true,
        'delete_others_afc_listings'  => true,
        'edit_private_afc_listings'   => true,
        'create_afc_listings'         => true,
    ]);

    // 2. Listing Agent (Production Only - Elevated for Build Phase)
    add_role( 'listing_agent', 'Listing Agent', [
        'read'                        => true,
        'manage_options'              => true, // Temporary for testing/building
        'upload_files'                => true,
        'edit_afc_listing'            => true,
        'read_afc_listing'            => true,
        'delete_afc_listing'          => true,
        'edit_afc_listings'           => true,
        'publish_afc_listings'        => true,
        'delete_afc_listings'         => true,
        'delete_published_afc_listings'=> true,
        'edit_published_afc_listings' => true,
        'create_afc_listings'         => true,
        'edit_others_afc_listings'    => true, // Temporary for building
    ]);

    // Ensure administrator always has full control
    $admin = get_role('administrator');
    if ($admin) {
        $admin->add_cap('edit_afc_listing');
        $admin->add_cap('read_afc_listing');
        $admin->add_cap('delete_afc_listing');
        $admin->add_cap('edit_afc_listings');
        $admin->add_cap('edit_others_afc_listings');
        $admin->add_cap('publish_afc_listings');
        $admin->add_cap('read_private_afc_listings');
        $admin->add_cap('delete_afc_listings');
        $admin->add_cap('delete_private_afc_listings');
        $admin->add_cap('delete_published_afc_listings');
        $admin->add_cap('delete_others_afc_listings');
        $admin->add_cap('edit_private_afc_listings');
        $admin->add_cap('edit_published_afc_listings');
        $admin->add_cap('create_afc_listings');
    }
}

/**
 * 11. DEACTIVATION HOOK
 */
register_deactivation_hook( __FILE__, 'afcglide_deactivate' );

function afcglide_deactivate() {
    flush_rewrite_rules();
}