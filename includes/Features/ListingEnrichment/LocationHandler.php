<?php
namespace Yardlii\Core\Features\ListingEnrichment;

use Yardlii\Core\Features\ListingEnrichment\GeocodingService;

class LocationHandler {

    private GeocodingService $geocoder;

    public function __construct() {
        $this->geocoder = new GeocodingService();
    }

    public function register(): void {
        add_action('acf/save_post', [$this, 'enrichLocationData'], 20);
        add_shortcode('yardlii_listing_location', [$this, 'renderLocationShortcode']);
    }

    /**
     * Intercepts post save to generate SEO location data.
     *
     * @param int|string $post_id The post ID (ACF sometimes passes string).
     */
    public function enrichLocationData($post_id): void {
        $id = (int) $post_id;

        if (get_post_type($id) !== 'listing') {
             // check for post type if needed
        }

        $rawLocation = get_post_meta($id, 'yardlii_listing_location', true);

        if (empty($rawLocation)) {
            return;
        }

        $zip = null;
        if (is_array($rawLocation)) {
            $zip = $rawLocation['zip'] ?? null;
        } elseif (is_string($rawLocation)) {
            $json = json_decode($rawLocation, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
                $zip = $json['zip'] ?? null;
            }
        }

        if (empty($zip) || !is_string($zip)) {
            return;
        }

        // Perform Lookup
        $locationData = $this->geocoder->lookup($zip, 'CA');

        if ($locationData) {
            // Save Main Data
            update_post_meta($id, 'yardlii_derived_city', $locationData['city']);
            update_post_meta($id, 'yardlii_derived_province', $locationData['state']);
            update_post_meta($id, 'yardlii_derived_full_string', $locationData['city'] . ', ' . $locationData['state']);
            update_post_meta($id, 'yardlii_derived_lat', $locationData['lat']);
            update_post_meta($id, 'yardlii_derived_lng', $locationData['lng']);
            
            // Save Debug/Diagnostic Data (So we know what happened)
            $source = $locationData['source'] ?? 'Unknown';
            $error  = $locationData['error'] ?? '';
            
            update_post_meta($id, '_yardlii_geo_source', $source);
            update_post_meta($id, '_yardlii_geo_error', $error);
        }
    }

    /**
     * Shortcode: [yardlii_listing_location]
     *
     * @param mixed $atts Shortcode attributes.
     * @return string
     */
    public function renderLocationShortcode($atts): string {
        global $post;
        if (!$post) return '';

        $city = get_post_meta($post->ID, 'yardlii_derived_city', true);
        $prov = get_post_meta($post->ID, 'yardlii_derived_province', true);

        if ($city && $prov) {
            return esc_html($city . ', ' . $prov);
        }

        return '';
    }
}