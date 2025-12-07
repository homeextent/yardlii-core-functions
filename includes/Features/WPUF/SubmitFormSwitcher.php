<?php

declare(strict_types=1);

namespace Yardlii\Core\Features\WPUF;
/**
 * Feature: Dynamic Submit Form Switcher
 * Renders the correct "Submit Listing" form based on user role.
 * Reuses settings from 'Smart Posting Logic'.
 */
class SubmitFormSwitcher {

    public function register(): void {
        add_shortcode('yardlii_submit_listing', [$this, 'render_smart_submit_form']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_tab_linker']);
    }

    public function enqueue_tab_linker(): void {
        // Load on Dashboard page (slug 'dashboard' or ID 4965)
        if (is_page('dashboard') || is_page(4965)) {
            wp_enqueue_script(
                'yardlii-tab-linker',
                defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL . 'assets/js/elementor-tab-linker.js' : '',
                [],
                defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0',
                true
            );
        }
    }

    /**
     * Usage: [yardlii_submit_listing]
     *
     * @param array<string, mixed>|string|null $atts
     * @return string
     */
    public function render_smart_submit_form($atts): string {
        if (!is_user_logged_in()) {
            return '<div class="yardlii-alert">Please log in to post a listing.</div>';
        }

        $user = wp_get_current_user();
        $roles = (array) $user->roles;

        // 1. Get Configured IDs (Reusing v3.18.4 settings)
        $basic_id = (int) get_option('yardlii_posting_logic_basic_form', 0);
        $pro_id   = (int) get_option('yardlii_posting_logic_pro_form', 0);

        // 2. Determine Target Form
        $target_id = $basic_id; // Default to Basic

        // Pro Roles get the Pro Form
        $pro_roles = ['verified_business', 'verified_contractor', 'administrator'];
        
        if (array_intersect($pro_roles, $roles)) {
            if ($pro_id > 0) {
                $target_id = $pro_id;
            }
        }

        // 3. Safety Check
        if ($target_id <= 0) {
            if (current_user_can('administrator')) {
                return 'Admin Notice: No Listing Forms configured in WPUF Customisations.';
            }
            return '';
        }

        // 4. Render WPUF Form
        return do_shortcode('[wpuf_form id="' . $target_id . '"]');
    }
}