<?php
namespace AFCGlide\Listings;

use AFCGlide\Listings\Helpers\Sanitizer;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_User_Profile {

    public static function init() {
        add_action( 'show_user_profile', [ __CLASS__, 'render_luxury_profile' ] );
        add_action( 'edit_user_profile', [ __CLASS__, 'render_luxury_profile' ] );
        add_action( 'personal_options_update', [ __CLASS__, 'save_fields' ] );
        add_action( 'edit_user_profile_update', [ __CLASS__, 'save_fields' ] );
        add_filter( 'manage_users_columns', [ __CLASS__, 'add_columns' ] );
        add_filter( 'manage_users_custom_column', [ __CLASS__, 'render_column' ], 10, 3 );
    }

    private static function get_fields() {
        return [
            'agent_photo'    => [ 'label' => 'Agent Photo', 'type' => 'image', 'desc' => 'Professional headshot' ],
            'agent_logo'     => [ 'label' => 'Company Logo', 'type' => 'image', 'desc' => 'Your agency branding' ],
            'agent_company'  => [ 'label' => 'Company Name', 'type' => 'text' ],
            'agent_license'  => [ 'label' => 'License #', 'type' => 'text' ],
            'agent_phone'    => [ 'label' => 'Phone Number', 'type' => 'text' ],
            'agent_whatsapp' => [ 'label' => 'WhatsApp Number', 'type' => 'text' ],
            'agent_bio'      => [ 'label' => 'Short Bio', 'type' => 'textarea' ],
        ];
    }

    public static function render_luxury_profile( $user ) {
        wp_nonce_field( 'afcglide_agent_nonce', 'afcglide_agent_nonce' );

        // COMMAND CENTER LOGIC: Check if editing is locked
        $lockdown_active = get_option('afc_identity_lockdown', 'no') === 'yes';
        $is_restricted   = !current_user_can('manage_options'); // Only restrict non-admins
        $should_freeze   = ($lockdown_active && $is_restricted);
        ?>
        
        <div class="afcglide-luxury-profile-section">
            <h2 class="afc-section-title">
                <span class="emerald-bar"></span>
                AFCGlide Agent Identity
            </h2>

            <?php if ( $should_freeze ) : ?>
                <div style="background: #eff6ff; border: 1px solid #dbeafe; padding: 20px; border-radius: 12px; color: #1e40af; margin-bottom: 25px; display: flex; align-items: center; gap: 15px;">
                    <span style="font-size: 24px;">🛡️</span>
                    <div>
                        <strong style="display: block; font-size: 15px;">Identity Lockdown Active</strong>
                        <span style="font-size: 13px; opacity: 0.9;">Your professional profile is currently managed by the office broker to ensure brand consistency.</span>
                    </div>
                </div>
            <?php endif; ?>

            <div class="afc-profile-grid <?php echo $should_freeze ? 'afc-is-locked' : ''; ?>">
                <?php foreach ( self::get_fields() as $key => $field ) : 
                    $value = get_user_meta( $user->ID, $key, true );
                    ?>
                    <div class="afc-profile-card <?php echo $field['type'] === 'image' ? 'photo-card' : ''; ?>">
                        <label class="afc-card-label"><?php echo esc_html( $field['label'] ); ?></label>
                        
                        <?php if ( $field['type'] === 'image' ) : 
                            $img_src = $value ? wp_get_attachment_url( $value ) : '';
                            ?>
                            <div class="afc-image-uploader">
                                <div class="afcglide-preview-box" style="width:120px; height:120px; border-radius:<?php echo $key === 'agent_photo' ? '50%' : '8px'; ?>; overflow:hidden; border:2px solid <?php echo $should_freeze ? '#cbd5e1' : '#10b981'; ?>; background:#f8fafc; margin-bottom:15px;">
                                    <?php if ($img_src) : ?>
                                        <img src="<?php echo esc_url($img_src); ?>" style="width:100%; height:100%; object-fit:cover; <?php echo $should_freeze ? 'filter: grayscale(0.5); opacity: 0.8;' : ''; ?>">
                                    <?php endif; ?>
                                </div>
                                
                                <input type="hidden" name="<?php echo esc_attr($key); ?>" id="afc_<?php echo esc_attr($key); ?>_id" value="<?php echo esc_attr($value); ?>">
                                
                                <div class="afc-btn-group">
                                    <button type="button" 
                                            class="afcglide-upload-image-btn button" 
                                            data-target="afc_<?php echo esc_attr($key); ?>_id"
                                            <?php disabled($should_freeze); ?>>
                                        <?php echo $should_freeze ? 'Locked' : 'Set ' . esc_html($field['label']); ?>
                                    </button>
                                </div>
                            </div>

                        <?php elseif ( $field['type'] === 'textarea' ) : ?>
                            <textarea name="<?php echo esc_attr( $key ); ?>" 
                                      class="afc-luxury-input" 
                                      rows="4" 
                                      <?php readonly($should_freeze); ?>><?php echo esc_textarea( $value ); ?></textarea>
                        
                        <?php else : ?>
                            <input type="text" 
                                   name="<?php echo esc_attr( $key ); ?>" 
                                   value="<?php echo esc_attr( $value ); ?>" 
                                   class="afc-luxury-input"
                                   <?php readonly($should_freeze); ?>>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <style>
            .afcglide-luxury-profile-section { margin-top: 40px; padding: 20px; background: #fdfdfd; border-radius: 12px; border: 1px solid #e2e8f0; }
            .afc-section-title { font-size: 24px !important; color: #1e293b; position: relative; padding-left: 20px; margin-bottom: 30px !important; }
            .emerald-bar { position: absolute; left: 0; top: 5px; bottom: 5px; width: 6px; background: #10b981; border-radius: 10px; }
            
            .afc-profile-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }
            .afc-profile-card { background: #fff; padding: 20px; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
            
            /* Visual feedback for Locked State */
            .afc-is-locked .afc-profile-card { background: #f8fafc; border-color: #f1f5f9; }
            .afc-is-locked .afc-luxury-input { background: #f1f5f9; color: #64748b; cursor: not-allowed; }
            
            .afc-card-label { display: block; font-weight: 700; font-size: 13px; text-transform: uppercase; color: #64748b; margin-bottom: 12px; letter-spacing: 0.5px; }
            .afc-luxury-input { width: 100%; border: 1px solid #cbd5e1 !important; border-radius: 8px !important; padding: 10px !important; font-size: 14px; }
            .afc-luxury-input:focus { border-color: #10b981 !important; outline: none; box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important; }
            .photo-card { grid-row: span 1; display: flex; flex-direction: column; align-items: center; text-align: center; }
        </style>
        <?php
    }

    public static function save_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return;
        if ( ! isset( $_POST['afcglide_agent_nonce'] ) || ! wp_verify_nonce( $_POST['afcglide_agent_nonce'], 'afcglide_agent_nonce' ) ) return;
        
        // SECURITY: If Lockdown is ON and user is not an Admin, BLOCK the save
        $lockdown_active = get_option('afc_identity_lockdown', 'no') === 'yes';
        $is_restricted   = !current_user_can('manage_options');

        if ( $lockdown_active && $is_restricted ) {
            return; // Exit without saving anything
        }

        foreach ( self::get_fields() as $key => $field ) {
            if ( isset( $_POST[ $key ] ) ) {
                update_user_meta( $user_id, $key, sanitize_text_field( $_POST[ $key ] ) );
            }
        }
    }

    public static function add_columns( $cols ) {
        $cols['agent_photo'] = 'Agent';
        $cols['agent_company'] = 'Agency';
        return $cols;
    }

    public static function render_column( $output, $col, $user_id ) {
        if ( $col === 'agent_photo' ) {
            $img_id = get_user_meta( $user_id, 'agent_photo', true );
            if ( $img_id ) {
                $src = wp_get_attachment_url( $img_id );
                return '<img src="' . esc_url( $src ) . '" style="width:40px;height:40px;border-radius:50%;object-fit:cover;border:1px solid #10b981;">';
            }
        }
        if ( $col === 'agent_company' ) {
            return '<strong>' . esc_html( get_user_meta( $user_id, 'agent_company', true ) ) . '</strong>';
        }
        return $output;
    }
}