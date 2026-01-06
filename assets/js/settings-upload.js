/**
 * AFCGlide Settings Upload Handler
 * Handles Agency Logo upload for the Global Config page.
 */
jQuery(document).ready(function ($) {

    // 1. UPLOAD HANDLER
    $(document).on('click', '.afcglide-upload-logo-btn', function (e) {
        e.preventDefault();

        const $button = $(this);
        const $wrapper = $button.closest('.afcglide-logo-upload');
        const $targetField = $wrapper.find('#afcglide_logo_id');
        const $preview = $wrapper.find('.afcglide-logo-preview');

        const frame = wp.media({
            title: 'Select Company Logo',
            button: { text: 'Set as Logo' },
            multiple: false,
            library: { type: 'image' }
        }).on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();

            // ✅ QUALITY CHECK: Logos should be at least 400px wide for high-end displays
            if (attachment.width < 400) {
                alert('⚠️ LOGO QUALITY WARNING\n\nYour logo is only ' + attachment.width + 'px wide. For a high-end look, we recommend at least 400px.');
            }

            $targetField.val(attachment.id).trigger('change');

            if ($preview.length) {
                // Moved the inline style to a class for our admin.css to handle
                $preview.hide().html(`
                    <img src="${attachment.url}" class="afcglide-settings-logo-img">
                    <button type="button" class="afcglide-remove-logo">Remove Logo</button>
                `).fadeIn(600);
            }
        }).open();
    });

    // 2. REMOVAL HANDLER (The "Agent-Proof" Safety Valve)
    $(document).on('click', '.afcglide-remove-logo', function (e) {
        e.preventDefault();
        const $wrapper = $(this).closest('.afcglide-logo-upload');
        $wrapper.find('#afcglide_logo_id').val('').trigger('change');
        $wrapper.find('.afcglide-logo-preview').fadeOut(300, function () {
            $(this).html('<p class="description">No logo selected.</p>').show();
        });
    });

});