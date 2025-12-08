/**
 * YARDLII: WPUF City Autocomplete (Initialization Only)
 * Note: Mobile viewport fixes are handled globally in frontend.js
 */
(function() {
    const INPUT_SELECTOR = '.yardlii-city-autocomplete input[type="text"]';
    
    // [CRITICAL FIX: RESTORE FUNCTIONALITY]
    const attachAutocomplete = (input) => {
        if (input.dataset.yardliiCityInit) return;
        
        // Use the explicit check here for robustness
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
    // [END CRITICAL FIX]

    const init = function() {
        const inputs = document.querySelectorAll(INPUT_SELECTOR);
        inputs.forEach(attachAutocomplete); // <-- This call is now valid
    };

    // [CRITICAL FIX: Move MutationObserver logic inside DOMContentLoaded block]
    document.addEventListener('DOMContentLoaded', function() {
        // Run initial scan (required by the existing logic)
        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            init();
        }

        // --- NEW/FIXED MutationObserver Setup ---
        // We only proceed if document.body is confirmed to be a Node (i.e., not null).
        if (document.body) {
            const observer = new MutationObserver((mutationsList, observer) => {
                // Run init() on every DOM change that affects the inputs
                init();
            });

            // Start observing the body *only* now that we are in DOMContentLoaded
            observer.observe(document.body, { childList: true, subtree: true });

            // Stop observing after 5 seconds to prevent performance issues
            setTimeout(() => {
                observer.disconnect();
                // One final check
                init(); 
            }, 5000);
        }
    });

    // Event listener for the Google Maps API load remains outside DOMContentLoaded for timing
    document.addEventListener('yardliiGoogleMapsLoaded', init);
})();