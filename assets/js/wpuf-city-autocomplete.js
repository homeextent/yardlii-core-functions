/**
 * YARDLII: WPUF City Autocomplete (Initialization Only)
 * Note: Mobile viewport fixes are handled globally in frontend.js
 */
(function() {
    const init = function() {
        // Only attach to specific WPUF/Pro inputs that need initialization
        const attachAutocomplete = (input) => {
            if (input.dataset.yardliiCityInit) return;
            
            if (typeof google === 'undefined' || !google.maps || !google.maps.places) return;

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

        const selector = '.yardlii-city-autocomplete input[type="text"]';
        const inputs = document.querySelectorAll(selector);
        inputs.forEach(attachAutocomplete);
    };

    document.addEventListener('yardliiGoogleMapsLoaded', init);
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            init();
        }
    });
})();