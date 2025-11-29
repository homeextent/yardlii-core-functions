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
    };

    const inputs = document.querySelectorAll('.yardlii-city-autocomplete input[type="text"]');
    inputs.forEach(attachAutocomplete);
});