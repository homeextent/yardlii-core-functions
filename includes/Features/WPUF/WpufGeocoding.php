<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\WPUF;
use Yardlii\Core\Services\Logger;

/**
 * Feature: WPUF Geocoding (Privacy Focused)
 */
class WpufGeocoding {

    const OPTION_MAPPING = 'yardlii_wpuf_geo_mapping';
    // 1. Define the Action Scheduler hook
    const ASYNC_HOOK = 'yardlii_geo_process_post'; //

    public function register(): void {
        // [MODIFICATION] Keep the initial synchronous hooks for post-creation/update to fire the scheduler.
        add_action('wpuf_add_post_after_insert', [$this, 'handle_submission'], 10, 4);
        add_action('wpuf_update_post_after_submit', [$this, 'handle_submission'], 10, 4);
        add_action('wp_ajax_yardlii_test_geocoding', [$this, 'ajax_test_geocoding']);
        add_filter('facetwp_proximity_store_keys', [$this, 'map_facetwp_keys']);
        
        // 2. Register the Deferred Handler (Action Scheduler)
        if (function_exists('as_enqueue_async_action')) {
            add_action(self::ASYNC_HOOK, [$this, 'deferred_process_post'], 10, 1);
        }
    }

    /**
     * @param array<mixed> $keys
     * @return array<string, string>
     */
    public function map_facetwp_keys(array $keys): array {
        return [
            'latitude'  => 'yardlii_listing_latitude',
            'longitude' => 'yardlii_listing_longitude',
        ];
    }

    public function ajax_test_geocoding(): void {
        check_ajax_referer('yardlii_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $postal = isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '';
        if (empty($postal)) {
            wp_send_json_error(['message' => 'Please enter a postal code.']);
        }

        $server_key = get_option('yardlii_google_server_key');
        $map_key    = get_option(\Yardlii\Core\Features\Integrations\GoogleMapKey::OPTION_KEY);
        $api_key = !empty($server_key) ? (string)$server_key : (string)$map_key;

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'No API Key found in settings.']);
        }

        $data = $this->fetch_coordinates($postal, $api_key);

        if ($data) {
            wp_send_json_success([
                'message' => '✅ Success! API is working.',
                'data'    => $data
            ]);
        } else {
            wp_send_json_error(['message' => '❌ Failed. Check debug.log for details.']);
        }
    }

