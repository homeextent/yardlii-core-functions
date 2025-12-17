<?php
/**
 * Stubs for Elementor classes to satisfy PHPStan.
 */

namespace Elementor;

if (!class_exists('Elementor\Widget_Base')) {
    abstract class Widget_Base {
        public function get_name() {}
        public function get_title() {}
        public function get_icon() {}
        public function get_categories() {}
        protected function _register_controls() {}
        protected function render() {}
        
        public function start_controls_section($section_id, array $args = []) {}
        public function end_controls_section() {}
        public function add_control($control_id, array $args = []) {}
        
        /** @return array<string, mixed> */
        public function get_settings_for_display() { return []; }
    }
}

if (!class_exists('Elementor\Controls_Manager')) {
    class Controls_Manager {
        const TEXT = 'text';
        const NUMBER = 'number';
        const SWITCHER = 'switcher';
        const COLOR = 'color';
        const DIMENSIONS = 'dimensions';
        const TAB_STYLE = 'style';
        const TAB_CONTENT = 'content';
        const TAB_ADVANCED = 'advanced';
    }
}

if (!class_exists('Elementor\Plugin')) {
    class Plugin {
        /** @var Plugin */
        public static $instance;
        
        /** @var Editor_Dummy */
        public $editor;
        
        /** @var Widgets_Manager_Dummy */
        public $widgets_manager;

        /** @var Preview_Dummy */
        public $preview;

        /** @return Plugin */
        public static function instance() {
            return new self();
        }
    }

    class Editor_Dummy {
        public function is_edit_mode() { return false; }
    }
    
    class Widgets_Manager_Dummy {
        public function register_widget_type($widget) {}
    }

    class Preview_Dummy {
        public function get_post_id() { return 0; }
    }
}