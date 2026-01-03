/**
 * AFCGlide Listings - Master Public JS (v3.2 Luxury Refactor)
 * Focus: High-End Buyer Experience & Lightning Fast Filtering
 */
jQuery(document).ready(function($) {

    // ======================================================
    // 1. AJAX LISTING ENGINE (The Grid & Filters)
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

            // Emerald Loading State
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
                    console.error('AFCGlide: AJAX Filter Error');
                }
            });
        }

        // Load More Event
        $(document).on('click', '.afcglide-load-more', function(e) {
            e.preventDefault();
            const nextPage = parseInt($(this).data('page')) + 1;
            fetchListings(nextPage, true);
        });

        // Live Filter Trigger
        let filterTimer;
        $filterForm.on('change input', 'select, input', function() {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => fetchListings(1, false), 400);
        });
    }

    // ======================================================
    // 2. LUXURY UI INTERACTION
    // ======================================================
    function initLuxuryUI() {
        // Smooth scroll for listing links
        $(document).on('click', 'a[href^="#afc-"]', function(e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $($.attr(this, 'href')).offset().top - 100
            }, 800);
        });

        // Hover effect for the 20-Point Amenities (Front-End)
        $('.afc-amenity-item').hover(
            function() { $(this).css('border-color', '#10b981'); },
            function() { $(this).css('border-color', '#e2e8f0'); }
        );
    }

    // Launch Engines
    initAFCGlideAJAX();
    initLuxuryUI();

});