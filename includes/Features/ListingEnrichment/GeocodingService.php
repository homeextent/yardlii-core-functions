<?php
namespace Yardlii\Core\Features\ListingEnrichment;

/**
 * Service to fetch location details from a Postal/Zip code.
 * Uses the free Zippopotam.us API.
 */
class GeocodingService {

    private const API_URL_TEMPLATE = 'https://api.zippopotam.us/%s/%s';

    /**
     * Lookup city and state/province from a zip code.
     *
     * @param string $zip The raw zip/postal code.
     * @param string $countryCode default 'CA' (Canada).
     * @return array{city: string, state: string}|null Returns array with specific keys or null.
     */
    public function lookup(string $zip, string $countryCode = 'CA'): ?array {
        $cleanZip = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $zip));

        if ($countryCode === 'CA') {
            $cleanZip = substr($cleanZip, 0, 3);
        }

        $url = sprintf(self::API_URL_TEMPLATE, strtolower($countryCode), $cleanZip);

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return null;
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code !== 200) {
            return null;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['places'][0])) {
            return null;
        }

        return [
            'city'  => (string) ($data['places'][0]['place name'] ?? ''),
            'state' => (string) ($data['places'][0]['state'] ?? ''),
        ];
    }
}