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
    'includes/class-afcglide-synergy-engine.php',
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
    'includes/class-afcglide-seo.php',
    'includes/class-afcglide-leads.php',
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
    
    // 0. Initialize Synergy Engine (THE BRAIN) - MUST BE FIRST
    if ( class_exists( '\AFCGlide\Core\AFCGlide_Synergy_Engine' ) ) {
        \AFCGlide\Core\AFCGlide_Synergy_Engine::init();
    }
    
    // 1. Sidebar & Menu Control - DISABLED (conflicts with Dashboard)
// if ( class_exists( '\AFCGlide\Admin\AFCGlide_Admin_Menu' ) ) {
//     \AFCGlide\Admin\AFCGlide_Admin_Menu::init();
// }

    // 2. Statistics & Scoreboard
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Scoreboard' ) ) {
        \AFCGlide\Admin\AFCGlide_Scoreboard::init();
    }

    // 3. Identity Shield (Branding)
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Identity_Shield' ) ) {
        \AFCGlide\Admin\AFCGlide_Identity_Shield::init();
    }
    
    // 4. Management Hub Frame
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Dashboard' ) ) {
        \AFCGlide\Admin\AFCGlide_Dashboard::init();
    }

    // 5. Existing Core Components
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Admin_UI' ) ) {
        \AFCGlide\Admin\AFCGlide_Admin_UI::init();
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
    
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Inventory' ) ) {
        \AFCGlide\Admin\AFCGlide_Inventory::init();
    }
    
    if ( class_exists( '\AFCGlide\Admin\AFCGlide_Welcome' ) ) {
        \AFCGlide\Admin\AFCGlide_Welcome::init();
    }

    if ( class_exists( '\AFCGlide\Core\AFCGlide_SEO' ) ) {
        \AFCGlide\Core\AFCGlide_SEO::init();
    }

    if ( class_exists( '\AFCGlide\Core\AFCGlide_Leads' ) ) {
        \AFCGlide\Core\AFCGlide_Leads::init();
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

    // Leaflet Assets for Mapping
    wp_enqueue_style( 'afc-leaflet-css', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css', [], '1.9.4' );
    wp_enqueue_script( 'afc-leaflet-js', 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', [], '1.9.4', true );

    wp_enqueue_script( 'afc-public-js', AFCG_URL . 'assets/js/afcglide-public.js', ['jquery', 'afc-leaflet-js'], AFCG_VERSION, true );
    wp_localize_script( 'afc-public-js', 'afc_vars', [
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce'    => wp_create_nonce( \AFCGlide\Core\Constants::NONCE_AJAX ),
        'lang'     => afcglide_get_current_lang(),
    ]);

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
            'lang'     => afcglide_get_current_lang(),
            'strings'  => [
                'loading'    => __('🚀 SYNCING ASSET...', 'afcglide'),
                'success'    => __('✨ ASSET DEPLOYED', 'afcglide'),
                'error'      => __('❌ ERROR:', 'afcglide'),
                'invalid'    => __('🚫 INVALID FILE: Please upload a JPG or PNG.', 'afcglide'),
                'too_small'  => __('⚠️ QUALITY REJECTED: Luxury listings require 1200px width minimum.', 'afcglide'),
                'retry'      => __('RETRY SUBMISSION', 'afcglide'),
                'verifying'  => __('Listing Verified. Redirecting...', 'afcglide'),
                'handshake'  => __('Initializing secure handshake with server...', 'afcglide'),
            ]
        ]);
    }
}

