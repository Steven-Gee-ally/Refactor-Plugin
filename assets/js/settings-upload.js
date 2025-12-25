/**
 * AFCGlide Settings Upload Handler (v3.0 Luxury Refactor)
 * Universal logic for agency logo, placeholders, and branding.
 */
jQuery(document).ready(function($) {
    
    // 1. Branding & Settings Upload Handler
    $(document).on('click', '.afcglide-upload-button', function(e) {
        e.preventDefault();

        const $button = $(this);
        const $wrapper = $button.closest('.afcglide-settings-upload-wrapper');
        const $targetField = $wrapper.find('.afcglide-settings-input');
        const $preview = $wrapper.find('.afcglide-settings-preview');

        const frame = wp.media({
            title: 'Select or Upload Branding Media',
            button: { text: 'Apply to Settings' }, // Luxury prompt
            multiple: false,
            library: { type: 'image' }
        }).on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            
            // Sync: We store the URL for settings generally, but IDs are better for listings.
            $targetField.val(attachment.url).trigger('change');

            // Update Preview (Syncs with admin.css .afcglide-media-preview styles)
            if ($preview.length) {
                $preview.hide().html(`
                    <div class="afcglide-media-preview">
                        <img src="${attachment.url}" class="admin-preview-img">
                    </div>
                `).fadeIn(600);
            }
        }).open();
    });

    // 2. Global Clear Logic
    $(document).on('click', '.afcglide-clear-button', function(e) {
        e.preventDefault();
        const $wrapper = $(this).closest('.afcglide-settings-upload-wrapper');
        
        $wrapper.find('.afcglide-settings-input').val('');
        
        // Return to the "No Image Selected" state from admin.css
        $wrapper.find('.afcglide-settings-preview').fadeOut(300, function() {
            $(this).empty().show(); 
        });
    });
});