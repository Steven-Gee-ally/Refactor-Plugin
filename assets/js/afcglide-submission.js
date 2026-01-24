/**
 * AFCGlide Submission Logic - v3.7.0 (Master Suite Rehab)
 * Vision: Zero-Error Asset Broadcasting
 */
jQuery(document).ready(function ($) {

    const $form = $('#afcglide-front-submission');
    const $submitBtn = $('#afc-submit-btn');
    const $feedback = $('#afc-feedback');

    // store original button text so we can restore it on error
    const originalSubmitText = $submitBtn.length ? $submitBtn.text() : '';

    // Single source of truth
    const maxGallery = 16;

    // Basic guards: ensure variables/elements we rely on exist
    if (typeof afc_vars === 'undefined') {
        console.warn('afc_vars is not defined — submission script disabled.');
        return;
    }

    if (!$form.length) {
        console.warn('#afcglide-front-submission not found — submission script disabled.');
        return;
    }

    // Accessibility: make feedback region an aria-live status
    if ($feedback.length) {
        $feedback.attr({ 'role': 'status', 'aria-live': 'polite' });
    }

    /**
     * 1. HERO QUALITY GATEKEEPER
     * Single-image validation ONLY
     */
    $(document).on('change', '#hero_file', function (e) {
        const file = e.target.files && e.target.files[0];
        const $previewBox = $('.hero-preview-box');

        if (!file) return;

        // File type check with fallback to filename extension
        const mimeOk = file.type && file.type.match('image.*');
        const extOk = /\.(jpe?g|png|gif|webp)$/i.test(file.name || '');
        if (!mimeOk && !extOk) {
            alert(afc_vars.strings.invalid || '🚫 INVALID FILE: Please upload a JPG or PNG.');
            $(e.target).val('');
            return;
        }

        // Use object URL for faster preview and better performance
        const objectUrl = URL.createObjectURL(file);
        const img = new Image();

        img.onload = function () {
            // Luxury minimum width
            if (this.width < 1200) {
                alert((afc_vars.strings.too_small || '⚠️ QUALITY REJECTED') + '\nDetected width: ' + this.width + 'px');
                $(e.target).val('');
                $previewBox.html('<p style="color:#ef4444;font-size:12px;">Image too small.</p>');
                URL.revokeObjectURL(objectUrl);
                return;
            }

            // Preview
            $previewBox
                .css('background', 'none')
                .html(`<img src="${objectUrl}" alt="Hero image preview" style="width:100%;height:100%;object-fit:cover;border-radius:12px;border:2px solid #10b981;">`);

            // Revoke when safe — after the image is loaded
            URL.revokeObjectURL(objectUrl);
        };

        img.onerror = function () {
            $(e.target).val('');
            $previewBox.html('<p style="color:#ef4444;font-size:12px;">Unable to preview image.</p>');
            URL.revokeObjectURL(objectUrl);
        };

        img.src = objectUrl;
    });

    /**
     * 2. GALLERY IMAGE PREVIEW
     * Enforces 16-photo limit
     */
    $(document).on('change', '#gallery_files', function (e) {
        const files = e.target.files;
        if (!files || files.length === 0) return;

        if (files.length > maxGallery) {
            alert(`🚫 Maximum ${maxGallery} images allowed.\nYou selected ${files.length}.`);
            $(e.target).val('');
            $('#new-gallery-preview').hide();
            return;
        }

        $('#new-gallery-preview').show();
        const $grid = $('#new-gallery-grid');
        $grid.empty();

        for (let i = 0; i < files.length; i++) {
            const file = files[i];
            const objectUrl = URL.createObjectURL(file);
            const $thumb = $(`<div class="new-gallery-thumb"><img src="${objectUrl}" alt="Gallery image ${i + 1}"></div>`);
            // Revoke after the image loads to avoid memory leaks
            $thumb.find('img').on('load error', function () {
                URL.revokeObjectURL(objectUrl);
            });
            $grid.append($thumb);
        }
    });

    /**
     * 3. AJAX BROADCAST
     */
    $form.on('submit', function (e) {
        e.preventDefault();

        $submitBtn
            .prop('disabled', true)
            .css('opacity', '0.6')
            .text(afc_vars.strings.loading || '🚀 SYNCING ASSET...');

        $feedback.fadeIn().html(
            `<p style="color:#6366f1;">
                ${afc_vars.strings.handshake || 'Initializing...'}
             </p>`
        );

        const formData = new FormData(this);
        formData.append('action', 'afcglide_submit_listing');
        formData.append('security', afc_vars.nonce);

        $.ajax({
            url: afc_vars.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success(response) {
                if (response && response.success) {
                    $submitBtn
                        .css({ background: '#10b981', opacity: '1' })
                        .text(afc_vars.strings.success || '✨ ASSET DEPLOYED');

                    $feedback.empty().append($('<p>').css('color', '#10b981').text(afc_vars.strings.verifying || 'Redirecting...'));

                    setTimeout(() => {
                        // Support different response shapes: data.url or data.data.url
                        const url = response.data && (response.data.url || (response.data.data && response.data.data.url));
                        if (url) {
                            window.location.href = url;
                        } else {
                            // If no URL provided, re-enable button to allow retry
                            $submitBtn.prop('disabled', false).css('opacity', '1').text(originalSubmitText || (afc_vars.strings.success || 'Done'));
                        }
                    }, 1200);
                } else {
                    const msg = (response && response.data && response.data.message) ? response.data.message : (afc_vars.strings.error || 'Unknown error');
                    $feedback.empty().append($('<p>').css('color', '#ef4444').text((afc_vars.strings.error || '❌ ERROR:') + ' ' + msg));
                    $submitBtn.prop('disabled', false).css('opacity', '1').text(originalSubmitText || 'PUBLISH GLOBAL LISTING');
                }
            },

            error(jqXHR, textStatus, errorThrown) {
                console.error('AFCGlide AJAX error:', textStatus, errorThrown, jqXHR);
                $feedback.empty().append($('<p>').css('color', '#ef4444').text('⚠️ Connection failure. Try again.'));
                $submitBtn.prop('disabled', false).css('opacity', '1').text(originalSubmitText || (afc_vars.strings.retry || 'RETRY SUBMISSION'));
            }
        });
    });

    // Autosave / Draft support
    (function enableAutosave() {
        const interval = (afc_vars && afc_vars.autosave_interval) ? parseInt(afc_vars.autosave_interval, 10) : 0;
        if (!interval || interval <= 0) return;

        let autosaveTimer = null;

        function doAutosave() {
            // Indicate saving
            if ($feedback.length) $feedback.text(afc_vars.strings.draft_saving || 'Saving draft...').css('color', '#6366f1');

            const formData = new FormData($form[0]);
            formData.append('action', 'afcglide_save_draft');
            formData.append('security', afc_vars.nonce);

            $.ajax({
                url: afc_vars.ajax_url,
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success(resp) {
                    // wp_send_json_success returns { success: true, data: { ... } }
                    if (resp && resp.success && resp.data && resp.data.data && resp.data.data.post_id) {
                        const savedId = resp.data.data.post_id;
                        // Ensure there's a hidden input for post_id so future saves update
                        if ($form.find('input[name="post_id"]').length) {
                            $form.find('input[name="post_id"]').val(savedId);
                        } else {
                            $form.append(`<input type="hidden" name="post_id" value="${savedId}">`);
                        }
                        if ($feedback.length) $feedback.text(afc_vars.strings.draft_saved || 'Draft saved').css('color', '#10b981');
                    } else if (resp && resp.success && resp.data && resp.data.post_id) {
                        // older response shape
                        const savedId = resp.data.post_id;
                        if ($form.find('input[name="post_id"]').length) {
                            $form.find('input[name="post_id"]').val(savedId);
                        } else {
                            $form.append(`<input type="hidden" name="post_id" value="${savedId}">`);
                        }
                        if ($feedback.length) $feedback.text(afc_vars.strings.draft_saved || 'Draft saved').css('color', '#10b981');
                    } else {
                        if ($feedback.length) $feedback.text(afc_vars.strings.error || 'Save failed').css('color', '#ef4444');
                    }
                },
                error(jqXHR, textStatus, errorThrown) {
                    console.error('Autosave error:', textStatus, errorThrown, jqXHR);
                    if ($feedback.length) $feedback.text('Autosave failed').css('color', '#ef4444');
                }
            });
        }

        // Trigger first autosave after a short delay
        autosaveTimer = setInterval(doAutosave, interval);
        // Optionally run one immediately
        setTimeout(doAutosave, 2500);
    })();

});
