/**
 * YARDLII: WPUF City Autocomplete
 */
document.addEventListener('yardliiGoogleMapsLoaded', function() {
    console.log('[YARDLII] WPUF Autocomplete: API Ready. Attaching...');
    
    const attachAutocomplete = (input) => {
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

        // FIX: Dispatch 'change' event on selection so Directory knows to filter
        autocomplete.addListener('place_changed', function() {
            input.dispatchEvent(new Event('change', { bubbles: true }));
        });
    };

    // Target both WPUF forms AND Directory inputs
    const selector = '.yardlii-city-autocomplete input[type="text"], input.yardlii-filter-location';
    const inputs = document.querySelectorAll(selector);
    inputs.forEach(attachAutocomplete);
});