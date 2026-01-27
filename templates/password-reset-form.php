<?php
/**
 * Synergy Terminal - Secure Password Reset Template
 * World-Class Security Interface
 */

if ( ! defined( 'ABSPATH' ) ) exit;

get_header(); ?>

<div class="afc-reset-container">
    <div class="afc-reset-card">
        <div class="afc-reset-header">
            <span class="afc-terminal-icon">◈</span>
            <h1><?php esc_html_e( 'Secure Gateway', 'afcglide' ); ?></h1>
            <p><?php esc_html_e( 'Initialize your agent credentials.', 'afcglide' ); ?></p>
        </div>

        <?php if ( isset( $error ) ) : ?>
            <div class="afc-reset-error">
                <?php echo esc_html( $error ); ?>
            </div>
        <?php endif; ?>

        <form method="post" class="afc-reset-form">
            <?php wp_nonce_field( 'afc_set_password_action', 'afc_set_password_nonce' ); ?>
            
            <div class="afc-input-group">
                <label for="afc_new_password"><?php esc_html_e( 'New Terminal Password', 'afcglide' ); ?></label>
                <input type="password" name="afc_new_password" id="afc_new_password" required minlength="8" placeholder="••••••••">
                <small><?php esc_html_e( 'Minimum 8 characters for high-level security.', 'afcglide' ); ?></small>
            </div>

            <div class="afc-input-group">
                <label for="afc_new_password_confirm"><?php esc_html_e( 'Confirm Password', 'afcglide' ); ?></label>
                <input type="password" name="afc_new_password_confirm" id="afc_new_password_confirm" required placeholder="••••••••">
            </div>

            <button type="submit" class="afc-reset-btn">
                <?php esc_html_e( 'ACTIVATE ACCOUNT', 'afcglide' ); ?>
            </button>
        </form>
        
        <div class="afc-reset-footer">
            <p><?php esc_html_e( 'Pura Vida! Welcome to the team.', 'afcglide' ); ?></p>
        </div>
    </div>
</div>

<?php get_footer(); ?>