function afcglide_admin_assets( $hook ) {
    global $post_type;
    
    $is_afc_page = ( 
        \AFCGlide\Core\Constants::POST_TYPE === $post_type || 
        ( isset($_GET['page']) && strpos($_GET['page'], 'afcglide') !== false ) ||
        'profile.php' === $hook ||
        'user-edit.php' === $hook ||
        'users.php' === $hook ||
        'user-new.php' === $hook
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
 * 10. MULTILINGUAL ENGINE (EN/ES)
 */
function afcglide_get_current_lang() {
    if ( isset($_GET['lang']) && in_array($_GET['lang'], ['en', 'es']) ) {
        // Option: set cookie here for persistence
        return $_GET['lang'];
    }
    return 'en'; // Default
}

function afcglide_get_localized_url( $lang ) {
    return add_query_arg( 'lang', $lang, get_permalink() );
}

add_action( 'wp_footer', 'afcglide_language_toggle' );
function afcglide_language_toggle() {
    // Only show on AFC pages for now or globally? Let's go global for Enterprise feel.
    $current_lang = afcglide_get_current_lang();
    ?>
    <style>
        .afc-lang-switcher {
            position: fixed;
            bottom: 30px;
            left: 30px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            padding: 8px 15px;
            border-radius: 50px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
            z-index: 10000;
            display: flex;
            gap: 15px;
            font-family: 'Inter', sans-serif;
            font-weight: 800;
            font-size: 11px;
            letter-spacing: 1px;
            border: 1px solid rgba(0,0,0,0.05);
        }
        .afc-lang-switcher a {
            text-decoration: none;
            color: #94a3b8;
            transition: all 0.3s ease;
        }
        .afc-lang-switcher a.active {
            color: #1e293b;
        }
        .afc-lang-switcher a:hover {
            color: #10b981;
        }
    </style>
    <div class="afc-lang-switcher">
        <a href="<?php echo esc_url( add_query_arg('lang', 'en') ); ?>" class="<?php echo $current_lang === 'en' ? 'active' : ''; ?>">EN</a>
        <span style="color: #e2e8f0;">|</span>
        <a href="<?php echo esc_url( add_query_arg('lang', 'es') ); ?>" class="<?php echo $current_lang === 'es' ? 'active' : ''; ?>">ES</a>
    </div>

    <?php if ( is_singular( \AFCGlide\Core\Constants::POST_TYPE ) ) : ?>
    <div id="afc-showing-modal" class="afc-modal-overlay" style="display:none;">
        <div class="afc-modal-container">
            <button class="afc-modal-close">×</button>
            <div class="afc-modal-header">
                <h3>💎 <?php echo $current_lang === 'es' ? 'Solicitar Visita Privada' : 'Request Private Showing'; ?></h3>
                <p><?php echo $current_lang === 'es' ? 'Nuestros especialistas coordinarán su recorrido exclusivo.' : 'Our specialists will coordinate your exclusive tour.'; ?></p>
            </div>
            <form id="afc-showing-form">
                <input type="hidden" name="post_id" value="<?php echo get_the_ID(); ?>">
                <input type="text" name="lead_name" placeholder="<?php echo $current_lang === 'es' ? 'Nombre Completo' : 'Full Name'; ?>" required>
                <input type="email" name="lead_email" placeholder="<?php echo $current_lang === 'es' ? 'Correo Electrónico' : 'Email Address'; ?>" required>
                <input type="text" name="lead_phone" placeholder="<?php echo $current_lang === 'es' ? 'Teléfono / WhatsApp' : 'Phone Number'; ?>">
                <textarea name="lead_message" placeholder="<?php echo $current_lang === 'es' ? 'Intereses o preguntas especiales...' : 'Special interests or questions...'; ?>"></textarea>
                <button type="submit" class="afc-btn-primary"><?php echo $current_lang === 'es' ? 'Enviar Solicitud' : 'Submit Request'; ?></button>
                <div id="afc-modal-feedback"></div>
            </form>
        </div>
    </div>

    <style>
        .afc-modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.8); backdrop-filter: blur(8px); z-index: 100000; display: flex; align-items: center; justify-content: center; }
        .afc-modal-container { background: white; width: 100%; max-width: 450px; padding: 40px; border-radius: 20px; position: relative; box-shadow: 0 25px 50px rgba(0,0,0,0.3); }
        .afc-modal-close { position: absolute; top: 20px; right: 20px; font-size: 24px; border: none; background: none; cursor: pointer; color: #94a3b8; }
        .afc-modal-header h3 { margin-top: 0; color: #1e293b; font-size: 22px; }
        .afc-modal-header p { color: #64748b; font-size: 14px; margin-bottom: 30px; }
        #afc-showing-form input, #afc-showing-form textarea { width: 100%; margin-bottom: 20px; padding: 15px; border: 1px solid #e2e8f0; border-radius: 10px; font-family: inherit; }
        #afc-showing-form button { width: 100%; padding: 18px; font-weight: 800; }
    </style>

    <script>
    jQuery(document).ready(function($) {
        $('.afc-trigger-showing').click(function() { 
            $('#afc-showing-modal').fadeIn(300).css('display', 'flex'); 
        });
        $('.afc-modal-close, .afc-modal-overlay').click(function(e) {
            if (e.target !== this) return;
            $('#afc-showing-modal').fadeOut(300);
        });
        $('#afc-showing-form').submit(function(e) {
            e.preventDefault();
            const $btn = $(this).find('button');
            $btn.prop('disabled', true).text('⌛ SYNCING...');
            $.post(afc_vars.ajax_url, $(this).serialize() + '&action=afc_submit_lead&security=' + afc_vars.nonce, function(res) {
                if(res.success) {
                    $('#afc-modal-feedback').html('<p style="color:#10b981; margin-top:15px; font-weight:bold;">' + res.data + '</p>');
                    $btn.text('✅ SENT');
                    setTimeout(function() { $('#afc-showing-modal').fadeOut(300); }, 2000);
                } else {
                    alert(res.data);
                    $btn.prop('disabled', false).text('RETRY REQUEST');
                }
            });
        });
    });
    </script>
    <?php endif; ?>
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

    // 2. Listing Agent
    add_role( 'listing_agent', 'Listing Agent', [
        'read'                        => true,
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
 * 11. ASSET OPTIMIZATION & MACHINE LOGIC
 */

// Enable WebP support for older WP versions (if applicable)
add_filter( 'upload_mimes', function( $mimes ) {
    $mimes['webp'] = 'image/webp';
    return $mimes;
});

// High-Res Auto-Resizer: Prevents site bloat from 50MB photos
add_filter( 'wp_handle_upload_prefilter', function( $file ) {
    if ( ! get_option('afc_quality_gatekeeper', 1) ) return $file;

    $img = getimagesize( $file['tmp_name'] );
    $minimum_width = 1200;
    
    // Hard rejection if too small (The 1200px Gate)
    if ( $img && $img[0] < $minimum_width ) {
        $file['error'] = "⚠️ ASSET REJECTED: Luxury listings require 1200px minimum width. Detected: {$img[0]}px";
    }

    return $file;
});

// SUCCESS NOTIFICATION LOGIC: When a listing hits "SOLD"
add_action( 'save_post_afcglide_listing', function( $post_id, $post, $update ) {
    if ( ! $update || $post->post_status !== 'sold' ) return;
    
    // Check if it was already sold (prevent duplicate logs)
    if ( get_post_meta( $post_id, '_afc_sold_logged', true ) ) return;

    // Log the success for the Broker Activity Stream
    update_post_meta( $post_id, '_afc_sold_logged', time() );
    
    // Pro-tip: Here is where we would trigger an email or SMS notification
}, 10, 3 );

/**
 * Enterprise Cache Refresh on Deletion
 */
add_action( 'deleted_post', function( $post_id ) {
    if ( get_post_type($post_id) === \AFCGlide\Core\Constants::POST_TYPE ) {
        if ( class_exists('\AFCGlide\Listings\AFCGlide_Ajax_Handler') ) {
            \AFCGlide\Listings\AFCGlide_Ajax_Handler::clear_filter_cache();
        }
    }
});

/**
 * 12. DEACTIVATION HOOK
 */
register_deactivation_hook( __FILE__, 'afcglide_deactivate' );

function afcglide_deactivate() {
    flush_rewrite_rules();
}