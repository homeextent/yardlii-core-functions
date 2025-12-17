<?php
namespace Yardlii\Core\Features\Integrations\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;

if (!defined('ABSPATH')) exit;

class WidgetGoogleMap extends Widget_Base {

    public function get_name(): string {
        return 'custom_google_map';
    }

    public function get_title(): string {
        return __('Custom Google Map', 'yardlii-core');
    }

    public function get_icon(): string {
        return 'eicon-google-maps';
    }

    /** @return string[] */
    public function get_categories(): array {
        return ['general'];
    }

    protected function _register_controls(): void {
        
        $this->start_controls_section(
            'section_content',
            ['label' => __('Content', 'yardlii-core')]
        );

        $this->add_control(
            'address',
            [
                'label' => __('Address', 'yardlii-core'),
                'type' => Controls_Manager::TEXT,
                'dynamic' => ['active' => true],
                'placeholder' => __('Leave empty to auto-detect', 'yardlii-core'),
                'description' => __('Auto-detects from: yardlii_display_city_province, yardlii_listing_postal_code, or yardlii_listing_location.', 'yardlii-core')
            ]
        );

        $this->add_control(
            'zoom',
            [
                'label' => __('Zoom Level', 'yardlii-core'),
                'type' => Controls_Manager::NUMBER,
                'default' => 14,
            ]
        );

        $this->add_control(
            'map_height',
            [
                'label' => __('Map Height (px)', 'yardlii-core'),
                'type' => Controls_Manager::NUMBER,
                'default' => 400,
            ]
        );

        $this->end_controls_section();

        // --- Radius Section ---
        $this->start_controls_section(
            'section_radius',
            ['label' => __('Radius Circle', 'yardlii-core')]
        );

        $this->add_control(
            'show_radius',
            [
                'label' => __('Show Radius', 'yardlii-core'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'auto_zoom_to_radius',
            [
                'label' => __('Auto-zoom to Radius', 'yardlii-core'),
                'type' => Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'no',
                'condition' => ['show_radius' => 'yes'],
            ]
        );

        $this->add_control(
            'radius_km',
            [
                'label' => __('Radius (km)', 'yardlii-core'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'min' => 0.1,
                'step' => 0.1,
                'condition' => ['show_radius' => 'yes'],
            ]
        );

        $this->add_control(
            'radius_fill_color',
            [
                'label' => __('Fill Color', 'yardlii-core'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(66, 133, 244, 0.2)',
                'condition' => ['show_radius' => 'yes'],
            ]
        );

        $this->add_control(
            'radius_stroke_color',
            [
                'label' => __('Border Color', 'yardlii-core'),
                'type' => Controls_Manager::COLOR,
                'default' => 'rgba(66, 133, 244, 0.5)',
                'condition' => ['show_radius' => 'yes'],
            ]
        );

        $this->add_control(
            'radius_stroke_weight',
            [
                'label' => __('Border Weight', 'yardlii-core'),
                'type' => Controls_Manager::NUMBER,
                'default' => 1,
                'condition' => ['show_radius' => 'yes'],
            ]
        );

        $this->end_controls_section();

        // --- Style Tab ---
        $this->start_controls_section(
            'section_style_container',
            [
                'label' => __('Container', 'yardlii-core'),
                'tab' => Controls_Manager::TAB_STYLE,
            ]
        );

        $this->add_control(
            'border_radius',
            [
                'label' => __('Border Radius', 'yardlii-core'),
                'type' => Controls_Manager::DIMENSIONS,
                'size_units' => ['px', '%', 'em'],
                'selectors' => [
                    '{{WRAPPER}} .cegm-map-container' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render(): void {
        $settings = $this->get_settings_for_display();
        $address_value = $settings['address'];

        // Auto-Detect Logic
        if (empty($address_value)) {
            $post_id = get_the_ID();
            
            // Handle Elementor Editor context
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                // If in editor and post_id is not set correctly, fallback
                if (!$post_id) $post_id = get_option('page_on_front'); 
            }

            if ($post_id) {
                // Priority 1: Privacy Engine
                $address_value = get_post_meta($post_id, 'yardlii_display_city_province', true);
                
                // Priority 2: Raw Postal Code
                if (empty($address_value)) {
                    $address_value = get_post_meta($post_id, 'yardlii_listing_postal_code', true);
                }
                
                // Priority 3: Legacy
                if (empty($address_value)) {
                    $address_value = get_post_meta($post_id, 'yardlii_listing_location', true);
                }
            }
        }

        $final_address_string = $this->parse_address($address_value);

        // Fallback for Editor (to prevent empty boxes)
        if (empty($final_address_string) && \Elementor\Plugin::$instance->editor->is_edit_mode()) {
            $final_address_string = 'St. Catharines, ON';
        }

        // Don't render empty container on frontend
        if (empty($final_address_string)) {
            return;
        }

        ?>
        <div class="cegm-map-container"
             data-address="<?php echo esc_attr($final_address_string); ?>"
             data-zoom="<?php echo esc_attr($settings['zoom']); ?>"
             data-show-radius="<?php echo esc_attr($settings['show_radius']); ?>"
             data-auto-zoom="<?php echo esc_attr($settings['auto_zoom_to_radius']); ?>"
             data-radius-km="<?php echo esc_attr($settings['radius_km']); ?>"
             data-radius-fill-color="<?php echo esc_attr($settings['radius_fill_color']); ?>"
             data-radius-stroke-color="<?php echo esc_attr($settings['radius_stroke_color']); ?>"
             data-radius-stroke-weight="<?php echo esc_attr($settings['radius_stroke_weight']); ?>"
             style="width:100%; height:<?php echo esc_attr($settings['map_height']); ?>px;">
        </div>
        <?php
    }

    /**
     * Helper to parse complex meta values (WPUF arrays, JSON strings)
     * @param mixed $val
     * @return string
     */
    private function parse_address($val): string {
        if (empty($val)) return '';

        if (is_array($val) || is_object($val)) {
            $arr = (array) $val;
            return $arr['address'] ?? $arr['zip'] ?? $arr['city'] ?? '';
        }

        if (is_string($val)) {
            $trimmed = trim($val);
            if (strpos($trimmed, '{') === 0 || strpos($trimmed, '[') === 0) {
                $decoded = json_decode(html_entity_decode($trimmed), true);
                if (is_array($decoded)) {
                    return $decoded['address'] ?? $decoded['zip'] ?? $decoded['city'] ?? $trimmed;
                }
            }
            return $trimmed;
        }

        return (string) $val;
    }
}