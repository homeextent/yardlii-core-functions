<?php

namespace Yardlii\Core\Features;

/**
 * Class PostingLogic
 * Handles dynamic form switching by intercepting low-level metadata calls.
 * "God Mode" - Bypasses WPUF permission checks.
 */
class PostingLogic {

    public function register(): void {
        // Hook into get_post_metadata to catch the read BEFORE WPUF sees it
        add_filter( 'get_post_metadata', [ $this, 'intercept_metadata' ], 10, 4 );
    }

    /**
     * Intercepts calls to get_post_meta for '_wpuf_form_id'.
     *
     * @param mixed  $value     The value to return.
     * @param int    $object_id The Post ID.
     * @param string $meta_key  The meta key being requested.
     * @param bool   $single    Whether a single value is requested.
     * @return mixed
     */
    public function intercept_metadata( mixed $value, int $object_id, string $meta_key, bool $single ): mixed {
        if ( '_wpuf_form_id' !== $meta_key ) {
            return $value;
        }

        $user = wp_get_current_user();
        if ( 0 === $user->ID ) {
            return $value;
        }

        // Get Pro Form ID
        $pro_form_id = (int) get_option( 'yardlii_posting_logic_pro_form', 0 );
        if ( empty( $pro_form_id ) ) {
            return $value;
        }

        $roles          = (array) $user->roles;
        $verified_roles = [ 'verified_contractor', 'verified_business', 'administrator' ];

        // LOGIC: If user is Verified, Force Pro Form.
        // Everyone else (Basic, Pending) stays on their original form (handled by DB).
        if ( ! empty( array_intersect( $verified_roles, $roles ) ) ) {
            return $pro_form_id;
        }

        return $value;
    }
}