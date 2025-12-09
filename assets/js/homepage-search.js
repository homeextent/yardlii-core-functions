/**
 * YARDLII Core - Homepage Search & Geolocation Logic
 * Version: 3.29.0 (Decoupled from frontend.js)
 * * Logic includes:
 * - Autocomplete attachment (targets #yardlii_location_input)
 * - Locate Me button functionality (Browser Geolocation)
 * - Form submission interception (Geocoding and FacetWP data formatting)
 */
(function($) {
  $(document).ready(function() {
    const form = $('.yardlii-search-form');
    const input = document.getElementById('yardlii_location_input');
    const range = document.getElementById('yardlii_radius_range');
    const tooltip = document.getElementById('yardlii_radius_tooltip');
    const locateBtn = document.getElementById('yardlii_locate_me');
    let autocomplete, geocoder;

    // --- Function to Initialize Google Places Autocomplete ---
    function initHomepageAutocomplete() {
        const input = document.getElementById('yardlii_location_input');
        
        if (!input) {
            return; 
        }

        // Prevent double initialization (e.g., from immediate call + event listener)
        if (input.dataset.autoInit) return;
        input.dataset.autoInit = 'true';

        // We rely on the event listener to ensure the google object is defined.

        console.log('YARDLII: Initializing Google Places autocomplete...');
        autocomplete = new google.maps.places.Autocomplete(input, {
            types: ['(cities)'],
            fields: ['formatted_address', 'geometry']
        });
        geocoder = new google.maps.Geocoder();
    }

    // --- Hook the initialization to the router event ---
    document.addEventListener('yardliiGoogleMapsLoaded', initHomepageAutocomplete);

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

  }); // END document.ready
})(jQuery);