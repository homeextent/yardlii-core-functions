/**
 * YARDLII Core - Location + Radius Slider with Tooltip + Locate Me
 * Uses Google Maps Autocomplete + Geocoding + Browser Geolocation
 * Formats FacetWP-compatible value: lat,lng,distance,address
 */
(function($) {
  $(document).ready(function() {
    const form = $('.yardlii-search-form');
    const input = document.getElementById('yardlii_location_input');
    const range = document.getElementById('yardlii_radius_range');
    const tooltip = document.getElementById('yardlii_radius_tooltip');
    const locateBtn = document.getElementById('yardlii_locate_me');
    let autocomplete, geocoder;

    // --- Function to Initialize Google Places Autocomplete (FIXED RACE CONDITION) ---
    function initHomepageAutocomplete() {
        // [MODIFIED] Only check if the input is present. We trust the event means the API is loading/ready.
        if (!input) {
            console.warn('YARDLII: Location input missing for autocomplete init.');
            return;
        }

        // Prevent double initialization (e.g., from immediate call + event listener)
        if (input.dataset.autoInit) return;
        input.dataset.autoInit = 'true';

        // [CRITICAL CHECK] Ensure the google object is defined by the script loader
        if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
            console.warn('YARDLII: Google object is not yet defined. Deferring.');
            // Defer: Exit and rely on the next call when the DOM is ready or event fires again.
            return;
        }

        console.log('YARDLII: Initializing Google Places autocomplete...');
        autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['(cities)'],
            fields: ['formatted_address', 'geometry']
        });
        geocoder = new google.maps.Geocoder();
    }

    // --- Hook the initialization to the router event ---
    document.addEventListener('yardliiGoogleMapsLoaded', initHomepageAutocomplete);

    // [REMOVED/FIXED] The original synchronous check block on lines 56-64 is replaced 
    // by calling the function once immediately, which safely checks the google object.
    if (typeof google !== 'undefined' && google.maps && google.maps.places) {
        initHomepageAutocomplete();
    }



    // --- Initialize Google Places Autocomplete ---
    if (input && typeof google !== 'undefined' && google.maps && google.maps.places) {
      console.log('YARDLII: Initializing Google Places autocomplete...');
      autocomplete = new google.maps.places.Autocomplete(input, {
        types: ['(cities)'],
        fields: ['formatted_address', 'geometry']
      });
      geocoder = new google.maps.Geocoder();
    } else {
      console.warn('YARDLII: Google Maps Places API not loaded or input missing.');
    }

 // === 📍 Compact Compass Locate Feature ===
const locateIcon = document.getElementById('yardlii_locate_me');
if (locateIcon && typeof google !== 'undefined' && google.maps) {
  locateIcon.addEventListener('click', function() {
    if (!navigator.geolocation) {
      alert('Geolocation is not supported by your browser.');
      return;
    }

    locateIcon.classList.add('locating');

    navigator.geolocation.getCurrentPosition(
      function(position) {
        const lat = position.coords.latitude;
        const lng = position.coords.longitude;
        const latlng = { lat: lat, lng: lng };
        const geocoder = new google.maps.Geocoder();

        geocoder.geocode({ location: latlng }, function(results, status) {
          if (status === 'OK' && results[0]) {
            const address = results[0].formatted_address;
            $('#yardlii_location_input').val(address);
            console.log('YARDLII: Current location →', address);
          } else {
            alert('Unable to determine your location. Please type manually.');
          }
          locateIcon.classList.remove('locating');
        });
      },
      function() {
        alert('Unable to access your location. Please allow permission.');
        locateIcon.classList.remove('locating');
      }
    );
  });
}


    // --- Handle form submission ---
    form.on('submit', function(e) {
      const address = input ? input.value.trim() : '';
      const distance = range ? range.value : 25;

      if (!address || typeof google === 'undefined' || !google.maps) {
        return true;
      }

      e.preventDefault(); // Wait for geocode before submit

      geocoder.geocode({ address: address }, function(results, status) {
        if (status === 'OK' && results[0]) {
          const loc = results[0].geometry.location;
          const lat = loc.lat();
          const lng = loc.lng();
          const encoded = `${lat},${lng},${distance},${address}`;

          // Remove any previous _location fields
          form.find('input[name="_location"]').remove();

          // Add hidden _location field
          $('<input>')
            .attr({
              type: 'hidden',
              name: '_location',
              value: encoded
            })
            .appendTo(form);

          console.log('YARDLII: Geocoded & submitting →', encoded);
          form.off('submit').submit();
        } else {
          console.warn('YARDLII: Geocode failed, submitting plain address.');
          form.off('submit').submit();
        }
      });
    });
  });
})(jQuery);

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