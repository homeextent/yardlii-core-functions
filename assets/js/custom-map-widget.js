/* eslint-disable */
(function($) {
    "use strict";

    /**
     * YARDLII: Custom Map Widget (Integrated)
     * Listens for the Core 'yardliiGoogleMapsLoaded' event to ensure API readiness.
     */
    
    function initCustomMaps() {
        // Safety: Check for Google Maps Global
        if (typeof google === 'undefined' || typeof google.maps === 'undefined') {
            console.error('[YARDLII][MAPS] Google Maps API not found. Waiting for global event...');
            return;
        }

        const $containers = $('.cegm-map-container:not([data-cegm-initialized])');

        if ($containers.length === 0) return;

        console.log(`[YARDLII][MAPS] Found ${$containers.length} widget(s). Initializing...`);

        $containers.each(function() {
            const $this = $(this);
            $this.attr('data-cegm-initialized', 'true');

            // 1. Extract Data
            const address = $this.data('address');
            if (!address) return;

            const config = {
                element: this,
                address: address,
                zoom: parseInt($this.data('zoom')) || 14,
                showRadius: $this.data('show-radius') === 'yes',
                autoZoom: $this.data('auto-zoom') === 'yes',
                radiusMeters: (parseFloat($this.data('radius-km')) || 1) * 1000,
                radiusFillColor: parseColor($this.data('radius-fill-color')).color,
                radiusFillOpacity: parseColor($this.data('radius-fill-color')).opacity,
                radiusStrokeColor: parseColor($this.data('radius-stroke-color')).color,
                radiusStrokeOpacity: parseColor($this.data('radius-stroke-color')).opacity,
                radiusStrokeWeight: parseInt($this.data('radius-stroke-weight')) || 1
            };

            renderMap(config);
        });
    }

    function renderMap(cfg) {
        const geocoder = new google.maps.Geocoder();

        geocoder.geocode({ address: cfg.address }, function(results, status) {
            if (status === 'OK' && results[0]) {
                const center = results[0].geometry.location;

                const map = new google.maps.Map(cfg.element, {
                    center: center,
                    zoom: cfg.zoom,
                    mapId: 'YARDLII_WIDGET_MAP', // Required for Advanced Markers if used
                    mapTypeControl: false,
                    streetViewControl: false
                });

                // Marker Logic: Try AdvancedMarkerElement (if 'marker' lib loaded), else fallback
                if (google.maps.marker && google.maps.marker.AdvancedMarkerElement) {
                    new google.maps.marker.AdvancedMarkerElement({
                        map: map,
                        position: center,
                        title: cfg.address
                    });
                } else {
                    new google.maps.Marker({
                        map: map,
                        position: center,
                        title: cfg.address
                    });
                }

                // Radius Logic
                if (cfg.showRadius) {
                    const circle = new google.maps.Circle({
                        strokeColor: cfg.radiusStrokeColor,
                        strokeOpacity: cfg.radiusStrokeOpacity,
                        strokeWeight: cfg.radiusStrokeWeight,
                        fillColor: cfg.radiusFillColor,
                        fillOpacity: cfg.radiusFillOpacity,
                        map: map,
                        center: center,
                        radius: cfg.radiusMeters
                    });

                    if (cfg.autoZoom) {
                        map.fitBounds(circle.getBounds());
                    }
                }

            } else {
                console.warn(`[YARDLII][MAPS] Geocode failed for "${cfg.address}": ${status}`);
                $(cfg.element).html(`<div style="color:#d63638;padding:20px;text-align:center;">Map Location Error</div>`);
            }
        });
    }

    // Helper: Color Parser
    function parseColor(colorString) {
        if (!colorString) return { color: '#4285F4', opacity: 0.3 };
        // Basic hex handling
        if (colorString.startsWith('#')) return { color: colorString, opacity: 0.3 };
        
        // Elementor rgba handling
        if (colorString.includes('rgba')) {
            const parts = colorString.match(/[\d.]+/g);
            if (parts && parts.length >= 4) {
                return {
                    color: `rgb(${parts[0]},${parts[1]},${parts[2]})`,
                    opacity: parseFloat(parts[3])
                };
            }
        }
        return { color: colorString, opacity: 0.3 };
    }

    // --- EVENT LISTENERS ---

    // 1. Listen for the Core Traffic Controller event (Primary)
    document.addEventListener('yardliiGoogleMapsLoaded', initCustomMaps);

    // 2. Elementor Editor Hook (Secondary)
    // The editor might load AFTER the event has fired, so we hook into Elementor's init
    $(window).on('elementor/frontend/init', function() {
        if (typeof elementorFrontend !== 'undefined') {
            elementorFrontend.hooks.addAction('frontend/element_ready/custom_google_map.default', function($scope) {
                // If Google is ready, init. If not, we wait for the event above.
                if (typeof google !== 'undefined' && google.maps) {
                    // Reset for editor redraws
                    const $container = $scope.find('.cegm-map-container');
                    $container.removeAttr('data-cegm-initialized').empty();
                    initCustomMaps();
                }
            });
        }
    });

})(jQuery);