// === YARDLII: Auto-run FacetWP proximity facet when location prefilled (Listings page only) ===
(function($) {
  // Only run this on the Listings page
  if (window.location.pathname.includes('/listings')) {
    let firstLoad = true;

    $(document).on('facetwp-loaded', function() {
      if (firstLoad) {
        const $locFacet = $('[data-name="location"] input.facetwp-location');
        if ($locFacet.length && $locFacet.val().trim() !== '') {
          // 🧠 Show visual confirmation ONLY for logged-in admins
          if ($('#wpadminbar').length) {
            const msg = $('<div id="yardlii-filter-msg">Applying location filter…</div>').css({
              position: 'fixed',
              top: '80px',
              left: '50%',
              transform: 'translateX(-50%)',
              background: '#0b5eb8',
              color: '#fff',
              padding: '8px 16px',
              borderRadius: '6px',
              fontSize: '13px',
              zIndex: 9999,
              opacity: 0.95,
              boxShadow: '0 2px 6px rgba(0,0,0,0.15)',
            });
            $('body').append(msg);
            setTimeout(() => msg.fadeOut(400, () => msg.remove()), 1500);
          }

          console.log('YARDLII: Auto-refreshing preloaded location facet...');
          FWP.refresh();
        }
        firstLoad = false;
      }
    });
  }
})(jQuery);

/**
 * YARDLII: Global Mobile Viewport Fix (Universal Location Engine)
 * Context: Fixes Google Autocomplete hiding behind mobile keyboards.
 * Upgraded: v3.26.2 - FacetWP Flyout & Directory Support
 */
(function() {
    document.addEventListener('focusin', function(e) {
        const target = e.target;

        // 1. ROBUST IDENTIFICATION
        // cathes: WPUF, FacetWP, Homepage, and Directory ID explicitly
        const isLocationInput = (
            target.closest('.yardlii-city-autocomplete') ||        // WPUF / Dashboard
            target.classList.contains('yardlii-location-input') || // Homepage Class
            target.id === 'yardlii_location_input' ||              // Directory / Homepage ID
            target.classList.contains('facetwp-location') ||       // FacetWP Standard
            target.closest('.fwp-location-search') ||              // FacetWP Wrapper
            target.id === 'fwp-location-search'                    // Fallback
        );

        if (!isLocationInput) return;

        // 2. Mobile Device Check
        if (window.innerWidth >= 768) return;

        // 3. CONTEXT DETECTION (The Flyout Fix)
        // Check if we are inside the FacetWP Mobile Flyout
        const flyout = target.closest('.facetwp-flyout');
        
        // Define where to put the spacer
        let injectionTarget = document.body;
        
        if (flyout) {
            // If in Flyout, try to find the specific scrollable content area
            // FacetWP usually uses .facetwp-flyout-content
            const flyoutContent = flyout.querySelector('.facetwp-flyout-content');
            injectionTarget = flyoutContent || flyout;
        }

        // 4. Inject Phantom Spacer
        let spacer = document.getElementById('yardlii-mobile-spacer');
        if (!spacer) {
            spacer = document.createElement('div');
            spacer.id = 'yardlii-mobile-spacer';
            spacer.style.height = '45vh'; // 45% of viewport height
            spacer.style.width = '100%';
            spacer.style.minHeight = '300px'; 
            spacer.style.pointerEvents = 'none';
            spacer.style.backgroundColor = 'transparent';
            
            // Append to either Body OR the Flyout
            injectionTarget.appendChild(spacer);
        }

        // 5. Scroll to Top
        setTimeout(() => {
            target.scrollIntoView({
                behavior: "smooth", 
                block: "start" 
            });
        }, 300);
    });

    // 6. Cleanup on Blur
    document.addEventListener('focusout', function(e) {
        setTimeout(() => {
            const spacer = document.getElementById('yardlii-mobile-spacer');
            if (spacer) {
                spacer.remove();
            }
        }, 200);
    });
})();