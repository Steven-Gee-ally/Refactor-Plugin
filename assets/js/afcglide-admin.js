jQuery(document).ready(function ($) {

    // --- 1. GENERIC IMAGE UPLOADER (For Hero & Agent Photo) ---
    $('.afcglide-upload-image-btn').on('click', function (e) {
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
            $('#' + targetId).val(attachment.id);

            // Determine specific class for styling based on target ID
            var imgClass = '';
            if (targetId === 'agent_photo_id') {
                imgClass = 'afcglide-agent-photo';
            } else if (targetId === 'hero_image_id') {
                imgClass = 'afcglide-hero-preview';
            }

            // Using pure CSS classes instead of inline styles where possible
            previewDiv.html('<img src="' + attachment.url + '" class="' + imgClass + '" alt="Preview">');
        });

        frame.open();
    });

    // --- 2. THE 16-PHOTO GALLERY SLIDER (Bulk Upload) ---
    $('.afcglide-add-slider-images-btn').on('click', function (e) {
        e.preventDefault();

        var frame = wp.media({
            title: 'Select Gallery Photos (Max 16 Total)',
            button: { text: 'Add to Gallery' },
            multiple: true
        });

        frame.on('select', function () {
            var selection = frame.state().get('selection');
            var container = $('#afc-slider-container');

            selection.map(function (attachment) {
                attachment = attachment.toJSON();
                // Check current count to enforce the 16-photo limit
                // UPDATED SELECTOR: .afcglide-image-item (Matches CSS/PHP)
                var currentCount = container.find('.afcglide-image-item').length;

                if (currentCount < 16) {
                    // UPDATED STRUCTURE: Matches existing PHP output exactly
                    container.append(`
                        <div class="afcglide-image-item">
                            <img src="${attachment.url}" alt="Gallery Image">
                            <input type="hidden" name="_property_slider_ids[]" value="${attachment.id}">
                            <button type="button" class="afc-remove-slider-img" aria-label="Remove image">&times;</button>
                        </div>
                    `);
                }
            });
            updateSliderCount();
        });

        frame.open();
    });

    // --- 3. THE 3-PHOTO STACK (Logic) ---
    $('.afcglide-add-stack-image-btn').on('click', function (e) {
        e.preventDefault();
        var frame = wp.media({
            title: 'Select 3 Photos for Stack',
            button: { text: 'Set Stack Photos' },
            multiple: true
        });

        frame.on('select', function () {
            var selection = frame.state().get('selection');
            var container = $('#stack-images-container');
            container.empty(); // Clear existing to maintain exactly 3

            var i = 0;
            selection.map(function (attachment) {
                if (i < 3) {
                    attachment = attachment.toJSON();
                    // UPDATED STRUCTURE: Matches existing PHP output exactly
                    container.append(`
                        <div class="afcglide-stack-item">
                            <img src="${attachment.url}" alt="Stack Image">
                            <input type="hidden" name="_property_stack_ids[]" value="${attachment.id}">
                            <button type="button" class="afc-remove-stack-img" aria-label="Remove image">&times;</button>
                        </div>
                    `);
                }
                i++;
            });
        });
        frame.open();
    });

    // --- 4. GENERIC REMOVE FUNCTION ---
    // Handles both Slider (.afc-remove-slider-img) and Stack (.afc-remove-stack-img)
    $(document).on('click', '.afc-remove-slider-img, .afc-remove-stack-img', function () {
        $(this).parent().remove();
        updateSliderCount();
    });

    // --- 5. IMAGE REMOVE BTN (Single Image) ---
    $('.afcglide-remove-image-btn').on('click', function (e) {
        e.preventDefault();
        var button = $(this);
        var targetId = button.data('target');

        $('#' + targetId).val('');
        button.siblings('.afcglide-preview-box').html('');
        button.remove(); // Remove the "Remove" button itself after clicking
    });

    function updateSliderCount() {
        // UPDATED SELECTOR
        var count = $('#afc-slider-container .afcglide-image-item').length;
        var max = 16; // Default

        // Try to get max from localized script if available
        if (typeof afcglideConfig !== 'undefined' && afcglideConfig.maxSliderImages) {
            max = afcglideConfig.maxSliderImages;
        }

        $('#afc-slider-count').text(count + ' / ' + max + ' Photos');

        // Disable button if full
        if (count >= max) {
            $('.afcglide-add-slider-images-btn').prop('disabled', true);
        } else {
            $('.afcglide-add-slider-images-btn').prop('disabled', false);
        }
    }
});
