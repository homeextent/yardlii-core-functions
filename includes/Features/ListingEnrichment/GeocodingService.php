<?php
namespace Yardlii\Core\Features\ListingEnrichment;

/**
 * Service to fetch location details.
 * Priority 1: Google Maps API (Best Data, supports Rural).
 * Priority 2: Zippopotam.us (Free, Urban only).
 * Priority 3: OpenStreetMap (Free, Rural Fallback).
 */
class GeocodingService {

    private const GOOGLE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';
    private const ZIPPO_URL  = 'https://api.zippopotam.us/%s/%s';
    private const OSM_URL    = 'https://nominatim.openstreetmap.org/search';
    
    /**
     * Holds the last error message for debugging.
     */
    private string $lastError = '';

    /**
     * Lookup city, state, and coordinates.
     *
     * @param string $zip The raw zip/postal code.
     * @param string $countryCode default 'CA' (Canada).
     * @return array{city: string, state: string, lat: string, lng: string, source: string, error: string}|null
     */
    public function lookup(string $zip, string $countryCode = 'CA'): ?array {
        $cleanZip = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $zip));
        $this->lastError = ''; // Reset error
        
        // --- PRIORITY 1: Google Maps API (Best) ---
        $googleKey = $this->getGoogleKey();
        
        if (!empty($googleKey)) {
            $googleData = $this->queryGoogle($cleanZip, $countryCode, $googleKey);
            if ($googleData) {
                // We perform the array union here to match the return shape
                return array_merge($googleData, [
                    'source' => 'Google Maps API',
                    'error'  => ''
                ]);
            }
            // Log failure but continue to fallback
            $this->log("Google Failed for $zip. Error: " . $this->lastError);
        } else {
            $this->lastError = 'No API Key found in settings';
        }

        // --- PRIORITY 2: Zippopotam (Free, Urban) ---
        $zippoZip = ($countryCode === 'CA') ? substr($cleanZip, 0, 3) : $cleanZip;
        $data = $this->queryZippopotam($zippoZip, $countryCode);
        
        if ($data) {
            return array_merge($data, [
                'source' => 'Zippopotam',
                'error'  => 'Google Fallback: ' . $this->lastError
            ]);
        }

        // --- PRIORITY 3: OpenStreetMap (Free, Rural Fallback) ---
        $osmData = $this->queryNominatim($cleanZip, $countryCode);
        if ($osmData) {
            return array_merge($osmData, [
                'source' => 'OpenStreetMap',
                'error'  => 'Google/Zippo Fallback: ' . $this->lastError
            ]);
        }
        
        return null;
    }

    private function getGoogleKey(): string {
        $key = get_option('yardlii_google_maps_api_key', '');
        if (empty($key)) {
            $key = get_option('yardlii_google_map_key', '');
        }
        return (string) $key;
    }

    /**
     * Query Google Geocoding API
     *
     * @return array{city: string, state: string, lat: string, lng: string}|null
     */
    private function queryGoogle(string $zip, string $country, string $apiKey): ?array {
        // Format: "L2N 2E2"
        $formattedZip = $zip;
        if ($country === 'CA' && strlen($zip) === 6) {
            $formattedZip = substr($zip, 0, 3) . ' ' . substr($zip, 3);
        }
        
        $address = $formattedZip . ',' . $country;
        $url = add_query_arg(['address' => $address, 'key' => $apiKey], self::GOOGLE_URL);

        // FIX: Ensure trailing slash on Referer (e.g. "https://yardlii.com/")
        $referer = trailingslashit(get_bloginfo('url'));

        $args = [
            'timeout' => 5,
            'headers' => [
                'Referer' => $referer
            ]
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            $this->lastError = $response->get_error_message();
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status = $body['status'] ?? 'UNKNOWN';

        if ($status !== 'OK') {
            // Capture the specific reason (e.g. REQUEST_DENIED)
            $errMsg = $body['error_message'] ?? 'Unknown API Error';
            $this->lastError = "$status - $errMsg";
            return null;
        }

        if (empty($body['results'][0])) {
            $this->lastError = 'ZERO_RESULTS';
            return null;
        }

        $result = $body['results'][0];
        $comps  = $result['address_components'] ?? [];
        
        $city = '';
        $state = '';

        foreach ($comps as $c) {
            if (in_array('locality', $c['types'])) $city = (string) $c['long_name'];
            elseif (in_array('administrative_area_level_1', $c['types'])) $state = (string) $c['long_name'];
            
            if (empty($city) && in_array('administrative_area_level_2', $c['types'])) $city = (string) $c['long_name'];
            if (empty($city) && in_array('administrative_area_level_3', $c['types'])) $city = (string) $c['long_name'];
            if (empty($city) && in_array('postal_town', $c['types'])) $city = (string) $c['long_name'];
        }
        
        if (empty($city) && !empty($state)) {
             $city = "Rural " . $state;
        }

        return [
            'city'  => (string) $city,
            'state' => (string) $state,
            'lat'   => (string) ($result['geometry']['location']['lat'] ?? ''),
            'lng'   => (string) ($result['geometry']['location']['lng'] ?? ''),
        ];
    }

    /**
     * Query Zippopotam API
     *
     * @return array{city: string, state: string, lat: string, lng: string}|null
     */
    private function queryZippopotam(string $zip, string $country): ?array {
        $url = sprintf(self::ZIPPO_URL, strtolower($country), $zip);
        $response = wp_remote_get($url, ['timeout' => 3]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['places'][0])) return null;

        $place = $data['places'][0];
        return [
            'city'  => (string) ($place['place name'] ?? ''),
            'state' => (string) ($place['state'] ?? ''),
            'lat'   => (string) ($place['latitude'] ?? ''),
            'lng'   => (string) ($place['longitude'] ?? ''),
        ];
    }

    /**
     * Query Nominatim API
     *
     * @return array{city: string, state: string, lat: string, lng: string}|null
     */
    private function queryNominatim(string $zip, string $country): ?array {
        $formattedZip = $zip;
        if ($country === 'CA' && strlen($zip) === 6) {
            $formattedZip = substr($zip, 0, 3) . ' ' . substr($zip, 3);
        }

        $query = add_query_arg([
            'postalcode' => $formattedZip,
            'country'    => ($country === 'CA' ? 'Canada' : 'USA'),
            'format'     => 'json',
            'addressdetails' => 1
        ], self::OSM_URL);

        $response = wp_remote_get($query, ['timeout' => 5, 'headers' => ['User-Agent' => 'YardliiPlugin/1.0']]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) return null;

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data[0])) return null;

        $place = $data[0];
        $addr  = $place['address'] ?? [];
        $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $addr['hamlet'] ?? $addr['municipality'] ?? '';
        
        return [
            'city'  => (string) $city,
            'state' => (string) ($addr['state'] ?? ''),
            'lat'   => (string) ($place['lat'] ?? ''),
            'lng'   => (string) ($place['lon'] ?? ''),
        ];
    }

    private function log(string $msg): void {
        // Check both WP_DEBUG and the plugin's internal debug option
        $plugin_debug = (bool) get_option('yardlii_debug_mode', false);
        if ((defined('WP_DEBUG') && WP_DEBUG) || $plugin_debug) {
            error_log('[Yardlii Geocoding] ' . $msg);
        }
    }
}