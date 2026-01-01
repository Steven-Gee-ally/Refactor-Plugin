/**
 * AFCGlide Settings Upload Handler
 * Handles Agency Logo upload for the Global Config page.
 */
jQuery(document).ready(function ($) {

    // Branding & Settings Upload Handler
    // UPDATED SELECTOR: Targeting .afcglide-upload-logo-btn (matches PHP)
    $(document).on('click', '.afcglide-upload-logo-btn', function (e) {
        e.preventDefault();

        const $button = $(this);
        // UPDATED SELECTOR: Targeting .afcglide-logo-upload (matches PHP)
        const $wrapper = $button.closest('.afcglide-logo-upload');
        // UPDATED SELECTOR: Targeting hidden input by ID (matches PHP)
        const $targetField = $wrapper.find('#afcglide_logo_id');
        // UPDATED SELECTOR: Targeting .afcglide-logo-preview (matches PHP)
        const $preview = $wrapper.find('.afcglide-logo-preview');

        const frame = wp.media({
            title: 'Select Company Logo',
            button: { text: 'Set as Logo' },
            multiple: false,
            library: { type: 'image' }
        }).on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();

            // Set the attachment ID into the hidden field
            $targetField.val(attachment.id).trigger('change');

            // Update Preview
            if ($preview.length) {
                // Using style="max-width: 300px;" to match the PHP's initial render style
                $preview.hide().html(`
                    <img src="${attachment.url}" style="max-width: 300px;">
                `).fadeIn(600);
            }
        }).open();
    });

});