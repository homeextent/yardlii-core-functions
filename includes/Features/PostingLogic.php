<?php

namespace Yardlii\Core\Features;

/**
 * Class PostingLogic
 * Handles dynamic form switching based on user roles using Admin settings.
 */
class PostingLogic {

    /**
     * Register hooks.
     */
    public function register(): void {
        add_filter( 'wpuf_edit_post_form_id', [ $this, 'dynamic_form_switch' ], 10, 2 );
    }

    /**
     * Forces WPUF to load the appropriate form for the user's CURRENT role,
     * overriding the form ID originally saved with the post.
     *
     * @param int $form_id The form ID stored with the post.
     * @param int $post_id The post ID being edited.
     * @return int The filtered form ID.
     */
    public function dynamic_form_switch( $form_id, $post_id ) {
        // Security: Ensure we have a user context
        $user = wp_get_current_user();
        if ( ! $user || ! $user->exists() ) {
            return $form_id;
        }

        // 1. Get Target Form IDs from Settings
        $pro_form_id         = (int) get_option( 'yardlii_posting_logic_pro_form', 0 );
        $provisional_form_id = (int) get_option( 'yardlii_posting_logic_provisional_form', 0 );

        // If settings are not configured, abort and return original ID
        if ( empty( $pro_form_id ) && empty( $provisional_form_id ) ) {
            return $form_id;
        }

        $roles = (array) $user->roles;

        // 2. CHECK: Is User Verified? (Level 2/3)
        $verified_roles = [ 'verified_contractor', 'verified_business', 'administrator' ];
        
        // Only switch if we have a valid Pro Form ID configured
        if ( $pro_form_id > 0 && ! empty( array_intersect( $verified_roles, $roles ) ) ) {
            return $pro_form_id;
        }

        // 3. CHECK: Is User Pending? (Provisional)
        // Only switch if we have a valid Provisional Form ID configured
        if ( $provisional_form_id > 0 && in_array( 'pending_verification', $roles, true ) ) {
            return $provisional_form_id;
        }

        // 4. Default: Return original form
        return $form_id;
    }
}