/**
 * AFCGlide Listings - Master Public JS (v3.3 Luxury Refactor & Lead Routing)
 * Focus: High-End Buyer Experience & Lightning Fast Filtering
 * World-Class Standard: No code omitted.
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
                url: afc_vars.ajax_url,
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

    // ======================================================
    // 5. INQUIRY LEAD GATEWAY (Synergy Routing)
    // ======================================================
    function initInquiryGateway() {
        const $inquiryForm = $('#afc-listing-inquiry');

        if (!$inquiryForm.length) return;

        $inquiryForm.on('submit', function (e) {
            e.preventDefault();

            const $form = $(this);
            const $submitBtn = $form.find('.afc-inquiry-submit');
            const $responseBox = $('#afc-inquiry-response');

            // Gather data for the Synergy Engine
            const formData = new FormData(this);
            formData.append('action', 'afc_submit_inquiry');
            formData.append('security', afc_vars.inquiry_nonce);

            // Luxury UI: Transmitting State
            $submitBtn.prop('disabled', true)
                .html('<span class="afc-pulse"></span> TRANSMITTING INQUIRY...')
                .css('opacity', '0.7');

            $.ajax({
                url: afc_vars.ajax_url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function (res) {
                    if (res.success) {
                        // Success: Smooth transition to confirmation
                        $responseBox.hide().html('<div class="afc-success-msg">🚀 ' + res.data.message + '</div>').fadeIn(400);
                        $form[0].reset();
                        $submitBtn.html('INQUIRY DISPATCHED').css('background', '#064e3b').css('color', '#fff');

                        // Link to Section #3: Auto-trigger Concierge on Success
                        setTimeout(() => {
                            $('.afc-trigger-concierge').trigger('click');
                        }, 1000);

                    } else {
                        // Failure: Allow retry
                        $responseBox.html('<div class="afc-error-msg">⚠️ Protocol Error. Please verify details and retry.</div>');
                        $submitBtn.prop('disabled', false).html('RETRY INITIALIZATION');
                    }
                },
                error: function () {
                    $submitBtn.prop('disabled', false).html('SYSTEM ERROR: RETRY');
                }
            });
        });
    }

    // ======================================================
    // 6. AGENT RECRUITMENT (Broker Pulse)
    // ======================================================
    function initAgentRecruitment() {
        $(document).on('click', '.afc-trigger-recruit', function (e) {
            e.preventDefault();

            // Premium Recruitment Modal (Dynamically Injected for Bulletproof performance)
            if (!$('#afc-recruit-modal').length) {
                $('body').append(`
                    <div id="afc-recruit-modal" class="afc-overlay">
                        <div class="afc-overlay-content" style="max-width: 400px; padding: 40px; border-radius: 20px;">
                            <h2 style="margin-top:0; letter-spacing:-1px;">Recruit New Agent</h2>
                            <p style="font-size:12px; color:#64748b; margin-bottom:20px;">Provisioning a new operator into the Synergy Network.</p>
                            
                            <form id="afc-recruit-form">
                                <input type="text" name="agent_username" placeholder="Username" required style="width:100%; margin-bottom:10px; padding:12px; border:1px solid #e2e8f0; border-radius:8px;">
                                <input type="email" name="agent_email" placeholder="Email Address" required style="width:100%; margin-bottom:10px; padding:12px; border:1px solid #e2e8f0; border-radius:8px;">
                                <input type="password" name="password" placeholder="Initial Password" required style="width:100%; margin-bottom:20px; padding:12px; border:1px solid #e2e8f0; border-radius:8px;">
                                <button type="submit" class="afc-view-btn" style="width:100%; border:none;">PERSONNEL ACTIVATION</button>
                            </form>
                            <div id="afc-recruit-response" style="margin-top:20px; font-size:12px; font-weight:700;"></div>
                            <button class="afc-overlay-close">×</button>
                        </div>
                    </div>
                `);
            }

            $('#afc-recruit-modal').addClass('afc-show');
            $('body').css('overflow', 'hidden');
        });

        $(document).on('submit', '#afc-recruit-form', function (e) {
            e.preventDefault();
            const $form = $(this);
            const $response = $('#afc-recruit-response');
            const $btn = $form.find('button');

            $btn.prop('disabled', true).html('<span class="afc-pulse"></span> ACTIVATING...');

            $.ajax({
                url: afc_vars.ajax_url,
                type: 'POST',
                data: $form.serialize() + '&action=afcg_recruit_agent&security=' + afc_vars.recruit_nonce,
                success: function (res) {
                    if (res.success) {
                        $response.css('color', '#10b981').html(res.data.message);
                        setTimeout(() => location.reload(), 2000);
                    } else {
                        $response.css('color', '#ef4444').html(res.data.message);
                        $btn.prop('disabled', false).html('RETRY ACTIVATION');
                    }
                }
            });
        });
    }

    function initFocusMode() {
        $(document).on('change', '#afc-focus-toggle', function () {
            const isChecked = $(this).is(':checked') ? '1' : '0';

            $.ajax({
                url: afc_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'afcg_toggle_focus',
                    security: afc_vars.nonce,
                    status: isChecked
                },
                success: function (res) {
                    if (res.success) {
                        location.reload(); // Reload to apply admin filter
                    }
                }
            });
        });
    }

    // Launch High-End Engines
    initAFCGlideAJAX();
    initFilmstrip();
    initConciergeOverlay();
    initLuxuryUI();
    initInquiryGateway();
    initAgentRecruitment();
    initFocusMode();

});