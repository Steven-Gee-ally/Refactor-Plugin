<?php
/**
 * AFCGlide AJAX Handler
 * Handles all AJAX requests with proper error handling and logging
 * 
 * @package AFCGlide\Listings
 * @version 4.0.0
 */

namespace AFCGlide\Admin;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Ajax_Handler {

    /**
     * Initialize AJAX hooks
     */
    public static function init() {
        // Frontend submission (logged in users)
        add_action( 'wp_ajax_' . C::AJAX_SUBMIT, [ __CLASS__, 'handle_front_submission' ] );
        
        // Lockdown toggle (admin only)
        add_action( 'wp_ajax_' . C::AJAX_LOCKDOWN, [ __CLASS__, 'handle_lockdown_toggle' ] );
        
        // Listings filter (public + logged in)
        add_action( 'wp_ajax_' . C::AJAX_FILTER, [ __CLASS__, 'handle_listings_filter' ] );
        add_action( 'wp_ajax_nopriv_' . C::AJAX_FILTER, [ __CLASS__, 'handle_listings_filter' ] );
    }

    /**
     * Handle Frontend Listing Submission
     * Processes both new listings and updates
     */
    public static function handle_front_submission() {
        
        // 1. SECURITY CHECKS
        check_ajax_referer( C::NONCE_AJAX, 'security' );
        
        $user_id = get_current_user_id();
        if ( ! $user_id || ! current_user_can( 'edit_posts' ) ) {
            self::send_error( 'Access Denied: Specialized Agent credentials required.' );
        }
        
        // 2. GLOBAL LOCKDOWN CHECK
        if ( C::get_option( C::OPT_GLOBAL_LOCKDOWN ) === '1' && ! current_user_can( C::CAP_MANAGE ) ) {
            self::send_error( '🔒 SYSTEM LOCKDOWN ACTIVE: All listing updates are currently frozen by the Lead Broker.' );
        }
        
        // 3. VALIDATE REQUIRED FIELDS
        $title = sanitize_text_field( $_POST['listing_title'] ?? '' );
        
        if ( empty( $title ) ) {
            self::send_error( 'Property title is required.' );
        }
        
        // 4. PREPARE POST DATA
        $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
        
        $post_data = [
            'post_title'   => $title,
            'post_content' => wp_kses_post( $_POST['listing_description'] ?? '' ),
            'post_status'  => 'publish',
            'post_type'    => C::POST_TYPE,
            'post_author'  => $user_id,
        ];
        
        // 5. UPDATE vs INSERT
        if ( $post_id > 0 ) {
            // Verify ownership
            $existing_post = get_post( $post_id );
            
            if ( ! $existing_post ) {
                self::send_error( 'Listing not found.' );
            }
            
            if ( $existing_post->post_author != $user_id && ! current_user_can( C::CAP_MANAGE ) ) {
                self::send_error( 'Access Denied: You do not own this asset.' );
            }
            
            $post_data['ID'] = $post_id;
            $final_id = wp_update_post( $post_data, true );
            $message = '✅ Asset Updated Successfully!';
            
        } else {
            // Create new listing
            $final_id = wp_insert_post( $post_data, true );
            $message = '🚀 Asset Published Live!';
        }
        
        // Check for errors
        if ( is_wp_error( $final_id ) ) {
            self::log_error( 'Post save failed: ' . $final_id->get_error_message() );
            self::send_error( 'Database sync failed. Please try again.' );
        }
        
        // 6. SAVE STANDARD META FIELDS
        self::save_standard_meta( $final_id );
        
        // 7. SAVE AMENITIES
        self::save_amenities( $final_id );
        
        // 8. HANDLE HERO IMAGE UPLOAD
        $hero_saved = self::handle_hero_upload( $final_id );
        
        // 9. HANDLE GALLERY IMAGES (if provided)
        self::handle_gallery_upload( $final_id );

        // 10. HANDLE PDF BROCHURE
        self::handle_pdf_upload( $final_id );

        // 11. HANDLE AGENT & BROKER BRANDING ASSETS
        self::handle_branding_uploads( $final_id );
        
        // 12. SUCCESS RESPONSE
        wp_send_json_success([
            'url'     => get_permalink( $final_id ),
            'message' => $message,
            'post_id' => $final_id,
            'hero_saved' => $hero_saved
        ]);
    }
    
