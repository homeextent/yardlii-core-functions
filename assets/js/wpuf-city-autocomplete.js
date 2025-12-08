/**
 * YARDLII: WPUF City Autocomplete (Initialization Only)
 * Note: Mobile viewport fixes are handled globally in frontend.js
 */
(function() {
    const INPUT_SELECTOR = '.yardlii-city-autocomplete input[type="text"]';
    
    // Function that performs the actual initialization on a single input
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

    const init = function() {
        const inputs = document.querySelectorAll(INPUT_SELECTOR);
        inputs.forEach(attachAutocomplete);
    };

    // [CRITICAL FIX: Use MutationObserver for dynamic content]
    const observer = new MutationObserver((mutationsList, observer) => {
        // Run init() on every DOM change that affects the inputs
        init();
    });

    // We observe the body for configuration changes
    observer.observe(document.body, { childList: true, subtree: true });

    // Stop observing after 5 seconds to prevent performance issues
    setTimeout(() => {
        observer.disconnect();
        // One final check
        init(); 
    }, 5000); 

    // Initial run and event listener remain the same:
    document.addEventListener('yardliiGoogleMapsLoaded', init);
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof google !== 'undefined' && google.maps && google.maps.places) {
            init();
        }
    });
})();