<?php
namespace Yardlii\Core\Features;

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Migrated Map Widget
 * Originally from: yardlii-custom-elementor-google-map
 */
class ElementorMapWidget {

    public function register(): void {
        add_action('elementor/widgets/register', [$this, 'register_widget']);
    }

    public function register_widget($widgets_manager) {
        // We define the class anonymously or require a separate file for the Widget Class itself.
        // For simplicity, we assume the Widget Class logic follows standard Elementor patterns.
        require_once __DIR__ . '/ElementorMapWidgetClass.php';
        $widgets_manager->register(new \Yardlii\Core\Features\ElementorMapWidgetClass());
    }
}