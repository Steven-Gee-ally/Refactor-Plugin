jQuery(document).ready(function ($) {

    // ==========================================
    // UNSAVED CHANGES WARNING - Prevents Data Loss
    // ==========================================
    let formChanged = false;

    // Track any changes to form fields
    $('input, textarea, select').on('change input', function () {
        formChanged = true;
    });

    // Warn before leaving if there are unsaved changes
    $(window).on('beforeunload', function (e) {
        if (formChanged) {
            const message = 'You have unsaved changes. Are you sure you want to leave?';
            e.returnValue = message; // Standard
            return message; // For older browsers
        }
    });

    // Don't warn when actually submitting the form
    $('form#post').on('submit', function () {
        formChanged = false;
    });

    // Don't warn when clicking publish/update button
    $('#publish, #save-post').on('click', function () {
        formChanged = false;
    });

    // ==========================================
    // 1. MASTER IMAGE UPLOADER (Hero & Agent Photo)
    // ==========================================
    $(document).on('click', '.afcglide-upload-image-btn', function (e) {
        e.preventDefault();
        var button = $(this);
        var targetId = button.data('target');
        var previewDiv = button.siblings('.afcglide-preview-box');

        var frame = wp.media({
            title: 'Select or Upload Media',
            button: { text: 'Use this photo' },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();

            // ✅ VALIDATE IMAGE DIMENSIONS (Minimum 1200px width)
            if (attachment.width < 1200) {
                alert('⚠️ IMAGE TOO SMALL!\n\n' +
                    'Minimum width required: 1200px\n' +
                    'Your image width: ' + attachment.width + 'px\n\n' +
                    'Please select a higher resolution image for best quality.');
                return; // Stop upload
            }

            $('#' + targetId).val(attachment.id);
            previewDiv.html('<img src="' + attachment.url + '" style="width:100%; height:100%; object-fit:cover;" alt="Preview">');

            // Mark form as changed
            formChanged = true;
        });

        frame.open();
    });

    // ==========================================
    // 2. THE 16-PHOTO GALLERY SLIDER
    // ==========================================
    $(document).on('click', '.afcglide-add-slider-images-btn', function (e) {
        e.preventDefault();
        var container = $('#afc-slider-container');

        var frame = wp.media({
            title: 'Select Gallery Photos (Max 16 Total)',
            button: { text: 'Add to Gallery' },
            multiple: true
        });

        frame.on('select', function () {
            var selection = frame.state().get('selection');
            var skippedImages = [];

            selection.map(function (attachment) {
                attachment = attachment.toJSON();
                var currentCount = container.find('.afcglide-image-item').length;

                if (currentCount < 16) {
                    // ✅ VALIDATE IMAGE DIMENSIONS (Minimum 1200px width)
                    if (attachment.width < 1200) {
                        skippedImages.push(attachment.filename + ' (' + attachment.width + 'px)');
                        return; // Skip this image
                    }

                    container.append(`
                        <div class="afcglide-image-item" style="position:relative; display:inline-block; margin:5px;">
                            <img src="${attachment.url}" style="width:80px; height:80px; object-fit:cover; border-radius:8px; border:2px solid #e2e8f0;">
                            <input type="hidden" name="_property_slider_ids[]" value="${attachment.id}">
                            <button type="button" class="afc-remove-slider-img" style="position:absolute; top:-5px; right:-5px; background:#ef4444; color:white; border-radius:50%; border:none; cursor:pointer; width:20px; height:20px; line-height:18px; font-weight:bold;">&times;</button>
                        </div>
                    `);

                    // Mark form as changed
                    formChanged = true;
                }
            });

            // Show warning if any images were skipped
            if (skippedImages.length > 0) {
                alert('⚠️ SOME IMAGES SKIPPED\n\n' +
                    'The following images are too small (min: 1200px width):\n\n' +
                    skippedImages.join('\n') + '\n\n' +
                    'Only high-resolution images were added to the gallery.');
            }

            updateSliderCount();
        });

        frame.open();
    });

    // ==========================================
    // 3. THE 3-PHOTO STACK
    // ==========================================
    $(document).on('click', '.afcglide-add-stack-image-btn', function (e) {
        e.preventDefault();
        var container = $('#stack-images-container');

        var frame = wp.media({
            title: 'Select 3 Photos for Stack',
            button: { text: 'Set Stack Photos' },
            multiple: true
        });

        frame.on('select', function () {
            var selection = frame.state().get('selection');
            var skippedImages = [];
            container.empty();

            selection.each(function (attachment, i) {
                if (i < 3) {
                    attachment = attachment.toJSON();

                    // ✅ VALIDATE IMAGE DIMENSIONS (Minimum 1200px width)
                    if (attachment.width < 1200) {
                        skippedImages.push(attachment.filename + ' (' + attachment.width + 'px)');
                        return; // Skip this image
                    }

                    container.append(`
                        <div class="afcglide-stack-item" style="position:relative; width:100%; height:100%;">
                            <img src="${attachment.url}" style="width:100%; height:100%; object-fit:cover; border-radius:4px;">
                            <input type="hidden" name="_property_stack_ids[]" value="${attachment.id}">
                        </div>
                    `);

                    // Mark form as changed
                    formChanged = true;
                }
            });

            // Show warning if any images were skipped
            if (skippedImages.length > 0) {
                alert('⚠️ SOME IMAGES SKIPPED\n\n' +
                    'The following images are too small (min: 1200px width):\n\n' +
                    skippedImages.join('\n') + '\n\n' +
                    'Please select higher resolution images.');
            }
        });
        frame.open();
    });

    // ==========================================
    // 4. GLOBAL REMOVE & COUNT
    // ==========================================
    $(document).on('click', '.afc-remove-slider-img', function () {
        $(this).closest('.afcglide-image-item').remove();
        updateSliderCount();

        // Mark form as changed
        formChanged = true;
    });

    function updateSliderCount() {
        var count = $('#afc-slider-container .afcglide-image-item').length;
        var max = 16;
        if (typeof afcglideConfig !== 'undefined' && afcglideConfig.maxSliderImages) {
            max = afcglideConfig.maxSliderImages;
        }
        $('#afc-slider-count').text(count + ' / ' + max + ' Photos');
        $('.afcglide-add-slider-images-btn').prop('disabled', (count >= max));
    }

    // ==========================================
    // 5. THE GLOBAL CONFIG LOGO UPLOADER
    // ==========================================
    $(document).on('click', '.afcglide-upload-logo-btn', function (e) {
        e.preventDefault();
        const $button = $(this);
        const $wrapper = $button.closest('.afcglide-logo-upload');
        const $targetField = $wrapper.find('#afcglide_logo_id');
        const $preview = $wrapper.find('.afcglide-logo-preview');

        const frame = wp.media({
            title: 'Select Company Logo',
            button: { text: 'Set as Logo' },
            multiple: false
        }).on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            $targetField.val(attachment.id).trigger('change');

            if ($preview.length) {
                $preview.hide().html(`<img src="${attachment.url}" style="max-width: 300px; border-radius: 8px;">`).fadeIn(600);
            }

            // Mark form as changed
            formChanged = true;
        }).open();
    });

    // ==========================================
    // 6. AGENT AUTO-FILL
    // ==========================================
    $(document).on('change', '#afc_agent_selector', function () {
        var selected = $(this).find(':selected');
        var name = selected.data('name');
        var phone = selected.data('phone');
        var photoId = selected.data('photo-id');
        var photoUrl = selected.data('photo-url');

        if (!name) return; // Ignore if placeholder

        // Fill Fields
        $('#afc_agent_name').val(name);
        $('#afc_agent_phone').val(phone);
        $('#agent_photo_id').val(photoId);

        // Update Photo Preview
        if (photoUrl) {
            $('.afcglide-agent-photo-wrapper .afcglide-preview-box').html(
                '<img src="' + photoUrl + '" style="width:100%; height:100%; object-fit:cover;">'
            );
        } else {
            $('.afcglide-agent-photo-wrapper .afcglide-preview-box').html('<span>No Photo</span>');
        }

        // Mark as changed
        formChanged = true;
    });

    // ==========================================
    // 7. INITIALIZE ON PAGE LOAD
    // ==========================================

    // Update slider count on page load
    if ($('#afc-slider-container').length) {
        updateSliderCount();
    }

}); // The Final Seal - Now Agent-Proof! 🛡️