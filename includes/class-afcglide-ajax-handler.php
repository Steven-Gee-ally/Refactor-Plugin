<?php
/**
 * AFCGlide AJAX Handler (S-Grade Production Master)
 * Version 5.0.0 - Enhanced with Cache Management
 * 
 * Handles: Full Submissions, Autosave Drafts, Global Lockdown, 
 * Advanced Image Processing (Auto-Resize), AJAX Filtering, and Cache Clearing.
 */

namespace AFCGlide\Listings;

use AFCGlide\Core\Constants as C;
use AFCGlide\Core\AFCGlide_Synergy_Engine as Engine;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Ajax_Handler {

    /**
     * Initialize All Core AJAX Protocols
     */
    public static function init() {
        // Frontend Asset Deployment
        add_action( 'wp_ajax_' . C::AJAX_SUBMIT, [ __CLASS__, 'handle_front_submission' ] );
        
        // Secure Autosave/Draft Protocol
        add_action( 'wp_ajax_' . C::AJAX_SAVE_DRAFT, [ __CLASS__, 'handle_save_draft' ] );

        // Admin Lockdown Control
        add_action( 'wp_ajax_' . C::AJAX_LOCKDOWN, [ __CLASS__, 'handle_lockdown_toggle' ] );

        // Global Grid Filtering (Public + Private)
        add_action( 'wp_ajax_' . C::AJAX_FILTER, [ __CLASS__, 'handle_listings_filter' ] );
        add_action( 'wp_ajax_nopriv_' . C::AJAX_FILTER, [ __CLASS__, 'handle_listings_filter' ] );
        
        // NEW: Cache Management Hooks
        add_action( 'save_post_' . C::POST_TYPE, [ __CLASS__, 'clear_stats_on_save' ], 10, 3 );
        add_action( 'delete_post', [ __CLASS__, 'clear_stats_on_delete' ], 10, 2 );
        add_action( 'transition_post_status', [ __CLASS__, 'clear_stats_on_status_change' ], 10, 3 );

        // NEW: Agent Recruitment Protocol (Broker Only)
        add_action( 'wp_ajax_afcg_recruit_agent', [ __CLASS__, 'handle_agent_recruitment' ] );

        // NEW: Focus Mode Toggle
        add_action( 'wp_ajax_afcg_toggle_focus', [ __CLASS__, 'handle_focus_toggle' ] );

        // NEW: Backbone System Sync
        add_action( 'wp_ajax_afcg_sync_backbone', [ __CLASS__, 'handle_sync_backbone' ] );
    }

    /**
     * Standardized JSON Handshakes
     */
    private static function send_success( $message = '', $data = [] ) {
        wp_send_json_success(['message' => $message, 'data' => $data]);
    }

    private static function send_error( $message = '', $data = [] ) {
        wp_send_json_error(['message' => $message, 'data' => $data]);
    }

    private static function log_error( $message ) {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log( 'AFCGlide Critical: ' . $message );
        }
    }

    /**
     * 🚀 HANDLE FULL SUBMISSION
     */
    public static function handle_front_submission() {
        // Security Verification (Aligned with 'security' field in Form)
        check_ajax_referer( C::NONCE_AJAX, 'security' );

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            self::send_error( __( 'Security Session expired. Please re-authenticate.', 'afcglide' ) );
        }

        // Check Lockdown State
        if ( C::get_option( C::OPT_GLOBAL_LOCKDOWN ) === '1' && ! current_user_can( C::CAP_MANAGE ) ) {
            self::send_error( __( '🔒 GLOBAL LOCKDOWN: All asset transmissions are currently frozen.', 'afcglide' ) );
        }

        $title = sanitize_text_field( $_POST['listing_title'] ?? '' );
        if ( empty( $title ) ) {
            self::send_error( __( 'Asset Title is mandatory for deployment.', 'afcglide' ) );
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );

        $post_data = [
            'post_title'   => $title,
            'post_content' => wp_kses_post( $_POST['listing_description'] ?? '' ),
            'post_status'  => 'publish',
            'post_type'    => C::POST_TYPE,
            'post_author'  => $user_id,
        ];

        // Process Update vs. New Entry
        if ( $post_id > 0 ) {
            $existing_post = get_post( $post_id );
            if ( ! $existing_post ) self::send_error( __( 'Target asset not found in database.', 'afcglide' ) );
            if ( $existing_post->post_author != $user_id && ! current_user_can( C::CAP_MANAGE ) ) {
                self::send_error( __( 'Unauthorized: You do not possess the credentials for this asset.', 'afcglide' ) );
            }
            $post_data['ID'] = $post_id;
            $final_id = wp_update_post( $post_data, true );
            $message = __( '✅ Global Asset Synced Successfully!', 'afcglide' );
        } else {
            $final_id = wp_insert_post( $post_data, true );
            $message = __( '🚀 Global Asset Deployed Successfully!', 'afcglide' );
        }

        if ( is_wp_error( $final_id ) ) {
            self::log_error( 'Post save error: ' . $final_id->get_error_message() );
            self::send_error( __( 'Database Synchronization Failed.', 'afcglide' ) );
        }

        // Save Standardized Meta Map
        self::save_standard_meta( $final_id );
        self::save_amenities( $final_id );

        // Process Hero Image (Required)
        $hero_saved = self::upload_image( 'hero_file', $final_id, C::META_HERO_ID );

        // Process Gallery Batch (Maximum Limit Enforced)
        $existing_gallery = C::get_meta( $final_id, C::META_GALLERY_IDS, true ) ?: [];
        $existing_count = is_array( $existing_gallery ) ? count( $existing_gallery ) : 0;
        $allowed = max( 0, C::MAX_GALLERY - $existing_count );
        
        $gallery_saved_ids = [];
        if ( $allowed > 0 && !empty($_FILES['gallery_files']['name'][0]) ) {
            $gallery_saved_ids = self::upload_gallery( 'gallery_files', $final_id, C::META_GALLERY_IDS, $allowed );
        }

        // NEW: Clear cache after successful save
        $post = get_post( $final_id );
        if ( $post ) {
            Engine::clear_stats_cache( $post->post_author );
            
            // If admin, also clear global cache
            if ( current_user_can( C::CAP_MANAGE ) ) {
                Engine::clear_stats_cache( get_current_user_id() );
            }
        }

        self::send_success( $message, [
            'url'           => get_permalink( $final_id ),
            'post_id'       => $final_id,
            'hero_status'   => $hero_saved ? 'Updated' : 'No Change',
            'gallery_added' => count($gallery_saved_ids)
        ]);
    }

    /**
     * 💾 HANDLE AUTOSAVE DRAFT
     */
    public static function handle_save_draft() {
        check_ajax_referer( C::NONCE_AJAX, 'security' );

        $user_id = get_current_user_id();
        if ( ! $user_id ) self::send_error( 'Session expired.' );

        $post_id = intval( $_POST['post_id'] ?? 0 );
        $title = sanitize_text_field( $_POST['listing_title'] ?? '' );

        $post_data = [
            'post_title'   => $title ?: __( 'Draft Asset', 'afcglide' ),
            'post_content' => wp_kses_post( $_POST['listing_description'] ?? '' ),
            'post_status'  => 'draft',
            'post_type'    => C::POST_TYPE,
            'post_author'  => $user_id,
        ];

        if ( $post_id > 0 ) {
            $post_data['ID'] = $post_id;
            $final_id = wp_update_post( $post_data, true );
        } else {
            $final_id = wp_insert_post( $post_data, true );
        }

        if ( ! is_wp_error( $final_id ) ) {
            self::save_standard_meta( $final_id );
            
            // NEW: Clear cache on draft save
            Engine::clear_stats_cache( $user_id );
            
            self::send_success( 'Draft Auto-Synced', [ 'post_id' => $final_id ] );
        } else {
            self::send_error( 'Draft sync failed.' );
        }
    }

    /**
     * 🗺️ SYNC CORE META & GEOSPATIAL DATA
     */
    private static function save_standard_meta( $post_id ) {
        $meta_map = [
            'listing_price'        => C::META_PRICE,
            'listing_address'      => C::META_ADDRESS,
            'listing_beds'         => C::META_BEDS,
            'listing_baths'        => C::META_BATHS,
            'listing_sqft'         => C::META_SQFT,
            'listing_status'       => C::META_STATUS,
            'gps_lat'              => C::META_GPS_LAT,
            'gps_lng'              => C::META_GPS_LNG,
            'listing_intro_es'     => C::META_INTRO_ES,
            'listing_narrative_es' => C::META_NARRATIVE_ES,
        ];

        foreach ( $meta_map as $form_field => $meta_key ) {
            if ( isset( $_POST[$form_field] ) ) {
                
                // WORLD-CLASS CLEANER: Specifically for GPS
                if ( $form_field === 'gps_lat' || $form_field === 'gps_lng' ) {
                    // Strips everything except numbers, dots, and minus signs
                    $value = preg_replace( '/[^0-9.-]/', '', $_POST[$form_field] );
                } else {
                    // Standard cleaning for everything else
                    $value = is_numeric($_POST[$form_field]) ? floatval($_POST[$form_field]) : sanitize_text_field($_POST[$form_field]);
                }
                
                C::update_meta( $post_id, $meta_key, $value );
            }
        }
    }

    /**
     * 🏡 SYNC AMENITIES ARRAY
     */
    private static function save_amenities( $post_id ) {
        $amenities = $_POST['listing_amenities'] ?? [];
        if ( is_array($amenities) ) {
            $sanitized = array_map('sanitize_text_field', $amenities);
            C::update_meta( $post_id, C::META_AMENITIES, $sanitized );
        } else {
            delete_post_meta( $post_id, C::META_AMENITIES );
        }
    }

    /**
     * 🖼️ S-GRADE IMAGE PROCESSOR (With Auto-Resize)
     */
    private static function upload_image( $file_key, $post_id, $meta_key ) {
        if ( empty($_FILES[$file_key]['name']) ) return false;

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        // Secure Upload Handshake
        $attach_id = media_handle_upload( $file_key, $post_id );
        if ( is_wp_error($attach_id) ) return false;

        // Auto-Resize Large Assets (Cap at 2500px for high-end display without bloating server)
        $file_path = get_attached_file( $attach_id );
        $editor = wp_get_image_editor( $file_path );
        if ( ! is_wp_error( $editor ) ) {
            $size = $editor->get_size();
            if ( $size['width'] > 2500 ) {
                $editor->resize( 2500, null, false );
                $editor->save( $file_path );
                $metadata = wp_generate_attachment_metadata( $attach_id, $file_path );
                wp_update_attachment_metadata( $attach_id, $metadata );
            }
        }

        set_post_thumbnail( $post_id, $attach_id );
        C::update_meta( $post_id, $meta_key, $attach_id );
        return $attach_id;
    }

    /**
     * 📸 GALLERY BATCH PROCESSOR
     */
    private static function upload_gallery( $file_key, $post_id, $meta_key, $max_files ) {
        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        $gallery_ids = [];
        $files = $_FILES[$file_key];

        for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
            if ( empty( $files['name'][ $i ] ) ) continue;
            if ( count( $gallery_ids ) >= $max_files ) break;

            $_FILES['single_batch'] = [
                'name'     => $files['name'][ $i ],
                'type'     => $files['type'][ $i ],
                'tmp_name' => $files['tmp_name'][ $i ],
                'error'    => $files['error'][ $i ],
                'size'     => $files['size'][ $i ],
            ];

            $attach_id = media_handle_upload( 'single_batch', $post_id );
            if ( ! is_wp_error( $attach_id ) ) {
                $gallery_ids[] = $attach_id;
            }
        }

        if ( ! empty( $gallery_ids ) ) {
            $existing = (array) C::get_meta( $post_id, $meta_key );
            $merged = array_unique( array_merge( $existing, $gallery_ids ) );
            C::update_meta( $post_id, $meta_key, array_values($merged) );
        }
        return $gallery_ids;
    }

    /**
     * 🔒 GLOBAL LOCKDOWN TOGGLE
     */
    public static function handle_lockdown_toggle() {
        check_ajax_referer( C::NONCE_AJAX, 'security' );
        if ( ! current_user_can( C::CAP_MANAGE ) ) self::send_error('Unauthorized Access.');

        $type   = sanitize_text_field( $_POST['type'] ?? '' );
        $status = sanitize_text_field( $_POST['status'] ?? '0' );

        update_option( 'afc_' . $type, $status === '1' ? '1' : '0' );
        self::send_success('Security Settings Updated.');
    }

    /**
     * 🔍 GLOBAL LISTINGS GRID FILTER
     */
    public static function handle_listings_filter() {
        check_ajax_referer( C::NONCE_AJAX, 'security' );

        $page    = intval( $_POST['page'] ?? 1 );
        $filters = $_POST['filters'] ?? [];

        $args = [
            'post_type'      => C::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 9,
            'paged'          => $page,
        ];

        // Advanced Meta Querying for Filters
        $meta_query = [];
        if ( ! empty($filters['min_price']) ) {
            $meta_query[] = [
                'key' => C::META_PRICE,
                'value' => floatval($filters['min_price']),
                'compare' => '>=',
                'type' => 'NUMERIC',
            ];
        }
        
        if ( $meta_query ) $args['meta_query'] = $meta_query;

        $query = new \WP_Query($args);

        ob_start();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $template_path = AFCG_PATH . 'templates/listing-card.php';
                if ( file_exists($template_path) ) {
                    include $template_path;
                }
            }
            wp_reset_postdata();
        } else {
            echo '<p class="afc-no-results">No luxury assets match your current parameters.</p>';
        }
        $html = ob_get_clean();

        self::send_success('Network Scanned.', [
            'html'      => $html,
            'pages'     => $query->max_num_pages,
            'total'     => $query->found_posts,
        ]);
    }
    
    /**
     * ============================================================================
     * NEW: CACHE MANAGEMENT HOOKS
     * ============================================================================
     */
    
    /**
     * Clear stats cache when a listing is saved
     * 
     * @param int $post_id The post ID
     * @param WP_Post $post The post object
     * @param bool $update Whether this is an update
     */
    public static function clear_stats_on_save( $post_id, $post, $update ) {
        // Don't clear cache for autosaves or revisions
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }
        
        if ( wp_is_post_revision( $post_id ) ) {
            return;
        }
        
        // Clear cache for the post author
        Engine::clear_stats_cache( $post->post_author );
        
        // If admin/broker is editing, also clear their cache
        $current_user_id = get_current_user_id();
        if ( $current_user_id !== $post->post_author && current_user_can( C::CAP_MANAGE ) ) {
            Engine::clear_stats_cache( $current_user_id );
        }
    }
    
    /**
     * Clear stats cache when a listing is deleted
     * 
     * @param int $post_id The post ID
     * @param WP_Post $post The post object
     */
    public static function clear_stats_on_delete( $post_id, $post ) {
        // Only for our post type
        if ( ! $post || $post->post_type !== C::POST_TYPE ) {
            return;
        }
        
        // Clear cache for the post author
        Engine::clear_stats_cache( $post->post_author );
        
        // Clear cache for current user if different
        $current_user_id = get_current_user_id();
        if ( $current_user_id !== $post->post_author ) {
            Engine::clear_stats_cache( $current_user_id );
        }
    }
    
    /**
     * Clear stats cache when post status changes
     * (e.g., draft to publish, publish to sold)
     * 
     * @param string $new_status New post status
     * @param string $old_status Old post status
     * @param WP_Post $post The post object
     */
    public static function clear_stats_on_status_change( $new_status, $old_status, $post ) {
        // Only for our post type
        if ( $post->post_type !== C::POST_TYPE ) {
            return;
        }
        
        // Only clear if status actually changed
        if ( $new_status === $old_status ) {
            return;
        }
        
        // Clear cache for the post author
        Engine::clear_stats_cache( $post->post_author );
        
        // Clear cache for current user if admin
        if ( current_user_can( C::CAP_MANAGE ) ) {
            Engine::clear_stats_cache( get_current_user_id() );
        }
    }

    /**
     * 👑 AGENT RECRUITMENT PROTOCOL (Bulletproof)
     */
    public static function handle_agent_recruitment() {
        check_ajax_referer( C::NONCE_RECRUITMENT, 'security' );
        if ( ! current_user_can( C::CAP_MANAGE ) ) self::send_error( 'Permission Denied.' );
        $user_id = wp_create_user( sanitize_user($_POST['agent_username']), $_POST['password'], sanitize_email($_POST['agent_email']) );
        if ( is_wp_error($user_id) ) self::send_error( $user_id->get_error_message() );
        $user = new \WP_User( $user_id );
        $user->set_role( 'listing_agent' ); 
        self::send_success( '🚀 AGENT RECRUITED' );
    }

    /**
     * 👁️ FOCUS MODE TOGGLE
     */
    public static function handle_focus_toggle() {
        check_ajax_referer( C::NONCE_AJAX, 'security' );
        $status = sanitize_text_field( $_POST['status'] ?? '0' );
        update_user_meta( get_current_user_id(), 'afc_focus_mode', $status === '1' ? '1' : '0' );
        self::send_success( 'Focus Mode Updated' );
    }

    /**
     * ⚙️ SYSTEM BACKBONE SYNC
     */
    public static function handle_sync_backbone() {
        check_ajax_referer( C::NONCE_AJAX, 'security' );
        if ( ! current_user_can( C::CAP_MANAGE ) ) self::send_error( 'Permission Denied.' );

        update_option( 'afc_system_label', sanitize_text_field( $_POST['system_label'] ?? '' ) );
        update_option( 'afc_whatsapp_color', sanitize_hex_color( $_POST['whatsapp_color'] ?? '#25d366' ) );
        update_option( 'afc_global_lockdown', ( $_POST['lockdown'] === '1' ) ? '1' : '0' );
        update_option( 'afc_quality_gatekeeper', ( $_POST['gatekeeper'] === '1' ) ? '1' : '0' );

        self::send_success( 'System Sync Executed' );
    }
}