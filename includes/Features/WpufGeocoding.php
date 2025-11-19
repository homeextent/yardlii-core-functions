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
        // 1. Load the Mapping Config
        $raw_mapping = (string) get_option(self::OPTION_MAPPING, '');
        $map = $this->parse_mapping_config($raw_mapping);

        // 2. Check if this Form ID is monitored
        // Convert form_id to string for array key comparison
        $fid_str = (string) $form_id;
        if (!isset($map[$fid_str])) {
            return;
        }

        $input_meta_key = $map[$fid_str];

        // 3. Retrieve the user's submitted Postal Code
        $postal_code = get_post_meta($post_id, $input_meta_key, true);

        if (empty($postal_code) || !is_string($postal_code)) {
            return;
        }

        // 4. Get API Key
        $api_key = get_option(\Yardlii\Core\Features\GoogleMapKey::OPTION_KEY);
        if (empty($api_key) || !is_string($api_key)) {
            return;
        }

        // 5. Call Google Geocoding API
        $data = $this->fetch_coordinates($postal_code, $api_key);

        // 6. Save Derived Data
        if ($data) {
            update_post_meta($post_id, 'yardlii_listing_latitude', $data['lat']);
            update_post_meta($post_id, 'yardlii_listing_longitude', $data['lng']);
            update_post_meta($post_id, 'yardlii_display_city_province', $data['address']);
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
            return null;
        }

        $body_json = wp_remote_retrieve_body($response);
        if (!is_string($body_json)) {
            return null;
        }

        $body = json_decode($body_json, true);

        if (is_array($body) && isset($body['status']) && $body['status'] === 'OK') {
            $result = $body['results'][0];
            
            // Ensure result parts exist and are arrays before passing
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
        }

        return null;
    }
    
    /**
     * Extracts only "City, Province" to preserve privacy.
     *
     * @param array<array<string, mixed>> $components The address_components from Google API.
     */
    private function format_privacy_address(array $components): string {
        $city = '';
        $province = '';

        foreach ($components as $comp) {
            // Ensure array structure
            if (!is_array($comp) || !isset($comp['types'])) {
                continue;
            }
            
            // Strict type checking for 'types'
            $types = $comp['types'];
            if (!is_array($types)) {
                continue;
            }

            if (in_array('locality', $types, true)) {
                $city = isset($comp['long_name']) ? (string) $comp['long_name'] : '';
            }
            if (in_array('administrative_area_level_1', $types, true)) {
                $province = isset($comp['short_name']) ? (string) $comp['short_name'] : '';
            }
        }

        // Fallbacks
        if ($city === '') {
            $city = 'Unknown City';
        }
        
        // Build string
        $parts = [];
        $parts[] = $city;
        
        if ($province !== '') {
            $parts[] = $province;
        }

        return implode(', ', $parts);
    }
}