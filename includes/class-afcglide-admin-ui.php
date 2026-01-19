<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Ghost Mode 4.0: Stealth UI Control
 * Centralizes all admin menu streamlining and role protections.
 */
class AFCGlide_Admin_UI {

    public static function init() {
        add_action( 'admin_menu', [ __CLASS__, 'streamline_admin_menu' ], 999 );
        add_action( 'admin_bar_menu', [ __CLASS__, 'add_admin_bar_shortcut' ], 999 );
        add_action( 'pre_get_posts', [ __CLASS__, 'filter_inventory_for_agents' ] );
        add_filter( 'admin_footer_text', [ __CLASS__, 'custom_admin_footer' ] );
        
        // 🚀 GLOBAL AGENT PORTAL: Login Customization
        add_action( 'login_enqueue_scripts', [ __CLASS__, 'custom_login_styles' ] );
        add_filter( 'login_headerurl', [ __CLASS__, 'custom_login_url' ] );
        add_filter( 'login_headertext', [ __CLASS__, 'custom_login_title' ] );
        add_filter( 'login_redirect', [ __CLASS__, 'agent_login_redirect' ], 10, 3 );
    }

    /**
     * AFCGlide Pro: Custom Login Aesthetic
     */
    public static function custom_login_styles() {
        ?>
        <style type="text/css">
            body.login { 
                background: #f8fafc !important; 
                display: flex; flex-direction: column; align-items: center; justify-content: center;
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif !important;
            }
            #login { width: 450px !important; padding: 0 !important; }
            
            /* 🌈 THE PAZAAZ LOGIN HEADER */
            #login h1 a {
                background: linear-gradient(90deg, #ff9a9e 0%, #fad0c4 25%, #a1c4fd 50%, #c2e9fb 75%, #d4fc79 100%) !important;
                width: 100% !important; height: 80px !important; background-size: cover !important;
                border-radius: 15px 15px 0 0 !important; margin: 0 auto !important;
                display: flex !important; align-items: center !important; justify-content: center !important;
                color: #1e293b !important; font-size: 20px !important; font-weight: 900 !important;
                text-indent: 0 !important; text-decoration: none !important; padding-top: 25px;
            }
            #login h1 a::after { content: 'AFCG-PRO PORTAL v5.0'; letter-spacing: 2px; }

            .login form { 
                background: white !important; border: 1px solid #e2e8f0 !important; 
                box-shadow: 0 25px 50px -12px rgba(0,0,0,0.1) !important;
                padding: 45px !important; border-radius: 0 0 15px 15px !important;
                margin-top: 0 !important;
            }
            .login label { font-weight: 800 !important; color: #64748b !important; text-transform: uppercase; font-size: 11px !important; letter-spacing: 1.5px; }
            .login input[type="text"], .login input[type="password"] {
                border-radius: 8px !important; border: 1px solid #cbd5e1 !important; padding: 12px !important;
                font-size: 16px !important;
            }
            .wp-core-ui .button-primary {
                background: #10b981 !important; border: none !important; border-radius: 8px !important;
                padding: 10px 30px !important; font-weight: 900 !important; text-transform: uppercase;
                letter-spacing: 1px !important; height: auto !important; line-height: 2 !important;
                box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3) !important;
                width: 100% !important; margin-top: 20px !important;
            }
            .login #nav, .login #backtoblog { text-align: center !important; font-weight: 700 !important; }
            .login #nav a, .login #backtoblog a { color: #64748b !important; }
        </style>
        <?php
    }

    public static function custom_login_url() { return home_url(); }
    public static function custom_login_title() { return 'Powered by AFCGlide Global Infrastructure'; }

    /**
     * Unbreakable Navigation: Send Agents straight to the Hub
     */
    public static function agent_login_redirect( $redirect_to, $request, $user ) {
        if ( isset( $user->roles ) && is_array( $user->roles ) ) {
            if ( in_array( 'listing_agent', $user->roles ) || in_array( 'managing_broker', $user->roles ) ) {
                return admin_url( 'admin.php?page=afcglide-dashboard' );
            }
        }
        return $redirect_to;
    }

    public static function add_admin_bar_shortcut( $wp_admin_bar ) {
        $wp_admin_bar->add_node([
            'id'    => 'afc-add-listing',
            'title' => '<span class="ab-icon dashicons-plus"></span><span class="ab-label"> ADD ASSET</span>',
            'href'  => admin_url('post-new.php?post_type=afcglide_listing'),
            'meta'  => [ 'title' => 'Initialize New AFCGlide Asset' ]
        ]);
    }

    /**
     * Ghost Mode: Hide WP Clutter for non-admins
     */
    public static function streamline_admin_menu() {
        if ( current_user_can('manage_options') ) return; // Brokers/Admins see more

        // 👻 GHOST MODE: Remove standard WP noise
        remove_menu_page( 'edit.php' );                   // Posts
        remove_menu_page( 'upload.php' );                 // Media (handled via listing)
        remove_menu_page( 'edit-comments.php' );          // Comments
        remove_menu_page( 'tools.php' );                  // Tools
        remove_menu_page( 'options-general.php' );        // Settings
        
        // Streamline AFCGlide menu
        remove_submenu_page( 'afcglide-dashboard', 'afcglide-settings' ); // Merged into dashboard
        
        // Hide Taxonomies from sidebar to keep it clean
        remove_submenu_page( 'edit.php?post_type=afcglide_listing', 'edit-tags.php?taxonomy=property_type&amp;post_type=afcglide_listing' );
        remove_submenu_page( 'edit.php?post_type=afcglide_listing', 'edit-tags.php?taxonomy=property_status&amp;post_type=afcglide_listing' );
        remove_submenu_page( 'edit.php?post_type=afcglide_listing', 'edit-tags.php?taxonomy=property_location&amp;post_type=afcglide_listing' );
        remove_submenu_page( 'edit.php?post_type=afcglide_listing', 'edit-tags.php?taxonomy=property_amenity&amp;post_type=afcglide_listing' );

        // 🚑 EMERGENCY RECOVERY: Ensure "Add New" is present for the CPT
        // Sometimes custom capabilities hide it if not perfectly mapped
        add_submenu_page(
            'edit.php?post_type=afcglide_listing',
            'Add New Listing',
            'Add New',
            'edit_posts',
            'post-new.php?post_type=afcglide_listing'
        );
    }

    /**
     * Unbreakable Data Isolation: Filter the inventory table
     */
    public static function filter_inventory_for_agents( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) return;
        
        $screen = get_current_screen();
        if ( isset($screen->post_type) && 'afcglide_listing' === $screen->post_type && 'edit-afcglide_listing' === $screen->id ) {
            // If they can't edit others' listings, they can't see them
            if ( ! current_user_can( 'edit_others_afc_listings' ) ) {
                $query->set( 'author', get_current_user_id() );
            }
        }
    }

    public static function custom_admin_footer() {
        return '<span id="footer-thankyou">AFCGlide Global infrastructure &copy; ' . date('Y') . ' | <span style="color:#10b981; font-weight:900;">SYSTEM ACTIVE</span></span>';
    }
}
