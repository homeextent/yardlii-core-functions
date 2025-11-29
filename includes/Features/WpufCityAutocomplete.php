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
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets'], 30); // Load AFTER GoogleMapKey
    }

    public function enqueue_assets(): void {
        if (!class_exists('WPUF_Main')) {
            return;
        }

        // Depend on the master handle defined in GoogleMapKey.php
        $handle = 'google-maps-api';

        // Register Our Logic
        wp_register_script(
            'yardlii-wpuf-autocomplete',
            $this->coreUrl . 'assets/js/wpuf-city-autocomplete.js',
            [$handle], // Dependency ensures Maps loads first
            $this->coreVersion,
            true
        );

        // Enqueue
        wp_enqueue_script('yardlii-wpuf-autocomplete');
    }
}