<?php
/**
 * AFCGlide Constants
 * Central definition of all meta keys, options, and system constants
 * 
 * @package AFCGlide
 * @version 4.0.0
 */

namespace AFCGlide\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Constants {
    
    /**
     * Plugin Version
     */
    const VERSION = '4.0.0';
    
    /**
     * Meta Keys - Listing Data
     */
    const META_INTRO       = '_listing_intro_text';
    const META_NARRATIVE   = '_listing_narrative';
    const META_PRICE       = '_listing_price';
    const META_BEDS        = '_listing_beds';
    const META_BATHS       = '_listing_baths';
    const META_SQFT        = '_listing_sqft';
    const META_ADDRESS     = '_listing_address';
    const META_STATUS      = '_listing_status';
    const META_AMENITIES   = '_listing_amenities';
    const META_VIEWS       = '_listing_views_count';
    const META_PDF_ID      = '_listing_pdf_id';
    const META_OPEN_HOUSE  = '_listing_showing_schedule';
    
    /**
     * Meta Keys - Location
     */
    const META_GPS_LAT     = '_gps_lat';
    const META_GPS_LNG     = '_gps_lng';
    
    /**
     * Meta Keys - Media
     */
    const META_HERO_ID     = '_listing_hero_id';
    const META_GALLERY_IDS = '_listing_gallery_ids';
    const META_STACK_IDS   = '_listing_stack_ids';
    
    /**
     * Meta Keys - Agent Info
     */
    const META_AGENT_NAME  = '_agent_name_display';
    const META_AGENT_PHONE = '_agent_phone_display';
    const META_AGENT_PHOTO = '_agent_photo_id';
    const META_SHOW_WA     = '_show_floating_whatsapp';
    
    /**
     * User Meta Keys
     */
    const USER_PHONE       = 'agent_phone';
    const USER_LICENSE     = 'agent_license';
    const USER_OFFICE      = 'agent_office';
    const USER_SPECIALTIES = 'agent_specialties';
    const USER_BIO         = 'agent_bio';
    const USER_PHOTO       = 'agent_photo';
    
    /**
     * Options Keys
     */
    const OPT_AGENT_NAME      = 'afc_agent_name';
    const OPT_AGENT_PHONE     = 'afc_agent_phone_display';
    const OPT_PRIMARY_COLOR   = 'afc_primary_color';
    const OPT_WA_COLOR        = 'afc_whatsapp_color';
    const OPT_BROKERAGE_ADDR  = 'afc_brokerage_address';
    const OPT_LICENSE_NUM     = 'afc_license_number';
    const OPT_QUALITY_GATE    = 'afc_quality_gatekeeper';
    const OPT_ADMIN_LOCKDOWN  = 'afc_admin_lockdown';
    const OPT_WA_GLOBAL       = 'afc_whatsapp_global';
    const OPT_GLOBAL_LOCKDOWN = 'afc_global_lockdown';
    const OPT_IDENTITY_SHIELD = 'afc_identity_shield';
    
    /**
     * Post Type
     */
    const POST_TYPE        = 'afcglide_listing';
    
    /**
     * Taxonomies
     */
    const TAX_LOCATION     = 'property_location';
    const TAX_TYPE         = 'property_type';
    const TAX_STATUS       = 'property_status';
    const TAX_AMENITY      = 'property_amenity';
    
    /**
     * AJAX Actions
     */
    const AJAX_SUBMIT      = 'afcglide_submit_listing';
    const AJAX_FILTER      = 'afcglide_filter_listings';
    const AJAX_SAVE_DRAFT  = 'afcglide_save_draft';
    const AJAX_LOCKDOWN    = 'afc_toggle_lockdown_ajax';
    
    /**
     * Nonces
     */
    const NONCE_AJAX       = 'afc_nonce';
    const NONCE_META       = 'afcglide_meta_nonce';
    const NONCE_PROTOCOLS  = 'afc_protocols';
    
    /**
     * Limits
     */
    const MAX_GALLERY      = 16;
    const MAX_STACK        = 3;
    const MIN_IMAGE_WIDTH  = 1200;
    
    /**
     * Capabilities
     */
    const CAP_MANAGE       = 'manage_options';
    const CAP_EDIT_POSTS   = 'edit_posts';
    
    /**
     * Menu Slugs
     */
    const MENU_DASHBOARD   = 'afcglide-dashboard';
    const MENU_SETTINGS    = 'afcglide-settings';
    
    /**
     * Helper: Get option with default
     */
    public static function get_option( $key, $default = '' ) {
        return get_option( $key, $default );
    }
    
    /**
     * Helper: Update option
     */
    public static function update_option( $key, $value ) {
        return update_option( $key, $value );
    }
    
    /**
     * Helper: Get post meta
     */
    public static function get_meta( $post_id, $key, $single = true ) {
        return get_post_meta( $post_id, $key, $single );
    }
    
    /**
     * Helper: Update post meta
     */
    public static function update_meta( $post_id, $key, $value ) {
        return update_post_meta( $post_id, $key, $value );
    }
}