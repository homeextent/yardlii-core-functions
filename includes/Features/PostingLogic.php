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
        // PRIORITY FIX: Increased from 10 to 999 to ensure we override WPUF defaults
        add_filter( 'wpuf_edit_post_form_id', [ $this, 'dynamic_form_switch' ], 999, 2 );
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
        
        // Safety check
        if ( 0 === $user->ID ) {
            return $form_id;
        }

        // 1. Get Target Form IDs from Settings
        $pro_form_id         = (int) get_option( 'yardlii_posting_logic_pro_form', 0 );
        $provisional_form_id = (int) get_option( 'yardlii_posting_logic_provisional_form', 0 );

        // If settings are not configured, abort.
        if ( empty( $pro_form_id ) && empty( $provisional_form_id ) ) {
            return $form_id;
        }

        $roles = (array) $user->roles;

        // DEBUG: Check if Debug Mode is ON in "Advanced > Feature Flags"
        $debug_mode = (bool) get_option( 'yardlii_debug_mode', false );
        if ( $debug_mode ) {
            error_log( sprintf( 
                '[YARDLII] PostingLogic: Checking Post %d. User Roles: %s. Current Form: %d.', 
                $post_id, 
                implode( ',', $roles ), 
                $form_id 
            ));
        }

        // 2. CHECK: Is User Verified? (Level 2/3)
        // Uses array_intersect to check against ANY of these roles
        $verified_roles = [ 'verified_contractor', 'verified_business', 'administrator' ];
        
        if ( $pro_form_id > 0 && ! empty( array_intersect( $verified_roles, $roles ) ) ) {
            if ( $debug_mode ) error_log( "[YARDLII] >> User is Verified. Switching to Form ID: $pro_form_id" );
            return $pro_form_id;
        }

        // 3. CHECK: Is User Pending? (Provisional)
        if ( $provisional_form_id > 0 && in_array( 'pending_verification', $roles, true ) ) {
            if ( $debug_mode ) error_log( "[YARDLII] >> User is Pending. Switching to Form ID: $provisional_form_id" );
            return $provisional_form_id;
        }

        if ( $debug_mode ) error_log( "[YARDLII] >> No match found. Returning original ID: $form_id" );

        // 4. Default: Return original form
        return $form_id;
    }
}