/**
 * YARDLII: WPUF City Autocomplete (Compatibility Mode)
 * Handles both Event-Driven (Async) and Immediate (Sync) loading.
 */
(function() {
    const init = function() {
        console.log('[YARDLII] WPUF Autocomplete: Initializing...');
        
        const attachAutocomplete = (input) => {
            if (input.dataset.yardliiCityInit) return;
            
            // Safety check
            if (typeof google === 'undefined' || !google.maps || !google.maps.places) {
                return;
            }

            const options = {
                types: ['(cities)'],
                componentRestrictions: { country: ['ca', 'us'] },
                fields: ['formatted_address', 'name', 'geometry']
            };

            const autocomplete = new google.maps.places.Autocomplete(input, options);
            input.setAttribute('autocomplete', 'off');
            input.setAttribute('placeholder', 'Start typing your city...');
            input.dataset.yardliiCityInit = 'true';

            autocomplete.addListener('place_changed', function() {
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        };

        const selector = '.yardlii-city-autocomplete input[type="text"], input.yardlii-filter-location';
        const inputs = document.querySelectorAll(selector);
        inputs.forEach(attachAutocomplete);
    };

    // 1. Listen for the Event (Async/Footer loading)
    document.addEventListener('yardliiGoogleMapsLoaded', init);

    // 2. Check Immediate Availability (Sync/Header loading)
    // This catches the case where Google loaded before this script ran.
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            init();
        }
    });
})();