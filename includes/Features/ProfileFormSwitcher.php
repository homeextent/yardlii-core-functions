<?php

declare(strict_types=1);

namespace Yardlii\Core\Features;

/**
 * Feature: Dynamic Profile Form Switcher (v3.26.1)
 * - Intercepts settings (God Mode)
 * - Provides [yardlii_edit_profile] wrapper
 */
class ProfileFormSwitcher {

    public function register(): void {
        // 1. Filter for standard WPUF logic
        add_filter('wpuf_edit_profile_form_id', [$this, 'switch_form_by_role'], 999, 1);

        // 2. Filter for Dashboard / My Account interception
        add_filter('wpuf_get_option', [$this, 'intercept_wpuf_option'], 999, 3);

        // 3. NEW: Wrapper Shortcode for manual placement
        add_shortcode('yardlii_edit_profile', [$this, 'render_smart_profile_form']);
    }

    /**
     * Renders the correct WPUF profile form for the current user.
     * Usage: [yardlii_edit_profile]
     * * @param array<string, mixed>|string|null $atts
     * @return string
     */
    public function render_smart_profile_form($atts): string {
        // 1. Calculate the correct ID for this user
        $form_id = $this->switch_form_by_role(0);

        // 2. Safety check: Do we have a valid ID?
        if ($form_id <= 0) {
            if (current_user_can('administrator')) {
                return '<div class="yardlii-alert">Admin Notice: No Profile Form mapped for your role. Check Yardlii Settings.</div>';
            }
            return '';
        }

        // 3. Render the WPUF Shortcode with the dynamic ID
        // We explicitly use type="profile" to ensure this is an EDIT form, not registration.
        return do_shortcode('[wpuf_profile id="' . $form_id . '" type="profile"]');
    }

    /**
     * Intercepts WPUF options to force our dynamic ID.
     * @param mixed $value
     * @param string $option
     * @param string $section
     * @return mixed
     */
    public function intercept_wpuf_option($value, $option, $section) {
        if ($option === 'edit_profile_form' && $section === 'wpuf_my_account') {
            return $this->switch_form_by_role($value);
        }
        return $value;
    }

    /**
     * @param mixed $original_form_id
     * @return int
     */
    public function switch_form_by_role($original_form_id) {
        $form_id = (int) $original_form_id;

        if (!is_user_logged_in()) {
            return $form_id;
        }

        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;

        $map = get_option('yardlii_profile_form_map', []);
        if (empty($map) || !is_array($map)) {
            return $form_id;
        }

        foreach ($map as $rule) {
            $role_slug = $rule['role'] ?? '';
            $target_id = (int) ($rule['form_id'] ?? 0);

            if ($role_slug && $target_id > 0) {
                if (in_array($role_slug, $user_roles, true)) {
                    return $target_id;
                }
            }
        }

        return $form_id;
    }
}