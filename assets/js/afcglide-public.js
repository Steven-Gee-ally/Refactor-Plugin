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

        // ✅ FIX: Move this to the top so fetchListings can see it!
        let isFetching = false;

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
                success: function (res) {
                    isFetching = false; // Now this works!
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
                error: function () {
                    isFetching = false; // Now this works!
                    $grid.removeClass('is-loading').css('opacity', '1');
                    console.error('AFCGlide: AJAX Filter Error');
                }
            });
        }

        // Load More Event
        $(document).on('click', '.afcglide-load-more', function (e) {
            e.preventDefault();
            if (isFetching) return;

            isFetching = true;
            const nextPage = parseInt($(this).data('page')) + 1;
            fetchListings(nextPage, true);
        });

        // Live Filter Trigger
        let filterTimer;
        $filterForm.on('change input', 'select, input', function () {
            clearTimeout(filterTimer);
            filterTimer = setTimeout(() => fetchListings(1, false), 400);
        });
    }

    // ======================================================
    // 2. LUXURY UI INTERACTION (Optimized)
    // ======================================================
    function initLuxuryUI() {
        $(document).on('click', 'a[href^="#afc-"]', function (e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').stop().animate({
                    scrollTop: target.offset().top - 100
                }, 800);
            }
        });
    }

    // ======================================================
    // 3. AGENT REGISTRATION ENGINE
    // ======================================================
    function initAFCGlideAuth() {
        const $regForm = $('#afcglide-registration');
        if (!$regForm.length) return;

        $regForm.on('submit', function (e) {
            e.preventDefault();
            const $btn = $(this).find('button');
            const originalText = $btn.text();

            $btn.prop('disabled', true).text(afcglide_ajax_object.strings.loading || 'Registering...');

            $.ajax({
                url: afcglide_ajax_object.ajax_url,
                type: 'POST',
                data: $(this).serialize() + '&action=afc_register_agent&nonce=' + $(this).find('[name="register_nonce"]').val(),
                success: function (res) {
                    if (res.success) {
                        $regForm.html('<div class="afc-success-msg">✨ ' + res.data + '</div>');
                    } else {
                        alert(res.data);
                        $btn.prop('disabled', false).text(originalText);
                    }
                },
                error: function () {
                    alert('Registration failed. Please try again.');
                    $btn.prop('disabled', false).text(originalText);
                }
            });
        });
    }

    // Launch Engines
    initAFCGlideAJAX();
    initLuxuryUI();
    initAFCGlideAuth();

});