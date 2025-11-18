<?php
namespace Yardlii\Tests\Integration\Features\ListingEnrichment;

use Yardlii\Core\Features\ListingEnrichment\GeocodingService;
use WP_UnitTestCase;

class GeocodingServiceTest extends WP_UnitTestCase {

    public function test_lookup_returns_parsed_data_on_success() {
        // 1. Mock the HTTP response from Zippopotam
        add_filter('pre_http_request', function($pre, $args, $url) {
            // Verify we are calling the right URL structure
            if (strpos($url, 'api.zippopotam.us/ca/L2N') !== false) {
                return [
                    'response' => ['code' => 200],
                    'body' => json_encode([
                        'post code' => 'L2N', 
                        'country' => 'Canada', 
                        'places' => [[
                            'place name' => 'St. Catharines', 
                            'state' => 'Ontario',
                            'latitude' => '43.15',
                            'longitude' => '-79.25'
                        ]]
                    ])
                ];
            }
            return $pre;
        }, 10, 3);

        // 2. Run the service
        $service = new GeocodingService();
        $result = $service->lookup('L2N 2E2', 'CA');

        // 3. Assertions
        $this->assertIsArray($result);
        $this->assertEquals('St. Catharines', $result['city']);
        $this->assertEquals('Ontario', $result['state']);
        $this->assertEquals('43.15', $result['lat']);
        $this->assertEquals('-79.25', $result['lng']);
    }

    public function test_lookup_returns_null_on_invalid_zip() {
        // Mock a 404
        add_filter('pre_http_request', function($pre, $args, $url) {
            return [
                'response' => ['code' => 404],
                'body' => '{}'
            ];
        }, 10, 3);

        $service = new GeocodingService();
        $result = $service->lookup('INVALID');

        $this->assertNull($result);
    }
}