    /**
     * Save standard listing meta fields
     */
    private static function save_standard_meta( $post_id ) {
        $meta_fields = [
            'listing_headline'       => C::META_INTRO,
            'listing_headline_es'    => C::META_INTRO_ES,
            'listing_description_es' => C::META_NARRATIVE_ES,
            'listing_type'           => C::META_TYPE,
            'listing_price'          => C::META_PRICE,
            'listing_address'        => C::META_ADDRESS,
            'listing_beds'           => C::META_BEDS,
            'listing_baths'          => C::META_BATHS,
            'listing_sqft'           => C::META_SQFT,
            'listing_status'         => C::META_STATUS,
            'listing_showing'        => C::META_OPEN_HOUSE,
            'agent_name'             => C::META_AGENT_NAME,
            'agent_phone'            => C::META_AGENT_PHONE,
            'agent_whatsapp'         => C::META_AGENT_WHATSAPP,
        ];
        
        foreach ( $meta_fields as $form_key => $meta_key ) {
            if ( isset( $_POST[$form_key] ) ) {
                $value = (strpos($form_key, 'description') !== false) ? wp_kses_post($_POST[$form_key]) : sanitize_text_field( $_POST[$form_key] );
                C::update_meta( $post_id, $meta_key, $value );
            }
        }
        
        // GPS Coordinates
        if ( isset( $_POST['gps_lat'] ) ) {
            C::update_meta( $post_id, C::META_GPS_LAT, sanitize_text_field( $_POST['gps_lat'] ) );
        }
        if ( isset( $_POST['gps_lng'] ) ) {
            C::update_meta( $post_id, C::META_GPS_LNG, sanitize_text_field( $_POST['gps_lng'] ) );
        }
    }
    
    /**
     * Save amenities array
     */
    private static function save_amenities( $post_id ) {
        if ( isset( $_POST['listing_amenities'] ) && is_array( $_POST['listing_amenities'] ) ) {
            $amenities = array_map( 'sanitize_text_field', $_POST['listing_amenities'] );
            C::update_meta( $post_id, C::META_AMENITIES, $amenities );
        } else {
            // Clear amenities if none selected
            delete_post_meta( $post_id, C::META_AMENITIES );
        }
    }
    
