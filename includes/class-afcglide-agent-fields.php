<?php
namespace AFCGlide\Listings;

if ( ! defined( 'ABSPATH' ) ) exit;

class AFCGlide_Agent_Fields {

    public static function init() {
        $instance = new self();
        // Hook into both the "Your Profile" and "Edit User" screens
        add_action( 'show_user_profile', [ $instance, 'render_agent_fields' ] );
        add_action( 'edit_user_profile', [ $instance, 'render_agent_fields' ] );

        // Hook into the save process
        add_action( 'personal_options_update', [ $instance, 'save_agent_fields' ] );
        add_action( 'edit_user_profile_update', [ $instance, 'save_agent_fields' ] );
    }

    public function render_agent_fields( $user ) {
        ?>
        <hr>
        <h2 class="afcglide-admin-heading">🏆 AFCGlide Agent Branding</h2>
        <p class="description">Upload your assets here to brand your listings and the floating WhatsApp button.</p>
        
        <table class="form-table">
            <tr>
                <th><label for="afc_agent_whatsapp">WhatsApp Phone</label></th>
                <td>
                    <input type="text" name="afc_agent_whatsapp" id="afc_agent_whatsapp" value="<?php echo esc_attr( get_user_meta( $user->ID, 'afc_agent_whatsapp', true ) ); ?>" class="regular-text" placeholder="+506 0000 0000">
                    <p class="description">Include country code. This powers the floating chat button.</p>
                </td>
            </tr>

            <tr>
                <th><label>Agent Headshot</label></th>
                <td>
                    <div class="afcglide-settings-upload-wrapper">
                        <input type="hidden" name="afc_agent_headshot" class="afcglide-settings-input" value="<?php echo esc_attr( get_user_meta( $user->ID, 'afc_agent_headshot', true ) ); ?>">
                        <div class="afcglide-settings-preview" style="margin-bottom:10px;">
                            <?php 
                            $headshot = get_user_meta( $user->ID, 'afc_agent_headshot', true );
                            if ( $headshot ) echo '<img src="' . esc_url($headshot) . '" style="max-width:120px; border-radius:50%; border:3px solid #4f46e5;">';
                            ?>
                        </div>
                        <button type="button" class="button afcglide-upload-button">Select Headshot</button>
                        <button type="button" class="button afcglide-clear-button">Remove</button>
                    </div>
                </td>
            </tr>

            <tr>
                <th><label>Agency Logo</label></th>
                <td>
                    <div class="afcglide-settings-upload-wrapper">
                        <input type="hidden" name="afc_agency_logo" class="afcglide-settings-input" value="<?php echo esc_attr( get_user_meta( $user->ID, 'afc_agency_logo', true ) ); ?>">
                        <div class="afcglide-settings-preview" style="margin-bottom:10px;">
                            <?php 
                            $logo = get_user_meta( $user->ID, 'afc_agency_logo', true );
                            if ( $logo ) echo '<img src="' . esc_url($logo) . '" style="max-width:150px; border:1px solid #ddd; padding:5px;">';
                            ?>
                        </div>
                        <button type="button" class="button afcglide-upload-button">Select Agency Logo</button>
                        <button type="button" class="button afcglide-clear-button">Remove</button>
                    </div>
                </td>
            </tr>

            <tr>
                <th><label for="afc_agent_license">Agent License #</label></th>
                <td>
                    <input type="text" name="afc_agent_license" id="afc_agent_license" value="<?php echo esc_attr( get_user_meta( $user->ID, 'afc_agent_license', true ) ); ?>" class="regular-text">
                </td>
            </tr>
        </table>
        <?php
    }

    public function save_agent_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) return false;

        update_user_meta( $user_id, 'afc_agent_whatsapp', sanitize_text_field( $_POST['afc_agent_whatsapp'] ) );
        update_user_meta( $user_id, 'afc_agent_headshot', esc_url_raw( $_POST['afc_agent_headshot'] ) );
        update_user_meta( $user_id, 'afc_agency_logo', esc_url_raw( $_POST['afc_agency_logo'] ) );
        update_user_meta( $user_id, 'afc_agent_license', sanitize_text_field( $_POST['afc_agent_license'] ) );
    }
}