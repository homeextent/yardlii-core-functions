/**
 * YARDLII: WPUF City Autocomplete (Async-Safe)
 * Enhances standard text inputs with Google Places (Cities only).
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // 1. The Logic to Attach Autocomplete
    const attachAutocomplete = (input) => {
        if (input.dataset.yardliiCityInit) return; // Prevent double init
        
        const options = {
            types: ['(cities)'],
            componentRestrictions: { country: ['ca', 'us'] },
            fields: ['formatted_address', 'name', 'geometry']
        };

        const autocomplete = new google.maps.places.Autocomplete(input, options);

        input.setAttribute('autocomplete', 'off');
        input.setAttribute('placeholder', 'Start typing your city...');
        input.dataset.yardliiCityInit = 'true';

        // Keep the input clean
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            // Optional: You can force the input to the formatted address here
            // if (place.formatted_address) { input.value = place.formatted_address; }
        });
    };

    // 2. The Initializer
    const init = () => {
        const inputs = document.querySelectorAll('.yardlii-city-autocomplete input[type="text"]');
        if (inputs.length > 0) {
            inputs.forEach(attachAutocomplete);
        }
    };

    // 3. The "Wait for Google" Loop
    // Since the API loads async, we check every 100ms until it's ready.
    const checkGoogle = setInterval(() => {
        if (typeof google !== 'undefined' && typeof google.maps !== 'undefined' && typeof google.maps.places !== 'undefined') {
            clearInterval(checkGoogle); // Stop checking
            init(); // Run logic
        }
    }, 100);

    // 4. Fallback: Stop trying after 10 seconds to save memory
    setTimeout(() => clearInterval(checkGoogle), 10000);
});