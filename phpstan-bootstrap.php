<?php
// Minimal bootstrap for static analysis
define('ABSPATH', __DIR__ . '/'); // silence some WP checks
require __DIR__ . '/vendor/autoload.php';

/* =====================================================
 * MOCKS for External Dependencies (PHPStan Only)
 * ===================================================== */
namespace Elementor {
    if (!class_exists('Elementor\Widget_Base')) {
        class Widget_Base {
            public function get_name() {}
            public function get_title() {}
            public function get_icon() {}
            public function get_categories() {}
            public function get_script_depends() {}
            protected function register_controls() {}
            protected function render() {}
            protected function start_controls_section($id, array $args = []) {}
            protected function end_controls_section() {}
            protected function add_control($id, array $args = []) {}
            protected function get_settings_for_display() { return []; }
        }
    }

    if (!class_exists('Elementor\Controls_Manager')) {
        class Controls_Manager {
            const TAB_CONTENT = 'content';
            const TEXT = 'text';
            const SLIDER = 'slider';
            const CODE = 'code';
        }
    }
}
