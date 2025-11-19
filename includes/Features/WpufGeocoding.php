<?php
declare(strict_types=1);

namespace Yardlii\Core\Features;

/**
 * Feature: WPUF Geocoding (Privacy Focused)
 * -----------------------------------------
 * Intercepts WPUF form submissions to convert Postal Codes into:
 * 1. Lat/Lng for FacetWP Search.
 * 2. "City, Province" string for privacy-safe display.
 */
class WpufGeocoding {

    // Option key for storing the Form ID -> Meta Key mapping
    const OPTION_MAPPING = 'yardlii_wpuf_geo_mapping';

    public function register(): void {
        // Hooks for creation and updates
        add_action('wpuf_add_post_after_insert', [$this, 'handle_submission'], 10, 4);
        add_action('wpuf_update_post_after_submit', [$this, 'handle_submission'], 10, 4);

        // Diagnostic AJAX Test
        add_action('wp_ajax_yardlii_test_geocoding', [$this, 'ajax_test_geocoding']);

        // FacetWP Integration
        add_filter('facetwp_proximity_store_keys', [$this, 'map_facetwp_keys']);
    }

    /**
     * Map FacetWP's default latitude/longitude keys to our custom YARDLII fields.
     *
     * @param array<mixed> $keys Incoming keys from FacetWP.
     * @return array<string, string> The modified key map.
     */
    public function map_facetwp_keys(array $keys): array {
        return [
            'latitude'  => 'yardlii_listing_latitude',
            'longitude' => 'yardlii_listing_longitude',
        ];
    }

    /**
     * AJAX Handler: Diagnostics Test Tool
     */
    public function ajax_test_geocoding(): void {
        check_ajax_referer('yardlii_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Unauthorized']);
        }

        $postal = isset($_POST['postal_code']) ? sanitize_text_field($_POST['postal_code']) : '';
        if (empty($postal)) {
            wp_send_json_error(['message' => 'Please enter a postal code.']);
        }

        // Resolve Key
        $server_key = get_option('yardlii_google_server_key');
        $map_key    = get_option(\Yardlii\Core\Features\GoogleMapKey::OPTION_KEY);
        
        // Cast to string to satisfy strict types
        $api_key = !empty($server_key) ? (string)$server_key : (string)$map_key;

        if (empty($api_key)) {
            wp_send_json_error(['message' => 'No API Key found in settings.']);
        }

        // Perform Request
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
     * The Conversion Engine
     *
     * @param int          $post_id       The ID of the post being saved.
     * @param int          $form_id       The ID of the WPUF form.
     * @param array<mixed> $form_settings Form settings array.
     * @param array<mixed> $form_vars     Form variables array.
     */
    public function handle_submission(int $post_id, int $form_id, array $form_settings, array $form_vars): void {
        // [DEBUG] Log entry
        error_log("[YARDLII GEO] Processing submission for Post ID: $post_id, Form ID: $form_id");

        // 1. Load the Mapping Config
        $raw_mapping = (string) get_option(self::OPTION_MAPPING, '');
        $map = $this->parse_mapping_config($raw_mapping);

        // 2. Check if this Form ID is monitored
        $fid_str = (string) $form_id;
        if (!isset($map[$fid_str])) {
            error_log("[YARDLII GEO] Skipped: Form ID $fid_str is NOT in the mapping config.");
            return;
        }

        $input_meta_key = $map[$fid_str];

        // 3. Retrieve the user's submitted Postal Code
        $postal_code = get_post_meta($post_id, $input_meta_key, true);

        if (empty($postal_code) || !is_string($postal_code)) {
            error_log("[YARDLII GEO] Failed: Postal code empty or invalid for Post $post_id.");
            return;
        }

        // 4. Get API Key (Prioritize Server Key)
        $server_key = get_option('yardlii_google_server_key');
        $map_key    = get_option(\Yardlii\Core\Features\GoogleMapKey::OPTION_KEY);
        
        $api_key = !empty($server_key) ? (string)$server_key : (string)$map_key;

        if (empty($api_key)) {
            error_log("[YARDLII GEO] Error: Google API Key is missing.");
            return;
        }

        // 5. Call Google Geocoding API
        error_log("[YARDLII GEO] Calling Google API for postal code: $postal_code");
        $data = $this->fetch_coordinates($postal_code, $api_key);

        // 6. Save Derived Data
        if ($data) {
            update_post_meta($post_id, 'yardlii_listing_latitude', $data['lat']);
            update_post_meta($post_id, 'yardlii_listing_longitude', $data['lng']);
            update_post_meta($post_id, 'yardlii_display_city_province', $data['address']);
            
            error_log("[YARDLII GEO] SUCCESS! Saved data for Post $post_id: " . print_r($data, true));
        } else {
            error_log("[YARDLII GEO] Failed: Google API returned no valid data.");
        }
    }

    /**
     * Parses the text area config into a usable array.
     *
     * @param string $input The raw textarea string.
     * @return array<string, string> Map of FormID => MetaKey.
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
     * Performs the API Request and extracts clean data.
     *
     * @param string $postal_code The postal code to geocode.
     * @param string $key         The Google API key.
     * @return array{lat: float, lng: float, address: string}|null
     */
    private function fetch_coordinates(string $postal_code, string $key): ?array {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($postal_code) . "&key=" . $key;
        $response = wp_remote_get($url);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            error_log("[YARDLII GEO] HTTP Error: " . print_r($response, true));
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
             error_log("[YARDLII GEO] API Error Status: " . $body['status']);
             if (isset($body['error_message'])) {
                 error_log("[YARDLII GEO] API Message: " . $body['error_message']);
             }
        }

        return null;
    }
    
    /**
     * Extracts only "City, Province" to preserve privacy.
     *
     * @param array<mixed> $components The address_components from Google API.
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