/**
     * Synchronous handler: Replaced heavy lifting with an asynchronous scheduler call.
     * @param int $post_id
     * @param int $form_id
     * @param array<string, mixed> $form_settings
     * @param array<string, mixed> $form_vars
     */
    public function handle_submission(int $post_id, int $form_id, array $form_settings, array $form_vars): void {
        Logger::log("Processing submission for Post ID: $post_id, Form ID: $form_id", 'GEO');

        $raw_mapping = (string) get_option(self::OPTION_MAPPING, '');
        $map = $this->parse_mapping_config($raw_mapping);

        $fid_str = (string) $form_id;
        if (!isset($map[$fid_str])) {
            Logger::log("Skipped: Form ID $fid_str is NOT in the mapping config.", 'GEO');
            return;
        }

        // 3. [MODIFICATION] Replace synchronous I/O with async action
        if (function_exists('as_enqueue_async_action')) {
            as_enqueue_async_action(
                self::ASYNC_HOOK, 
                [ 'post_id' => $post_id ], 
                'yardlii-geocoding' // Group to prevent race condition/double-queueing
            );
            Logger::log("SUCCESS: Geocoding task deferred to Action Scheduler for Post ID: $post_id", 'GEO');
        } else {
            // Fallback (or if AS is missing): log an error and skip the API call.
            // We do NOT run the synchronous call as it is too risky.
            Logger::log("ERROR: Action Scheduler not available. Geocoding skipped for Post ID: $post_id", 'GEO');
        }
    }

    /**
     * 4. New Deferred Handler: Runs the actual Geocoding logic in the background.
     * @param int $post_id
     */
    public function deferred_process_post(int $post_id): void {
        $post = get_post($post_id);
        if (!$post || $post->post_type !== 'listings') return;
        
        Logger::log("Deferred processing started for Post ID: $post_id", 'GEO');

        $raw_mapping = (string) get_option(self::OPTION_MAPPING, '');
        $map = $this->parse_mapping_config($raw_mapping);
        $form_id = (int) get_post_meta($post_id, '_wpuf_form_id', true); // Re-fetch form ID
        $fid_str = (string) $form_id;

        if (!isset($map[$fid_str])) {
            Logger::log("Deferred Skipped: Form ID $fid_str is NOT in the mapping config.", 'GEO');
            return;
        }
        
        $input_meta_key = $map[$fid_str];
        $postal_code = get_post_meta($post_id, $input_meta_key, true);

        if (empty($postal_code) || !is_string($postal_code)) {
            Logger::log("Deferred Failed: Postal code empty or invalid for Post $post_id.", 'GEO');
            return;
        }

        // Re-fetch API Keys inside the deferred process for isolation
        $server_key = get_option('yardlii_google_server_key');
        $map_key    = get_option(\Yardlii\Core\Features\Integrations\GoogleMapKey::OPTION_KEY);
        $api_key = !empty($server_key) ? (string)$server_key : (string)$map_key;

        if (empty($api_key)) {
            Logger::log("Deferred Error: Google API Key is missing.", 'GEO');
            return;
        }

        Logger::log("Deferred Calling Google API for postal code: $postal_code", 'GEO');
        $data = $this->fetch_coordinates($postal_code, $api_key); // <-- I/O operation here

        if ($data) {
            // Note: Data is saved to the same meta keys as before.
            update_post_meta($post_id, 'yardlii_listing_latitude', $data['lat']);
            update_post_meta($post_id, 'yardlii_listing_longitude', $data['lng']);
            update_post_meta($post_id, 'yardlii_display_city_province', $data['address']);
            
            Logger::log("Deferred SUCCESS! Saved data for Post $post_id.", 'GEO', $data);
        } else {
            Logger::log("Deferred Failed: Google API returned no valid data.", 'GEO');
        }
    }

    /**
     * @return array<string, string>
     */
    private function parse_mapping_config(string $input): array {
        $lines = explode("\n", $input);
        $map = [];
        foreach ($lines as $line) {
            $parts = explode(':', trim($line));
            if (count($parts) === 2) {
                $fid = trim($parts[0]);
                $key = trim($parts[1]);
                if (is_numeric($fid) && !empty($key)) {
                    $map[$fid] = $key;
                }
            }
        }
        return $map;
    }

    /**
     * @return array{lat: float, lng: float, address: string}|null
     */
    private function fetch_coordinates(string $postal_code, string $key): ?array {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($postal_code) . "&key=" . $key;
        $response = wp_remote_get($url);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            Logger::log("HTTP Error.", 'GEO', ['error' => $response]);
            return null;
        }

        $body_json = wp_remote_retrieve_body($response);
        if (!is_string($body_json)) {
            return null;
        }

        $body = json_decode($body_json, true);

        if (is_array($body) && isset($body['status']) && $body['status'] === 'OK') {
            $result = $body['results'][0];
            
            $geometry = $result['geometry'] ?? [];
            $location = is_array($geometry) ? ($geometry['location'] ?? []) : [];
            $components = $result['address_components'] ?? [];

            if (
                is_array($location)
                && isset($location['lat'], $location['lng'])
                && is_array($components)
            ) {
                return [
                    'lat'     => (float) $location['lat'],
                    'lng'     => (float) $location['lng'],
                    'address' => $this->format_privacy_address($components)
                ];
            }
        } elseif (is_array($body) && isset($body['status'])) {
             Logger::log("API Error.", 'GEO', ['status' => $body['status'], 'message' => $body['error_message'] ?? '']);
        }

        return null;
    }
    
    /**
     * @param array<mixed> $components
     */
    private function format_privacy_address(array $components): string {
        $city = '';
        $province = '';

        foreach ($components as $comp) {
            if (!is_array($comp) || !isset($comp['types'])) {
                continue;
            }
            
            /** @var array<mixed> $types */
            $types = $comp['types'];

            if (in_array('locality', $types, true)) {
                $city = isset($comp['long_name']) ? (string) $comp['long_name'] : '';
            }
            if (in_array('administrative_area_level_1', $types, true)) {
                $province = isset($comp['short_name']) ? (string) $comp['short_name'] : '';
            }
        }

        if ($city === '') {
            $city = 'Unknown City';
        }
        
        $parts = [];
        $parts[] = $city;
        if ($province !== '') {
            $parts[] = $province;
        }

        return implode(', ', $parts);
    }
}