<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide Ghost Mode 4.0: Stealth UI Control
 * Centralizes all admin menu streamlining and role protections.
 */
class AFCGlide_Admin_UI {

    public static function init() {
        add_action( 'admin_init', [ __CLASS__, 'redirect_native_list_table' ] );
        add_action( 'admin_menu', [ __CLASS__, 'streamline_admin_menu' ], 999 );
        add_action( 'admin_bar_menu', [ __CLASS__, 'add_admin_bar_shortcut' ], 999 );
        add_action( 'pre_get_posts', [ __CLASS__, 'filter_inventory_for_agents' ] );
        add_filter( 'admin_footer_text', [ __CLASS__, 'custom_admin_footer' ] );
        add_filter( 'admin_body_class', [ __CLASS__, 'add_role_body_class' ] );
        
        // 🚀 GLOBAL AGENT PORTAL: Login Customization
        add_action( 'login_enqueue_scripts', [ __CLASS__, 'custom_login_styles' ] );
        add_filter( 'login_headerurl', [ __CLASS__, 'custom_login_url' ] );
        add_filter( 'login_headertext', [ __CLASS__, 'custom_login_title' ] );
        add_filter( 'login_redirect', [ __CLASS__, 'agent_login_redirect' ], 10, 3 );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'global_admin_styles' ] );
    }

    /**
     * AFCGlide Global Admin Refinement
     * Applies the "Pazaaz" theme to standard WP pages.
     */
    public static function global_admin_styles() {
        // Styles moved to assets/css/afcglide-admin.css
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
     * Real Estate Machine: Role-Based Sidebar
     * Agents get minimal, focused UI / Brokers get full command center
     */
    public static function streamline_admin_menu() {
        $is_broker = current_user_can('manage_options');
        $is_agent = in_array('listing_agent', wp_get_current_user()->roles);

        // ==========================================
        // AGENTS: Ultra-Clean Real Estate Machine
        // ==========================================
        if ($is_agent && !$is_broker) {
            
            // 👻 Remove ALL WordPress Default Menus
            remove_menu_page( 'index.php' );                  // Dashboard
            remove_menu_page( 'edit.php' );                   // Posts
            remove_menu_page( 'upload.php' );                 // Media
            remove_menu_page( 'edit.php?post_type=page' );    // Pages
            remove_menu_page( 'edit-comments.php' );          // Comments
            remove_menu_page( 'themes.php' );                 // Appearance
            remove_menu_page( 'plugins.php' );                // Plugins
            remove_menu_page( 'users.php' );                  // Users
            remove_menu_page( 'tools.php' );                  // Tools
            remove_menu_page( 'options-general.php' );        // Settings
            
            // Remove the default "Listings" CPT menu entirely
            remove_menu_page( 'edit.php?post_type=afcglide_listing' );
            
            // Keep ONLY AFCGlide menu items (already registered in dashboard)
            // This gives agents: Hub, Add New Asset, Inventory (via custom pages)
            
        }

        // BROKERS: Focus Mode - Strip everything but AFCGlide
        if ( current_user_can( 'manage_options' ) ) {
            if ( get_user_meta( get_current_user_id(), 'afc_focus_mode', true ) === '1' ) {
                global $menu, $submenu;
                $allowed = [
                    'afcglide-dashboard',
                    'edit.php?post_type=afcglide_listing',
                    'profile.php'
                ];
                
                foreach ( $menu as $key => $item ) {
                    if ( ! in_array( $item[2], $allowed ) && strpos($item[2], 'afcglide') === false ) {
                        unset( $menu[$key] );
                        unset( $submenu[$item[2]] ); // Clear submenus
                    }
                }
            }
        }

        // AGENTS: Hide standard WP Listing Sidebar menu
        remove_menu_page( 'edit.php?post_type=afcglide_listing' );
    }

    /**
     * UNBREAKABLE REDIRECTION
     * Intercepts standard WordPress list table and redirects to the Hub.
     */
    public static function redirect_native_list_table() {
        global $pagenow;
        
        if ( $pagenow === 'edit.php' && isset( $_GET['post_type'] ) && $_GET['post_type'] === \AFCGlide\Core\Constants::POST_TYPE ) {
            // Already filtered for AJAX/REST in restrictive loops elsewhere, but being safe
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) return;
            
            wp_safe_redirect( admin_url( 'admin.php?page=afcglide-dashboard' ) );
            exit;
        }
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
        return '';
    }

    /**
     * Add role-specific body class for theme separation
     */
    public static function add_role_body_class( $classes ) {
        $user = wp_get_current_user();
        
        if ( in_array( 'listing_agent', $user->roles ) && ! current_user_can('manage_options') ) {
            $classes .= ' afc-agent-portal';
        } elseif ( current_user_can('manage_options') ) {
            $classes .= ' afc-broker-command';
        }

        if ( get_user_meta( $user->ID, 'afc_focus_mode', true ) === '1' ) {
            $classes .= ' afc-focus-active';
        }
        
        return $classes;
    }
}
