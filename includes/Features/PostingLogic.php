<?php

namespace Yardlii\Core\Features;

/**
 * Class PostingLogic
 * Handles dynamic form switching by intercepting WPUF at multiple levels.
 */
class PostingLogic {

    /**
     * Register hooks.
     */
    public function register(): void {
        // CONFIRMATION LOG: Prove this class is loaded
        error_log( '[YARDLII] PostingLogic: Class registered and listening.' );

        // 1. The "God Mode" DB Intercept (Priority 10)
        add_filter( 'get_post_metadata', [ $this, 'intercept_metadata' ], 10, 4 );

        // 2. The Standard WPUF Filter (Priority 999)
        add_filter( 'wpuf_edit_post_form_id', [ $this, 'dynamic_switch' ], 999, 2 );
        
        // 3. The Shortcode Attribute Intercept (Catch-all)
        add_filter( 'shortcode_atts_wpuf_edit', [ $this, 'intercept_shortcode' ], 999, 3 );
    }

    /**
     * Helper: Central logic to determine the correct Form ID for the current user.
     * Returns 0 if no override is needed.
     */
    private function get_target_form_id(): int {
        $user = wp_get_current_user();
        if ( 0 === $user->ID ) {
            return 0;
        }

        $pro_form_id         = (int) get_option( 'yardlii_posting_logic_pro_form', 0 );
        $provisional_form_id = (int) get_option( 'yardlii_posting_logic_provisional_form', 0 );

        if ( empty( $pro_form_id ) && empty( $provisional_form_id ) ) {
            return 0;
        }

        $roles = (array) $user->roles;
        $verified_roles = [ 'verified_contractor', 'verified_business', 'administrator' ];

        // Check Verified
        if ( $pro_form_id > 0 && ! empty( array_intersect( $verified_roles, $roles ) ) ) {
            return $pro_form_id;
        }

        // Check Provisional
        if ( $provisional_form_id > 0 && in_array( 'pending_verification', $roles, true ) ) {
            return $provisional_form_id;
        }

        return 0;
    }

    /**
     * Hook 1: Low-level Metadata Intercept
     */
    public function intercept_metadata( $value, $object_id, $meta_key, $single ) {
        if ( '_wpuf_form_id' !== $meta_key ) {
            return $value;
        }

        $target_id = $this->get_target_form_id();
        if ( $target_id > 0 ) {
            // error_log( "[YARDLII-META] Override Post $object_id to Form $target_id" );
            return $target_id;
        }

        return $value;
    }

    /**
     * Hook 2: Standard WPUF Filter
     */
    public function dynamic_switch( $form_id, $post_id ) {
        $target_id = $this->get_target_form_id();
        if ( $target_id > 0 ) {
            error_log( "[YARDLII-FILTER] Switching Post $post_id to Form $target_id" );
            return $target_id;
        }
        return $form_id;
    }

    /**
     * Hook 3: Shortcode Attributes
     * This forces the form ID even if WPUF ignored the post meta.
     */
    public function intercept_shortcode( $out, $pairs, $atts ) {
        $target_id = $this->get_target_form_id();
        if ( $target_id > 0 ) {
            error_log( "[YARDLII-SHORTCODE] Forcing Form ID: $target_id" );
            $out['id'] = $target_id;
            $out['form_id'] = $target_id; // Just in case
        }
        return $out;
    }
}