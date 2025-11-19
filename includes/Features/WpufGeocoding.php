<?php
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
     */
    public function handle_submission($post_id, $form_id, $form_settings, $form_vars): void {
        // 1. Load the Mapping Config
        // Format is "FormID:MetaKey" (one per line)
        $raw_mapping = get_option(self::OPTION_MAPPING, '');
        $map = $this->parse_mapping_config($raw_mapping);

        // 2. Check if this Form ID is monitored
        if (!isset($map[$form_id])) {
            return;
        }

        $input_meta_key = $map[$form_id]; // e.g., 'yardlii_listing_postal_code'

        // 3. Retrieve the user's submitted Postal Code
        $postal_code = get_post_meta($post_id, $input_meta_key, true);

        if (empty($postal_code)) {
            return;
        }

        // 4. Get API Key (Reusing existing Core architecture)
        $api_key = get_option(\Yardlii\Core\Features\GoogleMapKey::OPTION_KEY);
        if (empty($api_key)) {
            // Optional: Log error here if Logger exists
            return;
        }

        // 5. Call Google Geocoding API
        $data = $this->fetch_coordinates($postal_code, $api_key);

        // 6. Save Derived Data (Privacy & Search)
        if ($data) {
            // For FacetWP Search
            update_post_meta($post_id, 'yardlii_listing_latitude', $data['lat']);
            update_post_meta($post_id, 'yardlii_listing_longitude', $data['lng']);
            
            // For Privacy Display (Elementor)
            update_post_meta($post_id, 'yardlii_display_city_province', $data['address']);
        }
    }

    /**
     * Parses the text area config into a usable array [FormID => MetaKey]
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
     * Performs the API Request and extracts clean data
     */
    private function fetch_coordinates($postal_code, $key): ?array {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($postal_code) . "&key=" . $key;
        $response = wp_remote_get($url);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['status']) && $body['status'] === 'OK') {
            $result = $body['results'][0];
            
            return [
                'lat' => $result['geometry']['location']['lat'],
                'lng' => $result['geometry']['location']['lng'],
                'address' => $this->format_privacy_address($result['address_components'])
            ];
        }

        return null;
    }
    
    /**
     * Extracts only "City, Province" to preserve privacy
     */
    private function format_privacy_address($components): string {
        $city = '';
        $province = '';

        foreach ($components as $comp) {
            if (in_array('locality', $comp['types'])) {
                $city = $comp['long_name'];
            }
            if (in_array('administrative_area_level_1', $comp['types'])) {
                $province = $comp['short_name']; // Use short name (e.g., ON, NY)
            }
        }

        // Fallbacks
        if (!$city) $city = 'Unknown City';
        if (!$province) $province = '';

        return trim("$city, $province", ", "); 
    }
}