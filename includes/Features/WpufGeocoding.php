<?php
namespace Yardlii\Core\Features;

// We use the existing class to securely access the saved API key
use Yardlii\Core\Features\GoogleMapKey; 

class WpufGeocoding
{
    // The option key for the form configuration, saved under the General Tab group
    const OPTION_CONFIG = 'yardlii_wpuf_geocoding_forms';
    
    // New metadata keys (Mandatory 'yardlii_' prefix)
    const META_POSTAL_CODE = 'yardlii_raw_postal_code';
    const META_LATITUDE    = 'yardlii_listing_latitude';
    const META_LONGITUDE   = 'yardlii_listing_longitude';
    const META_DISPLAY     = 'yardlii_display_city_province';

    /**
     * Called by the Loader to activate the feature.
     */
    public function register(): void
    {
        add_action('admin_init', [$this, 'register_settings']);
        add_action('wpuf_add_post_after_insert', [$this, 'geocode_listing_data'], 10, 2);
    }
    
    /**
     * Registers the configuration option under the existing 'yardlii_general_group'.
     */
    public function register_settings(): void
    {
        register_setting('yardlii_general_group', self::OPTION_CONFIG, [$this, 'sanitize_config']);
    }

    /**
     * Sanitizes the Form ID and Field Key inputs.
     *
     * @param array<mixed> $input The raw input array from the form.
     * @return array<int, array{form_id: int, postal_code_key: string}> The cleaned array.
     */
    public function sanitize_config(array $input): array
    {
        $clean = [];
        // Ensure input is iterable
        if (is_array($input)) {
            foreach ($input as $row) {
                // Explicitly cast to array to satisfy PHPStan if $row is mixed
                $row = (array) $row; 
                
                // Only save rows that have both a form ID and a postal code key
                if (!empty($row['form_id']) && !empty($row['postal_code_key'])) {
                    $clean[] = [
                        'form_id'         => absint($row['form_id']),
                        'postal_code_key' => sanitize_key($row['postal_code_key']),
                    ];
                }
            }
        }
        return $clean;
    }

    /**
     * Performs the server-side geocoding and saves the coordinates and location name.
     *
     * @param int $post_id The ID of the newly inserted post.
     * @param array<string, mixed> $form_settings The WPUF form settings array.
     * @return void
     */
    public function geocode_listing_data(int $post_id, array $form_settings): void
    {
        $form_id = absint($form_settings['id'] ?? 0);
        
        /** @var array<int, array{form_id: int, postal_code_key: string}> $config */
        $config  = get_option(self::OPTION_CONFIG, []);
        
        $field_name = null;
        
        // Ensure $config is iterable
        if (!is_array($config)) {
            return;
        }

        foreach ($config as $row) {
            // We rely on the @var type annotation here, avoiding redundant checks.
            // The cast is still necessary if $row comes from get_option which returns 'mixed'.
            $row = (array) $row;
            
            // FIX: Removed isset() check as PHPStan guarantees its existence due to @var
            if (absint($row['form_id']) === $form_id) {
                
                // FIX: Removed ?? null as PHPStan guarantees its existence due to @var
                $field_name = $row['postal_code_key']; 
                break;
            }
        }
        
        if (empty($field_name)) return;

        $postal_code = sanitize_text_field($_POST[$field_name] ?? '');
        if (empty($postal_code)) return;

        $api_key = get_option(GoogleMapKey::OPTION_KEY); 
        if (empty($api_key)) {
            if (function_exists('yardlii_log')) yardlii_log('WPUF Geocoding failed: Google Map Key is missing.');
            return;
        }

        $api_url = add_query_arg([
            'address' => urlencode($postal_code),
            'key'     => $api_key
        ], 'https://maps.googleapis.com/maps/api/geocode/json');

        $response = wp_remote_get($api_url);
        
        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            if (function_exists('yardlii_log')) yardlii_log("WPUF Geocoding API Error for Post ID {$post_id}");
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (empty($data['results']) || !isset($data['status']) || strtolower($data['status']) !== 'ok') return;
        
        $result = $data['results'][0];
        $location = $result['geometry']['location'];
        
        $city = $province = '';
        if (isset($result['address_components']) && is_array($result['address_components'])) {
            foreach ($result['address_components'] as $component) {
                if (in_array('locality', $component['types'], true)) $city = $component['long_name'];
                if (in_array('administrative_area_level_1', $component['types'], true)) $province = $component['short_name'];
            }
        }
        
        $display_name = trim("{$city}, {$province}", ', ');
        
        update_post_meta($post_id, self::META_POSTAL_CODE, $postal_code);
        update_post_meta($post_id, self::META_LATITUDE, $location['lat']);
        update_post_meta($post_id, self::META_LONGITUDE, $location['lng']);
        update_post_meta($post_id, self::META_DISPLAY, $display_name); 
    }
}