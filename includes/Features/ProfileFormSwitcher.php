<?php

declare(strict_types=1);

namespace Yardlii\Core\Features;

/**
 * Feature: Dynamic Profile Form Switcher
 * Swaps the WPUF Edit Profile Form ID based on user roles.
 */
class ProfileFormSwitcher {

    public function register(): void {
        add_filter('wpuf_edit_profile_form_id', [$this, 'switch_form_by_role'], 20, 1);
    }

    /**
     * @param int $original_form_id
     * @return int
     */
    public function switch_form_by_role($original_form_id) {
        $user = wp_get_current_user();
        if (empty($user->roles)) {
            return $original_form_id;
        }

        $roles = (array) $user->roles;

        // 1. Administrator
        if (in_array('administrator', $roles, true)) {
            $id = (int) get_option('yardlii_profile_form_admin');
            return $id > 0 ? $id : $original_form_id;
        }

        // 2. Verified Business
        if (in_array('verified_business', $roles, true)) {
            $id = (int) get_option('yardlii_profile_form_business');
            return $id > 0 ? $id : $original_form_id;
        }

        // 3. Contractor Group (Contractor, Pending, Employee)
        $contractor_group = ['verified_contractor', 'pending_verification', 'verified_pro_employee'];
        if (array_intersect($contractor_group, $roles)) {
            $id = (int) get_option('yardlii_profile_form_contractor');
            return $id > 0 ? $id : $original_form_id;
        }

        // 4. Supplier
        if (in_array('verified_supplier', $roles, true)) {
            $id = (int) get_option('yardlii_profile_form_supplier');
            return $id > 0 ? $id : $original_form_id;
        }

        // 5. Basic / Default
        // If they are a subscriber OR if they didn't match any above
        $id = (int) get_option('yardlii_profile_form_basic');
        return $id > 0 ? $id : $original_form_id;
    }
}