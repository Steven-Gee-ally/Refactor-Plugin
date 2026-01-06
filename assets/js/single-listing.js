/**
 * AFCGlide Single Listing JavaScript
 * Version: 6.0 - Luxury Gallery Edition
 * Handles high-end lightbox interactions
 */

document.addEventListener('DOMContentLoaded', function () {

    // 1. Initialize GLightbox with Premium Settings
    if (typeof GLightbox !== 'undefined') {
        const lightbox = GLightbox({
            selector: '.glightbox',
            touchNavigation: true,
            loop: true,
            autoplayVideos: false,
            zoomable: true,            // High-end buyers want to see the details
            draggable: true,           // Premium mobile feel
            closeOnOutsideClick: true,
            openEffect: 'zoom',        // Luxury transition
            closeEffect: 'zoom'
        });

        // 2. Keyboard Accessibility (The Professional Touch)
        // Allows users to use arrow keys immediately
        document.addEventListener('keydown', function (e) {
            if (e.key === "Escape") lightbox.close();
        });
    }

    // 3. Image Error Fallback
    // If a property image fails to load, we don't show a broken icon
    const listingImages = document.querySelectorAll('.afcglide-listing-image');
    listingImages.forEach(img => {
        img.onerror = function () {
            this.src = afcglide_vars.placeholder_url || '';
            this.classList.add('is-placeholder');
        };
    });
});