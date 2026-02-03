<?php
/**
 * AFCGlide Constants
 * Central definition of all meta keys, options, and system constants.
 *
 * @package AFCGlide
 * @version 5.0.0-GOLD
 */

namespace AFCGlide\Core;

if ( ! defined( 'ABSPATH' ) ) exit;

final class Constants {

	/** Plugin Version [cite: 2026-01-14] */
	const VERSION = '5.0.0-GOLD';

	/**
	 * =========================================================
	 * Meta Keys – Primary Listing Data (CANONICAL)
	 * =========================================================
	 */
	const META_INTRO = '_afc_intro';
	const META_NARRATIVE = '_afc_narrative';
	const META_PRICE = '_afc_price';
	const META_CURRENCY = '_afc_currency'; // NEW: Multi-site support [cite: 2026-01-16]
	const META_BEDS = '_afc_beds';
	const META_BATHS = '_afc_baths';
	const META_SQFT = '_afc_sqft';
	const META_LOT_SIZE = '_afc_lot_size'; // NEW: Essential for Central America [cite: 2025-12-22]
	const META_ADDRESS = '_afc_address';
	const META_LOCATION = '_afc_location'; // NEW: Regional/Area field
	const META_TYPE = '_afc_listing_type';
	const META_STATUS = '_afc_status';
	const META_AMENITIES = '_afc_amenities';
	const META_VIEWS = '_afc_views_count';
	const META_PDF_ID = '_afc_pdf_id';
	const META_VIDEO_URL = '_afc_video_url'; // NEW: High-end feature [cite: 2026-01-01]
	const META_OPEN_HOUSE = '_afc_showing_schedule';

	/** Meta Keys – Spanish Translations [cite: 2025-07-12] */
	const META_INTRO_ES = '_afc_intro_es';
	const META_NARRATIVE_ES = '_afc_narrative_es';

	/** Meta Keys – Location (Bulletproof GPS) [cite: 2025-12-22] */
	const META_GPS_LAT = '_afc_gps_lat';
	const META_GPS_LNG = '_afc_gps_lng';
	const META_MAP_ZOOM = '_afc_map_zoom_level'; // NEW: Terrain precision
	const META_MAP_TYPE = '_afc_map_display_mode'; // NEW: Satellite vs Street

	/** Meta Keys – Media [cite: 2026-01-16] */
	const META_HERO_ID = '_afc_hero_id';
	const META_GALLERY_IDS = '_afc_gallery_ids';
	const META_STACK_IDS = '_afc_stack_ids';
	const META_SLIDER_JSON = '_slider_images_json'; // Legacy slider storage

	/** Meta Keys – Agent Info [cite: 2026-01-16, 2026-01-30] */
	const META_AGENT_NAME = '_afc_agent_name';
	const META_AGENT_PHONE = '_afc_agent_phone';
	const META_AGENT_PHOTO = '_afc_agent_photo';
	const META_AGENT_WHATSAPP = '_afc_agent_whatsapp'; // NEW: Direct lead flow
	const META_BROKER_LOGO = '_afc_broker_logo';
	const META_SHOW_WA = '_afc_show_floating_whatsapp';

	/** Legacy Aliases (Phase 4 Consistency) [cite: 2026-01-20] */
	const LEGACY_META_INTRO = '_listing_intro_text';
	const LEGACY_META_NARRATIVE = '_listing_narrative';
	const LEGACY_META_STATUS = '_listing_status';

	/** User Meta Keys (Agent Profile) [cite: 2026-01-16] */
	const USER_PHONE = 'agent_phone';
	const USER_WHATSAPP = 'agent_whatsapp'; // NEW: Sync with floating button
	const USER_LICENSE = 'agent_license';
	const USER_PHOTO = 'agent_photo';
	const USER_BIO = 'agent_bio';

	/**
	 * =========================================================
	 * Options & Branding [cite: 2026-01-07, 2026-01-16]
	 * Powers the "Settings" page and "Professional Terminal" UI
	 * =========================================================
	 */
	const OPT_SYSTEM_LABEL = 'afc_system_label'; // Fixes Red Line - The missing piece!
	const OPT_WA_COLOR = 'afc_whatsapp_color'; // Fixes the Public Class error
	const OPT_WA_NUMBER = 'afc_global_whatsapp'; // Essential for lead flow
	const OPT_BRAND_COLOR = 'afc_primary_color'; // Powers the "Launch" buttons
	const OPT_PRIMARY_COLOR = 'afc_primary_color'; // Alias for OPT_BRAND_COLOR
	const OPT_GLOBAL_LOCKDOWN = 'afc_admin_lockdown'; // Powers the Broker kill-switch
	const OPT_QUALITY_GATE = 'afc_quality_gatekeeper';
	const OPT_IDENTITY_SHIELD = 'afc_identity_shield'; // Keeps agents out of the backend
	const OPT_MAINTENANCE = 'afc_maintenance_mode';

	/**
	 * Permissions & Capabilities [cite: 2026-01-30]
	 * Defines who can bypass lockdown and manage the network.
	 */
	const CAP_MANAGE = 'manage_options'; // High-level Broker/Admin
	const CAP_SUBMIT = 'read'; // Standard Agent access

	/** Post Type & Taxonomies [cite: 2025-09-11] */
	const POST_TYPE = 'afcglide_listing';
	const TAX_LOCATION = 'property_location';
	const TAX_TYPE = 'property_type';
	const TAX_STATUS = 'property_status';
	const TAX_AMENITY = 'property_amenity';

	/** AJAX Actions (Phase 3 Resilience) [cite: 2026-01-01] */
	const AJAX_SUBMIT = 'afc_handle_submission';
	const AJAX_DELETE_MEDIA = 'afc_delete_listing_media'; // NEW: UI stability
	const AJAX_LOCKDOWN = 'afc_toggle_lockdown_ajax';

	/** Nonces (Phase 3 Security) [cite: 2026-01-30] */
	const NONCE_AJAX = 'afc_submit_listing_action';
	const NONCE_META = 'afcglide_meta_nonce';
	const NONCE_RECRUITMENT = 'afc_recruitment_nonce';

	/** Limits [cite: 2026-01-16] */
	const MAX_GALLERY = 24; // Increased for high-end listings
	const MIN_IMAGE_WIDTH = 1200;

	/** Menu Slugs */
	const MENU_DASHBOARD = 'afcglide-dashboard';
	const MENU_SETTINGS = 'afcglide-settings';

	/**
	 * =========================================================
	 * Helper CRUD Methods (The "No Bandage" Approach)
	 * [cite: 2026-01-14]
	 * =========================================================
	 */
	public static function get_meta( $post_id, $key, $single = true ) {
		return get_post_meta( $post_id, $key, $single );
	}

	public static function update_meta( $post_id, $key, $value ) {
		return update_post_meta( $post_id, $key, $value );
	}
}