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
        wp_enqueue_style( 
            'afc-login-styles', 
            AFCG_URL . 'assets/css/afcglide-login.css', 
            [], 
            AFCG_VERSION 
        );
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
