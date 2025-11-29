/**
 * YARDLII: WPUF City Autocomplete
 * Enhances standard text inputs with Google Places (Cities only).
 */
document.addEventListener('DOMContentLoaded', function() {
    
    // Helper to init autocomplete on a specific input
    const attachAutocomplete = (input) => {
        if (input.dataset.yardliiCityInit) return; // Prevent double init
        
        // Configuration: Cities only to protect privacy (no street addresses)
        const options = {
            types: ['(cities)'],
            componentRestrictions: { country: ['ca', 'us'] }, // Limit to relevant markets
            fields: ['formatted_address', 'name', 'geometry']
        };

        const autocomplete = new google.maps.places.Autocomplete(input, options);

        // UI enhancements
        input.setAttribute('autocomplete', 'off'); // Stop browser history suggestions
        input.setAttribute('placeholder', 'Start typing your city...');
        input.dataset.yardliiCityInit = 'true';

        // Optional: Ensure the text stays clean
        autocomplete.addListener('place_changed', function() {
            const place = autocomplete.getPlace();
            // If user just typed text without selecting, place.geometry is undefined.
            // We generally let the text stick, but you could enforce selection here.
        });
    };

    // 1. Init on Load (for standard forms)
    // WPUF applies the custom class to the <li> wrapper, not the input itself.
    const inputs = document.querySelectorAll('.yardlii-city-autocomplete input[type="text"]');
    inputs.forEach(attachAutocomplete);

    // 2. Observer (Optional) - In case WPUF loads steps dynamically/via AJAX
    // For simple forms, the above is enough. For multi-step, we might need more logic later.
});