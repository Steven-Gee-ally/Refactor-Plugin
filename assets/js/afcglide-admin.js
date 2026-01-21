/**
 * AFCGlide Admin JavaScript
 * Version 4.0.0 - Production Ready
 * Handles all admin interface interactions
 */

jQuery(document).ready(function ($) {

    // ==========================================
    // 0. VERIFY DEPENDENCIES
    // ==========================================
    if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
        console.error('AFCGlide Error: WordPress Media Library not loaded');
        return;
    }

    // ==========================================
    // 1. DATA LOSS PREVENTION
    // ==========================================
    let formChanged = false;

    $('input, textarea, select').on('change input', function () {
        formChanged = true;
    });

    $(window).on('beforeunload', function (e) {
        if (formChanged) {
            return 'You have unsaved changes. Are you sure you want to leave?';
        }
    });

    $('form#post').on('submit', function () {
        formChanged = false;
    });

    // ==========================================
    // 2. AGENT PHOTO UPLOAD (Single Select)
    // ==========================================
    $(document).on('click', '.afcglide-upload-image-btn', function (e) {
        e.preventDefault();

        const $btn = $(this);
        const frame = wp.media({
            title: 'Select Agent Photo',
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();

            // Validate dimensions
            if (attachment.width < 500) {
                alert('⚠️ IMAGE TOO SMALL\nMinimum 500px width required for agent photos.');
                return;
            }

            // Update preview and hidden field
            $('#agent_photo_id').val(attachment.id);
            $('#agent-photo-img').attr('src', attachment.url);

            formChanged = true;
        });

        frame.open();
    });

    // ==========================================
    // 2.2 PDF UPLOAD (Asset Intelligence)
    // ==========================================
    $(document).on('click', '.afc-pdf-upload-btn', function (e) {
        e.preventDefault();

        const frame = wp.media({
            title: 'Select Property Fact Sheet (PDF)',
            multiple: false,
            library: { type: 'application/pdf' }
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            if (attachment.mime !== 'application/pdf') {
                alert('⚠️ INVALID FILE TYPE\nPlease upload a PDF document.');
                return;
            }
            $('#afc_pdf_id').val(attachment.id);
            $('#pdf-filename').text(attachment.filename);
            formChanged = true;
        });

        frame.open();
    });

    // ==========================================
    // 3. HERO IMAGE UPLOAD (Single Select)
    // ==========================================
    $(document).on('click', '.afc-upload-zone[data-type="hero"] .afc-upload-btn', function (e) {
        e.preventDefault();

        const $zone = $(this).closest('.afc-upload-zone');
        const $input = $zone.find('input[type="hidden"]');
        const $preview = $zone.find('.afc-preview-grid');

        const frame = wp.media({
            title: 'Select Hero Image',
            multiple: false,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();

            // Validate dimensions
            if (attachment.width < 1200) {
                alert('⚠️ IMAGE TOO SMALL\n\nLuxury listings require 1200px width minimum.\n\nCurrently detected: ' + attachment.width + 'px');
                return;
            }

            // Update hidden field
            $input.val(attachment.id);

            // Update preview
            $preview.html(
                '<div class="afc-preview-item" data-id="' + attachment.id + '">' +
                '<img src="' + attachment.url + '" alt="">' +
                '<span class="afc-remove-img">×</span>' +
                '</div>'
            );

            formChanged = true;
        });

        frame.open();
    });

    // ==========================================
    // 4. STACK & GALLERY UPLOAD (Multi-Select)
    // ==========================================
    $(document).on('click', '.afc-upload-zone[data-type="stack"] .afc-upload-btn, .afc-upload-zone[data-type="gallery"] .afc-upload-btn', function (e) {
        e.preventDefault();

        const $zone = $(this).closest('.afc-upload-zone');
        const type = $zone.data('type');
        const limit = parseInt($zone.data('limit'));
        const $input = $zone.find('input[type="hidden"]');
        const $preview = $zone.find('.afc-preview-grid');

        // Get current IDs
        let currentIds = $input.val() ? $input.val().split(',').filter(Boolean) : [];

        const frame = wp.media({
            title: type === 'stack' ? 'Select Stack Images (Max ' + limit + ')' : 'Select Gallery Images (Max ' + limit + ')',
            multiple: true,
            library: { type: 'image' }
        });

        frame.on('select', function () {
            const selection = frame.state().get('selection');

            selection.each(function (attachment) {
                attachment = attachment.toJSON();

                // Check limit
                if (currentIds.length >= limit) {
                    console.log('Limit reached for ' + type);
                    return false;
                }

                // Check if already added
                if (currentIds.includes(attachment.id.toString())) {
                    return; // Skip duplicates
                }

                // Validate dimensions
                if (attachment.width < 1200) {
                    console.log('Skipped small image: ' + attachment.filename + ' (' + attachment.width + 'px)');
                    return;
                }

                // Add to array
                currentIds.push(attachment.id);

                // Add to preview
                $preview.append(
                    '<div class="afc-preview-item" data-id="' + attachment.id + '">' +
                    '<img src="' + attachment.url + '" alt="">' +
                    '<span class="afc-remove-img">×</span>' +
                    '</div>'
                );
            });

            // Update hidden field
            $input.val(currentIds.join(','));

            // Reinitialize sortable for new items
            initSortable();

            formChanged = true;
        });

        frame.open();
    });

    // ==========================================
    // 5. REMOVE IMAGE
    // ==========================================
    $(document).on('click', '.afc-remove-img', function (e) {
        e.preventDefault();

        const $item = $(this).closest('.afc-preview-item');
        const $zone = $item.closest('.afc-upload-zone');
        const $input = $zone.find('input[type="hidden"]');
        const imageId = $item.data('id').toString();

        // Remove from hidden field
        let ids = $input.val().split(',').filter(Boolean);
        ids = ids.filter(id => id !== imageId);
        $input.val(ids.join(','));

        // Remove from DOM
        $item.fadeOut(300, function () {
            $(this).remove();
        });

        formChanged = true;
    });

    // ==========================================
    // 6. AGENT AUTO-FILL
    // ==========================================
    $(document).on('change', '#afc_agent_selector', function () {
        const $selected = $(this).find(':selected');

        if (!$selected.val()) return;

        // Fill in fields
        $('#afc_agent_name').val($selected.data('name'));
        $('#afc_agent_phone').val($selected.data('phone'));
        $('#agent_photo_id').val($selected.data('photo-id'));

        // Update photo preview
        const photoUrl = $selected.data('photo-url');
        if (photoUrl) {
            $('#agent-photo-img').attr('src', photoUrl);
        }

        formChanged = true;
    });

    // ==========================================
    // 7. DRAG & DROP SORTING (Gallery & Stack)
    // ==========================================
    function initSortable() {
        if (typeof $.fn.sortable === 'undefined') {
            console.warn('jQuery UI Sortable not loaded');
            return;
        }

        $('.afc-preview-grid').sortable({
            items: '.afc-preview-item',
            cursor: 'grabbing',
            placeholder: 'afc-sortable-placeholder',
            tolerance: 'pointer',
            update: function (event, ui) {
                const $zone = $(this).closest('.afc-upload-zone');
                const $input = $zone.find('input[type="hidden"]');

                // Recalculate order
                const newIds = [];
                $(this).find('.afc-preview-item').each(function () {
                    newIds.push($(this).data('id'));
                });

                $input.val(newIds.join(','));

                console.log('New order saved: ' + newIds.join(','));
                formChanged = true;
            }
        });
    }

    // Initialize on page load
    initSortable();

    // ==========================================
    // 8. ENFORCE METABOX ORDER
    // ==========================================
    function enforceLayoutOrder() {
        const $container = $('#normal-sortables');
        if (!$container.length) return;

        const order = [
            'afc_intro',       // 1. Property Description (Headline)
            'afc_description', // 2. Property Narrative
            'afc_details',     // 3. Property Specifications
            'afc_media_hub',   // 4. Visual Command Center
            'afc_slider',      // 5. Gallery Slider
            'afc_location_v2', // 6. Location & GPS
            'afc_amenities',   // 7. Property Features
            'afc_agent',       // 8. Agent Branding
            'afc_intelligence',// 10. Intelligence & Files
            'afc_publish_box'  // 11. Publish Control
        ];

        order.forEach(function (id) {
            const $box = $('#' + id);
            if ($box.length) {
                $container.append($box);
            }
        });
    }

    enforceLayoutOrder();

    // ==========================================
    // 9. PUBLISH BUTTON ENHANCEMENT
    // ==========================================
    $(document).on('click', '#publish', function (e) {
        const $title = $('#title');

        if ($title.val().trim() === '') {
            e.preventDefault();
            alert('⚠️ Please enter a property title before publishing.');
            $title.focus();
            return false;
        }

        // Check if hero image is set
        const heroId = $('input[name="_listing_hero_id"]').val();

        if (!heroId) {
            const confirmed = confirm('⚠️ NO HERO IMAGE SET\n\nThis listing does not have a hero image. Publishing without one may result in poor presentation.\n\nDo you want to continue anyway?');

            if (!confirmed) {
                e.preventDefault();
                return false;
            }
        }

        // Show loading state
        $(this).prop('disabled', true).val('Publishing...').css('opacity', '0.7');
    });

    // ==========================================
    // 10. CONSOLE WELCOME MESSAGE
    // ==========================================
    console.log('%c🚀 AFCGlide Admin v4.0.0 Loaded', 'color: #10b981; font-weight: bold; font-size: 14px;');
    console.log('%cDrag to reorder | Click × to remove | Auto-save on publish', 'color: #64748b; font-size: 12px;');

});