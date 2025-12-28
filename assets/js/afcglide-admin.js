jQuery(document).ready(function($) {
    
    // DEBUG: This will tell us if the script is even loading
    console.log('AFCGlide Admin JS Loaded');

    // 1. Single Image Upload (Hero, Agent Photo, Agency Logo)
    $(document).on('click', '.afcglide-upload-image-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var targetId = button.attr('data-target'); // Get the ID of the hidden input
        var inputField = $('#' + targetId);
        
        // Find the preview container - looking for a sibling with the class
        var previewContainer = button.siblings('.afcglide-preview-box');

        var frame = wp.media({
            title: 'Select Image',
            button: { text: 'Use This Image' },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            inputField.val(attachment.id);
            
            // Show the preview
            previewContainer.html('<img src="' + attachment.url + '" style="max-width: 200px; height: auto; border-radius: 8px; margin-top:10px; display:block;">');
            
            button.text('Change Image');
            // Show the remove button if it exists
            button.siblings('.afcglide-remove-image-btn').show();
        });

        frame.open();
    });

    // 2. Remove Single Image
    $(document).on('click', '.afcglide-remove-image-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var targetId = button.attr('data-target');
        $('#' + targetId).val('');
        button.siblings('.afcglide-preview-box').empty();
        button.siblings('.afcglide-upload-image-btn').text('Select Image');
        button.hide();
    });

    // 3. Stack Images (Max 3)
    $(document).on('click', '.afcglide-add-stack-image-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var container = $('#stack-images-container');
        
        var frame = wp.media({
            title: 'Select Stack Image',
            button: { text: 'Add to Stack' },
            multiple: false
        });

        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            var html = `
                <div class="stack-image-item" style="margin-bottom: 10px; display: flex; align-items: center; gap: 10px; background:#f0f0f0; padding:5px; border-radius:5px;">
                    <img src="${attachment.url}" style="width: 80px; height: 60px; object-fit: cover; border-radius: 4px;">
                    <input type="hidden" name="_property_stack_ids[]" value="${attachment.id}">
                    <button type="button" class="button remove-stack-image">Remove</button>
                </div>`;
            container.append(html);
            updateCounts(button, container, 3, 'Stack');
        });
        frame.open();
    });

    // 4. Slider Images (Max 12)
    $(document).on('click', '.afcglide-add-slider-image-btn', function(e) {
        e.preventDefault();
        var button = $(this);
        var container = $('#slider-images-container');

        var frame = wp.media({
            title: 'Select Gallery Images',
            button: { text: 'Add to Gallery' },
            multiple: true
        });

        frame.on('select', function() {
            var attachments = frame.state().get('selection').toJSON();
            attachments.forEach(function(img) {
                if (container.find('.slider-image-item').length < 12) {
                    var html = `
                        <div class="slider-image-item" style="display:inline-block; margin-right:10px; position: relative;">
                            <img src="${img.url}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 4px;">
                            <input type="hidden" name="_property_slider_ids[]" value="${img.id}">
                            <button type="button" class="remove-slider-image" style="position: absolute; top: -5px; right: -5px; background: #ff0000; color: #fff; border: none; border-radius: 50%; width: 20px; height: 20px; cursor: pointer;">×</button>
                        </div>`;
                    container.append(html);
                }
            });
            updateCounts(button, container, 12, 'Gallery');
        });
        frame.open();
    });

    function updateCounts(btn, container, max, label) {
        var count = container.children().length;
        btn.text('Add ' + label + ' Image (' + count + '/' + max + ')');
        btn.prop('disabled', count >= max);
    }

    $(document).on('click', '.remove-stack-image, .remove-slider-image', function() {
        var container = $(this).closest('.afcglide-image-container'); // Ensure your containers have this class
        var btn = container.siblings('button');
        var isStack = container.attr('id').includes('stack');
        var max = isStack ? 3 : 12;
        var label = isStack ? 'Stack' : 'Gallery';
        
        $(this).parent().remove();
        updateCounts(btn, container, max, label);
    });
});