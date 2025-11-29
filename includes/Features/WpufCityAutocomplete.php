<?php

declare(strict_types=1);

namespace Yardlii\Core\Features;

/**
 * Feature: WPUF City Autocomplete
 * Enhances text fields with class 'yardlii-city-autocomplete' to use Google Places.
 */
class WpufCityAutocomplete {

    private string $coreUrl;
    private string $coreVersion;

    public function __construct(string $coreUrl, string $coreVersion)
    {
        $this->coreUrl = $coreUrl;
        $this->coreVersion = $coreVersion;
    }

    public function register(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(): void {
        // 1. Only load if WPUF is potentially active
        if (!class_exists('WPUF_Main')) {
            return;
        }

        // 2. Reuse API Key from Core Settings
        $api_key = get_option('yardlii_google_map_key', '');
        if (empty($api_key)) {
            return;
        }

        // 3. Register Google Maps (if not already by BusinessDirectory or others)
        if (!wp_script_is('yardlii-google-places', 'registered')) {
            wp_register_script(
                'yardlii-google-places', 
                "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places&loading=async", 
                [], 
                null, 
                true
            );
        }

        // 4. Register Our Logic using injected properties
        wp_register_script(
            'yardlii-wpuf-autocomplete',
            $this->coreUrl . 'assets/js/wpuf-city-autocomplete.js',
            ['yardlii-google-places'], 
            $this->coreVersion,
            true
        );

        // 5. Enqueue
        wp_enqueue_script('yardlii-wpuf-autocomplete');
    }
}