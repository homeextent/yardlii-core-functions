<?php

namespace Yardlii\Core\Features;

/**
 * Class PostingLogic
 * Handles dynamic form switching by intercepting low-level metadata calls.
 * "God Mode" - Bypasses WPUF permission checks by lying to the database reader.
 */
class PostingLogic {

    /**
     * Register hooks.
     */
    public function register(): void {
        // Log that we are active
        error_log( '[YARDLII] PostingLogic: Active (God Mode).' );

        // Hook into get_post_metadata to catch the read BEFORE WPUF sees it
        add_filter( 'get_post_metadata', [ $this, 'intercept_metadata' ], 10, 4 );
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
    public function intercept_metadata( mixed $value, int $object_id, string $meta_key, bool $single ): mixed {
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

        if ( empty( $pro_form_id ) && empty( $provisional_form_id ) ) {
            return $value;
        }

        $roles          = (array) $user->roles;
        $verified_roles = [ 'verified_contractor', 'verified_business', 'administrator' ];

        // 4. Logic: Return the NEW ID directly.
        // This effectively "hides" the old ID from WPUF entirely.

        // Check Verified
        if ( $pro_form_id > 0 && ! empty( array_intersect( $verified_roles, $roles ) ) ) {
             // Log the interception if needed for debugging
             // error_log( "[YARDLII-META] Post $object_id: Swapping to Pro Form $pro_form_id" );
            return $pro_form_id;
        }

        // Check Provisional
        if ( $provisional_form_id > 0 && in_array( 'pending_verification', $roles, true ) ) {
            return $provisional_form_id;
        }

        // Default: Return $value (usually null) to let WordPress fetch the real DB value
        return $value;
    }
}