<?php
/**
 * AFCGlide Inquiry Form v1.0
 * The Lead Capture Gateway
 */

if ( ! defined( 'ABSPATH' ) ) exit;

global $post;
$listing_id = $post->ID;
$agent_id   = $post->post_author;
?>

<div class="afc-inquiry-box">
    <h3>Inquire About This Asset</h3>
    <p class="afc-inquiry-subtitle">Direct routing to the listing agent.</p>
    
    <form id="afc-listing-inquiry" class="afc-inquiry-form">
        <input type="hidden" name="listing_id" value="<?php echo esc_attr($listing_id); ?>">
        <input type="hidden" name="agent_id" value="<?php echo esc_attr($agent_id); ?>">
        
        <div class="afc-input-group">
            <input type="text" name="lead_name" placeholder="Full Name" required>
        </div>
        
        <div class="afc-input-row">
            <input type="email" name="lead_email" placeholder="Email Address" required>
            <input type="tel" name="lead_phone" placeholder="Phone Number" required>
        </div>

        <div class="afc-input-group">
            <textarea name="lead_message" placeholder="I am interested in this property..." rows="4"></textarea>
        </div>

        <button type="submit" class="afc-inquiry-submit">
            <span class="btn-text">INITIALIZE INQUIRY</span>
            <span class="afc-pulse"></span>
        </button>
        
        <div id="afc-inquiry-response"></div>
    </form>
</div>

<style>
    .afc-inquiry-box {
        background: #f8fafc;
        padding: 30px;
        border-radius: 20px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 10px 25px rgba(0,0,0,0.05);
    }
    .afc-inquiry-box h3 { margin: 0 0 5px 0; color: #1e293b; font-weight: 800; }
    .afc-inquiry-subtitle { font-size: 13px; color: #64748b; margin-bottom: 25px; }
    
    .afc-inquiry-form input, .afc-inquiry-form textarea {
        width: 100%;
        padding: 12px 15px;
        margin-bottom: 15px;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        font-family: inherit;
    }
    
    .afc-input-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    
    .afc-inquiry-submit {
        width: 100%;
        background: #1a1a1a;
        color: #c9a227; /* Your Gold signature */
        border: none;
        padding: 15px;
        border-radius: 10px;
        font-weight: 900;
        letter-spacing: 1px;
        cursor: pointer;
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 10px;
        transition: 0.3s;
    }
    .afc-inquiry-submit:hover { background: #000; transform: translateY(-2px); }
</style>