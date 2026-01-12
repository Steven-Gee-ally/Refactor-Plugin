jQuery(document).ready(function ($) {

    // ==========================================
    // 0. LUXURY LAYOUT ENFORCER (FORCE 1-7 ORDER)
    // ==========================================
    const enforceLayoutOrder = () => {
        const container = $('#normal-sortables');
        if (!container.length) return;

        const order = [
            'afc_agent',       // 1. Agent Branding
            'afc_media_hub',   // 2. Visual Command Center
            'afc_slider',      // 3. Gallery Slider
            'afc_details',     // 4. Property Specifications
            'afc_location',    // 5. Location & GPS
            'afc_amenities',   // 6. Property Features
            'afc_publish_box'  // 7. Publish
        ];

        order.forEach(id => {
            const box = $('#' + id);
            if (box.length) {
                container.append(box); // Moves element to end of container in specific order
            }
        });
    };

    // Run immediately
    enforceLayoutOrder();

    // ==========================================
    // 1. COMMAND CENTER - INSTANT SAVE TOGGLES
    // ==========================================
    $('.afc-toggle').on('click', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const type = $btn.data('type');
        const currentStatus = $btn.data('status');
        const newStatus = (currentStatus === 'yes') ? 'no' : 'yes';

        // Visual feedback
        $btn.text('Saving...').prop('disabled', true).css('opacity', '0.5');

        $.ajax({
            url: afc_vars.ajax_url,
            type: 'POST',
            data: {
                action: 'afc_toggle_lockdown_ajax', // Ensure this matches your Ajax Handler!
                nonce: afc_vars.lockdown_nonce,
                type: type,
                status: newStatus
            },
            success: function (response) {
                $btn.prop('disabled', false).css('opacity', '1');
                if (response.success) {
                    $btn.data('status', newStatus);
                    $btn.text(newStatus === 'yes' ? 'ON' : 'OFF');
                    // Luxury touch: Change color based on state
                    if (newStatus === 'yes') {
                        $btn.css('background', 'var(--afc-primary)');
                    } else {
                        $btn.css('background', 'var(--afc-dark)');
                    }
                } else {
                    alert('Error: ' + response.data);
                }
            }
        });
    });

    // ==========================================
    // UNSAVED CHANGES WARNING - Prevents Data Loss
    // ==========================================
    let formChanged = false;

    $('input, textarea, select').on('change input', function () {
        formChanged = true;
    });

    $(window).on('beforeunload', function (e) {
        if (formChanged) {
            const message = 'You have unsaved changes. Are you sure you want to leave?';
            e.returnValue = message;
            return message;
        }
    });

    $('form#post').on('submit', function () {
        formChanged = false;
    });

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

            if (attachment.width < 1200) {
                alert('⚠️ IMAGE TOO SMALL!\nMin width: 1200px. Your image: ' + attachment.width + 'px');
                return;
            }

            $('#' + targetId).val(attachment.id).trigger('change');
            previewDiv.html('<img src="' + attachment.url + '" class="afcglide-preview-img">');
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
                    if (attachment.width < 1200) {
                        skippedImages.push(attachment.filename);
                        return;
                    }

                    container.append(`
                        <div class="afcglide-image-item">
                            <img src="${attachment.url}">
                            <input type="hidden" name="_property_slider_ids[]" value="${attachment.id}">
                            <button type="button" class="afc-remove-slider-img">&times;</button>
                        </div>
                    `);
                    formChanged = true;
                }
            });

            if (skippedImages.length > 0) {
                alert('⚠️ SOME IMAGES SKIPPED (Min 1200px required):\n' + skippedImages.join('\n'));
            }
            updateSliderCount();
        });
        frame.open();
    });

    // ENABLE DRAG & DROP SORTING
    if ($.fn.sortable) {
        $("#afc-slider-container").sortable({
            placeholder: "ui-state-highlight",
            update: function () { formChanged = true; }
        });
    }

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
            var skippedCount = 0;
            container.empty();

            selection.each(function (attachment, i) {
                if (i < 3) {
                    attachment = attachment.toJSON();
                    if (attachment.width < 1200) {
                        skippedCount++;
                        return;
                    }
                    container.append(`
                        <div class="afcglide-stack-item">
                            <img src="${attachment.url}">
                            <input type="hidden" name="_property_stack_ids[]" value="${attachment.id}">
                        </div>
                    `);
                    formChanged = true;
                }
            });

            if (skippedCount > 0) alert('⚠️ ' + skippedCount + ' images were too small and skipped.');
        });
        frame.open();
    });

    // ==========================================
    // 4. GLOBAL REMOVE & COUNT
    // ==========================================
    $(document).on('click', '.afc-remove-slider-img', function () {
        $(this).closest('.afcglide-image-item').remove();
        updateSliderCount();
        formChanged = true;
    });

    function updateSliderCount() {
        var count = $('#afc-slider-container .afcglide-image-item').length;
        var max = (typeof afcglideConfig !== 'undefined') ? afcglideConfig.maxSliderImages : 16;
        $('#afc-slider-count').text(count + ' / ' + max + ' Photos');
        $('.afcglide-add-slider-images-btn').prop('disabled', (count >= max));
    }

    // ==========================================
    // 5. AGENT AUTO-FILL
    // ==========================================
    $(document).on('change', '#afc_agent_selector', function () {
        var selected = $(this).find(':selected');
        if (!selected.val()) return;

        $('#afc_agent_name').val(selected.data('name')).trigger('change');
        $('#afc_agent_phone').val(selected.data('phone')).trigger('change');
        $('#agent_photo_id').val(selected.data('photo-id')).trigger('change');

        var photoUrl = selected.data('photo-url');
        if (photoUrl) {
            $('.afcglide-agent-photo-wrapper .afcglide-preview-box').html('<img src="' + photoUrl + '" class="afcglide-agent-photo">');
        }
        formChanged = true;
    });

    if ($('#afc-slider-container').length) updateSliderCount();

});