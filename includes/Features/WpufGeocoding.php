<?php
declare(strict_types=1);

namespace Yardlii\Core\Features;

use Yardlii\Core\Services\Logger;

/**
 * Feature: WPUF Geocoding (Privacy Focused)
 */
class WpufGeocoding {

    const OPTION_MAPPING = 'yardlii_wpuf_geo_mapping';

    public function register(): void {
        add_action('wpuf_add_post_after_insert', [$this, 'handle_submission'], 10, 4);
        add_action('wpuf_update_post_after_submit', [$this, 'handle_submission'], 10, 4);
        add_action('wp_ajax_yardlii_test_geocoding', [$this, 'ajax_test_geocoding']);
        add_filter('facetwp_proximity_store_keys', [$this, 'map_facetwp_keys']);
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
        $map_key    = get_option(\Yardlii\Core\Features\GoogleMapKey::OPTION_KEY);
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

        $input_meta_key = $map[$fid_str];
        $postal_code = get_post_meta($post_id, $input_meta_key, true);

        if (empty($postal_code) || !is_string($postal_code)) {
            Logger::log("Failed: Postal code empty or invalid for Post $post_id.", 'GEO');
            return;
        }

        $server_key = get_option('yardlii_google_server_key');
        $map_key    = get_option(\Yardlii\Core\Features\GoogleMapKey::OPTION_KEY);
        $api_key = !empty($server_key) ? (string)$server_key : (string)$map_key;

        if (empty($api_key)) {
            Logger::log("Error: Google API Key is missing.", 'GEO');
            return;
        }

        Logger::log("Calling Google API for postal code: $postal_code", 'GEO');
        $data = $this->fetch_coordinates($postal_code, $api_key);

        if ($data) {
            update_post_meta($post_id, 'yardlii_listing_latitude', $data['lat']);
            update_post_meta($post_id, 'yardlii_listing_longitude', $data['lng']);
            update_post_meta($post_id, 'yardlii_display_city_province', $data['address']);
            
            Logger::log("SUCCESS! Saved data for Post $post_id.", 'GEO', $data);
        } else {
            Logger::log("Failed: Google API returned no valid data.", 'GEO');
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