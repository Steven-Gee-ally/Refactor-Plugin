/**
 * AFCGlide Listings - Master Public JS (v3.2 Luxury Refactor)
 * Optimized for: Multi-Upload AJAX, Luxury Transitions, & Brand Sync
 */
jQuery(document).ready(function($) {

    // ======================================================
    // 1. AJAX Listing Engine (Grid & Filters)
    // ======================================================
    function initAFCGlideAJAX() {
        // SYNC: Changed from .afcglide-grid-ready to .afcglide-grid
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

            // Using the loading overlay from Master CSS Section 8
            $grid.addClass('is-loading'); 

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
                    $grid.removeClass('is-loading');
                    if (res.success) {
                        append ? $grid.append(res.data.html) : $grid.html(res.data.html);
                        $loadMoreBtn.data('page', page);
                        (page >= res.data.max_pages) ? $loadMoreBtn.fadeOut() : $loadMoreBtn.fadeIn();
                    }
                }
            });
        }

        $(document).on('click', '.afcglide-load-more', function(e) {
            e.preventDefault();
            fetchListings(parseInt($(this).data('page')) + 1, true);
        });

        let filterTimer;
        $filterForm.on('change input', 'select, input', function() {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => fetchListings(1, false), 400);
        });
    }

    // ======================================================
    // 4. THE SUBMISSION ENGINE (AJAX + MULTI-UPLOAD)
    // ======================================================
    function initSubmissionUI() {
        const $form = $('#afcglide-submit-property');
        if (!$form.length) return;

        $form.on('submit', function(e) {
            e.preventDefault(); // Stop page reload

            const $btn = $(this).find('.afcglide-btn');
            const $msgArea = $('#afc-form-messages'); // Your alert zone
            
            // Create FormData to handle the image files
            let formData = new FormData(this);
            formData.append('action', 'afcglide_submit_listing');
            formData.append('nonce', afcglide_ajax_object.nonce);

            // Trigger Loading State (Master CSS Section 9)
            $btn.addClass('is-processing').prop('disabled', true);

            $.ajax({
                url: afcglide_ajax_object.ajax_url,
                type: 'POST',
                data: formData,
                processData: false, // Critical for images
                contentType: false, // Critical for images
                success: function(res) {
                    if (res.success) {
                        // Success Alert (Master CSS Section 9)
                        $msgArea.html('<div class="afc-success-msg">' + res.data.message + '</div>');
                        $form[0].reset();
                        $('html, body').animate({ scrollTop: $msgArea.offset().top - 100 }, 500);
                    } else {
                        // Error Alert
                        $msgArea.html('<div class="afc-error-msg">' + res.data.message + '</div>');
                    }
                },
                error: function() {
                    $msgArea.html('<div class="afc-error-msg">A server error occurred. Please try again.</div>');
                },
                complete: function() {
                    $btn.removeClass('is-processing').prop('disabled', false);
                }
            });
        });
    }

    // Initialize all
    initAFCGlideAJAX();
    initSubmissionUI();
    // (Other init functions stay the same)
});