    /**
     * Handle Hero Image Upload
     * Returns true if upload succeeded or was skipped
     */
    private static function handle_hero_upload( $post_id ) {
        
        // Check if file was uploaded
        if ( empty( $_FILES['hero_file']['name'] ) ) {
            return false; // No file uploaded (not an error)
        }
        
        // Validate file type
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        $file_type = $_FILES['hero_file']['type'];
        
        if ( ! in_array( $file_type, $allowed_types ) ) {
            self::log_error( "Invalid hero file type: {$file_type}" );
            return false;
        }
        
        // Require WordPress upload functions
        if ( ! function_exists( 'media_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
        }
        
        // Upload the file
        $hero_id = media_handle_upload( 'hero_file', $post_id );
        
        if ( is_wp_error( $hero_id ) ) {
            self::log_error( 'Hero upload failed: ' . $hero_id->get_error_message() );
            return false;
        }
        
        // Validate image dimensions
        $image_data = wp_get_attachment_metadata( $hero_id );
        
        if ( isset( $image_data['width'] ) && $image_data['width'] < C::MIN_IMAGE_WIDTH ) {
            // Delete the uploaded file
            wp_delete_attachment( $hero_id, true );
            self::log_error( "Hero image too small: {$image_data['width']}px (min: " . C::MIN_IMAGE_WIDTH . "px)" );
            return false;
        }
        
        // Save as featured image AND meta
        set_post_thumbnail( $post_id, $hero_id );
        C::update_meta( $post_id, C::META_HERO_ID, $hero_id );
        
        return true;
    }
    
    /**
     * Handle Gallery Images Upload
     * Supports multiple file upload for gallery slider
     */
    private static function handle_gallery_upload( $post_id ) {
        
        // Check if gallery files exist
        if ( empty( $_FILES['gallery_files'] ) ) {
            return; // No gallery uploaded (not an error)
        }
        
        // Require upload functions
        if ( ! function_exists( 'media_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
        }
        
        $gallery_ids = [];
        $files = $_FILES['gallery_files'];
        
        // Check if multiple files uploaded
        if ( is_array( $files['name'] ) ) {
            
            $file_count = count( $files['name'] );
            
            for ( $i = 0; $i < $file_count; $i++ ) {
                
                // Skip if no file name
                if ( empty( $files['name'][$i] ) ) continue;
                
                // Limit to max gallery size
                if ( count( $gallery_ids ) >= C::MAX_GALLERY ) {
                    self::log_error( "Gallery limit reached (" . C::MAX_GALLERY . " images)" );
                    break;
                }
                
                // Prepare individual file array for media_handle_upload
                $_FILES['gallery_file'] = [
                    'name'     => $files['name'][$i],
                    'type'     => $files['type'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'error'    => $files['error'][$i],
                    'size'     => $files['size'][$i],
                ];
                
                // Upload the file
                $attachment_id = media_handle_upload( 'gallery_file', $post_id );
                
                if ( is_wp_error( $attachment_id ) ) {
                    self::log_error( 'Gallery image upload failed: ' . $attachment_id->get_error_message() );
                    continue;
                }
                
                // Validate dimensions
                $image_data = wp_get_attachment_metadata( $attachment_id );
                
                if ( isset( $image_data['width'] ) && $image_data['width'] < C::MIN_IMAGE_WIDTH ) {
                    wp_delete_attachment( $attachment_id, true );
                    continue;
                }
                
                $gallery_ids[] = $attachment_id;
            }
            
            // Save gallery IDs
            if ( ! empty( $gallery_ids ) ) {
                C::update_meta( $post_id, C::META_GALLERY_IDS, $gallery_ids );
            }
        }
    }
    
    /**
     * Handle Lockdown Toggle (Admin Only)
     */
    public static function handle_lockdown_toggle() {
        
        check_ajax_referer( C::NONCE_AJAX, 'security' );
        
        if ( ! current_user_can( C::CAP_MANAGE ) ) {
            wp_send_json_error( 'Unauthorized' );
        }
        
        $type   = sanitize_text_field( $_POST['type'] ?? '' );
        $status = sanitize_text_field( $_POST['status'] ?? '' );
        
        // Validate type
        $allowed_types = ['global_lockdown', 'identity_shield'];
        
        if ( ! in_array( $type, $allowed_types ) ) {
            wp_send_json_error( 'Invalid type' );
        }
        
        // Update option
        $option_key = 'afc_' . $type;
        update_option( $option_key, $status );
        
        wp_send_json_success( 'Settings Updated' );
    }
    
    /**
     * Handle Listings Filter (Public Grid)
     */
    public static function handle_listings_filter() {
        
        check_ajax_referer( C::NONCE_AJAX, 'nonce' );
        
        $page = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
        $filters = isset( $_POST['filters'] ) ? $_POST['filters'] : [];
        $lang = isset( $_POST['lang'] ) ? sanitize_text_field( $_POST['lang'] ) : 'en';

        // Set global language context for this AJAX request
        $_GET['lang'] = $lang; 
        
        // Generate Unique Cache Key based on inputs + Enterprise Cache Version + Language
        $cache_ver = get_option( 'afcg_cache_version', '1' );
        $cache_key = 'afcg_filter_' . $cache_ver . '_' . $lang . '_' . md5( serialize($filters) . '_' . $page );
        $cached_response = get_transient( $cache_key );

        if ( false !== $cached_response ) {
            wp_send_json_success($cached_response);
        }

        // Build query args
        $args = [
            'post_type'      => C::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 9,
            'paged'          => $page,
        ];
        
        // Apply filters if provided
        if ( ! empty( $filters['min_price'] ) ) {
            $args['meta_query'][] = [
                'key'     => C::META_PRICE,
                'value'   => floatval( $filters['min_price'] ),
                'compare' => '>=',
                'type'    => 'NUMERIC'
            ];
        }
        
        if ( ! empty( $filters['max_price'] ) ) {
            $args['meta_query'][] = [
                'key'     => C::META_PRICE,
                'value'   => floatval( $filters['max_price'] ),
                'compare' => '<=',
                'type'    => 'NUMERIC'
            ];
        }
        
        if ( ! empty( $filters['beds'] ) ) {
            $args['meta_query'][] = [
                'key'     => C::META_BEDS,
                'value'   => intval( $filters['beds'] ),
                'compare' => '>=',
                'type'    => 'NUMERIC'
            ];
        }
        
        $query = new \WP_Query( $args );
        
        ob_start();
        
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                
                // Load listing card template
                $template_path = AFCG_PATH . 'templates/listing-card.php';
                if ( file_exists( $template_path ) ) {
                    include $template_path;
                }
            }
            wp_reset_postdata();
        } else {
            echo '<div class="afc-no-results">No luxury assets found matching your criteria.</div>';
        }
        
        $html = ob_get_clean();
        
        $response = [
            'html'      => $html,
            'max_pages' => (int)$query->max_num_pages,
            'found'     => (int)$query->found_posts
        ];

        // Cache for 1 hour (Enterprise Standard)
        set_transient( $cache_key, $response, HOUR_IN_SECONDS );
        
        wp_send_json_success($response);
    }
    
    /**
     * Handle PDF Brochure Upload
     */
    private static function handle_pdf_upload( $post_id ) {
        if ( empty( $_FILES['pdf_file']['name'] ) ) return;

        if ( ! function_exists( 'media_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
        }

        $pdf_id = media_handle_upload( 'pdf_file', $post_id );
        if ( ! is_wp_error( $pdf_id ) ) {
            C::update_meta( $post_id, C::META_PDF_ID, $pdf_id );
        }
    }

    /**
     * Handle Agent & Broker Branding Uploads
     */
    private static function handle_branding_uploads( $post_id ) {
        if ( ! function_exists( 'media_handle_upload' ) ) {
            require_once( ABSPATH . 'wp-admin/includes/image.php' );
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            require_once( ABSPATH . 'wp-admin/includes/media.php' );
        }

        // Agent Photo
        if ( ! empty( $_FILES['agent_photo_file']['name'] ) ) {
            $photo_id = media_handle_upload( 'agent_photo_file', $post_id );
            if ( ! is_wp_error( $photo_id ) ) {
                C::update_meta( $post_id, C::META_AGENT_PHOTO, $photo_id );
            }
        }

        // Broker Logo
        if ( ! empty( $_FILES['broker_logo_file']['name'] ) ) {
            $logo_id = media_handle_upload( 'broker_logo_file', $post_id );
            if ( ! is_wp_error( $logo_id ) ) {
                C::update_meta( $post_id, C::META_BROKER_LOGO, $logo_id );
            }
        }
    }

    /**
     * Increment the global cache version to instantly invalidate all transients
     */
    public static function clear_filter_cache() {
        $version = get_option( 'afcg_cache_version', '1' );
        update_option( 'afcg_cache_version', (int)$version + 1 );
    }

    /**
     * Send error response and exit
     */
    private static function send_error( $message ) {
        wp_send_json_error([ 'message' => $message ]);
    }
    
    /**
     * Log error to WordPress error log
     */
    private static function log_error( $message ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
            error_log( 'AFCGlide Error: ' . $message );
        }
    }
}