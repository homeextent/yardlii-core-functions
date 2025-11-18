<?php
namespace Yardlii\Core\Features\ListingEnrichment;

use Yardlii\Core\Features\ListingEnrichment\GeocodingService;

class LocationHandler {

    private GeocodingService $geocoder;

    public function __construct() {
        $this->geocoder = new GeocodingService();
    }

    public function register(): void {
        // Priority 20 ensures ACF has finished saving its own data
        add_action('acf/save_post', [$this, 'enrichLocationData'], 20);
        add_shortcode('yardlii_listing_location', [$this, 'renderLocationShortcode']);
    }

    /**
     * Intercepts post save to generate SEO location data.
     */
    public function enrichLocationData($post_id): void {
        // 1. Check if this post type is relevant (optional, strictly for safety)
        if (get_post_type($post_id) !== 'listing') { // Change 'listing' to your actual CPT slug if different
             // If you are using standard posts, remove this check.
        }

        // 2. Get the raw ACF field
        $rawLocation = get_field('yardlii_listing_location', $post_id, false); // false = get raw value

        if (empty($rawLocation)) {
            return;
        }

        // 3. Parse the weird string format: {"zip":"L2N 2E2"}
        // Sometimes ACF returns an array, sometimes a JSON string. Handle both.
        $zip = null;
        if (is_array($rawLocation)) {
            $zip = $rawLocation['zip'] ?? null;
        } elseif (is_string($rawLocation)) {
            $json = json_decode($rawLocation, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $zip = $json['zip'] ?? null;
            }
        }

        if (empty($zip)) {
            return;
        }

        // 4. Check if we already have processed this specific Zip to avoid API spam? 
        // For now, we just process it.

        // 5. Fetch Data
        $locationData = $this->geocoder->lookup($zip, 'CA'); // Defaulting to CA based on your prompt

        if ($locationData) {
            update_post_meta($post_id, 'yardlii_derived_city', $locationData['city']);
            update_post_meta($post_id, 'yardlii_derived_province', $locationData['state']);
            update_post_meta($post_id, 'yardlii_derived_full_string', $locationData['city'] . ', ' . $locationData['state']);
        }
    }

    /**
     * Shortcode: [yardlii_listing_location]
     */
    public function renderLocationShortcode($atts): string {
        global $post;
        if (!$post) return '';

        $city = get_post_meta($post->ID, 'yardlii_derived_city', true);
        $prov = get_post_meta($post->ID, 'yardlii_derived_province', true);

        if ($city && $prov) {
            return esc_html($city . ', ' . $prov);
        }

        // Fallback: If data missing, try to return just the raw zip?
        // Or return empty to hide it.
        return '';
    }
}