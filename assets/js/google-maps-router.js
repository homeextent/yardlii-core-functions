/**
 * YARDLII: Google Maps Global Router
 * Defines the single callback that Google Maps API looks for.
 * Dispatches a custom event so multiple features can hook into it.
 */

window.yardliiInitAutocomplete = function() {
    console.log('[YARDLII] Google Maps Router: API Loaded. Dispatching event...');
    
    // Dispatch a custom event that other scripts can listen for
    const event = new Event('yardliiGoogleMapsLoaded');
    document.dispatchEvent(event);
};