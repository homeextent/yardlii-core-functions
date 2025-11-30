<?php

declare(strict_types=1);

namespace Yardlii\Core\Features;

/**
 * Feature: Google Map API Key Management
 * Centralizes the API key registration and prevents conflicts.
 */
class GoogleMapKey {

    public const OPTION_KEY = 'yardlii_google_map_key';
    public const API_HANDLE = 'google-maps-api';

    public function register(): void {
        add_action('acf/init', [$this, 'apply_api_key']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_master_api'], 5); 
        add_filter('facetwp_load_gmaps', '__return_false'); 
        
        add_action('admin_init', [$this, 'register_settings']);
        add_filter('facetwp_map_init_args', [$this, 'apply_map_controls']);
        add_action('wp_ajax_yardlii_test_google_map_key', [$this, 'ajax_test_google_map_key']);
    }

    /**
     * The Master Enqueue
     * Loads the API with the 'places' library for everyone to use.
     */
   public function enqueue_master_api(): void {
        $key = get_option(self::OPTION_KEY, '');
        
        if (empty($key) || !is_string($key)) {
            return;
        }

        // 1. Register Router (HEADER - Priority)
        if (!wp_script_is('yardlii-maps-router', 'registered')) {
            wp_register_script(
                'yardlii-maps-router',
                defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL . 'assets/js/google-maps-router.js' : '', 
                [], 
                defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0', 
                false // Load in Header
            );
            wp_enqueue_script('yardlii-maps-router');
        }

        // 2. Deregister conflicts
        if (wp_script_is('google-maps-places', 'enqueued')) {
            wp_dequeue_script('google-maps-places');
        }
        
        // 3. Register API with Callback
        if (!wp_script_is(self::API_HANDLE, 'registered')) {
            $url = 'https://maps.googleapis.com/maps/api/js';
            $args = [
                'key'       => $key,
                'libraries' => 'places,geometry', 
                'loading'   => 'async',
                'v'         => 'weekly',
                'callback'  => 'yardliiInitAutocomplete'
            ];
            
            $final_url = add_query_arg($args, $url);

            // Load in HEADER (false) to guarantee availability for FacetWP
            wp_enqueue_script(self::API_HANDLE, $final_url, ['yardlii-maps-router'], null, false);
        }
    }

    public function apply_api_key(): void {
        $key = get_option(self::OPTION_KEY, '');
        if ($key && function_exists('acf_update_setting')) {
            acf_update_setting('google_api_key', $key);
        }
    }

    /**
     * AJAX: Test the API Key validity
     */
    public function ajax_test_google_map_key(): void {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $key = get_option(self::OPTION_KEY, '');
        
        if (empty($key)) {
            wp_send_json_error(['message' => 'No API key saved.']);
        }

        wp_send_json_success(['message' => 'API Key is saved.']);
    }

    public function register_settings(): void {
        // Placeholder
    }

    /**
     * Sanitize map controls input
     * @param mixed $input
     * @return array<string, string>
     */
    public function sanitize_map_controls($input): array {
        if (!is_array($input)) {
            return [];
        }
        // array_map returns array, we cast to ensure PHPStan knows it's string[]
        /** @var array<string, string> */
        return array_map('sanitize_text_field', $input);
    }

    /**
     * Apply map controls to FacetWP
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function apply_map_controls(array $args): array {
        $controls = get_option('yardlii_map_controls', []);
        if (is_array($controls) && !empty($controls)) {
            $args = array_merge($args, $controls);
        }
        return $args;
    }
}