<?php

namespace Yardlii\Core\Features;

/**
 * Class SmartFormOverrides
 * Applies "Smart Logic" to the Basic Form for Pending Verification users.
 * - Forces "Publish" status (Instant Access).
 * - Forces Redirect to the new post (instead of a "Pending" page).
 */
class SmartFormOverrides {

    public function register(): void {
        add_filter( 'wpuf_add_post_args', [ $this, 'force_publish_status' ], 10, 2 );
        add_filter( 'wpuf_add_post_redirect', [ $this, 'force_redirect' ], 10, 4 );
    }

    /**
     * Force post status to 'publish' if User is Pending AND using Basic Form.
     * * @param array $post_args The arguments for wp_insert_post
     * @param int   $form_id   The form ID being submitted
     * @return array
     */
    public function force_publish_status( array $post_args, int $form_id ): array {
        $user = wp_get_current_user();
        if ( 0 === $user->ID ) return $post_args;

        // Check if this is the Basic Form
        $basic_form_id = (int) get_option( 'yardlii_posting_logic_basic_form', 0 );
        if ( $basic_form_id !== $form_id ) return $post_args;

        // Check if User is Pending Verification
        if ( in_array( 'pending_verification', (array) $user->roles, true ) ) {
            $post_args['post_status'] = 'publish';
        }

        return $post_args;
    }

    /**
     * Force redirect to the newly created post if User is Pending.
     * * @param string $response      The redirect URL
     * @param int    $post_id       The new post ID
     * @param int    $form_id       The form ID
     * @param array  $form_settings Form settings
     * @return string
     */
    public function force_redirect( $response, $post_id, $form_id, $form_settings ) {
        $user = wp_get_current_user();
        if ( 0 === $user->ID ) return $response;

        $basic_form_id = (int) get_option( 'yardlii_posting_logic_basic_form', 0 );
        if ( $basic_form_id !== (int) $form_id ) return $response;

        if ( in_array( 'pending_verification', (array) $user->roles, true ) ) {
            // Force redirect to the new post
            return get_permalink( $post_id );
        }

        return $response;
    }
}