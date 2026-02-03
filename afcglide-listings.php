<?php
/*
 * Plugin Name: AFCGlide Listings - REFACTOR V5
 * Description: Synergy Terminal Build | World-Class Real Estate Infrastructure
 * Version: 5.0.0-GOLD
 * Author: Stevo
 * Text Domain: afcglide
 */

if ( ! defined( 'ABSPATH' ) ) exit;

// DEBUGGING - REMOVE AFTER FIX
error_reporting(E_ALL);
ini_set('display_errors', 1);

/**
 * ============================================================================
 * 1. CORE CONSTANTS & CONFIGURATION
 * ============================================================================
 */
define( 'AFCG_VERSION', '5.3.0-PASTEL-MASTER' );
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
    'includes/class-afcglide-synergy-engine.php',
    'includes/class-afcglide-agent-protection.php',
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
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Settings' ) ) \AFCGlide\Admin\AFCGlide_Settings::init();
    
    // Listings Core
    if ( class_exists( '\AFCGlide\Listings\AFCGlide_Metaboxes' ) ) \AFCGlide\Listings\AFCGlide_Metaboxes::init();
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Ajax_Handler' ) ) \AFCGlide\Admin\AFCGlide_Ajax_Handler::init(); // Corrected Namespace
    
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
   
    // Synergy Engine (The Brains of the Workspace)
    if ( class_exists( '\AFCGlide\Core\AFCGlide_Synergy_Engine' ) ) \AFCGlide\Core\AFCGlide_Synergy_Engine::init();

    // Agent Protection Gatekeeper
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Agent_Protection' ) ) \AFCGlide\Admin\AFCGlide_Agent_Protection::init();
}

/**
 * ============================================================================
 * 5. FRONTEND ASSET LOADING (THE SYNERGY GATEWAY)
 * ============================================================================
 */
add_action( 'wp_enqueue_scripts', 'afcglide_frontend_assets' );

function afcglide_frontend_assets() {
    global $post;
    
    // 1. Base Design System (Always Load for Brand Consistency)
    wp_enqueue_style( 'afc-global', AFCG_URL . 'assets/css/afcglide-global.css', [], AFCG_VERSION );

    // 2. Identify Terminal Pages
    $has_terminal_shortcode = false;
    if ( is_a($post, 'WP_Post') ) {
        $shortcodes = ['afcglide_submission_form', 'afc_agent_inventory', 'afcglide_submit_listing'];
        foreach ( $shortcodes as $sc ) {
            if ( has_shortcode( $post->post_content, $sc ) ) {
                $has_terminal_shortcode = true;
                break;
            }
        }
    }

    $is_core_terminal = is_page(['portfolio', 'agent-hub', 'submit-listing', 'agent-login', 'submit-asset']);

    if ( $has_terminal_shortcode || $is_core_terminal ) {
        wp_enqueue_style( 'afc-dashboard', AFCG_URL . 'assets/css/afcglide-dashboard.css', [], AFCG_VERSION );
        wp_enqueue_style( 'afc-frontend-sub', AFCG_URL . 'assets/css/afcglide-frontend-submission.css', [], AFCG_VERSION );
        
        // FontAwesome
        wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', [], '6.4.0' );

        // JS
        wp_enqueue_script( 'afc-dashboard-js', AFCG_URL . 'assets/js/afcglide-dashboard.js', ['jquery'], AFCG_VERSION, true );
        
        // Localize
        wp_localize_script( 'afc-dashboard-js', 'afc_vars', [
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'afc_ajax_nonlinear_nonce' ),
            'site_url' => site_url(),
            'user_id'  => get_current_user_id()
        ]);
    }
    
    // 3. Single Listings (The Money Page)
    if ( is_singular( 'afcglide_listing' ) ) {
        wp_enqueue_style( 'afc-single', AFCG_URL . 'assets/css/afcglide-single-listing.css', [], AFCG_VERSION );
        wp_enqueue_script( 'afc-public-js', AFCG_URL . 'assets/js/afcglide-public.js', ['jquery'], AFCG_VERSION, true );
    }
}
