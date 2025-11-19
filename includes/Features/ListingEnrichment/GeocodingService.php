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
     * Lookup city, state, and coordinates.
     *
     * @param string $zip The raw zip/postal code.
     * @param string $countryCode default 'CA' (Canada).
     * @return array{city: string, state: string, lat: string, lng: string}|null
     */
    public function lookup(string $zip, string $countryCode = 'CA'): ?array {
        // 0. Clean the input
        $cleanZip = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $zip));
        
        // --- PRIORITY 1: Google Maps API (Best) ---
        $googleKey = $this->getGoogleKey();
        
        if (!empty($googleKey)) {
            $googleData = $this->queryGoogle($cleanZip, $countryCode, $googleKey);
            if ($googleData) {
                return $googleData;
            }
        }

        // --- PRIORITY 2: Zippopotam (Free, Urban) ---
        // Zippopotam needs FSA (first 3 chars) for Canada
        $zippoZip = ($countryCode === 'CA') ? substr($cleanZip, 0, 3) : $cleanZip;
        
        $data = $this->queryZippopotam($zippoZip, $countryCode);
        if ($data) {
            return $data;
        }

        // --- PRIORITY 3: OpenStreetMap (Free, Rural Fallback) ---
        // Uses full zip for better rural precision
        return $this->queryNominatim($cleanZip, $countryCode);
    }

    /**
     * Helper to find the API Key in WP Options.
     * Tries common key names to ensure we find it.
     */
    private function getGoogleKey(): string {
        // Try the standard name first
        $key = get_option('yardlii_google_maps_api_key', '');
        
        // If empty, try the alternative name sometimes used in settings
        if (empty($key)) {
            $key = get_option('yardlii_google_map_key', '');
        }
        
        return (string) $key;
    }

    /**
     * Query Google Geocoding API
     *
     * @param string $zip
     * @param string $country
     * @param string $apiKey
     * @return array{city: string, state: string, lat: string, lng: string}|null
     */
    private function queryGoogle(string $zip, string $country, string $apiKey): ?array {
        // 1. Format for better Google accuracy (e.g. "T0A 0A0" instead of "T0A0A0")
        $formattedZip = $zip;
        if ($country === 'CA' && strlen($zip) === 6) {
            $formattedZip = substr($zip, 0, 3) . ' ' . substr($zip, 3);
        }
        
        // Add country for context
        $address = $formattedZip . ',' . $country;
        
        $url = add_query_arg([
            'address' => $address,
            'key'     => $apiKey
        ], self::GOOGLE_URL);

        // CRITICAL FIX: Add Referer Header to satisfy API Key restrictions
        $args = [
            'timeout' => 5,
            'headers' => [
                'Referer' => get_bloginfo('url')
            ]
        ];

        $response = wp_remote_get($url, $args);

        if (is_wp_error($response)) {
            $this->log("Google API Error: " . $response->get_error_message());
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code !== 200) {
            // Log the specific rejection reason (e.g. REQUEST_DENIED)
            $status = $body['status'] ?? 'UNKNOWN';
            $msg    = $body['error_message'] ?? '';
            $this->log("Google API Rejected ($status): $msg");
            return null;
        }

        // Check for valid results
        if (empty($body['results'][0])) {
            return null;
        }

        $result = $body['results'][0];
        $comps  = $result['address_components'] ?? [];
        
        $city = '';
        $state = '';

        // Google Address Component Parsing
        foreach ($comps as $c) {
            if (in_array('locality', $c['types'])) {
                $city = $c['long_name'];
            } elseif (in_array('administrative_area_level_1', $c['types'])) { // Province/State
                $state = $c['long_name'];
            }
            // Fallback: Rural areas often don't have 'locality', they use 'administrative_area_level_2' or '3'
            if (empty($city) && in_array('administrative_area_level_2', $c['types'])) {
                $city = $c['long_name'];
            }
            if (empty($city) && in_array('administrative_area_level_3', $c['types'])) {
                $city = $c['long_name'];
            }
            // Fallback: Some rural areas just have 'postal_town'
            if (empty($city) && in_array('postal_town', $c['types'])) {
                $city = $c['long_name'];
            }
        }
        
        // Rural Fallback: If we found a State/Province but NO City
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
     * @param string $zip
     * @param string $country
     * @return array{city: string, state: string, lat: string, lng: string}|null
     */
    private function queryZippopotam(string $zip, string $country): ?array {
        $url = sprintf(self::ZIPPO_URL, strtolower($country), $zip);
        $response = wp_remote_get($url, ['timeout' => 3]);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data['places'][0])) {
            return null;
        }

        $place = $data['places'][0];
        return [
            'city'  => (string) ($place['place name'] ?? ''),
            'state' => (string) ($place['state'] ?? ''),
            'lat'   => (string) ($place['latitude'] ?? ''),
            'lng'   => (string) ($place['longitude'] ?? ''),
        ];
    }

    /**
     * Query OpenStreetMap Nominatim
     *
     * @param string $zip
     * @param string $country
     * @return array{city: string, state: string, lat: string, lng: string}|null
     */
    private function queryNominatim(string $zip, string $country): ?array {
        $args = [
            'headers' => ['User-Agent' => 'YardliiPlugin/1.0 (' . get_bloginfo('url') . ')'],
            'timeout' => 5
        ];

        // Format Zip for OSM too (it prefers spaces for Canada)
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

        $response = wp_remote_get($query, $args);

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            return null;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);
        if (empty($data[0])) {
            return null;
        }

        $place = $data[0];
        $addr  = $place['address'] ?? [];
        // OSM Logic: Try city, then town, then village, then municipality
        $city = $addr['city'] ?? $addr['town'] ?? $addr['village'] ?? $addr['hamlet'] ?? $addr['municipality'] ?? '';
        
        return [
            'city'  => (string) $city,
            'state' => (string) ($addr['state'] ?? ''),
            'lat'   => (string) ($place['lat'] ?? ''),
            'lng'   => (string) ($place['lon'] ?? ''),
        ];
    }

    /**
     * Simple logger to help debug API failures
     */
    private function log(string $msg): void {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[Yardlii Geocoding] ' . $msg);
        }
    }
}