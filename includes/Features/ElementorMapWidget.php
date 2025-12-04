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

    /**
     * @param mixed $widgets_manager Elementor widgets manager instance
     */
    public function register_widget( mixed $widgets_manager ): void {
        require_once __DIR__ . '/ElementorMapWidgetClass.php';
        $widgets_manager->register(new \Yardlii\Core\Features\ElementorMapWidgetClass());
    }
}