/**
 * AFCGlide Listings - Master Public JS (v3.2 Luxury Refactor)
 * Optimized for: Multi-Upload AJAX, Luxury Transitions, & Brand Sync
 */
jQuery(document).ready(function($) {

    // ======================================================
    // 1. AJAX Listing Engine (Grid & Filters)
    // ======================================================
    function initAFCGlideAJAX() {
        const $grid = $('.afcglide-grid'); 
        const $loadMoreBtn = $('.afcglide-load-more');
        const $filterForm = $('.afcglide-filter-form');

        if (!$grid.length) return;

        function fetchListings(page = 1, append = true) {
            let filterData = {};
            if ($filterForm.length) {
                $filterForm.serializeArray().forEach(item => {
                    filterData[item.name] = item.value;
                });
            }

            $grid.addClass('is-loading').css('opacity', '0.6'); 

            $.ajax({
                url: afcglide_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'afcglide_filter_listings',
                    nonce: afcglide_ajax_object.nonce,
                    page: page,
                    filters: filterData
                },
                success: function(res) {
                    $grid.removeClass('is-loading').css('opacity', '1');
                    if (res.success) {
                        append ? $grid.append(res.data.html) : $grid.html(res.data.html);
                        
                        $loadMoreBtn.data('page', page);
                        if (res.data.max_pages <= page) {
                            $loadMoreBtn.fadeOut();
                        } else {
                            $loadMoreBtn.fadeIn();
                        }
                    }
                },
                error: function() {
                    $grid.removeClass('is-loading').css('opacity', '1');
                }
            });
        }

        $(document).on('click', '.afcglide-load-more', function(e) {
            e.preventDefault();
            const nextPage = parseInt($(this).data('page')) + 1;
            fetchListings(nextPage, true);
        });

        let filterTimer;
        $filterForm.on('change input', 'select, input', function() {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => fetchListings(1, false), 400);
        });
    }

    // ======================================================
    // 2. THE SUBMISSION ENGINE (AJAX + MULTI-UPLOAD)
    // ======================================================
    function initSubmissionUI() {
        const $form = $('#afcglide-submit-property');
        if (!$form.length) return;

        $form.on('submit', function(e) {
            e.preventDefault(); 

            const $btn = $(this).find('.afcglide-btn');
            const $msgArea = $('#afc-form-messages'); 
            
            let formData = new FormData(this);
            formData.append('action', 'afcglide_submit_listing');
            formData.append('nonce', afcglide_ajax_object.nonce);

            $btn.addClass('is-processing').prop('disabled', true);
            $btn.find('.btn-text').text(afcglide_ajax_object.strings.loading);
            $msgArea.html('');

            $.ajax({
                url: afcglide_ajax_object.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(res) {
                    if (res.success) {
                        $msgArea.html('<div class="afc-success-msg">' + afcglide_ajax_object.strings.success + '</div>');
                        $form[0].reset();
                        $('html, body').animate({ scrollTop: $msgArea.offset().top - 150 }, 600);
                    } else {
                        const errorMsg = res.data.message || afcglide_ajax_object.strings.error;
                        $msgArea.html('<div class="afc-error-msg">' + errorMsg + '</div>');
                    }
                },
                error: function() {
                    $msgArea.html('<div class="afc-error-msg">A server error occurred. Please check your file sizes.</div>');
                },
                complete: function() {
                    $btn.removeClass('is-processing').prop('disabled', false);
                    $btn.find('.btn-text').text('Submit Luxury Listing');
                }
            });
        });
    }

    // ======================================================
    // 3. UI ENHANCEMENTS (The "Agent Experience")
    // ======================================================
    function initUX() {
        // 1. Trigger hidden file inputs when clicking a "Branded" button
        $(document).on('click', '.afc-public-upload-trigger', function(e) {
            e.preventDefault();
            $(this).siblings('input[type="file"]').click();
        });

        // 2. Real-time Image Preview for Agents
        $(document).on('change', 'input[type="file"]', function(e) {
            const input = this;
            const $previewBox = $(this).siblings('.afc-public-preview');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(event) {
                    $previewBox.html(`
                        <div class="preview-wrapper" style="position:relative; margin-top:15px;">
                            <img src="${event.target.result}" style="width:100%; max-width:300px; border-radius:12px; border:2px solid #4f46e5;">
                            <span class="remove-preview" style="position:absolute; top:-10px; right:-10px; background:red; color:white; border-radius:50%; width:25px; height:25px; text-align:center; cursor:pointer; line-height:25px;">×</span>
                        </div>
                    `);
                };
                reader.readAsDataURL(input.files[0]);
                $(this).closest('.upload-zone').addClass('has-file');
            }
        });

        // 3. Remove Image logic
        $(document).on('click', '.remove-preview', function() {
            const $parent = $(this).closest('.upload-zone');
            $parent.find('input[type="file"]').val('');
            $parent.find('.afc-public-preview').empty();
            $parent.removeClass('has-file');
        });
    }

    // Initialize all modules
    initAFCGlideAJAX();
    initSubmissionUI();
    initUX();

}); // <-- THE MISSING SHOE