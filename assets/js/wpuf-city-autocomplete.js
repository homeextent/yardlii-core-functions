/**
 * YARDLII: WPUF City Autocomplete (Callback Strategy)
 * Ensures init only happens exactly when Google Maps is ready.
 */

// 1. Define the Logic Function
function yardliiAttachCityAutocomplete() {
    const inputs = document.querySelectorAll('.yardlii-city-autocomplete input[type="text"]');
    
    if (inputs.length === 0) return;

    inputs.forEach(input => {
        if (input.dataset.yardliiCityInit) return;

        const options = {
            types: ['(cities)'],
            componentRestrictions: { country: ['ca', 'us'] },
            fields: ['formatted_address', 'name', 'geometry']
        };

        const autocomplete = new google.maps.places.Autocomplete(input, options);

        input.setAttribute('autocomplete', 'off');
        input.setAttribute('placeholder', 'Start typing your city...');
        input.dataset.yardliiCityInit = 'true';
    });
}

// 2. Define the Global Callback (Must match the PHP param)
window.yardliiInitAutocomplete = function() {
    console.log('[YARDLII] Google Maps Callback Fired.');
    yardliiAttachCityAutocomplete();
};

// 3. Fallback Safety (In case the API was already loaded by another plugin without our callback)
document.addEventListener('DOMContentLoaded', function() {
    if (typeof google !== 'undefined' && typeof google.maps !== 'undefined' && typeof google.maps.places !== 'undefined') {
        yardliiAttachCityAutocomplete();
    }
});