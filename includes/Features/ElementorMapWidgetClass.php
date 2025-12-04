<?php
namespace Yardlii\Core\Features;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Migrated Elementor Map Widget
 * Original Source: yardlii-custom-elementor-google-map/widget-google-map.php
 * Migrated to Core: v3.26.3
 */
class ElementorMapWidgetClass extends Widget_Base {

    /**
     * Get widget name.
     */
    public function get_name(): string {
        return 'yardlii_google_map';
    }

    public function get_title(): string {
        return esc_html__( 'Yardlii Google Map', 'yardlii-core' );
    }

    public function get_icon(): string {
        return 'eicon-google-maps';
    }

    /**
     * Get widget categories.
     * @return array<string>
     */
    public function get_categories(): array {
        return [ 'general' ];
    }

    /**
     * Get widget scripts.
     * @return array<string>
     */
    public function get_script_depends(): array {
        return [ 'yardlii-core-frontend' ];
    }

    protected function register_controls(): void {
        $this->start_controls_section(
            'section_map',
            [
                'label' => esc_html__( 'Map Settings', 'yardlii-core' ),
            ]
        );

        $this->add_control(
            'latitude',
            [
                'label' => esc_html__( 'Latitude', 'yardlii-core' ),
                'type' => Controls_Manager::TEXT,
                'default' => '43.159374',
                'description' => esc_html__( 'e.g. 43.159374', 'yardlii-core' ),
            ]
        );

        $this->add_control(
            'longitude',
            [
                'label' => esc_html__( 'Longitude', 'yardlii-core' ),
                'type' => Controls_Manager::TEXT,
                'default' => '-79.246864',
                'description' => esc_html__( 'e.g. -79.246864', 'yardlii-core' ),
            ]
        );

        $this->add_control(
            'zoom',
            [
                'label' => esc_html__( 'Zoom Level', 'yardlii-core' ),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 1,
                        'max' => 20,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 10,
                ],
            ]
        );

        $this->add_control(
            'height',
            [
                'label' => esc_html__( 'Height', 'yardlii-core' ),
                'type' => Controls_Manager::SLIDER,
                'range' => [
                    'px' => [
                        'min' => 50,
                        'max' => 1000,
                    ],
                ],
                'default' => [
                    'unit' => 'px',
                    'size' => 400,
                ],
                'selectors' => [
                    '{{WRAPPER}} #yardlii-google-map' => 'height: {{SIZE}}{{UNIT}};',
                ],
            ]
        );

        $this->add_control(
            'style',
            [
                'label' => esc_html__( 'Map Style (JSON)', 'yardlii-core' ),
                'type' => Controls_Manager::CODE,
                'language' => 'json',
                'rows' => 20,
                'description' => esc_html__( 'Paste Google Maps JSON style here.', 'yardlii-core' ),
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output on the frontend.
     */
    protected function render(): void {
        $settings = $this->get_settings_for_display();
        
        // Extract settings
        $lat = isset($settings['latitude']) ? $settings['latitude'] : '43.159374';
        $lng = isset($settings['longitude']) ? $settings['longitude'] : '-79.246864';
        $zoom = isset($settings['zoom']['size']) ? $settings['zoom']['size'] : 10;
        $style = isset($settings['style']) ? $settings['style'] : '';

        // Render the container. The JS in frontend.js will pick this up.
        ?>
        <div id="yardlii-google-map"
             data-lat="<?php echo esc_attr( $lat ); ?>"
             data-lng="<?php echo esc_attr( $lng ); ?>"
             data-zoom="<?php echo esc_attr( $zoom ); ?>"
             data-style="<?php echo esc_attr( $style ); ?>"
        ></div>
        <?php
    }
}