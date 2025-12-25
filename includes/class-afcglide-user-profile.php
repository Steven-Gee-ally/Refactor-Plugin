<?php
/**
 * Master User Profile Class - Refactored for AFCGlide v3
 */

namespace AFCGlide\Listings;

use AFCGlide\Listings\Helpers\Sanitizer;
use AFCGlide\Listings\Helpers\Validator;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_User_Profile {

    public static function init() {
        add_action( 'show_user_profile', [ __CLASS__, 'render_fields' ] );
        add_action( 'edit_user_profile', [ __CLASS__, 'render_fields' ] );
        add_action( 'personal_options_update', [ __CLASS__, 'save_fields' ] );
        add_action( 'edit_user_profile_update', [ __CLASS__, 'save_fields' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_filter( 'manage_users_columns', [ __CLASS__, 'add_columns' ] );
        add_filter( 'manage_users_custom_column', [ __CLASS__, 'render_column' ], 10, 3 );
    }

    private static function get_fields() {
        return [
            'agent_photo'    => [ 'label' => 'Agent Photo', 'type' => 'image' ],
            'agent_logo'     => [ 'label' => 'Company Logo', 'type' => 'image' ],
            'agent_company'  => [ 'label' => 'Company Name', 'type' => 'text' ],
            'agent_license'  => [ 'label' => 'License #', 'type' => 'text' ],
            'agent_phone'    => [ 'label' => 'Phone', 'type' => 'phone' ],
            'agent_whatsapp' => [ 'label' => 'WhatsApp', 'type' => 'phone' ],
            'agent_bio'      => [ 'label' => 'Agent Bio', 'type' => 'textarea' ],
        ];
    }

    public static function enqueue_assets( $hook ) {
        if ( ! in_array( $hook, [ 'profile.php', 'user-edit.php' ], true ) ) {
            return;
        }
        
        wp_enqueue_media();

        ob_start(); ?>
        <script>
            jQuery(document).ready(function($){
                $('.afcglide-upload-btn').on('click', function(e) {
                    e.preventDefault();
                    var btn = $(this);
                    var input = btn.prev('input');
                    var img = btn.parent().find('.preview-img');
                    var frame = wp.media({ title: 'Select Photo', multiple: false, library: { type: 'image' }, button: { text: 'Use Photo' } });
                    frame.on('select', function(){
                        var attachment = frame.state().get('selection').first().toJSON();
                        input.val(attachment.id);
                        img.attr('src', attachment.url).show();
                    });
                    frame.open();
                });
                $('.afcglide-remove-btn').on('click', function(e) {
                    e.preventDefault();
                    $(this).prev().prev('input').val('');
                    $(this).parent().find('.preview-img').hide();
                });
            });
        </script>
        <?php
        $js_code = ob_get_clean();
        $js_code = str_replace(['<script>', '</script>'], '', $js_code);
        wp_add_inline_script( 'jquery', $js_code );
    }

    public static function render_fields( $user ) {
        wp_nonce_field( 'afcglide_agent_nonce', 'afcglide_agent_nonce' );
        echo '<h3>' . esc_html__( 'AFCGlide Agent Profile', 'afcglide' ) . '</h3>';
        echo '<table class="form-table">';
        foreach ( self::get_fields() as $key => $field ) {
            $value = get_user_meta( $user->ID, $key, true );
            echo '<tr><th><label>' . esc_html( $field['label'] ) . '</label></th><td>';
            if ( $field['type'] === 'image' ) {
                $img_src = $value ? wp_get_attachment_url( $value ) : '';
                echo '<img src="' . esc_url( $img_src ) . '" class="preview-img" style="width:80px;height:80px;object-fit:cover;border-radius:50%;display:' . ($img_src ? 'block' : 'none') . ';margin-bottom:10px;">';
                echo '<input type="hidden" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '">';
                echo '<button class="button afcglide-upload-btn">Upload</button> ';
                echo '<button class="button afcglide-remove-btn" style="color:#a00;">Remove</button>';
            } elseif ( $field['type'] === 'textarea' ) {
                echo '<textarea name="' . esc_attr( $key ) . '" rows="5" class="large-text">' . esc_textarea( $value ) . '</textarea>';
            } else {
                echo '<input type="text" name="' . esc_attr( $key ) . '" value="' . esc_attr( $value ) . '" class="regular-text">';
            }
            echo '</td></tr>';
        }
        echo '</table>';
    }

    public static function save_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;
        if ( ! isset( $_POST['afcglide_agent_nonce'] ) || ! wp_verify_nonce( $_POST['afcglide_agent_nonce'], 'afcglide_agent_nonce' ) ) return;
        foreach ( self::get_fields() as $key => $field ) {
            if ( ! isset( $_POST[ $key ] ) ) continue;
            $value = $_POST[ $key ];
            switch ( $field['type'] ) {
                case 'image': update_user_meta( $user_id, $key, Sanitizer::int( $value ) ); break;
                case 'textarea': update_user_meta( $user_id, $key, Sanitizer::html( $value ) ); break;
                case 'phone': update_user_meta( $user_id, $key, Sanitizer::phone( $value ) ); break;
                default: update_user_meta( $user_id, $key, Sanitizer::text( $value ) ); break;
            }
        }
    }

    public static function add_columns( $cols ) {
        $cols['agent_photo'] = 'Photo';
        $cols['agent_company'] = 'Company';
        return $cols;
    }

    public static function render_column( $output, $col, $user_id ) {
        if ( $col === 'agent_photo' ) {
            $img_id = get_user_meta( $user_id, 'agent_photo', true );
            if ( $img_id ) {
                $src = wp_get_attachment_url( $img_id );
                return '<img src="' . esc_url( $src ) . '" style="width:32px;height:32px;border-radius:50%;object-fit:cover;">';
            }
            return '—';
        }
        if ( $col === 'agent_company' ) {
            return esc_html( get_user_meta( $user_id, 'agent_company', true ) );
        }
        return $output;
    }
}