<?php
/**
 * Master User Profile Class
 * Handles Agent Fields, Saving, Media Uploader, and Admin Columns.
 *
 * @package AFCGlide_Listings
 */

namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_User_Profile {

    /**
     * 1. Initialize All Hooks
     */
    public static function init() {
        // Render Fields (Edit Profile)
        add_action( 'show_user_profile', [ __CLASS__, 'render_fields' ] );
        add_action( 'edit_user_profile', [ __CLASS__, 'render_fields' ] );

        // Save Fields
        add_action( 'personal_options_update', [ __CLASS__, 'save_fields' ] );
        add_action( 'edit_user_profile_update', [ __CLASS__, 'save_fields' ] );

        // Enqueue Assets (Media Uploader)
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );

        // Custom Columns (User List)
        add_filter( 'manage_users_columns', [ __CLASS__, 'add_columns' ] );
        add_filter( 'manage_users_custom_column', [ __CLASS__, 'render_column' ], 10, 3 );
    }

    /**
     * 2. Define Fields Schema (Central Source of Truth)
     */
    private static function get_fields() {
        return [
            'agent_photo' => [
                'label'       => __( 'Agent Photo', 'afcglide' ),
                'type'        => 'image',
                'description' => __( 'Upload a square (1:1) photo.', 'afcglide' ),
            ],
            'agent_logo' => [
                'label'       => __( 'Company Logo', 'afcglide' ),
                'type'        => 'image',
                'description' => __( 'Upload brokerage logo.', 'afcglide' ),
            ],
            'agent_company' => [
                'label'       => __( 'Company Name', 'afcglide' ),
                'type'        => 'text',
                'placeholder' => 'e.g. Keller Williams',
            ],
            'agent_license' => [
                'label'       => __( 'License #', 'afcglide' ),
                'type'        => 'text',
                'placeholder' => 'Lic #123456',
            ],
            'agent_phone' => [
                'label'       => __( 'Phone', 'afcglide' ),
                'type'        => 'text',
                'placeholder' => '+1 555 123 4567',
            ],
            'agent_whatsapp' => [
                'label'       => __( 'WhatsApp', 'afcglide' ),
                'type'        => 'text',
                'placeholder' => '+1 555 123 4567',
            ],
            'agent_bio' => [
                'label'       => __( 'Agent Bio', 'afcglide' ),
                'type'        => 'textarea',
                'description' => __( 'Short biography. Basic HTML allowed.', 'afcglide' ),
            ],
        ];
    }

    /**
     * 3. Enqueue Media Assets & Inline JS
     */
    public static function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, [ 'profile.php', 'user-edit.php' ], true ) ) {
            return;
        }
        
        wp_enqueue_media();

        // Inline JS to handle the image upload buttons without an extra file
        wp_add_inline_script( 'jquery', "
            jQuery(document).ready(function($){
                $('.afcglide-upload-btn').click(function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    var input = btn.prev('input');
                    var img = btn.parent().find('.preview-img');
                    
                    var frame = wp.media({ 
                        title: 'Select Photo', 
                        multiple: false, 
                        library: { type: 'image' }, 
                        button: { text: 'Use Photo' } 
                    });
                    
                    frame.on('select', function(){
                        var attachment = frame.state().get('selection').first().toJSON();
                        input.val(attachment.id);
                        img.attr('src', attachment.url).show();
                    });
                    
                    frame.open();
                });

                $('.afcglide-remove-btn').click(function(e) {
                    e.preventDefault();
                    $(this).prev().prev('input').val('');
                    $(this).parent().find('.preview-img').hide();
                });
            });
        " );
    }

    /**
     * 4. Render Fields
     */
    public static function render_fields( $user ) {
        wp_nonce_field( 'afcglide_agent_nonce', 'afcglide_agent_nonce' );
        
        echo '<h3>' . esc_html__( 'AFCGlide Agent Profile', 'afcglide' ) . '</h3>';
        echo '<table class="form-table">';

        foreach ( self::get_fields() as $key => $field ) {
            $value = get_user_meta( $user->ID, $key, true );
            
            echo '<tr>';
            echo '<th><label for="' . esc_attr( $key ) . '">' . esc_html( $field['label'] ) . '</label></th>';
            echo '<td>';

            if ( $field['type'] === 'image' ) {
                $img_src = $value ? wp_get_attachment_url( $value ) : '';
                echo '<img src="' . esc_url( $img_src ) . '" class="preview-img" style="width:80px;height:80px;object-fit:cover;border-radius:50%;display:' . ($img_src ? 'block' : 'none') . ';margin-bottom:10px;">';
                echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
                echo '<button class="button afcglide-upload-btn">' . esc_html__( 'Upload', 'afcglide' ) . '</button> ';
                echo '<button class="button afcglide-remove-btn" style="color:#a00;">' . esc_html__( 'Remove', 'afcglide' ) . '</button>';
            } 
            elseif ( $field['type'] === 'textarea' ) {
                echo '<textarea name="' . esc_attr( $key ) . '" rows="5" class="large-text">' . esc_textarea( $value ) . '</textarea>';
            } 
            else {
                echo '<input type="text" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="regular-text" placeholder="' . esc_attr( $field['placeholder'] ?? '' ) . '">';
            }

            if ( ! empty( $field['description'] ) ) {
                echo '<p class="description">' . esc_html( $field['description'] ) . '</p>';
            }

            echo '</td></tr>';
        }
        echo '</table>';
    }

    /**
     * 5. Save Fields
     */
    public static function save_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;
        if ( ! isset( $_POST['afcglide_agent_nonce'] ) || ! wp_verify_nonce( $_POST['afcglide_agent_nonce'], 'afcglide_agent_nonce' ) ) return;

        foreach ( self::get_fields() as $key => $field ) {
            if ( isset( $_POST[ $key ] ) ) {
                if ( $field['type'] === 'textarea' ) {
                    update_user_meta( $user_id, $key, wp_kses_post( $_POST[ $key ] ) );
                } else {
                    update_user_meta( $user_id, $key, sanitize_text_field( $_POST[ $key ] ) );
                }
            }
        }
    }

    /**
     * 6. Add User Columns
     */
    public static function add_columns( $cols ) {
        $cols['agent_photo'] = __( 'Agent Photo', 'afcglide' );
        $cols['agent_company'] = __( 'Company', 'afcglide' );
        return $cols;
    }

    /**
     * 7. Render User Columns
     */
    public static function render_column( $output, $col, $user_id ) {
        if ( $col === 'agent_photo' ) {
            $img_id = get_user_meta( $user_id, 'agent_photo', true );
            if ( $img_id ) {
                $src = wp_get_attachment_url( $img_id );
                return '<img src="' . esc_url( $src ) . '" style="width:40px;height:40px;border-radius:50%;object-fit:cover;">';
            }
            return '—';
        }
        if ( $col === 'agent_company' ) {
            return esc_html( get_user_meta( $user_id, 'agent_company', true ) );
        }
        return $output;
    }
}
?>