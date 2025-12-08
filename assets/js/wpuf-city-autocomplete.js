/**
 * YARDLII: WPUF City Autocomplete (Initialization Only)
 * Note: Mobile viewport fixes are handled globally in frontend.js
 */
(function() {
    const INPUT_SELECTOR = '.yardlii-city-autocomplete input[type="text"]';
    
    // Function that performs the actual initialization on a single input (attachAutocomplete)
    // ... (This function remains unchanged) ...

    const init = function() {
        const inputs = document.querySelectorAll(INPUT_SELECTOR);
        inputs.forEach(attachAutocomplete);
    };

    // [CRITICAL FIX] Move MutationObserver logic inside DOMContentLoaded block
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