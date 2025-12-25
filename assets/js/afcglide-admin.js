/**
 * AFCGlide Admin JS (v3.7.1 Luxury Master)
 * Synced with Master Metabox Refactor
 */
jQuery(document).ready(function($){

    // 1. Universal Media Uploader (Hero, Agent, Logo, Stack)
    $(document).on('click', '.afc-upload-btn', function(e){
        e.preventDefault();
        const button = $(this);
        const target = button.data('target'); // 'hero', 'stack', 'agent', or 'logo'
        
        const frame = wp.media({
            title: 'Select Luxury Media',
            multiple: (target === 'stack'), // Allows multiple only for the photo stack
            library: { type: 'image' }
        }).on('select', function(){
            
            if (target === 'stack') {
                // Logic for the Multi-Image Photo Stack
                const selection = frame.state().get('selection');
                let ids = [];
                selection.map(function(attachment) {
                    ids.push(attachment.toJSON().id);
                });
                $('#stack-images-data').val(JSON.stringify(ids));
                alert('Stack updated with ' + ids.length + ' images.');
            } else {
                // Logic for Single Images (Hero, Agent, Logo)
                const attachment = frame.state().get('selection').first().toJSON();
                
                // This maps the 'target' to the specific IDs in your PHP
                if (target === 'hero') {
                    $('#hero-image-id').val(attachment.id);
                    $('#hero-preview').attr('src', attachment.url).show();
                } else if (target === 'agent') {
                    $('#agent-photo-id').val(attachment.id);
                    $('#agent-preview').attr('src', attachment.url).show();
                } else if (target === 'logo') {
                    $('#logo-image-id').val(attachment.id);
                    $('#logo-preview').attr('src', attachment.url).show();
                }
            }
        }).open();
    });

    // 2. Initialize Color Pickers (If you use them in Settings)
    if( $.isFunction($.fn.wpColorPicker) ) {
        $('.afcglide-color-picker').wpColorPicker();
    }
});