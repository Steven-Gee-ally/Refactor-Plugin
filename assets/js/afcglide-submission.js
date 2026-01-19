/**
 * AFCGlide Submission Logic - v3.7.0 (Master Suite Rehab)
 * Vision: Zero-Error Asset Broadcasting
 */
jQuery(document).ready(function ($) {
    const $form = $('#afcglide-front-submission');
    const $submitBtn = $('#afc-submit-btn');
    const $feedback = $('#afc-feedback');

    /**
     * 1. HERO QUALITY GATEKEEPER
     * Prevents low-res assets from damaging the site's luxury brand.
     */
    $(document).on('change', '#hero_file', function (e) {
        const file = e.target.files[0];
        const $previewBox = $('.hero-preview-box');

        if (file) {
            // Check file type first
            if (!file.type.match('image.*')) {
                alert('🚫 INVALID FILE: Please upload a JPG or PNG.');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                const img = new Image();
                img.onload = function () {
                    // World-Class Standard: 1200px width minimum
                    if (this.width < 1200) {
                        alert('⚠️ QUALITY REJECTED: Luxury listings require 1200px width minimum.\nCurrently detected: ' + this.width + 'px');
                        e.target.value = '';
                        $previewBox.html('<p style="color:#ef4444; font-size:12px;">Image too small.</p>');
                        return;
                    }

                    // Smooth Transition to Preview
                    $previewBox.css('background', 'none').html(
                        `<img src="${event.target.result}" id="hero-preview" style="width:100%; height:100%; object-fit:cover; border-radius:12px; border: 2px solid #10b981;">`
                    );
                };
                img.src = event.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    /**
     * 2. GALLERY IMAGE PREVIEW
     * Handles multiple file preview for the slider
     */
    $(document).on('change', '#gallery_files', function (e) {
        const files = e.target.files;
        if (files.length === 0) return;

        $('#new-gallery-preview').show();
        const $grid = $('#new-gallery-grid');
        $grid.empty();

        // Max images allowed (hardcoded to match PHP constant, ideally passed via vars)
        const maxGallery = 12;

        for (let i = 0; i < files.length && i < maxGallery; i++) {
            const file = files[i];
            const reader = new FileReader();

            reader.onload = function (e) {
                const $thumb = $('<div class="new-gallery-thumb"><img src="' + e.target.result + '"></div>');
                $grid.append($thumb);
            }

            reader.readAsDataURL(file);
        }

        if (files.length > maxGallery) {
            alert('Maximum ' + maxGallery + ' images allowed. Only the first ' + maxGallery + ' will be uploaded.');
        }
    });

    /**
     * 3. AJAX BROADCAST (The Emerald Protocol)
     */
    $form.on('submit', function (e) {
        e.preventDefault();

        // UI Lockdown: Prevent double-click ghost listings
        $submitBtn.prop('disabled', true).css('opacity', '0.6').text('🚀 SYNCING ASSET...');
        $feedback.fadeIn().html('<p style="color: #6366f1;">Initializing secure handshake with server...</p>');

        const formData = new FormData(this);

        // Ensure the action matches class-afcglide-ajax-handler.php
        formData.append('action', 'afcglide_submit_listing');
        formData.append('security', afc_vars.nonce); // Added Nonce for security

        $.ajax({
            url: afc_vars.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.success) {
                    $submitBtn.css({ 'background': '#10b981', 'opacity': '1' }).text('✨ ASSET DEPLOYED');
                    $feedback.html('<p style="color: #10b981;">Listing Verified. Redirecting...</p>');

                    setTimeout(() => {
                        window.location.href = response.data.url;
                    }, 1200);
                } else {
                    $feedback.html('<p style="color: #ef4444;">❌ ERROR: ' + response.data.message + '</p>');
                    $submitBtn.prop('disabled', false).css('opacity', '1').text('PUBLISH GLOBAL LISTING');
                }
            },
            error: function () {
                $feedback.html('<p style="color: #ef4444;">⚠️ Critical Connection Failure. Check file sizes.</p>');
                $submitBtn.prop('disabled', false).css('opacity', '1').text('RETRY SUBMISSION');
            }
        });
    });
});