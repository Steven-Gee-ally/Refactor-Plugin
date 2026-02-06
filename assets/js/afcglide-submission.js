/**
 * AFCGlide Submission Logic - v3.7.0 (Master Suite Rehab)
 * Vision: Zero-Error Asset Broadcasting
 */
jQuery(document).ready(function ($) {
    // Guard: Ensure localization is present
    if (typeof afc_vars === 'undefined') {
        console.error('AFCGlide: afc_vars not localized. Submission disabled.');
        return;
    }

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
                alert(afc_vars.strings.invalid || '🚫 INVALID FILE: Please upload a JPG or PNG.');
                return;
            }

            const reader = new FileReader();
            reader.onload = function (event) {
                const img = new Image();
                img.onload = function () {
                    // World-Class Standard: 1200px width minimum
                    if (this.width < 1200) {
                        alert((afc_vars.strings.too_small || '⚠️ QUALITY REJECTED') + '\nCurrently detected: ' + this.width + 'px');
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

        // Max images allowed (synced from PHP via afc_vars, fallback to 16)
        const maxGallery = afc_vars.max_gallery || 16;

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
     * 3. PDF BROCHURE PREVIEW
     */
    $(document).on('change', '#pdf_file', function (e) {
        const file = e.target.files[0];
        if (file) {
            $('#pdf-filename').text(file.name);
            $('#pdf-status').text('✅');
        }
    });

    /**
     * 4. BRANDING PREVIEWS (Agent Photo & Broker Logo)
     */
    $(document).on('change', '#agent_photo_file', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                $('.agent-photo-preview').html(`<img src="${event.target.result}" style="width:100%; height:100%; object-fit:cover;">`);
            };
            reader.readAsDataURL(file);
        }
    });

    $(document).on('change', '#broker_logo_file', function (e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = (event) => {
                $('.broker-logo-preview').html(`<img src="${event.target.result}" style="max-width:100%; max-height:100%; object-fit:contain;">`);
            };
            reader.readAsDataURL(file);
        }
    });

    /**
     * 5. AJAX BROADCAST (The Emerald Protocol)
     */
    $form.on('submit', function (e) {
        e.preventDefault();

        // UI Lockdown: Prevent double-click ghost listings
        $submitBtn.prop('disabled', true).css('opacity', '0.6').text(afc_vars.strings.loading || '🚀 SYNCING ASSET...');
        $feedback.fadeIn().html('<p style="color: #6366f1;">' + (afc_vars.strings.handshake || 'Initializing...') + '</p>');

        const formData = new FormData(this);

        // Override action to ensure it matches PHP handler (C::AJAX_SUBMIT = 'afc_handle_submission')
        formData.set('action', afc_vars.action || 'afc_handle_submission');
        formData.set('security', afc_vars.nonce);

        $.ajax({
            url: afc_vars.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.success) {
                    $submitBtn.css({ 'background': '#10b981', 'opacity': '1' }).text(afc_vars.strings.success || '✨ ASSET DEPLOYED');
                    $feedback.html('<p style="color: #10b981;">' + (afc_vars.strings.verifying || 'Listing Verified. Redirecting...') + '</p>');

                    setTimeout(() => {
                        window.location.href = response.data.url;
                    }, 1200);
                } else {
                    $feedback.html('<p style="color: #ef4444;">' + (afc_vars.strings.error || '❌ ERROR:') + ' ' + response.data.message + '</p>');
                    $submitBtn.prop('disabled', false).css('opacity', '1').text('PUBLISH GLOBAL LISTING');
                }
            },
            error: function () {
                $feedback.html('<p style="color: #ef4444;">⚠️ Critical Connection Failure. Check file sizes.</p>');
                $submitBtn.prop('disabled', false).css('opacity', '1').text(afc_vars.strings.retry || 'RETRY SUBMISSION');
            }
        });
    });
});