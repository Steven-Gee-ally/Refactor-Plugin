jQuery(document).ready(function ($) {

    // 1. General Media Uploader
    function afc_media_upload(button_class, input_id, preview_id, type) {
        $('body').on('click', button_class, function (e) {
            e.preventDefault();
            var button = $(this);
            var custom_uploader = wp.media({
                title: 'Select Asset Media',
                library: { type: type === 'pdf' ? 'application/pdf' : 'image' },
                button: { text: 'Use this Media' },
                multiple: type === 'gallery' ? true : false
            }).on('select', function () {
                var attachment = custom_uploader.state().get('selection').first().toJSON();

                if (type === 'hero') {
                    $('#' + input_id).val(attachment.id);
                    $('#' + preview_id).html('<div class="afc-preview-item" data-id="' + attachment.id + '"><img src="' + attachment.url + '"><span class="afc-remove-img">×</span></div>');
                }
                else if (type === 'pdf') {
                    $('#' + input_id).val(attachment.id);
                    $('#pdf-filename').text(attachment.filename);
                    $('#pdf-status').text('File Attached (ID: ' + attachment.id + ')');
                }
            });

            custom_uploader.open();
        });
    }

    // Init Uploaders
    // Use specific selector for Hero to avoid conflict with Gallery button which shares the class
    afc_media_upload('.afc-upload-zone[data-type="hero"] .afc-upload-btn', '_listing_hero_id', 'afc-preview-grid', 'hero');
    afc_media_upload('.afc-pdf-upload-btn', '_listing_pdf_id', 'pdf-status', 'pdf');

    // Gallery Logic
    $('.afc-upload-zone[data-type="gallery"] .afc-upload-btn').click(function (e) {
        e.preventDefault();
        var custom_uploader = wp.media({
            title: 'Select Gallery Images',
            library: { type: 'image' },
            button: { text: 'Add to Gallery' },
            multiple: true
        }).on('select', function () {
            var selection = custom_uploader.state().get('selection');
            var ids = $('input[name="_listing_gallery_ids"]').val() ? $('input[name="_listing_gallery_ids"]').val().split(',') : [];

            selection.map(function (attachment) {
                attachment = attachment.toJSON();
                ids.push(attachment.id);
                // Append to gallery preview grid
                $('.afc-gallery-preview').append(
                    '<div class="afc-preview-item" data-id="' + attachment.id + '"><img src="' + attachment.url + '"><span class="afc-remove-img">×</span></div>'
                );
            });

            $('input[name="_listing_gallery_ids"]').val(ids.join(','));
        });
        custom_uploader.open();
    });

    // Remove Media Logic
    $('body').on('click', '.afc-remove-img, .afc-remove-media', function () {
        var container = $(this).closest('.afc-preview-item, .afc-thumb');
        var parent_grid = container.parent();

        // If Hero (check context)
        if (parent_grid.closest('.afc-upload-zone[data-type="hero"]').length > 0) {
            container.remove();
            $('input[name="_listing_hero_id"]').val('');
        }

        // If Gallery
        if (parent_grid.hasClass('afc-gallery-preview') || parent_grid.attr('id') === 'afc-gallery-sortable') {
            var id_to_remove = container.data('id');
            container.remove();
            var ids = $('input[name="_listing_gallery_ids"]').val().split(',');
            var new_ids = ids.filter(function (item) { return item != id_to_remove; });
            $('input[name="_listing_gallery_ids"]').val(new_ids.join(','));
        }
    });

    // Sortable Gallery
    if ($.fn.sortable) {
        $('.afc-gallery-preview').sortable({
            update: function (event, ui) {
                var ids = [];
                $('.afc-gallery-preview .afc-preview-item').each(function () {
                    ids.push($(this).data('id'));
                });
                $('input[name="_listing_gallery_ids"]').val(ids.join(','));
            }
        });
    }


    // LOCKDOWN: Force Remove Metabox Toggles
    $('.post-type-afcglide_listing .postbox .handlediv').remove();
    $('.post-type-afcglide_listing .postbox .handle-actions').remove();
    $('.post-type-afcglide_listing .postbox').removeClass('closed').addClass('open');
    $('.post-type-afcglide_listing .postbox .postbox-header').css('cursor', 'default');

});
