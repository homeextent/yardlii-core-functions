<?php

declare(strict_types=1);

namespace Yardlii\Core\Features;

/**
 * Feature: WPUF City Autocomplete
 * Enhances text fields with class 'yardlii-city-autocomplete' to use Google Places.
 */
class WpufCityAutocomplete {

    public function register(): void {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(): void {
        // 1. Only load if WPUF is potentially active (we can't easily check for specific shortcodes 
        // without parsing post content, so we load globally or check for WPUF class presence)
        if (!class_exists('WPUF_Main')) {
            return;
        }

        // 2. Reuse API Key from Core Settings
        $api_key = get_option('yardlii_google_map_key', '');
        if (empty($api_key)) {
            return;
        }

        // 3. Register Google Maps (if not already by BusinessDirectory or others)
        // We use the same handle 'yardlii-google-places' to share resources
        if (!wp_script_is('yardlii-google-places', 'registered')) {
            wp_register_script(
                'yardlii-google-places', 
                "https://maps.googleapis.com/maps/api/js?key={$api_key}&libraries=places&loading=async", 
                [], 
                null, 
                true
            );
        }

        // 4. Register Our Logic
        wp_register_script(
            'yardlii-wpuf-autocomplete',
            YARDLII_CORE_URL . 'assets/js/wpuf-city-autocomplete.js',
            ['yardlii-google-places'], // Dependency ensures Maps loads first
            YARDLII_CORE_VERSION,
            true
        );

        // 5. Enqueue
        wp_enqueue_script('yardlii-wpuf-autocomplete');
    }
}