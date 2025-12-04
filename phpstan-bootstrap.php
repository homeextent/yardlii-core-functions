<?php

/* =====================================================
 * GLOBAL BOOTSTRAP (Constants & Autoloaders)
 * ===================================================== */
namespace {
    // Define test constants if not already defined
    if (!defined('YARDLII_CORE_PATH')) {
        define('YARDLII_CORE_PATH', __DIR__ . '/');
    }
    if (!defined('YARDLII_CORE_URL')) {
        define('YARDLII_CORE_URL', 'http://example.org/wp-content/plugins/yardlii-core/');
    }
    if (!defined('YARDLII_CORE_VERSION')) {
        define('YARDLII_CORE_VERSION', '0.0.0');
    }

    // Load Composer autoloader
    $autoloader = __DIR__ . '/vendor/autoload.php';
    if (file_exists($autoloader)) {
        require_once $autoloader;
    }

    // Mock Action Scheduler if missing
    if (!class_exists('ActionScheduler_Store')) {
        class ActionScheduler_Store {
            public function save_action($action) {}
        }
        function as_get_scheduled_actions($args = [], $return_format = '') { return []; }
        function as_schedule_single_action($timestamp, $hook, $args = [], $group = '') {}
    }
}

/* =====================================================
 * MOCKS for Elementor (Fixes "Unknown Class" Errors)
 * ===================================================== */
namespace Elementor {
    // 1. Mock Widget_Base
    if (!class_exists('Elementor\Widget_Base')) {
        abstract class Widget_Base {
            // Core identification methods
            public function get_name() {}
            public function get_title() {}
            public function get_icon() {}
            public function get_categories() {}
            public function get_script_depends() {}

            // Methods used for controls and rendering
            protected function start_controls_section($section_id, array $args = []) {}
            protected function end_controls_section() {}
            protected function add_control($id, array $args = []) {}
            protected function get_settings_for_display() { return []; }
            
            // Abstract methods required by the real class
            protected function register_controls() {}
            protected function render() {}
        }
    }

    // 2. Mock Controls_Manager (and its Constants)
    if (!class_exists('Elementor\Controls_Manager')) {
        class Controls_Manager {
            const TAB_CONTENT = 'content';
            const TEXT = 'text';
            const SLIDER = 'slider';
            const CODE = 'code';
            const SELECT = 'select';
            const SWITCHER = 'switcher';
            const COLOR = 'color';
        }
    }
}