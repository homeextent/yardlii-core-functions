<?php

declare(strict_types=1);

namespace Yardlii\Core\Features;

/**
 * Feature: Dynamic Profile Form Switcher (v2.1 - Option Interceptor)
 * Swaps the WPUF Edit Profile Form ID based on user roles.
 */
class ProfileFormSwitcher {

    public function register(): void {
        // Hook 1: The standard filter (for standalone shortcodes)
        add_filter('wpuf_edit_profile_form_id', [$this, 'switch_form_by_role'], 999, 1);

        // Hook 2: The "God Mode" filter (Intercepts the settings retrieval itself)
        // This fixes the issue on the "My Account" / Dashboard page.
        add_filter('wpuf_get_option', [$this, 'intercept_wpuf_option'], 999, 3);
    }

    /**
     * Intercepts WPUF options to force our dynamic ID.
     * @param mixed $value
     * @param string $option
     * @param string $section
     * @return mixed
     */
    public function intercept_wpuf_option($value, $option, $section) {
        // Only target the specific 'Profile Form' setting in 'My Account'
        if ($option === 'edit_profile_form' && $section === 'wpuf_my_account') {
            // Pass the DB value ($value) to our switcher to see if it needs swapping
            return $this->switch_form_by_role($value);
        }
        return $value;
    }

    /**
     * @param mixed $original_form_id
     * @return int
     */
    public function switch_form_by_role($original_form_id) {
        // Cast to int for safety, default to 0 if empty
        $form_id = (int) $original_form_id;

        if (!is_user_logged_in()) {
            return $form_id;
        }

        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;

        // Fetch the repeater map
        $map = get_option('yardlii_profile_form_map', []);
        if (empty($map) || !is_array($map)) {
            return $form_id;
        }

        // Iterate through rules. First match wins.
        foreach ($map as $rule) {
            $role_slug = $rule['role'] ?? '';
            $target_id = (int) ($rule['form_id'] ?? 0);

            if ($role_slug && $target_id > 0) {
                // Check if user has this role
                if (in_array($role_slug, $user_roles, true)) {
                    return $target_id;
                }
            }
        }

        // Fallback: If no rule matches, return original
        return $form_id;
    }
}