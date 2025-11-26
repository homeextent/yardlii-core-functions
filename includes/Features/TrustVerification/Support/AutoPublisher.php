<?php

namespace Yardlii\Core\Features\TrustVerification\Support;

use WP_Query;

/**
 * Class AutoPublisher
 * Automatically publishes pending listings when a user is verified
 * OR when they switch to "Pending Verification" status.
 */
class AutoPublisher {

    public function register(): void {
        // 1. Run when Admin clicks "Approve"
        add_action('yardlii_tv_decision_made', [$this, 'handleDecision'], 10, 4);

        // 2. Run when User Role changes (e.g. submitting verification form)
        add_action('set_user_role', [$this, 'handleRoleChange'], 10, 3);
    }

    /**
     * Define which WPUF Forms are "safe" to auto-publish.
     * @return array<int>
     */
    private function get_allowed_form_ids(): array {
        $ids = [];
        $basic_id = (int) get_option('yardlii_posting_logic_basic_form', 0);
        if ($basic_id > 0) {
            $ids[] = $basic_id;
        }
        return apply_filters('yardlii_tv_auto_publish_forms', array_filter($ids));
    }

    /**
     * NEW: Handle Role Switch (Basic -> Pending Verification)
     *
     * @param int           $user_id   The user ID.
     * @param string        $new_role  The new role slug.
     * @param array<string> $old_roles Array of previous role slugs.
     */
    public function handleRoleChange(int $user_id, string $new_role, array $old_roles): void {
        // Only run if the new role is 'pending_verification'
        if ($new_role !== 'pending_verification') {
            return;
        }

        // Run the publishing logic (Actor ID = 0 means System)
        $this->runPublishing($user_id, 0, 'role_switch');
    }

    /**
     * Handle Admin Decision (Pending -> Verified)
     */
    public function handleDecision(int $request_id, string $action, int $user_id, int $actor_id): void {
        if ($action !== 'approve') {
            return;
        }
        $this->runPublishing($user_id, $actor_id, 'tv_decision', $request_id);
    }

    /**
     * Core Publishing Logic
     */
    private function runPublishing(int $user_id, int $actor_id, string $context, int $request_id = 0): void {
        if ($user_id < 1) return;

        error_log( sprintf( '[YARDLII] AutoPublisher: Triggered for User %d (Context: %s)', $user_id, $context ) );

        $allowed_forms = $this->get_allowed_form_ids();
        if (empty($allowed_forms)) {
            error_log( '[YARDLII] AutoPublisher: Aborting. No "Basic Form ID" configured.' );
            return;
        }

        // Support 'listings' plural slug
        $defaults = ['post', 'listing', 'listings', 'job_listing', 'product'];
        $post_types = apply_filters('yardlii_tv_auto_publish_post_types', $defaults);

        $args = [
            'post_type'      => $post_types,
            'post_status'    => 'pending',
            'author'         => $user_id,
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'     => '_wpuf_form_id',
                    'value'   => $allowed_forms,
                    'compare' => 'IN',
                ]
            ]
        ];

        $query = new WP_Query($args);

        error_log( sprintf( 
            '[YARDLII] AutoPublisher: Found %d pending posts to publish.', 
            count($query->posts)
        ));

        if (empty($query->posts)) return;

        $published_count = 0;
        /** @var array<int> $posts */
        $posts = $query->posts;

        foreach ($posts as $post_id) {
            wp_update_post([
                'ID'          => $post_id,
                'post_status' => 'publish'
            ]);
            $published_count++;
        }

        // Log to TV History if we have a request ID (Admin Decision context)
        if ($request_id > 0 && $published_count > 0 && class_exists(Meta::class)) {
            Meta::appendLog($request_id, 'auto_publish', $actor_id, [
                'count' => $published_count,
                'ids'   => implode(',', array_map('strval', $posts))
            ]);
        }
    }
}