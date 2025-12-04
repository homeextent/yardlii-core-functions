/**
 * YARDLII: Universal Frontend Logic
 * Includes: Map Rendering, Location Search, and Mobile Viewport Fixes.
 * Version: 3.26.3 - Consolidated with Map Widget
 */
(function($) {
  $(document).ready(function() {
    
    // =========================================================
    // 1. HOMEPAGE & DIRECTORY SEARCH LOGIC
    // =========================================================
    const form = $('.yardlii-search-form');
    const input = document.getElementById('yardlii_location_input');
    const range = document.getElementById('yardlii_radius_range');
    const tooltip = document.getElementById('yardlii_radius_tooltip');
    const locateBtn = document.getElementById('yardlii_locate_me');
    let autocomplete, geocoder;

    // --- Live tooltip updater (mobile-safe) ---
    if (range && tooltip) {
      const min = parseInt(range.min, 10);
      const max = parseInt(range.max, 10);
      let hideTimeout;

      const updateTooltip = () => {
        const val = parseInt(range.value, 10);
        tooltip.textContent = val + ' km';
        const percent = ((val - min) / (max - min)) * 100;
        tooltip.style.left = `calc(${percent}% + (${8 - percent * 0.16}px))`;

        // Show tooltip
        tooltip.classList.add('active');
        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(() => tooltip.classList.remove('active'), 2000);
      };

      // Listen to both mouse + touch + pointer events (for iOS)
      range.addEventListener('input', updateTooltip);
      range.addEventListener('pointerdown', () => {
        tooltip.classList.add('active');
        clearTimeout(hideTimeout);
      });
      range.addEventListener('pointerup', () => {
        clearTimeout(hideTimeout);
        hideTimeout = setTimeout(() => tooltip.classList.remove('active'), 2000);
      });

      updateTooltip(); // initialize once
    }

    // --- Initialize Google Places Autocomplete ---
    if (input && typeof google !== 'undefined' && google.maps && google.maps.places) {
      // console.log('YARDLII: Initializing Google Places autocomplete...');
      autocomplete = new google.maps.places.Autocomplete(input, {
        types: ['(cities)'],
        fields: ['formatted_address', 'geometry']
      });
      geocoder = new google.maps.Geocoder();
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

      e.preventDefault(); 

      geocoder.geocode({ address: address }, function(results, status) {
        if (status === 'OK' && results[0]) {
          const loc = results[0].geometry.location;
          const lat = loc.lat();
          const lng = loc.lng();
          const encoded = `${lat},${lng},${distance},${address}`;

          form.find('input[name="_location"]').remove();
          $('<input>').attr({type: 'hidden', name: '_location', value: encoded}).appendTo(form);
          form.off('submit').submit();
        } else {
          form.off('submit').submit();
        }
      });
    });

    // =========================================================
    // 2. NEW: ELEMENTOR MAP WIDGET LOGIC (Migrated)
    // =========================================================
    const initMapWidget = function() {
        // Look for the container ID used by the widget
        const $mapContainer = $('#yardlii-google-map'); 
        
        if ($mapContainer.length && !$mapContainer.data('init-done')) {
            if (typeof google === 'undefined' || !google.maps) return;
            
            // Extract data attributes
            const lat = parseFloat($mapContainer.data('lat')) || 43.159374;
            const lng = parseFloat($mapContainer.data('lng')) || -79.246864;
            const zoom = parseInt($mapContainer.data('zoom')) || 10;
            
            const map = new google.maps.Map($mapContainer[0], {
                center: { lat: lat, lng: lng },
                zoom: zoom,
                mapTypeId: google.maps.MapTypeId.ROADMAP,
                disableDefaultUI: false
            });
            
            new google.maps.Marker({
                position: { lat: lat, lng: lng },
                map: map
            });
            
            $mapContainer.data('init-done', true);
        }
    };

    // Run init immediately if Google is ready, or wait for loader
    if (typeof google !== 'undefined' && google.maps) {
        initMapWidget();
    }
    document.addEventListener('yardliiGoogleMapsLoaded', initMapWidget);

  }); // End Ready

  // =========================================================
  // 3. FACETWP AUTO-REFRESH
  // =========================================================
  if (window.location.pathname.includes('/listings')) {
    let firstLoad = true;
    $(document).on('facetwp-loaded', function() {
      if (firstLoad) {
        const $locFacet = $('[data-name="location"] input.facetwp-location');
        if ($locFacet.length && $locFacet.val().trim() !== '') {
          FWP.refresh();
        }
        firstLoad = false;
      }
    });
  }

  // =========================================================
  // 4. GLOBAL MOBILE VIEWPORT FIX (The Phantom Spacer)
  // =========================================================
  document.addEventListener('focusin', function(e) {
      const target = e.target;

      const isLocationInput = (
          target.closest('.yardlii-city-autocomplete') ||        
          target.classList.contains('yardlii-location-input') || 
          target.id === 'yardlii_location_input' ||              
          target.classList.contains('facetwp-location') ||       
          target.closest('.fwp-location-search') ||              
          target.id === 'fwp-location-search'                    
      );

      if (!isLocationInput) return;
      if (window.innerWidth >= 768) return;

      // Popup Awareness
      const flyout = target.closest('.facetwp-flyout');
      let injectionTarget = document.body;
      if (flyout) {
          const flyoutContent = flyout.querySelector('.facetwp-flyout-content');
          injectionTarget = flyoutContent || flyout;
      }

      // Inject Spacer
      let spacer = document.getElementById('yardlii-mobile-spacer');
      if (!spacer) {
          spacer = document.createElement('div');
          spacer.id = 'yardlii-mobile-spacer';
          spacer.style.height = '45vh'; 
          spacer.style.width = '100%';
          spacer.style.minHeight = '300px'; 
          spacer.style.pointerEvents = 'none';
          spacer.style.backgroundColor = 'transparent'; 
          injectionTarget.appendChild(spacer);
      }

      // Scroll
      setTimeout(() => {
          target.scrollIntoView({ behavior: "smooth", block: "start" });
      }, 300);
  });

  document.addEventListener('focusout', function(e) {
      setTimeout(() => {
          const spacer = document.getElementById('yardlii-mobile-spacer');
          if (spacer) spacer.remove();
      }, 200);
  });

})(jQuery);