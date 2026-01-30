<?php
namespace AFCGlide\Admin;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * AFCGlide User Profile Extensions
 * Version 2.1 - Identity Shield & Notification Routing
 */
class AFCGlide_User_Profile {

    public static function init() {
        add_action( 'show_user_profile', [ __CLASS__, 'add_agent_fields' ] );
        add_action( 'edit_user_profile', [ __CLASS__, 'add_agent_fields' ] );
        add_action( 'personal_options_update', [ __CLASS__, 'save_agent_fields' ] );
        add_action( 'edit_user_profile_update', [ __CLASS__, 'save_agent_fields' ] );
        
        add_action( 'admin_notices', [ __CLASS__, 'show_identity_shield_notice' ] );
        
        add_action( 'admin_head-profile.php', [ __CLASS__, 'profile_page_styles' ] );
        add_action( 'admin_head-user-edit.php', [ __CLASS__, 'profile_page_styles' ] );
    }

    /**
     * Show Identity Shield warning banner
     */
    public static function show_identity_shield_notice() {
        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->id, ['profile', 'user-edit'] ) ) {
            return;
        }

        if ( get_option('afc_identity_shield', '0') === '1' && ! current_user_can('manage_options') ) {
            ?>
            <div class="notice notice-warning" style="border-left-color: #f59e0b; background: #fffbeb;">
                <p style="display: flex; align-items: center; gap: 12px; margin: 12px 0;">
                    <span style="font-size: 24px;">🛡️</span>
                    <strong style="color: #92400e;">Identity Shield Active:</strong>
                    <span style="color: #78350f;">Your agent profile is protected and cannot be modified. Contact your Lead Broker to make changes.</span>
                </p>
            </div>
            <?php
        }
    }

    /**
     * Add custom styling to profile page
     */
    public static function profile_page_styles() {
        echo '<style>
            /* 🛑 SUPPRESS WORDPRESS CLUTTER */
            #personal-options, 
            .user-rich-editing-wrap, 
            .user-admin-color-wrap, 
            .user-comment-shortcuts-wrap, 
            .user-admin-bar-front-wrap, 
            .user-language-wrap,
            .user-first-name-wrap, 
            .user-last-name-wrap, 
            .user-nickname-wrap, 
            .user-display-name-wrap,
            .user-url-wrap,
            .user-description-wrap,
            .user-profile-picture-wrap,
            .application-passwords,
            form#your-profile > h2,
            form#your-profile > h3 {
                display: none !important;
            }

            /* 🟢 THE PREMIUM LOOK */
            .afc-profile-section {
                background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
                padding: 45px;
                border-radius: 25px;
                margin-bottom: 40px;
                border: 2px solid #86efac;
                box-shadow: 0 15px 35px rgba(22, 101, 52, 0.08);
                font-family: "Inter", sans-serif;
            }

            .afc-profile-section h2 {
                font-size: 32px !important;
                font-weight: 900 !important;
                color: #064e3b !important;
                margin-top: 0 !important;
                letter-spacing: -1.5px !important;
                display: flex;
                align-items: center;
                gap: 15px;
            }

            .afc-profile-section h3 {
                font-size: 18px !important;
                font-weight: 800 !important;
                color: #166534 !important;
                margin: 40px 0 20px 0 !important;
                text-transform: uppercase;
                letter-spacing: 1px;
                border-bottom: 2px solid rgba(22,101,52,0.1);
                padding-bottom: 10px;
            }

            .afc-profile-section table.form-table th {
                width: 250px;
                color: #166534;
                font-weight: 700;
            }

            .afc-profile-section table.form-table input[type="text"],
            .afc-profile-section table.form-table input[type="number"],
            .afc-profile-section table.form-table textarea {
                border-radius: 12px;
                border: 1px solid #86efac;
                padding: 12px 18px;
                background: white;
                box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
                width: 100%;
                max-width: 450px;
            }

            .afc-profile-section table.form-table input:focus {
                border-color: #10b981;
                box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.2);
            }

            .afc-broker-badge {
                background: #1e40af;
                color: white;
                padding: 6px 14px;
                border-radius: 30px;
                font-size: 12px;
                font-weight: 900;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            
            .user-email-wrap, .user-pass1-wrap, .user-pass2-wrap {
                background: white;
                padding: 20px;
                border-radius: 15px;
                margin-bottom: 10px;
                border: 1px solid #e2e8f0;
            }
        </style>';
    }

    /**
     * Add AFCGlide agent fields to profile
     */
    public static function add_agent_fields( $user ) {
        $is_locked = get_option('afc_identity_shield', '0') === '1' && ! current_user_can('manage_options');
        $is_broker = current_user_can('manage_options');
        $disabled = $is_locked ? 'disabled' : '';
        
        // Get existing values
        $phone       = get_user_meta( $user->ID, 'agent_phone', true );
        $whatsapp    = get_user_meta( $user->ID, 'agent_whatsapp', true );
        $cell_sms    = get_user_meta( $user->ID, 'agent_cell_sms', true );
        $license     = get_user_meta( $user->ID, 'agent_license', true );
        $bio         = get_user_meta( $user->ID, 'agent_bio', true );
        $specialties = get_user_meta( $user->ID, 'agent_specialties', true );
        $commission  = get_user_meta( $user->ID, 'agent_commission', true ) ?: '100';
        ?>
        
        <div class="afc-profile-section">
            <h2>
                <span style="font-size: 40px;">👤</span>
                <?php echo $is_broker ? 'Brokerage Headquarters' : 'Agent Identity Terminal'; ?>
                <?php if ($is_broker) : ?>
                    <span class="afc-broker-badge">LEAD OPERATOR</span>
                <?php endif; ?>
            </h2>

            <?php if ($is_broker) : ?>
            <h3>📉 Brokerage Protocols</h3>
            <table class="form-table">
                <tr>
                    <th><label for="agent_commission">Commission Split (%)</label></th>
                    <td>
                        <input type="number" name="agent_commission" id="agent_commission" value="<?php echo esc_attr($commission); ?>" class="small-text">
                        <p class="description">Global default for transactional calculations.</p>
                    </td>
                </tr>
            </table>
            <?php endif; ?>

            <h3>📱 Agent Identity & Contact</h3>
            <table class="form-table">
                <tbody>
                    <tr>
                        <th><label for="agent_phone">Public Display Phone</label></th>
                        <td>
                            <input type="text" name="agent_phone" id="agent_phone" value="<?php echo esc_attr( $phone ); ?>" placeholder="e.g. +1 (555) 123-4567" <?php echo $disabled; ?>>
                            <p class="description">This number is shown on the listing cards.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="agent_whatsapp">WhatsApp Lead Line</label></th>
                        <td>
                            <input type="text" name="agent_whatsapp" id="agent_whatsapp" value="<?php echo esc_attr( $whatsapp ); ?>" placeholder="Include Country Code (e.g. 50688889999)" <?php echo $disabled; ?>>
                            <p class="description">Direct routing for instant Lead Alerts via WhatsApp.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="agent_cell_sms">Direct Cell (SMS)</label></th>
                        <td>
                            <input type="text" name="agent_cell_sms" id="agent_cell_sms" value="<?php echo esc_attr( $cell_sms ); ?>" placeholder="For emergency SMS lead backups" <?php echo $disabled; ?>>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="agent_license">License Number</label></th>
                        <td>
                            <input type="text" name="agent_license" id="agent_license" value="<?php echo esc_attr( $license ); ?>" placeholder="e.g. BRE #12345678" <?php echo $disabled; ?>>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="agent_specialties">Luxury Specialties</label></th>
                        <td>
                            <input type="text" name="agent_specialties" id="agent_specialties" value="<?php echo esc_attr( $specialties ); ?>" placeholder="e.g. Waterfront, Modern Estates" <?php echo $disabled; ?>>
                        </td>
                    </tr>
                </tbody>
            </table>

            <h3>📝 Professional Narrative</h3>
            <table class="form-table">
                <tr>
                    <th><label for="agent_bio">Agent Bio</label></th>
                    <td>
                        <textarea name="agent_bio" id="agent_bio" rows="6" placeholder="The story of your real estate expertise..." <?php echo $disabled; ?>><?php echo esc_textarea( $bio ); ?></textarea>
                    </td>
                </tr>
            </table>
            <input type="hidden" name="afc_identity_operator" value="1">
        </div>

        <div class="afc-profile-section" style="background: white !important; border-color: #e2e8f0 !important;">
            <h2 style="font-size: 24px !important; color: #1e293b !important;">📡 System Access Protocols</h2>
            <p style="color: #64748b; margin-top: -10px;">Security and credentials for the AFCGlide secure network.</p>
        <?php
    }

    /**
     * Save agent fields (with Identity Shield check)
     */
    public static function save_agent_fields( $user_id ) {
        if ( ! current_user_can( 'edit_user', $user_id ) ) {
            return;
        }

        if ( get_option('afc_identity_shield', '0') === '1' && ! current_user_can('manage_options') ) {
            set_transient( 'afc_profile_locked_' . $user_id, true, 30 );
            return; 
        }

        $fields = [
            'agent_phone',
            'agent_whatsapp',
            'agent_cell_sms',
            'agent_license',
            'agent_office',
            'agent_specialties',
            'agent_bio',
            'agent_commission'
        ];

        foreach ($fields as $field) {
            if ( isset($_POST[$field]) ) {
                $value = ($field === 'agent_bio') ? sanitize_textarea_field( $_POST[$field] ) : sanitize_text_field( $_POST[$field] );
                update_user_meta( $user_id, $field, $value );
            }
        }

        set_transient( 'afc_profile_saved_' . $user_id, true, 30 );
    }
}