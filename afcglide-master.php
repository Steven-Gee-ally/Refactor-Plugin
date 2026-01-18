<?php
/**
 * Plugin Name: AFCGlide Listings v3 - Master Suite
 * Description: High-end Real Estate Asset Management. Backbone for 100+ sites.
 * Version: 3.7.0
 * Author: AFCGlide
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * 1. DIRECTORY CONSTANTS
 */
define( 'AFCG_PATH', plugin_dir_path( __FILE__ ) );
define( 'AFCG_URL', plugin_dir_url( __FILE__ ) );

/**
 * 2. BOOTSTRAP LOGIC
 */
require_once AFCG_PATH . 'includes/class-afcglide-metaboxes.php';
require_once AFCG_PATH . 'includes/class-afcglide-shortcodes.php';
require_once AFCG_PATH . 'includes/class-afcglide-table.php';
require_once AFCG_PATH . 'includes/class-afcglide-settings.php';
require_once AFCG_PATH . 'includes/class-afcglide-dashboard.php';
require_once AFCG_PATH . 'includes/class-afcglide-user-profile.php';
require_once AFCG_PATH . 'includes/class-afcglide-ajax-handler.php';

/**
 * 3. CUSTOM ASSET REGISTRATION & ENGINE START
 */
add_action( 'init', function() {
    
    // Start the Admin Engines
    if ( class_exists('\AFCGlide\Admin\AFCGlide_Dashboard') ) \AFCGlide\Admin\AFCGlide_Dashboard::init();
    if ( class_exists('\AFCGlide\Admin\AFCGlide_Settings') ) \AFCGlide\Admin\AFCGlide_Settings::init();
    if ( class_exists('\AFCGlide\Admin\AFCGlide_Table') ) \AFCGlide\Admin\AFCGlide_Table::init();
    if ( class_exists('\AFCGlide\Admin\AFCGlide_Shortcodes') ) \AFCGlide\Admin\AFCGlide_Shortcodes::init();
    if ( class_exists('\AFCGlide\Admin\AFCGlide_User_Profile') ) \AFCGlide\Admin\AFCGlide_User_Profile::init();
    if ( class_exists('\AFCGlide\Listings\AFCGlide_Metaboxes') ) \AFCGlide\Listings\AFCGlide_Metaboxes::init();
    if ( class_exists('\AFCGlide\Listings\AFCGlide_Ajax_Handler') ) \AFCGlide\Listings\AFCGlide_Ajax_Handler::init();


    // Register the CPT and nest it UNDER the Dashboard
    register_post_type( 'afcglide_listing', [
        'labels' => [
            'name'          => 'Listings',
            'singular_name' => 'Listing',
            'add_new'       => 'Add New Listing',
            'edit_item'     => 'Edit Luxury Asset',
            'all_items'     => 'All Luxury Assets',
        ],
        'public'             => true,
        'has_archive'        => true,
        'show_in_menu'       => 'afcglide-dashboard', 
        'supports'           => [ 'title', 'editor', 'thumbnail' ], 
        'show_in_rest'       => true,
        'menu_icon'          => 'dashicons-admin-home',
    ]);
});

/**
 * 4. DYNAMIC BRANDING & LOCKDOWN
 */
add_action('admin_head', function() {
    $screen = get_current_screen();
    if ( $screen && ( $screen->post_type === 'afcglide_listing' || strpos($screen->id, 'afcglide') !== false ) ) {
        
        $primary  = get_option('afc_primary_color', '#10b981');
        $lockdown = get_option('afc_admin_lockdown', '0'); 

        echo "<style>";
        if ( false ) { // temporary bypass
            echo "
            #wpadminbar, #wpfooter, .notice, #update-nag, #screen-meta-links { display: none !important; }
            html.wp-toolbar { padding-top: 0 !important; }
            #adminmenu > li:not([id*='afcglide']):not([class*='afcglide']):not(.menu-top-last) { display: none !important; }
            #wpcontent { margin-left: 160px !important; padding-top: 0 !important; }
            #wpbody-content { background: #f8fafc !important; }";
        }
        echo "
            .wp-core-ui .button-primary { 
                background: {$primary} !important; 
                border-color: {$primary} !important; 
                box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
                border-radius: 8px !important;
            }
            .afc-price-cell, .afc-stat-value { color: {$primary} !important; }
        </style>";
    }
});

/**
 * 5. SCRIPT & STYLE HANDSHAKE
 */
// 5a. FRONT-END
add_action( 'wp_enqueue_scripts', function() {
    if ( is_singular('afcglide_listing') ) {
        wp_enqueue_style( 'afc-single-listing', AFCG_URL . 'assets/css/afcglide-single-listing.css', [], '4.4.2' );
    }
});

// 5b. BACK-END
add_action( 'admin_enqueue_scripts', function($hook) {
    global $post_type;
    
    // Check if we are on an AFCGlide related page
    $is_afc_page = ( 'afcglide_listing' === $post_type || (isset($_GET['page']) && strpos($_GET['page'], 'afcglide') !== false) );

    if ( $is_afc_page ) {
        wp_enqueue_media(); // Loads WordPress Media uploader logic
        wp_enqueue_script('jquery-ui-sortable');
        
        wp_enqueue_style( 'afc-admin-styles', AFCG_URL . 'assets/css/afcglide-admin.css', [], time() );
        wp_enqueue_script( 'afc-admin-js', AFCG_URL . 'assets/js/afcglide-admin.js', ['jquery', 'jquery-ui-sortable'], time(), true );
        
        wp_localize_script( 'afc-admin-js', 'afc_vars', [
            'ajax_url'     => admin_url('admin-ajax.php'),
            'nonce'        => wp_create_nonce('afc_nonce'),
            'quality_gate' => get_option('afc_quality_gatekeeper', '1'),
        ]);
    }
});

/**
 * 6. DESCRIPTION LABEL INJECTOR
 */
add_action( 'edit_form_after_title', function($post) {
    if ( $post && $post->post_type === 'afcglide_listing' ) {
        echo '<div class="afc-section-header" style="background:#f1f5f9; padding:15px 25px; border-radius:12px; margin:30px 0 10px 0; border-left:4px solid #64748b;">
                <h3 style="margin:0; color:#1e293b; font-size:18px;">📋 Property Narrative & Description</h3>
              </div>';
    }
});

/**
 * 7. TEMPLATE LOADER
 */
add_filter( 'single_template', function( $template ) {
    global $post;
    if ( $post && $post->post_type === 'afcglide_listing' ) {
        $plugin_template = AFCG_PATH . 'templates/single-afcglide_listing.php';
        if ( file_exists( $plugin_template ) ) {
            return $plugin_template;
        }
    }
    return $template;
});

/**
 * 9. GLOBAL ASSET - FLOATING CONTACT
 */
add_action('wp_footer', function() {
    if ( get_option('afc_whatsapp_global') !== '1' || is_singular('afcglide_listing') ) return;

    $global_phone = get_option('afc_agent_phone_display');
    $wa_color     = get_option('afc_whatsapp_color', '#25D366');
    
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
});