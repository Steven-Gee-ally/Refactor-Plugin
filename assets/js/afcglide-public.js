/**
 * AFCGlide Listings - Master Public JS (v3.2 Luxury Refactor)
 * Focus: High-End Buyer Experience & Lightning Fast Filtering
 */
jQuery(document).ready(function ($) {

    // ======================================================
    // 1. AJAX LISTING ENGINE (The Grid & Filters)
    // ======================================================
    function initAFCGlideAJAX() {
        const $grid = $('.afcglide-grid');
        const $loadMoreBtn = $('.afcglide-load-more');
        const $filterForm = $('.afcglide-filter-form');
        let isFetching = false;
        let activeRequest = null;

        if (!$grid.length) return;

        function fetchListings(page = 1, append = true) {
            if (activeRequest) activeRequest.abort();

            isFetching = true;
            let filterData = {};

            if ($filterForm.length) {
                $filterForm.serializeArray().forEach(item => {
                    filterData[item.name] = item.value;
                });
            }

            $grid.addClass('is-loading').css('opacity', '0.5');

            activeRequest = $.ajax({
                url: afc_vars.ajax_url, // Using our localized var name
                type: 'POST',
                data: {
                    action: 'afcglide_filter_listings',
                    nonce: afc_vars.nonce,
                    page: page,
                    filters: filterData
                },
                success: function (res) {
                    isFetching = false;
                    $grid.removeClass('is-loading').css('opacity', '1');

                    if (res.success) {
                        const $newItems = $(res.data.html).css('opacity', 0);

                        if (append) {
                            $grid.append($newItems);
                        } else {
                            $grid.html($newItems);
                        }

                        $newItems.animate({ opacity: 1 }, 600);

                        if (res.data.max_pages <= page) {
                            $loadMoreBtn.stop().fadeOut(200);
                        } else {
                            $loadMoreBtn.stop().fadeIn(200).data('page', page);
                        }
                    }
                }
            });
        }

        $(document).on('click', '.afcglide-load-more', function (e) {
            e.preventDefault();
            if (isFetching) return;
            const nextPage = parseInt($(this).data('page')) + 1;
            fetchListings(nextPage, true);
        });

        let filterTimer;
        $filterForm.on('change keyup', 'select, input', function () {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => fetchListings(1, false), 400);
        });
    }

    // ======================================================
    // 2. FILMSTRIP ENGINE (Cinematic Hero Swapping)
    // ======================================================
    function initFilmstrip() {
        const $heroImg = $('.afcglide-hero-main img');

        $(document).on('click', '.afc-filmstrip-item', function () {
            const newSrc = $(this).find('img').attr('src');

            // Luxury Transition: Fade out, swap, fade in
            $heroImg.stop().animate({ opacity: 0 }, 200, function () {
                $(this).attr('src', newSrc).animate({ opacity: 1 }, 400);
            });

            // Active State Styling
            $('.afc-filmstrip-item').css('border', 'none').css('opacity', '1');
            $(this).css('opacity', '0.6');
        });
    }

    // ======================================================
    // 3. CONCIERGE OVERLAY (Success & Contact)
    // ======================================================
    function initConciergeOverlay() {
        const $overlay = $('#afc-success-overlay');
        const $closeBtn = $('.afc-overlay-close');

        // Trigger Overlay (Can be called on form success or button click)
        $(document).on('click', '.afc-trigger-concierge', function (e) {
            e.preventDefault();
            $overlay.addClass('afc-show');
            $('body').css('overflow', 'hidden'); // Lock scroll
        });

        // Close Logic
        $closeBtn.on('click', function () {
            $overlay.removeClass('afc-show');
            $('body').css('overflow', 'auto');
        });

        // Close on Click Outside
        $overlay.on('click', function (e) {
            if ($(e.target).is('#afc-success-overlay')) {
                $closeBtn.trigger('click');
            }
        });
    }

    // ======================================================
    // 4. LUXURY UI INTERACTION
    // ======================================================
    function initLuxuryUI() {
        $(document).on('click', 'a[href^="#afc-"]', function (e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 1000, 'swing');
            }
        });
    }

    // Launch High-End Engines
    initAFCGlideAJAX();
    initFilmstrip();
    initConciergeOverlay();
    initLuxuryUI();

});