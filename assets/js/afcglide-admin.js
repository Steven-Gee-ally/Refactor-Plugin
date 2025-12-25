/**
 * AFCGlide Admin JS (v3.0 Luxury Refactor)
 * Handles: Color Pickers, Image Roadmap, & Gallery Sorting
 */
jQuery(document).ready(function($){

    // 1. Initialize Color Pickers (Luxury Branding)
    if( $.isFunction($.fn.wpColorPicker) ) {
        $('.afcglide-color-picker').wpColorPicker();
    }

    // 2. Universal Media Uploader (Hero, Stack, Agent Photo, Agency Logo)
    // Updated selector to catch ALL single upload buttons
    $(document).on('click', '.afcglide-select-hero, .afcglide-select-stack, .afcglide-select-branding', function(e){
        e.preventDefault();
        const button = $(this);
        const wrapper = button.closest('.afcglide-media-uploader, .branding-upload-grid'); 
        
        const frame = wp.media({
            title: 'Select Image',
            multiple: false,
            library: { type: 'image' }
        }).on('select', function(){
            const attachment = frame.state().get('selection').first().toJSON();
            // Find the hidden input and the preview div in this specific zone
            wrapper.find('input[type="hidden"]').val(attachment.id);
            wrapper.find('.afcglide-media-preview').html(`<img src="${attachment.url}" class="admin-preview-img">`);
        }).open();
    });

    // 3. Multi-Image Gallery (Slider Roadmap)
    $(document).on('click', '.afcglide-add-slider', function(e){ // Synced class name
        e.preventDefault();
        const frame = wp.media({
            title: 'Select Gallery Images',
            multiple: true, 
            library: { type: 'image' }
        }).on('select', function(){
            const selection = frame.state().get('selection');
            let currentData = getSliderData();

            selection.map(function(attachment){
                attachment = attachment.toJSON();
                if( currentData.indexOf(attachment.id) === -1 ) {
                    currentData.push(attachment.id);
                    // Matches the Refactored Admin CSS Grid
                    $('#afcglide-slider-preview').append(`
                        <div class="afcglide-slider-thumb" data-id="${attachment.id}">
                            <img src="${attachment.url}">
                            <button type="button" class="afcglide-remove-slider" data-id="${attachment.id}">&times;</button>
                        </div>
                    `);
                }
            });
            updateSliderInput(currentData);
        }).open();
    });

    // 4. Drag-to-Sort (Luxury Organization)
    if ($.isFunction($.fn.sortable)) {
        $('#afcglide-slider-preview').sortable({
            placeholder: "ui-state-highlight", // Visual aid during drag
            update: function() {
                let sortedIDs = [];
                $('.afcglide-slider-thumb').each(function() {
                    sortedIDs.push($(this).data('id'));
                });
                updateSliderInput(sortedIDs);
            }
        });
    }

    // Helper: JSON Data Management
    function getSliderData() {
        const val = $('#afcglide_slider_json').val();
        try { return val ? JSON.parse(val) : []; } catch(e) { return []; }
    }

    function updateSliderInput(dataArray) {
        $('#afcglide_slider_json').val(JSON.stringify(dataArray));
    }

    // 5. Remove Logic (Single & Gallery)
    // Handle Gallery Removal
    $(document).on('click', '.afcglide-remove-slider', function(e){
        $(this).closest('.afcglide-slider-thumb').remove();
        let ids = [];
        $('.afcglide-slider-thumb').each(function() { ids.push($(this).data('id')); });
        updateSliderInput(ids);
    });

    // Handle Single Image Clear
    $(document).on('click', '.afcglide-remove-media', function(e){
        e.preventDefault();
        const wrapper = $(this).closest('.afcglide-media-uploader');
        wrapper.find('input[type="hidden"]').val('');
        wrapper.find('.afcglide-media-preview').empty();
    });
});