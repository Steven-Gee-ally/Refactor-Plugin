/**
 * AFCGlide Admin JavaScript
 * Version 5.0.0 - Full Hub Matrix Integration
 * Handles: Media, UI Optimization, Broker Matrix, and Focus Mode.
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
    // 2. MEDIA UPLOADERS (Hero, PDF, Gallery)
    // ==========================================
    $(document).on('click', '.afcglide-upload-image-btn, .afc-pdf-upload-btn, .afc-upload-btn', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const isPdf = $btn.hasClass('afc-pdf-upload-btn');
        const $zone = $btn.closest('.afc-upload-zone');
        const type = $zone.data('type'); // 'hero', 'gallery', or 'stack'
        const $input = $zone.find('input[type="hidden"]');
        const $preview = $zone.find('.afc-preview-grid');

        const frame = wp.media({
            title: isPdf ? 'Select Property Fact Sheet' : 'Select Asset Media',
            multiple: (type !== 'hero' && !isPdf && !$btn.hasClass('afcglide-upload-image-btn')),
            library: { type: isPdf ? 'application/pdf' : 'image' }
        });

        frame.on('select', function () {
            const selection = frame.state().get('selection');

            if (isPdf) {
                const attachment = selection.first().toJSON();
                $('input[name="_listing_pdf_id"]').val(attachment.id);
                $('#pdf-filename').text('Attached: ' + attachment.filename).css('color', '#3b82f6');
            } else if ($btn.hasClass('afcglide-upload-image-btn')) {
                const attachment = selection.first().toJSON();
                $('#agent_photo_id').val(attachment.id);
                $('#agent-photo-img').attr('src', attachment.url);
            } else {
                let currentIds = $input.val() ? $input.val().split(',').filter(Boolean) : [];
                if (type === 'hero') {
                    const attachment = selection.first().toJSON();
                    $input.val(attachment.id);
                    $preview.html(`<div class="afc-preview-item" data-id="${attachment.id}"><img src="${attachment.url}"><span class="afc-remove-img">×</span></div>`);
                } else {
                    selection.map(attachment => {
                        attachment = attachment.toJSON();
                        if (!currentIds.includes(attachment.id.toString())) {
                            currentIds.push(attachment.id);
                            $preview.append(`<div class="afc-preview-item" data-id="${attachment.id}"><img src="${attachment.url}"><span class="afc-remove-img">×</span></div>`);
                        }
                    });
                    $input.val(currentIds.join(','));
                }
                initSortable();
            }
            formChanged = true;
        });
        frame.open();
    });

    // ==========================================
    // 3. REMOVE IMAGE & SORTING
    // ==========================================
    $(document).on('click', '.afc-remove-img', function (e) {
        const $item = $(this).closest('.afc-preview-item');
        const $zone = $item.closest('.afc-upload-zone');
        const $input = $zone.find('input[type="hidden"]');
        $item.fadeOut(200, function () {
            $(this).remove();
            const remainingIds = $zone.find('.afc-preview-item').map(function () { return $(this).data('id'); }).get();
            $input.val(remainingIds.join(','));
        });
        formChanged = true;
    });

    function initSortable() {
        if (typeof $.fn.sortable !== 'undefined') {
            $('.afc-preview-grid').sortable({
                items: '.afc-preview-item',
                cursor: 'grabbing',
                placeholder: 'afc-sortable-placeholder',
                update: function () {
                    const $zone = $(this).closest('.afc-upload-zone');
                    const newIds = $(this).find('.afc-preview-item').map(function () { return $(this).data('id'); }).get();
                    $zone.find('input[type="hidden"]').val(newIds.join(','));
                    formChanged = true;
                }
            });
        }
    }
    initSortable();

    // ==========================================
    // 4. HUB INTERACTIVITY (MATRIX & FOCUS)
    // ==========================================

    // FOCUS MODE TOGGLE
    $(document).on('change', '#afc-focus-toggle', function () {
        $.post(ajaxurl, {
            action: 'afcg_toggle_focus',
            security: afc_vars.nonce,
            status: $(this).is(':checked') ? '1' : '0'
        }, function (res) {
            if (res.success) location.reload();
        });
    });

    // BACKBONE SYNC
    $(document).on('click', '#afc-save-backbone', function (e) {
        e.preventDefault();
        const $btn = $(this);
        $btn.text('SYNCING...').prop('disabled', true);

        $.post(ajaxurl, {
            action: 'afcg_sync_backbone',
            security: afc_vars.nonce,
            system_label: $('#afc-system-label').val(),
            whatsapp_color: $('#afc-whatsapp-color').val(),
            lockdown: $('#afc-lockdown-toggle').is(':checked') ? '1' : '0',
            gatekeeper: $('#afc-gatekeeper-toggle').is(':checked') ? '1' : '0'
        }, function (res) {
            if (res.success) {
                $btn.text('✅ SYNCED').css('background', '#3b82f6');
                setTimeout(() => location.reload(), 800);
            } else {
                alert('Sync Error: ' + (res.data || 'Permission Denied'));
                $btn.text('EXECUTE SYSTEM SYNC').prop('disabled', false);
            }
        });
    });

    // AGENT RECRUITMENT
    $(document).on('click', '#afc-recruit-btn', function (e) {
        e.preventDefault();
        const $btn = $(this);
        const data = {
            action: 'afcg_recruit_agent',
            security: afc_vars.nonce_recruit,
            agent_username: $('#afc-new-user').val(),
            agent_email: $('#afc-new-email').val(),
            password: $('#afc-new-pass').val()
        };

        if (!data.agent_username || !data.agent_email) {
            alert('Username and Email are mandatory.');
            return;
        }

        $btn.text('RECRUITING...').prop('disabled', true);
        $.post(ajaxurl, data, function (res) {
            if (res.success) {
                $btn.text('✅ RECRUITED');
                setTimeout(() => location.search += '&agent_added=1', 1000);
            } else {
                alert('Recruitment Error: ' + (res.data || 'User likely exists'));
                $btn.text('RECRUIT AGENT').prop('disabled', false);
            }
        });
    });

    // ==========================================
    // 5. LAYOUT ENFORCEMENT & OPTIMIZATION
    // ==========================================
    function enforceLayoutOrder() {
        const $container = $('#normal-sortables');
        if (!$container.length) return;
        const order = ['afc_intro', 'afc_description', 'afc_details', 'afc_media_hub', 'afc_slider', 'afc_location_v2', 'afc_amenities', 'afc_agent', 'afc_intelligence', 'afc_publish_box'];
        order.forEach(id => { const $box = $('#' + id); if ($box.length) $container.append($box); });
    }
    enforceLayoutOrder();

    // ==========================================
    // 6. CONSOLE LOG
    // ==========================================
    console.log('%c🚀 AFCGlide Admin v5.0.0 (Matrix-Ready) Loaded', 'color: #3b82f6; font-weight: 800;');

});