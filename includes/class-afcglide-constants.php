<?php
/**
 * AFCGlide Constants
 * Single source of truth for meta keys, options, and system constants.
 *
 * @package AFCGlide
 * @version 5.0.0-GOLD
 */

namespace AFCGlide\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Constants {

    /** Plugin Version */
    const VERSION = '5.0.0-GOLD';

    /* =========================================================
     * Post Type & Taxonomies
     * ========================================================= */
    const POST_TYPE    = 'afcglide_listing';
    const TAX_LOCATION = 'property_location';
    const TAX_TYPE     = 'property_type';
    const TAX_STATUS   = 'property_status';
    const TAX_AMENITY  = 'property_amenity';

    /* =========================================================
     * Meta Keys – Core Listing Data (CANONICAL)
     * ========================================================= */
    const META_INTRO      = '_afc_intro';
    const META_NARRATIVE  = '_afc_narrative';
    const META_PRICE      = '_afc_price';
    const META_CURRENCY   = '_afc_currency';
    const META_BEDS       = '_afc_beds';
    const META_BATHS      = '_afc_baths';
    const META_SQFT       = '_afc_sqft';
    const META_LOT_SIZE   = '_afc_lot_size';
    const META_ADDRESS    = '_afc_address';
    const META_TYPE       = '_afc_listing_type';
    const META_STATUS     = '_afc_status';
    const META_AMENITIES  = '_afc_amenities';
    const META_VIEWS      = '_afc_views_count';
    const META_PDF_ID     = '_afc_pdf_id';
    const META_VIDEO_URL  = '_afc_video_url';
    const META_OPEN_HOUSE = '_afc_showing_schedule';

    /* =========================================================
     * Meta Keys – Spanish
     * ========================================================= */
    const META_INTRO_ES     = '_afc_intro_es';
    const META_NARRATIVE_ES = '_afc_narrative_es';

    /* =========================================================
     * Meta Keys – Location (GPS)
     * ========================================================= */
    const META_GPS_LAT  = '_afc_gps_lat';
    const META_GPS_LNG  = '_afc_gps_lng';
    const META_MAP_ZOOM = '_afc_map_zoom_level';
    const META_MAP_TYPE = '_afc_map_display_mode';

    /* =========================================================
     * Meta Keys – Media
     * ========================================================= */
    const META_HERO_ID     = '_afc_hero_id';
    const META_GALLERY_IDS = '_afc_gallery_ids';
    const META_STACK_IDS   = '_afc_stack_ids';

    /* =========================================================
     * Meta Keys – Agent
     * ========================================================= */
    const META_AGENT_NAME     = '_afc_agent_name';
    const META_AGENT_PHONE    = '_afc_agent_phone';
    const META_AGENT_PHOTO    = '_afc_agent_photo';
    const META_AGENT_WHATSAPP = '_afc_agent_whatsapp';
    const META_BROKER_LOGO    = '_afc_broker_logo';
    const META_SHOW_WA        = '_afc_show_floating_whatsapp';

    /* =========================================================
     * Legacy Meta (Migration Only)
     * ========================================================= */
    const LEGACY_META_INTRO     = '_listing_intro_text';
    const LEGACY_META_NARRATIVE = '_listing_narrative';
    const LEGACY_META_STATUS   = '_listing_status';

    /* =========================================================
     * User Meta – Agent Profile
     * ========================================================= */
    const USER_PHONE    = 'agent_phone';
    const USER_WHATSAPP = 'agent_whatsapp';
    const USER_LICENSE  = 'agent_license';
    const USER_PHOTO    = 'agent_photo';
    const USER_BIO      = 'agent_bio';

/* =========================================================
 * Options – System & Branding (SINGLE SOURCE)
 * ========================================================= */
const OPT_SYSTEM_LABEL    = 'afc_system_label';
const OPT_PRIMARY_COLOR   = 'afc_primary_color';
const OPT_BRAND_COLOR     = 'afc_primary_color';
const OPT_WA_COLOR        = 'afc_whatsapp_color';
const OPT_WA_NUMBER       = 'afc_global_whatsapp';
const OPT_GLOBAL_LOCKDOWN = 'afc_admin_lockdown';
const OPT_LOCKDOWN        = 'afc_admin_lockdown';
const OPT_QUALITY_GATE    = 'afc_quality_gatekeeper';
const OPT_IDENTITY_SHIELD = 'afc_identity_shield';
const OPT_MAINTENANCE     = 'afc_maintenance_mode';
const OPT_WA_GLOBAL       = 'afc_whatsapp_global';
const OPT_AGENT_PHONE     = 'afc_agent_phone';
    /* =========================================================
     * Capabilities
     * ========================================================= */
    const CAP_MANAGE = 'manage_options';
    const CAP_SUBMIT = 'read';

    /* =========================================================
     * AJAX & Security
     * ========================================================= */
    const AJAX_SUBMIT       = 'afc_handle_submission';
    const AJAX_DELETE_MEDIA = 'afc_delete_listing_media';
    const AJAX_LOCKDOWN     = 'afc_toggle_lockdown_ajax';
    const AJAX_FILTER       = 'afc_filter_listings';
    const NONCE_RECRUITMENT = 'afcglide_recruit_agent';
    const NONCE_AJAX        = 'afc_submit_listing_action';
    const NONCE_META        = 'afcglide_meta_nonce';
    const NONCE_INQUIRY     = 'afc_submit_inquiry_action';

    /* =========================================================
     * Limits
     * ========================================================= */
    const MAX_GALLERY     = 24;
    const MIN_IMAGE_WIDTH = 1200;

    /* =========================================================
     * Menus
     * ========================================================= */
    const MENU_DASHBOARD = 'afcglide-dashboard';
    const MENU_SETTINGS  = 'afcglide-settings';

    /* =========================================================
     * Helpers
     * ========================================================= */
    public static function get_meta( $post_id, $key, $single = true ) {
        return get_post_meta( $post_id, $key, $single );
    }

    public static function update_meta( $post_id, $key, $value ) {
        return update_post_meta( $post_id, $key, $value );
    }
}
