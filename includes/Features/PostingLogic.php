<?php

namespace Yardlii\Core\Features;

/**
 * Class PostingLogic
 * Handles dynamic form switching by intercepting low-level metadata calls.
 */
class PostingLogic {

    /**
     * Register hooks.
     */
    public function register(): void {
        // "God Mode" Hook: Intercepts the database read itself.
        // This runs BEFORE WPUF checks permissions.
        add_filter( 'get_post_metadata', [ $this, 'intercept_form_id' ], 10, 4 );
    }

    /**
     * Intercepts calls to get_post_meta for '_wpuf_form_id'.
     *
     * @param mixed  $value     The value to return (null means "continue to DB").
     * @param int    $object_id The Post ID.
     * @param string $meta_key  The meta key being requested.
     * @param bool   $single    Whether a single value is requested.
     * @return mixed
     */
    public function intercept_form_id( $value, $object_id, $meta_key, $single ) {
        // 1. Performance Gate: ONLY run for our specific key
        if ( '_wpuf_form_id' !== $meta_key ) {
            return $value;
        }

        // 2. Safety: Ensure we have a user
        $user = wp_get_current_user();
        if ( 0 === $user->ID ) {
            return $value;
        }

        // 3. Load Settings
        $pro_form_id         = (int) get_option( 'yardlii_posting_logic_pro_form', 0 );
        $provisional_form_id = (int) get_option( 'yardlii_posting_logic_provisional_form', 0 );

        // If settings are off, let DB handle it.
        if ( empty( $pro_form_id ) && empty( $provisional_form_id ) ) {
            return $value;
        }

        $roles = (array) $user->roles;

        // 4. Debugging (Optional: writes to error_log if Debug Mode is ON)
        $debug_mode = (bool) get_option( 'yardlii_debug_mode', false );
        if ( $debug_mode ) {
            // Rate limit logs slightly or just log:
            // error_log( "[YARDLII-META] Intercepting Form ID for Post $object_id" );
        }

        // 5. Logic: Check Roles and Return NEW ID directly
        // This effectively "hides" the old ID from WPUF entirely.

        // Check Verified
        $verified_roles = [ 'verified_contractor', 'verified_business', 'administrator' ];
        if ( $pro_form_id > 0 && ! empty( array_intersect( $verified_roles, $roles ) ) ) {
             // We return the integer ID. WPUF expects a string or int.
            return $pro_form_id;
        }

        // Check Provisional
        if ( $provisional_form_id > 0 && in_array( 'pending_verification', $roles, true ) ) {
            return $provisional_form_id;
        }

        // Default: Return $value (null) to let WordPress fetch the real DB value
        return $value;
    }
}