<?php
/*
 * Plugin Name: AFCGlide Listings - REFACTOR V5
 * Description: Synergy Terminal Build | World-Class Real Estate Infrastructure
 * Version: 5.0.0-GOLD
 * Author: Stevo
 * Text Domain: afcglide
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * ============================================================================
 * 1. CORE CONSTANTS & CONFIGURATION
 * ============================================================================
 */
define( 'AFCG_VERSION', '5.0.0' );
define( 'AFCG_PATH', plugin_dir_path( __FILE__ ) );
define( 'AFCG_URL', plugin_dir_url( __FILE__ ) );

/**
 * ============================================================================
 * 2. LOAD CONSTANTS CLASS
 * ============================================================================
 */
require_once AFCG_PATH . 'includes/class-afcglide-constants.php';

/**
 * ============================================================================
 * 3. AUTOLOAD CORE CLASSES
 * ============================================================================
 */
$core_classes = [
    'includes/class-cpt-tax.php',
    'includes/class-afcglide-dashboard.php',
    'includes/class-afcglide-settings.php',
    'includes/class-afcglide-metaboxes.php',
    'includes/class-afcglide-ajax-handler.php',
    'includes/class-afcglide-shortcodes.php',
    'includes/class-afcglide-scoreboard.php',
    'includes/class-afcglide-table.php',
    'includes/class-afcglide-user-profile.php',
    'includes/class-afcglide-public.php',
    'includes/class-afcglide-admin-ui.php',
    'includes/class-afcglide-block-manager.php',
    'includes/class-afcglide-identity-shield.php',
    'includes/class-afcglide-inventory.php',
    'includes/class-afcglide-welcome.php',
    'includes/helpers/class-validator.php',
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
 * ============================================================================
 * 4. INITIALIZATION HOOKS
 * ============================================================================
 */
add_action( 'init', 'afcglide_register_cpt', 0 );
add_action( 'init', 'afcglide_init_components', 10 );

function afcglide_register_cpt() {
    if ( class_exists( '\AFCGlide\Listings\AFCGlide_CPT_Tax' ) ) {
        \AFCGlide\Listings\AFCGlide_CPT_Tax::init();
    }
}

function afcglide_init_components() {
    // Admin UI & Security
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Admin_UI' ) ) \AFCGlide\Admin\AFCGlide_Admin_UI::init();
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Identity_Shield' ) ) \AFCGlide\Admin\AFCGlide_Identity_Shield::init();
    
    // Dashboard & Settings
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Dashboard' ) ) \AFCGlide\Admin\AFCGlide_Dashboard::init();
    
    // Listings Core
    if ( class_exists( '\AFCGlide\Listings\AFCGlide_Metaboxes' ) ) \AFCGlide\Listings\AFCGlide_Metaboxes::init();
    if ( class_exists( '\AFCGlide\Listings\AFCGlide_Ajax_Handler' ) ) \AFCGlide\Listings\AFCGlide_Ajax_Handler::init();
    
    // Shortcodes & Display
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Shortcodes' ) ) \AFCGlide\Admin\AFCGlide_Shortcodes::init();
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Table' ) ) \AFCGlide\Admin\AFCGlide_Table::init();
    
    // User Management
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_User_Profile' ) ) \AFCGlide\Admin\AFCGlide_User_Profile::init();
    
    // Public Facing (Gateway & Global Buttons)
    if ( class_exists( '\AFCGlide\Listings\AFCGlide_Public' ) ) \AFCGlide\Listings\AFCGlide_Public::init();
    
    // Block Editor
    if ( class_exists( '\AFCGlide\Listings\AFCGlide_Block_Manager' ) ) \AFCGlide\Listings\AFCGlide_Block_Manager::init();
    
    // Inventory & Welcome
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Inventory' ) ) \AFCGlide\Admin\AFCGlide_Inventory::init();
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Welcome' ) ) \AFCGlide\Admin\AFCGlide_Welcome::init();
}

/**
 * ============================================================================
 * 5. FRONTEND ASSET LOADING (COMPLETE)
 * ============================================================================
 */
add_action( 'wp_enqueue_scripts', 'afcglide_frontend_assets' );

function afcglide_frontend_assets() {
    global $post;

    // 1. Single Listing Page Styles
    if ( is_singular( \AFCGlide\Core\Constants::POST_TYPE ) ) {
        wp_enqueue_style( 'afc-single-listing', AFCG_URL . 'assets/css/afcglide-single-listing.css', [], AFCG_VERSION );
    }

    // 2. Submission Form (The Professional Agent Interface)
    if ( is_a( $post, 'WP_Post' ) && has_shortcode( $post->post_content, 'afcglide_submission_form' ) ) {
        
        wp_enqueue_style( 'afc-submission-css', AFCG_URL . 'assets/css/afcglide-frontend-submission.css', [], AFCG_VERSION );
        wp_enqueue_script( 'afc-submission-js', AFCG_URL . 'assets/js/afcglide-submission.js', ['jquery'], AFCG_VERSION, true );

        wp_localize_script( 'afc-submission-js', 'afc_vars', [
            'ajax_url'          => admin_url('admin-ajax.php'),
            'nonce'             => wp_create_nonce( \AFCGlide\Core\Constants::NONCE_AJAX ),
            'autosave_interval' => 30000,
            'strings' => [
                'invalid'      => __( '🚫 INVALID FILE: Please upload a JPG or PNG.', 'afcglide' ),
                'too_small'    => __( '⚠️ QUALITY REJECTED', 'afcglide' ),
                'loading'      => __( '🚀 SYNCING ASSET...', 'afcglide' ),
                'handshake'    => __( 'Initializing...', 'afcglide' ),
                'success'      => __( '✨ ASSET DEPLOYED', 'afcglide' ),
                'verifying'    => __( 'Redirecting...', 'afcglide' ),
                'error'        => __( '❌ ERROR:', 'afcglide' ),
                'retry'        => __( 'RETRY SUBMISSION', 'afcglide' ),
                'draft_saved'  => __( 'Draft saved', 'afcglide' ),
                'draft_saving' => __( 'Saving draft...', 'afcglide' ),
            ],
        ]);
    }

    // 3. Shortcode / Grid Styles
    if ( is_a( $post, 'WP_Post' ) && (has_shortcode( $post->post_content, 'afcglide' ) || has_shortcode( $post->post_content, 'afcglide_grid' )) ) {
        wp_enqueue_style( 'afc-shortcodes', AFCG_URL . 'assets/css/afcglide-shortcodes.css', [], AFCG_VERSION );
    }
}

/**
 * ============================================================================
 * 6. ADMIN ASSET LOADING
 * ============================================================================
 */
add_action( 'admin_enqueue_scripts', 'afcglide_admin_assets' );

function afcglide_admin_assets( $hook ) {
    global $post_type;
    
    $is_afc_page = ( 
        \AFCGlide\Core\Constants::POST_TYPE === $post_type || 
        ( isset($_GET['page']) && strpos($_GET['page'], 'afcglide') !== false ) ||
        in_array($hook, ['profile.php', 'user-edit.php', 'users.php', 'user-new.php'])
    );
    
    if ( ! $is_afc_page ) return;
    
    wp_enqueue_media();
    wp_enqueue_script( 'jquery-ui-sortable' );
    
    wp_enqueue_style( 'afc-admin-base', AFCG_URL . 'assets/css/afcglide-admin.css', [], AFCG_VERSION );
    wp_enqueue_style( 'afc-admin-core', AFCG_URL . 'assets/css/admin-core.css', ['afc-admin-base'], AFCG_VERSION );
    wp_enqueue_style( 'afc-admin-media', AFCG_URL . 'assets/css/admin-media.css', ['afc-admin-base'], AFCG_VERSION );
    wp_enqueue_style( 'afc-admin-metaboxes', AFCG_URL . 'assets/css/admin-metaboxes.css', ['afc-admin-base'], AFCG_VERSION );
    wp_enqueue_style( 'afc-admin-roles', AFCG_URL . 'assets/css/admin-roles.css', ['afc-admin-base'], AFCG_VERSION );

    if ( in_array($hook, ['post.php', 'post-new.php']) ) {
        wp_enqueue_style( 'afc-admin-submission', AFCG_URL . 'assets/css/admin-submission.css', ['afc-admin-core'], AFCG_VERSION );
    }
    
    wp_enqueue_script( 'afc-admin-js', AFCG_URL . 'assets/js/afcglide-admin.js', ['jquery', 'jquery-ui-sortable'], AFCG_VERSION, true );
    wp_localize_script( 'afc-admin-js', 'afc_vars', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce( \AFCGlide\Core\Constants::NONCE_AJAX ),
    ]);

    if ( isset($_GET['page']) && $_GET['page'] === 'afcglide-dashboard' ) {
        wp_enqueue_style( 'afc-dashboard-css', AFCG_URL . 'assets/css/afcglide-dashboard.css', ['afc-admin-core'], AFCG_VERSION );
    }
}

