jQuery(document).ready(function ($) {
    const $form = $('#afcglide-front-submission');
    const $dropzone = $('#afc-upload-dropzone');
    const $fileInput = $('#afc_photos');
    const $previewGrid = $('#afc-preview-grid');
    const $submitBtn = $('#submit-listing-btn');
    const $statusMsg = $('#afc-form-status');

    // 1. Drag & Drop Visual States
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        $dropzone.on(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
        });
    });

    $dropzone.on('dragenter dragover', function () {
        $(this).addClass('is-dragover');
    });

    $dropzone.on('dragleave drop', function () {
        $(this).removeClass('is-dragover');
    });

    $dropzone.on('drop', function (e) {
        const files = e.originalEvent.dataTransfer.files;
        $fileInput[0].files = files;
        $fileInput.trigger('change');
    });

    $dropzone.on('click', () => $fileInput.click());

    // 2. High-End Image Preview & Validation
    $fileInput.on('change', function (e) {
        const files = Array.from(e.target.files);
        $previewGrid.empty();

        if (files.length > 16) {
            alert('⚠️ ' + (afc_ajax_obj.messages.max_files || 'Maximum 16 photos allowed.'));
            $(this).val('');
            return;
        }

        files.forEach((file, index) => {
            if (!file.type.startsWith('image/')) return;

            const reader = new FileReader();
            reader.onload = function (event) {
                const img = new Image();
                img.src = event.target.result;

                img.onload = function () {
                    let warning = '';
                    if (this.width < 1200) {
                        warning = `<div class="afc-dim-warning">⚠️ ${this.width}px - ${afc_ajax_obj.messages.low_res || 'Low Res'}</div>`;
                    }

                    const previewHtml = `
                        <div class="afc-preview-item" data-index="${index}">
                            <div class="afc-preview-wrapper">
                                <img src="${event.target.result}" alt="Preview">
                                ${warning}
                                <div class="afc-remove-photo" onclick="this.parentElement.parentElement.remove()">✕</div>
                            </div>
                        </div>
                    `;
                    $previewGrid.append(previewHtml);
                };
            };
            reader.readAsDataURL(file);
        });
    });

    // 3. Robust AJAX Submission
    $form.on('submit', function (e) {
        e.preventDefault();

        $submitBtn.prop('disabled', true).addClass('afc-is-loading');
        $statusMsg.html(`<div class="afc-loader-dots"><span></span><span></span><span></span></div><p class="info">${afc_ajax_obj.messages.uploading}</p>`);

        const formData = new FormData(this);
        formData.append('action', 'afcglide_submit_listing');
        formData.append('nonce', afc_ajax_obj.nonce);

        $.ajax({
            url: afc_ajax_obj.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.success) {
                    $statusMsg.html(`<p class="success">✨ ${afc_ajax_obj.messages.success}</p>`);
                    setTimeout(() => {
                        window.location.href = response.data.url;
                    }, 1500);
                } else {
                    const errorMsg = response.data && response.data.message ? response.data.message : 'Unknown Error';
                    $statusMsg.html(`<p class="error">❌ ${errorMsg}</p>`);
                    $submitBtn.prop('disabled', false).removeClass('afc-is-loading');
                }
            },
            error: function (xhr, status, error) {
                $statusMsg.html('<p class="error">❌ System Timeout. Check file sizes and try again.</p>');
                $submitBtn.prop('disabled', false).removeClass('afc-is-loading');
            }
        });
    });
}); 