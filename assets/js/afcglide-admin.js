jQuery(document).ready(function ($) {

    // ==========================================
    // 0. LUXURY LAYOUT ENFORCER
    // ==========================================
    const enforceLayoutOrder = () => {
        const container = $('#normal-sortables');
        if (!container.length) return;
        const order = ['afc_agent', 'afc_media_hub', 'afc_slider', 'afc_details', 'afc_location', 'afc_amenities', 'afc_publish_box'];
        order.forEach(id => {
            const box = $('#' + id);
            if (box.length) container.append(box);
        });
    };
    enforceLayoutOrder();

    // ==========================================
    // 1. DATA LOSS PREVENTION
    // ==========================================
    let formChanged = false;
    $('input, textarea, select').on('change input', () => formChanged = true);
    $(window).on('beforeunload', (e) => { if (formChanged) return 'Unsaved changes!'; });
    $('form#post').on('submit', () => formChanged = false);

    // ==========================================
    // 2. HERO & AGENT PHOTO (Single Select)
    // ==========================================
    $(document).on('click', '.afc-upload-btn, .afcglide-upload-image-btn', function (e) {
        const btn = $(this);
        const zone = btn.closest('.afc-upload-zone');
        if (zone.data('type') !== 'hero' && !btn.hasClass('afcglide-upload-image-btn')) return;

        e.preventDefault();
        const frame = wp.media({ title: 'Select Photo', multiple: false });

        frame.on('select', function () {
            const attachment = frame.state().get('selection').first().toJSON();
            if (attachment.width < 1200) { alert('⚠️ IMAGE TOO SMALL (Min 1200px)'); return; }

            if (btn.hasClass('afcglide-upload-image-btn')) {
                // Agent Photo Logic
                $('#agent_photo_id').val(attachment.id);
                btn.siblings('.afcglide-preview-box').html(`<img src="${attachment.url}" style="width:100%;">`);
            } else {
                // Hero Image Logic
                zone.find('input[type="hidden"]').val(attachment.id);
                zone.find('.afc-preview-grid').html(`<div class="afc-preview-item"><img src="${attachment.url}"><span class="afc-remove-img">×</span></div>`);
            }
            formChanged = true;
        });
        frame.open();
    });

    // ==========================================
    // 3. STACK & GALLERY (Multi-Select & Sync)
    // ==========================================
    $(document).on('click', '.afc-upload-btn', function (e) {
        const btn = $(this);
        const zone = btn.closest('.afc-upload-zone');
        const type = zone.data('type');
        
        if (type === 'hero') return; // Hero is handled in Section 2

        e.preventDefault();
        const limit = zone.data('limit');
        const input = zone.find('input[type="hidden"]');
        const preview = zone.find('.afc-preview-grid');

        const frame = wp.media({ 
            title: 'Select Gallery Photos', 
            multiple: true 
        });

        frame.on('select', function () {
            const selection = frame.state().get('selection');
            let ids = input.val() ? input.val().split(',') : [];

            selection.each(function (attachment) {
                attachment = attachment.toJSON();
                // Check if under limit and not a duplicate
                if (ids.length < limit && !ids.includes(attachment.id.toString())) {
                    if (attachment.width < 1200) { 
                        console.log('Skipped small image: ' + attachment.filename); 
                        return; 
                    }
                    
                    ids.push(attachment.id);
                    preview.append(`
                        <div class="afc-preview-item" data-id="${attachment.id}">
                            <img src="${attachment.url}">
                            <span class="afc-remove-img">×</span>
                        </div>
                    `);
                }
            });

            input.val(ids.join(','));
            formChanged = true;

            // This triggers Section 6 to allow dragging these new photos
            $(document).trigger('afc_images_updated'); 
        });
        frame.open();
    });
    // ==========================================
    // 4. THE REMOVER (Red Glass Sync)
    // ==========================================
    $(document).on('click', '.afc-remove-img', function () {
        const item = $(this).closest('.afc-preview-item');
        const zone = item.closest('.afc-upload-zone');
        const input = zone.find('input[type="hidden"]');
        const id = item.data('id') ? item.data('id').toString() : '';

        let ids = input.val().split(',');
        ids = ids.filter(val => val !== id);
        input.val(ids.join(','));

        item.fadeOut(300, function() { $(this).remove(); });
        formChanged = true;
    });

    // ==========================================
    // 5. AGENT AUTO-FILL
    // ==========================================
    $(document).on('change', '#afc_agent_selector', function () {
        const sel = $(this).find(':selected');
        if (!sel.val()) return;
        $('#afc_agent_name').val(sel.data('name'));
        $('#afc_agent_phone').val(sel.data('phone'));
        $('#agent_photo_id').val(sel.data('photo-id'));
        if (sel.data('photo-url')) $('#agent-photo-img').attr('src', sel.data('photo-url'));
        formChanged = true;
    });

    // ==========================================
// 6. DRAG & DROP SORTING (Gallery & Stack)
// ==========================================
const initSortable = () => {
    $(".afc-preview-grid").sortable({
        items: ".afc-preview-item",
        cursor: "grabbing",
        placeholder: "afc-sortable-placeholder",
        update: function(event, ui) {
            const zone = $(this).closest('.afc-upload-zone');
            const input = zone.find('input[type="hidden"]');
            
            // Re-calculate the ID order based on new positions
            const newIds = [];
            $(this).find('.afc-preview-item').each(function() {
                newIds.push($(this).data('id'));
            });
            
            input.val(newIds.join(','));
            console.log("New Order Saved: " + newIds.join(','));
        }
    });
};

// Initialize on load
initSortable();

// Re-initialize if images are added
$(document).on('afc_images_updated', function() {
    initSortable();
});
});