<?php

namespace Yardlii\Core\Features;

/**
 * Class PostingLogic
 * Handles dynamic form switching with FORCED DEBUGGING enabled.
 */
class PostingLogic {

    /**
     * Register hooks.
     */
    public function register(): void {
        // Run very late (999) to override others
        add_filter( 'wpuf_edit_post_form_id', [ $this, 'dynamic_form_switch' ], 999, 2 );
    }

    /**
     * Forces WPUF to load the appropriate form for the user's CURRENT role.
     *
     * @param int $form_id The form ID stored with the post.
     * @param int $post_id The post ID being edited.
     * @return int The filtered form ID.
     */
    public function dynamic_form_switch( $form_id, $post_id ) {
        // NUCLEAR LOG: Fire immediately to confirm hook execution
        error_log( "[YARDLII-DEBUG] Hook fired for Post ID: $post_id. Incoming Form ID: $form_id" );

        // 1. Check User
        $user = wp_get_current_user();
        if ( 0 === $user->ID ) {
            error_log( "[YARDLII-DEBUG] >> Abort: User ID is 0 (Not logged in?)" );
            return $form_id;
        }

        // 2. Get Settings
        $pro_form_id         = (int) get_option( 'yardlii_posting_logic_pro_form', 0 );
        $provisional_form_id = (int) get_option( 'yardlii_posting_logic_provisional_form', 0 );

        error_log( sprintf( 
            "[YARDLII-DEBUG] >> Settings Loaded. Pro ID: %d | Provisional ID: %d", 
            $pro_form_id, 
            $provisional_form_id 
        ));

        // 3. Check Roles
        $roles = (array) $user->roles;
        error_log( "[YARDLII-DEBUG] >> User Roles: " . implode( ', ', $roles ) );

        // 4. Match Logic
        $verified_roles = [ 'verified_contractor', 'verified_business', 'administrator' ];
        
        // Check Verified
        if ( $pro_form_id > 0 && ! empty( array_intersect( $verified_roles, $roles ) ) ) {
            error_log( "[YARDLII-DEBUG] >> MATCH VERIFIED! Switching to Form ID: $pro_form_id" );
            return $pro_form_id;
        }

        // Check Provisional
        if ( $provisional_form_id > 0 && in_array( 'pending_verification', $roles, true ) ) {
            error_log( "[YARDLII-DEBUG] >> MATCH PROVISIONAL! Switching to Form ID: $provisional_form_id" );
            return $provisional_form_id;
        }

        error_log( "[YARDLII-DEBUG] >> No role match found. Returning original." );
        return $form_id;
    }
}