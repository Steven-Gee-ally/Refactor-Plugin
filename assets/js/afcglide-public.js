/**
 * AFCGlide Listings - Master Public JavaScript
 * Handles: AJAX Load More, Filters, Hero Slider, & Lightbox
 */
jQuery(document).ready(function($) {

    // ======================================================
    // 1. AJAX Load More & Filtering
    // ======================================================
    function initAFCGlideCore() {
        const $grid = $('.afcglide-grid-ready');
        const $loadMoreButton = $('.afcglide-load-more');
        const $filterForm = $('.afcglide-filter-form'); // Ensure your shortcode form has this class

        /**
         * The Main Fetch Function
         */
        function fetchListings( page = 1, isAppend = true ) {
            
            // Safety check
            if (!$grid.length) return;

            // Get Filter Data
            let filterData = {};
            if ( $filterForm.length ) {
                filterData = {
                    location:  $filterForm.find('select[name="location"]').val(),
                    type:      $filterForm.find('select[name="type"]').val(),
                    status:    $filterForm.find('select[name="status"]').val(),
                    min_price: $filterForm.find('input[name="min_price"]').val(),
                    max_price: $filterForm.find('input[name="max_price"]').val()
                };
            }

            // UI: Set Loading State
            const loadingText = afcglide_ajax_object.strings.loading || 'Loading...';     
            $loadMoreButton.prop('disabled', true).text(loadingText);
            $grid.addClass('afcglide-loading');

            // AJAX Request
            $.ajax({
                url: afcglide_ajax_object.ajax_url,
                type: 'POST',
                data: {
                    action: 'afcglide_filter_listings', // Unified Action
                    nonce: afcglide_ajax_object.nonce,
                    page: page,
                    filters: filterData,
                    query_vars: $loadMoreButton.data('query') // Original shortcode args
                },
                success: function(response) {
                    $grid.removeClass('afcglide-loading');

                    if (response.success) {
                        // Render HTML
                        if (isAppend) {
                            $grid.append(response.data.html);
                        } else {
                            $grid.html(response.data.html); // Reset grid for filters
                        }
                        
                        // Update Button State
                        $loadMoreButton.data('page', page);
                        
                        // Hide button if no more pages
                        if ( page >= response.data.max_pages ) {
                            $loadMoreButton.hide();
                        } else {
                            $loadMoreButton.show();
                            $loadMoreButton.prop('disabled', false).text('Load More Listings');
                        }
                    } else {
                        // No results found
                        if (!isAppend) $grid.html(response.data.html);
                        $loadMoreButton.hide();
                    }
                },
                error: function() {
                    $grid.removeClass('afcglide-loading');
                    $loadMoreButton.prop('disabled', false).text('Error - Try Again');
                }
            });
        }

        // EVENT 1: Click Load More
        $('body').on('click', '.afcglide-load-more', function(e) {
            e.preventDefault();
            let currentPage = parseInt( $(this).data('page') ) || 1;
            fetchListings( currentPage + 1, true ); // True = Append to bottom
        });

        // EVENT 2: Filter Changes (Dropdowns)
        if ( $filterForm.length ) {
            $filterForm.on('change', 'select, input', function(e) {
                // Optional: Add a debounce here if you want to wait for typing to finish
                fetchListings( 1, false ); // False = Replace grid (don't append)
            });

            // Prevent Form Submit (since we use AJAX)
            $filterForm.on('submit', function(e) {
                e.preventDefault();
                fetchListings( 1, false );
            });
        }
    }

    // ======================================================
    // 2. Single Page: Hero Slider (Arrows)
    // ======================================================
    function initHeroSlider() {
        // Scroll Left
        $('.afcglide-slider-prev').on('click', function(e) {
            e.preventDefault();
            var $track = $(this).closest('.afcglide-slider').find('.afcglide-slider-track');
            $track.animate({ scrollLeft: '-=250' }, 300);
        });

        // Scroll Right
        $('.afcglide-slider-next').on('click', function(e) {
            e.preventDefault();
            var $track = $(this).closest('.afcglide-slider').find('.afcglide-slider-track');
            $track.animate({ scrollLeft: '+=250' }, 300);
        });
    }

    // ======================================================
    // 3. Single Page: Lightbox (GLightbox)
    // ======================================================
    function initLightbox() {
        if (typeof GLightbox === 'function') {
            const lightbox = GLightbox({
                selector: '.afcglide-lightbox',
                touchNavigation: true,
                loop: true,
                zoomable: true
            });
        }
    }

    // Initialize
    initAFCGlideCore();
    initHeroSlider();
    initLightbox();
});