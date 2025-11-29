<?php

declare(strict_types=1);

namespace Yardlii\Core\Features;

/**
 * Feature: Google Map API Key Management
 * Centralizes the API key registration and prevents conflicts.
 */
class GoogleMapKey {

    private const API_HANDLE = 'google-maps-api';

    public function register(): void {
        add_action('acf/init', [$this, 'apply_api_key']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_master_api'], 20); // Priority 20 to run after others
        add_filter('facetwp_load_gmaps', '__return_false'); // STOP FacetWP from loading its own API
        add_filter('script_loader_tag', [$this, 'add_async_defer'], 10, 2);
        
        // Admin settings registration
        add_action('admin_init', [$this, 'register_settings']);
        add_filter('facetwp_map_init_args', [$this, 'apply_map_controls']);
        add_action('wp_ajax_yardlii_test_google_map_key', [$this, 'ajax_test_google_map_key']);
    }

    /**
     * The Master Enqueue
     * Loads the API with the 'places' library for everyone to use.
     */
    public function enqueue_master_api(): void {
        $key = get_option('yardlii_google_map_key', '');
        
        if (empty($key)) {
            return;
        }

        // Deregister conflicting handles if they exist
        if (wp_script_is('google-maps-places', 'enqueued')) {
            wp_dequeue_script('google-maps-places');
        }
        
        // Register our Master Instance
        if (!wp_script_is(self::API_HANDLE, 'registered')) {
            $url = 'https://maps.googleapis.com/maps/api/js';
            $args = [
                'key'       => $key,
                'libraries' => 'places,geometry', // Load both places and geometry (for distance)
                'loading'   => 'async',
                'v'         => 'weekly'
            ];
            
            $final_url = add_query_arg($args, $url);

            wp_enqueue_script(self::API_HANDLE, $final_url, [], null, true);
        }
    }

    /**
     * Optimization: Async/Defer for performance
     */
    public function add_async_defer($tag, $handle) {
        if ($handle === self::API_HANDLE) {
            return str_replace(' src', ' async defer src', $tag);
        }
        return $tag;
    }

    public function apply_api_key(): void {
        $key = get_option('yardlii_google_map_key', '');
        if ($key && function_exists('acf_update_setting')) {
            acf_update_setting('google_api_key', $key);
        }
    }

    // ... (Keep existing ajax_test_google_map_key, register_settings, etc.) ...
    // NOTE: Copy the rest of the existing methods (register_settings, sanitize, etc.) from your current file here.
    // I am omitting them for brevity, but do not delete them!
    
    public function ajax_test_google_map_key(): void {
        // ... existing logic ...
    }

    public function register_settings(): void {
        // ... existing logic ...
    }

    public function sanitize_map_controls($input) {
        // ... existing logic ...
    }

    public function apply_map_controls($args) {
        // ... existing logic ...
    }
}