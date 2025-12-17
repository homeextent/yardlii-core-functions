<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\Integrations;

/**
 * Integration: Custom Elementor Map Widget
 * Migrated from standalone plugin (v1.2.2) to Core (v3.31.0).
 * Connects the widget to the Core's global Google Maps loader.
 */
class CustomMapWidget {

    public function register(): void {
        // Register Widget with Elementor
        add_action('elementor/widgets/widgets_registered', [$this, 'register_elementor_widget']);
        
        // Enqueue Assets (Live + Editor)
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('elementor/preview/enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function register_elementor_widget(): void {
        // Ensure Elementor is active
        if (!did_action('elementor/loaded')) {
            return;
        }

        // Include the widget class file
        require_once __DIR__ . '/Elementor/WidgetGoogleMap.php';

        // Register
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type(
            new Elementor\WidgetGoogleMap()
        );
    }

    public function enqueue_assets(): void {
        if (!defined('YARDLII_CORE_URL') || !defined('YARDLII_CORE_VERSION')) {
            return;
        }

        // CSS
        wp_enqueue_style(
            'yardlii-custom-map-widget',
            YARDLII_CORE_URL . 'assets/css/custom-map-widget.css',
            [],
            YARDLII_CORE_VERSION
        );

        // JS - Dependent on jQuery AND our router if available, but technically independent.
        // It simply listens for the window event.
        wp_enqueue_script(
            'yardlii-custom-map-widget',
            YARDLII_CORE_URL . 'assets/js/custom-map-widget.js',
            ['jquery'],
            YARDLII_CORE_VERSION,
            true // Footer
        );
    }
}