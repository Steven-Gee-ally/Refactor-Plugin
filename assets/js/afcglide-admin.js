/**
 * AFCGlide Admin JS
 * Handles Media Uploaders (Hero, Slider, Stack) & Color Pickers
 */
jQuery(document).ready(function($){

    // 1. Initialize Color Pickers
    if( $('.afcglide-color-picker').length ) {
        $('.afcglide-color-picker').wpColorPicker();
    }

    // ======================================================
    // 2. Single Image Uploader (Hero & Stack)
    // ======================================================
    $('body').on('click', '.afcglide-select-hero, .afcglide-select-stack', function(e){
        e.preventDefault();
        var button = $(this);
        var wrapper = button.closest('.afcglide-media-uploader, div'); 
        var input   = wrapper.find('input[type="hidden"]');
        var preview = wrapper.find('.afcglide-media-preview');
        var removeBtn = wrapper.find('.afcglide-remove-hero, .afcglide-remove-stack');

        var frame = wp.media({
            title: 'Select Image',
            multiple: false,
            library: { type: 'image' },
            button: { text: 'Use Image' }
        });

        frame.on('select', function(){
            var attachment = frame.state().get('selection').first().toJSON();
            input.val(attachment.id);
            preview.html('<img src="'+attachment.url+'" style="max-width:220px;height:auto;display:block;">');
            removeBtn.show();
        });

        frame.open();
    });

    // Remove Single Image
    $('body').on('click', '.afcglide-remove-hero, .afcglide-remove-stack', function(e){
        e.preventDefault();
        var wrapper = $(this).closest('.afcglide-media-uploader, div');
        wrapper.find('input[type="hidden"]').val('');
        wrapper.find('.afcglide-media-preview').html('<div style="padding:20px;color:#ccc;font-weight:bold;">No Image</div>');
    });

    // ======================================================
    // 3. Multi-Image Slider (The Pro Feature)
    // ======================================================
    
    // Open Media Library for Slider
    $('.afcglide-add-slider').on('click', function(e){
        e.preventDefault();
        
        var frame = wp.media({
            title: 'Select Slider Images',
            multiple: true, // Allow multiple selection
            library: { type: 'image' },
            button: { text: 'Add to Slider' }
        });

        frame.on('select', function(){
            var selection = frame.state().get('selection');
            var currentData = getSliderData();

            selection.map(function(attachment){
                attachment = attachment.toJSON();
                // Prevent duplicates
                if( currentData.indexOf(attachment.id) === -1 ) {
                    currentData.push(attachment.id);
                    // Append preview thumbnail
                    $('#afcglide-slider-preview').append(
                        '<div class="afcglide-slider-thumb" data-id="' + attachment.id + '" style="position:relative;">' +
                            '<img src="' + attachment.sizes.thumbnail.url + '" style="width:100px;height:70px;object-fit:cover;border-radius:6px;margin-bottom:10px;">' +
                            '<button type="button" class="button afcglide-remove-slider" data-id="' + attachment.id + '" style="position:absolute; top:-5px; right:-5px; border-radius:50%; padding:0 5px; background:red; color:white; border:none;">&times;</button>' +
                        '</div>'
                    );
                }
            });

            updateSliderInput(currentData);
        });

        frame.open();
    });

    // Remove Individual Slider Image
    $('body').on('click', '.afcglide-remove-slider', function(e){
        e.preventDefault();
        var idToRemove = $(this).data('id');
        var currentData = getSliderData();
        
        // Remove from array
        var index = currentData.indexOf(idToRemove);
        if (index > -1) {
            currentData.splice(index, 1);
        }

        // Remove visual element
        $(this).closest('.afcglide-slider-thumb').remove();

        // Update hidden input
        updateSliderInput(currentData);
    });

    // Helper: Get current IDs from hidden input
    function getSliderData() {
        var val = $('#_slider_images_json').val();
        try {
            return val ? JSON.parse(val) : [];
        } catch(e) {
            return [];
        }
    }

    // Helper: Update hidden input with new JSON
    function updateSliderInput(dataArray) {
        $('#_slider_images_json').val( JSON.stringify(dataArray) );
    }

});