/**
 * ============================================================================
 * 7. CUSTOM LOGIN & TEMPLATES
 * ============================================================================
 */
add_action( 'login_enqueue_scripts', function() {
    wp_enqueue_style( 'afc-login', AFCG_URL . 'assets/css/afcglide-login.css', [], AFCG_VERSION );
});

add_filter( 'single_template', function( $template ) {
    if ( is_singular( \AFCGlide\Core\Constants::POST_TYPE ) ) {
        $plugin_template = AFCG_PATH . 'templates/single-afcglide_listing.php';
        return file_exists( $plugin_template ) ? $plugin_template : $template;
    }
    return $template;
});

/**
 * ============================================================================
 * 8. ACTIVATION / DEACTIVATION
 * ============================================================================
 */
register_activation_hook( __FILE__, 'afcglide_activate' );
register_deactivation_hook( __FILE__, function() { flush_rewrite_rules(); } );

function afcglide_activate() {
    if ( ! class_exists( '\AFCGlide\Listings\AFCGlide_CPT_Tax' ) ) {
        require_once AFCG_PATH . 'includes/class-cpt-tax.php';
    }
    \AFCGlide\Listings\AFCGlide_CPT_Tax::register_post_type();
    \AFCGlide\Listings\AFCGlide_CPT_Tax::register_taxonomies();
    flush_rewrite_rules();
    
    // Roles
    if ( function_exists('afcglide_init_roles') ) {
        afcglide_init_roles();
    }
}

/**
 * ============================================================================
 * 9. QUALITY & SECURITY FILTERS
 * ============================================================================
 */
add_filter( 'upload_mimes', function( $mimes ) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
});

add_filter( 'wp_handle_upload_prefilter', function( $file ) {
    if ( ! get_option('afc_quality_gatekeeper', 1) ) return $file;
    $img = getimagesize( $file['tmp_name'] );
    if ( $img && $img[0] < 1200 ) {
        $file['error'] = "⚠️ ASSET REJECTED: Luxury listings require 1200px minimum width.";
    }
    return $file;
});

/**
 * ============================================================================
 * 10. NOTIFICATION LOGIC
 * ============================================================================
 */
add_action( 'save_post_afcglide_listing', function( $post_id, $post, $update ) {
    if ( ! $update || $post->post_status !== 'sold' ) return;
    if ( get_post_meta( $post_id, '_afc_sold_logged', true ) ) return;
    update_post_meta( $post_id, '_afc_sold_logged', time() );
}, 10, 3 );