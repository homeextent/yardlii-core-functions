<?php

declare(strict_types=1);

namespace Yardlii\Core\Features\WPUF;
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
        // Load in Header (Priority 5) to ensure it is defined before Google Maps (Priority 20)
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 5); 
    }

    public function enqueue_assets(): void {
        // REMOVED: class_exists check. We load if the feature is enabled.
        
        // Depend on the master handle defined in GoogleMapKey.php
        $handle = 'google-maps-api';

        // Register Our Logic
        wp_register_script(
            'yardlii-wpuf-autocomplete',
            $this->coreUrl . 'assets/js/wpuf-city-autocomplete.js',
            [], // No dependency on Google Maps itself, because WE are the callback
            $this->coreVersion,
            false // Load in Header
        );

        // Enqueue
        wp_enqueue_script('yardlii-wpuf-autocomplete');
    }
}