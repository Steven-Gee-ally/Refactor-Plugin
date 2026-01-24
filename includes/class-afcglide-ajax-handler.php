<?php
/**
 * AFCGlide AJAX Handler (Refactored)
 * Handles all AJAX requests with robust validation, uploads, and standardized responses
 *
 * @package AFCGlide\Listings
 * @version 4.1.0
 */

namespace AFCGlide\Listings;

use AFCGlide\Core\Constants as C;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Ajax_Handler {

    /**
     * Initialize AJAX hooks
     */
    public static function init() {
        // Frontend submission
        add_action( 'wp_ajax_' . C::AJAX_SUBMIT, [ __CLASS__, 'handle_front_submission' ] );

        // Lockdown toggle (admin only)
        add_action( 'wp_ajax_' . C::AJAX_LOCKDOWN, [ __CLASS__, 'handle_lockdown_toggle' ] );

        // Listings filter (public + logged in)
        add_action( 'wp_ajax_' . C::AJAX_FILTER, [ __CLASS__, 'handle_listings_filter' ] );
        add_action( 'wp_ajax_nopriv_' . C::AJAX_FILTER, [ __CLASS__, 'handle_listings_filter' ] );
    }

    /**
     * Standardized success response
     */
    private static function send_success( $message = '', $data = [] ) {
        wp_send_json_success([
            'message' => $message,
            'data'    => $data,
        ]);
    }

    /**
     * Standardized error response
     */
    private static function send_error( $message = '', $data = [] ) {
        wp_send_json_error([
            'message' => $message,
            'data'    => $data,
        ]);
    }

    /**
     * Log errors (optional persistent logging)
     */
    private static function log_error( $message ) {
        if ( defined('WP_DEBUG') && WP_DEBUG ) {
            error_log( 'AFCGlide Error: ' . $message );
        }
        // Could extend: store in transient or DB table for production tracking
    }

    /**
     * Handle Frontend Listing Submission
     */
    public static function handle_front_submission() {
        check_ajax_referer( C::NONCE_AJAX, 'security' );

        $user_id = get_current_user_id();
        if ( ! $user_id ) {
            self::send_error( __( 'Session expired. Please log in again.', 'afcglide' ) );
        }

        if ( C::get_option( C::OPT_GLOBAL_LOCKDOWN ) === '1' && ! current_user_can( C::CAP_MANAGE ) ) {
            self::send_error( __( '🔒 SYSTEM LOCKDOWN ACTIVE: Listing updates are frozen.', 'afcglide' ) );
        }

        $title = sanitize_text_field( $_POST['listing_title'] ?? '' );
        if ( empty( $title ) ) {
            self::send_error( __( 'Property title is required.', 'afcglide' ) );
        }

        $post_id = intval( $_POST['post_id'] ?? 0 );

        $post_data = [
            'post_title'   => $title,
            'post_content' => wp_kses_post( $_POST['listing_description'] ?? '' ),
            'post_status'  => 'publish',
            'post_type'    => C::POST_TYPE,
            'post_author'  => $user_id,
        ];

        // Update vs Insert
        if ( $post_id > 0 ) {
            $existing_post = get_post( $post_id );
            if ( ! $existing_post ) self::send_error( __( 'Listing not found.', 'afcglide' ) );
            if ( $existing_post->post_author != $user_id && ! current_user_can( C::CAP_MANAGE ) ) {
                self::send_error( __( 'Access Denied: You do not own this asset.', 'afcglide' ) );
            }
            $post_data['ID'] = $post_id;
            $final_id = wp_update_post( $post_data, true );
            $message = __( '✅ Asset Updated Successfully!', 'afcglide' );
        } else {
            $final_id = wp_insert_post( $post_data, true );
            $message = __( '🚀 Asset Published Live!', 'afcglide' );
        }

        if ( is_wp_error( $final_id ) ) {
            self::log_error( 'Post save failed: ' . $final_id->get_error_message() );
            self::send_error( __( 'Database sync failed. Please try again.', 'afcglide' ) );
        }

        // Save all meta
        self::save_standard_meta( $final_id );
        self::save_amenities( $final_id );

        // Handle uploads
        $hero_saved = self::upload_image( 'hero_file', $final_id, C::META_HERO_ID, C::MIN_IMAGE_WIDTH );

        // Enforce cumulative gallery limit: existing + incoming <= MAX_GALLERY
        $existing_gallery = C::get_meta( $final_id, C::META_GALLERY_IDS, true ) ?: [];
        $existing_count = is_array( $existing_gallery ) ? count( $existing_gallery ) : 0;
        $allowed = max( 0, C::MAX_GALLERY - $existing_count );
        $gallery_saved = [];
        if ( $allowed > 0 ) {
            $gallery_saved = self::upload_gallery( 'gallery_files', $final_id, C::META_GALLERY_IDS, $allowed );
        } else {
            self::log_error( "Gallery limit reached for post {$final_id}; skipping additional uploads." );
        }

        self::send_success( $message, [
            'url'        => get_permalink( $final_id ),
            'post_id'    => $final_id,
            'hero_saved' => $hero_saved,
            'gallery_saved_count' => count($gallery_saved),
        ]);
    }

    /**
     * Save standard meta fields
     */
    private static function save_standard_meta( $post_id ) {
        $meta_map = [
            'listing_price'   => C::META_PRICE,
            'listing_address' => C::META_ADDRESS,
            'listing_beds'    => C::META_BEDS,
            'listing_baths'   => C::META_BATHS,
            'listing_sqft'    => C::META_SQFT,
            'listing_status'  => C::META_STATUS,
        ];

        foreach ( $meta_map as $field => $meta ) {
            if ( isset( $_POST[$field] ) ) {
                $value = is_numeric($_POST[$field]) ? floatval($_POST[$field]) : sanitize_text_field($_POST[$field]);
                C::update_meta( $post_id, $meta, $value );
            }
        }

        // GPS
        C::update_meta( $post_id, C::META_GPS_LAT, floatval($_POST['gps_lat'] ?? 0) );
        C::update_meta( $post_id, C::META_GPS_LNG, floatval($_POST['gps_lng'] ?? 0) );
    }

    /**
     * Save amenities array
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
     * Generic single image upload
     */
    private static function upload_image( $file_key, $post_id, $meta_key, $min_width = 0 ) {
        if ( empty($_FILES[$file_key]['name']) ) return false;

        // Server-side limits and validation
        $max_bytes = 5 * 1024 * 1024; // 5MB per image
        if ( ! empty( $_FILES[$file_key]['error'] ) ) {
            self::log_error( "Upload error ({$file_key}): " . intval( $_FILES[$file_key]['error'] ) );
            return false;
        }

        if ( ! empty( $_FILES[$file_key]['size'] ) && intval( $_FILES[$file_key]['size'] ) > $max_bytes ) {
            self::log_error( "Upload too large ({$file_key}): {$_FILES[$file_key]['size']} bytes" );
            return false;
        }

        // Validate mime/extension using WP helper
        $check = wp_check_filetype_and_ext( $_FILES[$file_key]['tmp_name'], $_FILES[$file_key]['name'] );
        $mime = $check['type'] ?? '';
        $allowed = [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ];
        if ( empty( $mime ) || ! in_array( $mime, $allowed, true ) ) {
            self::log_error( "Invalid upload type: {$mime}" );
            return false;
        }

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        $attachment_id = media_handle_upload( $file_key, $post_id );
        if ( is_wp_error($attachment_id) ) {
            self::log_error('Upload failed: ' . $attachment_id->get_error_message());
            return false;
        }


        // Enforce minimum width and optionally resize large images for performance
        $meta = wp_get_attachment_metadata( $attachment_id );
        if ( $min_width && isset( $meta['width'] ) && $meta['width'] < $min_width ) {
            wp_delete_attachment( $attachment_id, true );
            self::log_error( "Upload too small: {$meta['width']}px (min: {$min_width})" );
            return false;
        }

        // Resize excessively large images to a sane maximum for storage (e.g., 2000px)
        $max_store_width = 2000;
        $file_path = get_attached_file( $attachment_id );
        if ( $file_path ) {
            $editor = wp_get_image_editor( $file_path );
            if ( ! is_wp_error( $editor ) ) {
                $size = $editor->get_size();
                if ( isset( $size['width'] ) && $size['width'] > $max_store_width ) {
                    $res = $editor->resize( $max_store_width, null );
                    if ( ! is_wp_error( $res ) ) {
                        $saved = $editor->save( $file_path );
                        if ( ! is_wp_error( $saved ) ) {
                            $new_meta = wp_generate_attachment_metadata( $attachment_id, $file_path );
                            if ( ! is_wp_error( $new_meta ) ) {
                                wp_update_attachment_metadata( $attachment_id, $new_meta );
                            }
                        }
                    }
                }
            }
        }

        set_post_thumbnail( $post_id, $attachment_id );
        C::update_meta( $post_id, $meta_key, $attachment_id );
        return $attachment_id;
    }

    /**
     * Handle multiple gallery images
     */
    private static function upload_gallery( $file_key, $post_id, $meta_key, $max_files = 10 ) {
        if ( empty($_FILES[$file_key]) ) return [];

        require_once( ABSPATH . 'wp-admin/includes/image.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/media.php' );

        $gallery_ids = [];
        $files = $_FILES[$file_key];

        if ( is_array($files['name']) ) {
            for ( $i = 0; $i < count( $files['name'] ); $i++ ) {
                if ( empty( $files['name'][ $i ] ) ) continue;
                if ( count( $gallery_ids ) >= $max_files ) break;

                // Basic per-file validation
                if ( ! empty( $files['error'][ $i ] ) ) {
                    self::log_error( "Gallery upload error: " . intval( $files['error'][ $i ] ) );
                    continue;
                }

                $max_bytes = 5 * 1024 * 1024; // 5MB
                if ( ! empty( $files['size'][ $i ] ) && intval( $files['size'][ $i ] ) > $max_bytes ) {
                    self::log_error( "Gallery upload too large: {$files['size'][$i]} bytes" );
                    continue;
                }

                $check = wp_check_filetype_and_ext( $files['tmp_name'][ $i ], $files['name'][ $i ] );
                $mime = $check['type'] ?? '';
                $allowed = [ 'image/jpeg', 'image/png', 'image/webp', 'image/gif' ];
                if ( empty( $mime ) || ! in_array( $mime, $allowed, true ) ) {
                    self::log_error( "Invalid gallery type: {$mime}" );
                    continue;
                }

                $_FILES['gallery_file'] = [
                    'name'     => $files['name'][ $i ],
                    'type'     => $files['type'][ $i ],
                    'tmp_name' => $files['tmp_name'][ $i ],
                    'error'    => $files['error'][ $i ],
                    'size'     => $files['size'][ $i ],
                ];

                $attach_id = media_handle_upload( 'gallery_file', $post_id );
                if ( is_wp_error( $attach_id ) ) {
                    self::log_error( 'Gallery upload failed: ' . $attach_id->get_error_message() );
                    continue;
                }

                $meta = wp_get_attachment_metadata( $attach_id );
                if ( isset( $meta['width'] ) && $meta['width'] < C::MIN_IMAGE_WIDTH ) {
                    wp_delete_attachment( $attach_id, true );
                    continue;
                }

                // Resize too-large images to a sane maximum (2000px)
                $max_store_width = 2000;
                $file_path = get_attached_file( $attach_id );
                if ( $file_path ) {
                    $editor = wp_get_image_editor( $file_path );
                    if ( ! is_wp_error( $editor ) ) {
                        $size = $editor->get_size();
                        if ( isset( $size['width'] ) && $size['width'] > $max_store_width ) {
                            $res = $editor->resize( $max_store_width, null );
                            if ( ! is_wp_error( $res ) ) {
                                $saved = $editor->save( $file_path );
                                if ( ! is_wp_error( $saved ) ) {
                                    $new_meta = wp_generate_attachment_metadata( $attach_id, $file_path );
                                    if ( ! is_wp_error( $new_meta ) ) {
                                        wp_update_attachment_metadata( $attach_id, $new_meta );
                                    }
                                }
                            }
                        }
                    }
                }

                $gallery_ids[] = $attach_id;
            }

            if ( $gallery_ids ) {
                // Merge with existing gallery ids if present
                $existing = C::get_meta( $post_id, $meta_key, true ) ?: [];
                if ( ! is_array( $existing ) ) $existing = [];
                $merged = array_values( array_merge( $existing, $gallery_ids ) );
                C::update_meta( $post_id, $meta_key, $merged );
            }
        }

        return $gallery_ids;
    }

    /**
     * Handle Lockdown Toggle
     */
    public static function handle_lockdown_toggle() {
        check_ajax_referer( C::NONCE_AJAX, 'security' );

        if ( ! current_user_can( C::CAP_MANAGE ) ) {
            self::send_error('Unauthorized');
        }

        $type   = sanitize_text_field( $_POST['type'] ?? '' );
        $status = sanitize_text_field( $_POST['status'] ?? '' );

        if ( ! in_array($type,['global_lockdown','identity_shield']) ) {
            self::send_error('Invalid type');
        }

        update_option( 'afc_' . $type, $status === '1' ? '1' : '0' );
        self::send_success('Settings Updated');
    }

    /**
     * Handle Listings Filter
     */
    public static function handle_listings_filter() {
        check_ajax_referer( C::NONCE_AJAX, 'nonce' );

        $page    = intval( $_POST['page'] ?? 1 );
        $filters = $_POST['filters'] ?? [];

        $args = [
            'post_type'      => C::POST_TYPE,
            'post_status'    => 'publish',
            'posts_per_page' => 9,
            'paged'          => $page,
        ];

        // Apply numeric filters
        $meta_query = [];
        if ( ! empty($filters['min_price']) ) $meta_query[] = [
            'key' => C::META_PRICE,
            'value' => floatval($filters['min_price']),
            'compare' => '>=',
            'type' => 'NUMERIC',
        ];
        if ( ! empty($filters['max_price']) ) $meta_query[] = [
            'key' => C::META_PRICE,
            'value' => floatval($filters['max_price']),
            'compare' => '<=',
            'type' => 'NUMERIC',
        ];
        if ( ! empty($filters['beds']) ) $meta_query[] = [
            'key' => C::META_BEDS,
            'value' => intval($filters['beds']),
            'compare' => '>=',
            'type' => 'NUMERIC',
        ];
        if ( $meta_query ) $args['meta_query'] = $meta_query;

        $query = new \WP_Query($args);

        ob_start();
        if ( $query->have_posts() ) {
            while ( $query->have_posts() ) {
                $query->the_post();
                $template_path = AFCG_PATH . 'templates/listing-card.php';
                if ( file_exists($template_path) ) include $template_path;
            }
            wp_reset_postdata();
        }
        $html = ob_get_clean();

        self::send_success('Listings Loaded', [
            'html' => $html,
            'max_pages' => $query->max_num_pages,
            'found' => $query->found_posts,
        ]);
    }
}
