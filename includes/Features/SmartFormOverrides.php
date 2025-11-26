<?php

namespace Yardlii\Core\Features;

/**
 * Class SmartFormOverrides
 * Applies "Smart Logic" to the Basic Form for Pending Verification users.
 * - Forces "Publish" status (Instant Access).
 * - Forces Redirect to the new post.
 * - Enforces STRICT 1-post limit (counting Pending/Drafts).
 */
class SmartFormOverrides {

    public function register(): void {
        add_filter( 'wpuf_add_post_args', [ $this, 'force_publish_status' ], 10, 2 );
        add_filter( 'wpuf_add_post_redirect', [ $this, 'force_redirect' ], 10, 4 );
        
        // STRICT LIMIT CHECK: Runs before the form outputs HTML
        add_action( 'wpuf_before_form_render', [ $this, 'enforce_strict_limit' ], 10, 1 );
    }

    /**
     * Prevent double-submissions by checking for ANY existing post (Pending included).
     * * @param int $form_id
     */
    public function enforce_strict_limit( int $form_id ): void {
        $user = wp_get_current_user();
        if ( 0 === $user->ID ) return;

        // 1. Check if this is the Basic Form
        $basic_form_id = (int) get_option( 'yardlii_posting_logic_basic_form', 0 );
        if ( $basic_form_id !== $form_id ) return;

        // 2. Count EXISTING posts (Pending, Draft, Publish, etc.)
        // WPUF's built-in limit often ignores Pending posts. We fix that here.
        $query = new \WP_Query([
            'post_type'      => 'any',
            'post_status'    => ['pending', 'draft', 'publish', 'future'],
            'author'         => $user->ID,
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'   => '_wpuf_form_id',
                    'value' => $form_id,
                ]
            ]
        ]);

        if ( ! empty( $query->posts ) ) {
            // Found an existing post! Block this form.
            $edit_url = home_url( '/dashboard/' ); // Fallback to generic dashboard
            
            // Try to find the edit page if we can
            // (Assuming standard WPUF dashboard page setup)
            
            echo '<div class="yardlii-alert yardlii-alert--warning">';
            echo '<h3>' . esc_html__('You already have a listing.', 'yardlii-core') . '</h3>';
            echo '<p>' . esc_html__('You have already submitted a listing using this form. Please verify your account or edit your existing listing.', 'yardlii-core') . '</p>';
            echo '<p><a href="' . esc_url($edit_url) . '" class="button">' . esc_html__('Go to Dashboard', 'yardlii-core') . '</a></p>';
            echo '</div>';

            // HACK: Hide the actual form that follows immediately after this hook
            echo '<style>form.wpuf-form-add { display: none !important; }</style>';
        }
    }

    /**
     * Force post status to 'publish' if User is Pending AND using Basic Form.
     *
     * @param array<string, mixed> $post_args The arguments for wp_insert_post
     * @param int                  $form_id   The form ID being submitted
     * @return array<string, mixed>
     */
    public function force_publish_status( array $post_args, int $form_id ): array {
        $user = wp_get_current_user();
        if ( 0 === $user->ID ) return $post_args;

        $basic_form_id = (int) get_option( 'yardlii_posting_logic_basic_form', 0 );
        if ( $basic_form_id !== $form_id ) return $post_args;

        if ( in_array( 'pending_verification', (array) $user->roles, true ) ) {
            $post_args['post_status'] = 'publish';
        }

        return $post_args;
    }

    /**
     * Force redirect to the newly created post if User is Pending.
     *
     * @param mixed  $response      The redirect URL (string) OR response array.
     * @param int    $post_id       The new post ID
     * @param int    $form_id       The form ID
     * @param mixed  $form_settings Form settings
     * @return mixed
     */
    public function force_redirect( mixed $response, int $post_id, int $form_id, mixed $form_settings ): mixed {
        $user = wp_get_current_user();
        if ( 0 === $user->ID ) return $response;

        $basic_form_id = (int) get_option( 'yardlii_posting_logic_basic_form', 0 );
        if ( $basic_form_id !== (int) $form_id ) return $response;

        if ( in_array( 'pending_verification', (array) $user->roles, true ) ) {
            $url = get_permalink( $post_id );

            if ( is_array( $response ) ) {
                $response['redirect_to'] = $url;
                $response['show_message'] = false; 
                return $response;
            }

            return $url;
        }

        return $response;
    }
}