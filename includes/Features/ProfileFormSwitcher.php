<?php

declare(strict_types=1);

namespace Yardlii\Core\Features;

/**
 * Feature: Dynamic Profile Form Switcher (v2 - Repeater Logic)
 */
class ProfileFormSwitcher {

    public function register(): void {
        // FIX: Bump priority to 999 to ensure we override any other defaults
        add_filter('wpuf_edit_profile_form_id', [$this, 'switch_form_by_role'], 999, 1);
    }

    /**
     * @param int $original_form_id
     * @return int
     */
    public function switch_form_by_role($original_form_id) {
        if (!is_user_logged_in()) {
            return $original_form_id;
        }

        $user = wp_get_current_user();
        $user_roles = (array) $user->roles;

        // Fetch the repeater map
        $map = get_option('yardlii_profile_form_map', []);
        if (empty($map) || !is_array($map)) {
            return $original_form_id;
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
        return $original_form_id;
    }
}