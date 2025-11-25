<?php

namespace Yardlii\Core\Features;

/**
 * Class PostingLogic
 * Handles dynamic form switching using the standard WPUF filter.
 * * REVERTED TO SIMPLE MODE:
 * - Uses only the official 'wpuf_edit_post_form_id' hook.
 * - Runs at Priority 999 to override defaults.
 */
class PostingLogic {

    /**
     * Register hooks.
     */
    public function register(): void {
        // Log to confirm the class is actually active
        error_log( '[YARDLII] PostingLogic: Registered and active (Simple Mode).' );

        add_filter( 'wpuf_edit_post_form_id', [ $this, 'dynamic_form_switch' ], 999, 2 );
    }

    /**
     * Forces WPUF to load the appropriate form for the user's CURRENT role.
     *
     * @param int|string $form_id The form ID stored with the post.
     * @param int|string $post_id The post ID being edited.
     * @return int|string
     */
    public function dynamic_form_switch( $form_id, $post_id ) {
        // 1. Log entry to debug.log to prove the hook fired
        // error_log( "[YARDLII] Hook fired on Post $post_id with Form $form_id" );

        $user = wp_get_current_user();
        if ( 0 === $user->ID ) {
            return $form_id;
        }

        // 2. Get Settings
        $pro_form_id         = (int) get_option( 'yardlii_posting_logic_pro_form', 0 );
        $provisional_form_id = (int) get_option( 'yardlii_posting_logic_provisional_form', 0 );

        if ( empty( $pro_form_id ) && empty( $provisional_form_id ) ) {
            return $form_id;
        }

        $roles = (array) $user->roles;
        $verified_roles = [ 'verified_contractor', 'verified_business', 'administrator' ];

        // 3. Logic: Check Verified
        if ( $pro_form_id > 0 && ! empty( array_intersect( $verified_roles, $roles ) ) ) {
            // error_log( "[YARDLII] Switching to Pro Form: $pro_form_id" );
            return $pro_form_id;
        }

        // 4. Logic: Check Provisional
        if ( $provisional_form_id > 0 && in_array( 'pending_verification', $roles, true ) ) {
            // error_log( "[YARDLII] Switching to Provisional Form: $provisional_form_id" );
            return $provisional_form_id;
        }

        return $form_id;
    }
}