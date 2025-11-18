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
     * @param string $countryCode default 'CA' (Canada) - logic can be expanded later.
     * @return array|null Returns ['city' => string, 'state' => string] or null on failure.
     */
    public function lookup(string $zip, string $countryCode = 'CA'): ?array {
        // 1. Sanitize: Remove spaces, uppercase (e.g., "L2N 2E2" -> "L2N2E2")
        // Note: Zippopotam requires the first 3 chars for CA, or 5 digits for US.
        $cleanZip = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $zip));

        if ($countryCode === 'CA') {
            // Canada API expects only the FSA (first 3 chars)
            $cleanZip = substr($cleanZip, 0, 3);
        }

        $url = sprintf(self::API_URL_TEMPLATE, strtolower($countryCode), $cleanZip);

        $response = wp_remote_get($url);

        if (is_wp_error($response)) {
            return null; // Fail silently or log if debug is on
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
            'city'  => $data['places'][0]['place name'] ?? '',
            'state' => $data['places'][0]['state'] ?? '', // 'state' maps to Province in CA
        ];
    }
}