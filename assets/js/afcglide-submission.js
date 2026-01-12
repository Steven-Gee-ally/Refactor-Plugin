jQuery(document).ready(function ($) {
    const $form = $('#afcglide-front-submission');
    const $dropzone = $('#afc-upload-dropzone');
    const $fileInput = $('#afc_photos');
    const $previewGrid = $('#afc-preview-grid');
    const $submitBtn = $('#submit-listing-btn');
    const $statusMsg = $('#afc-form-status');

    // 1. Trigger file input when clicking the custom dropzone
    $dropzone.on('click', function () {
        $fileInput.click();
    });

    // 2. Handle File Selection & Preview
    $fileInput.on('change', function (e) {
        const files = e.target.files;
        $previewGrid.empty();

        if (files.length > 16) {
            alert('⚠️ Maximum 16 photos allowed.');
            $(this).val('');
            return;
        }

        Array.from(files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function (e) {
                // Check image dimensions before showing preview
                const img = new Image();
                img.src = e.target.result;
                img.onload = function () {
                    let warning = '';
                    if (this.width < 1200) {
                        warning = '<span class="size-warning">⚠️ Too Small</span>';
                    }

                    $previewGrid.append(`
                        <div class="preview-item">
                            <img src="${e.target.result}">
                            ${warning}
                        </div>
                    `);
                };
            }
            reader.readAsDataURL(file);
        });
    });

    // 3. The AJAX Submission
    $form.on('submit', function (e) {
        e.preventDefault();

        // Visual feedback - Disable button
        $submitBtn.prop('disabled', true).addClass('loading');
        $statusMsg.html('<p class="info">' + afc_ajax_obj.messages.uploading + '</p>');

        const formData = new FormData(this);
        formData.append('action', 'afc_submit_listing');
        formData.append('nonce', afc_ajax_obj.nonce);

        $.ajax({
            url: afc_ajax_obj.ajax_url,
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            success: function (response) {
                if (response.success) {
                    $statusMsg.html('<p class="success">' + afc_ajax_obj.messages.success + '</p>');
                    // Redirect to the new listing after a short delay
                    setTimeout(() => {
                        window.location.href = response.data.redirect;
                    }, 2000);
                } else {
                    $statusMsg.html('<p class="error">❌ ' + response.data + '</p>');
                    $submitBtn.prop('disabled', false).removeClass('loading');
                }
            },
            error: function () {
                $statusMsg.html('<p class="error">❌ System Error. Please try again.</p>');
                $submitBtn.prop('disabled', false).removeClass('loading');
            }
        });
    });
});