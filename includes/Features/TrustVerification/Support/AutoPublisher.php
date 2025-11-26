<?php

namespace Yardlii\Core\Features\TrustVerification\Support;

use WP_Query;

/**
 * Class AutoPublisher
 * Automatically publishes pending listings when a user is verified,
 * BUT only if they were created by specific "safe" forms.
 */
class AutoPublisher {

    public function register(): void {
        add_action('yardlii_tv_decision_made', [$this, 'handleDecision'], 10, 4);
    }

    /**
     * Define which WPUF Forms are "safe" to auto-publish.
     * This targets the "Basic Member" form defined in settings.
     *
     * @return array<int>
     */
    private function get_allowed_form_ids(): array {
        $ids = [];

        // 1. Get Basic Form ID from Settings
        // This targets the "Basic Member" form where Pending users create their drafts.
        $basic_id = (int) get_option('yardlii_posting_logic_basic_form', 0);
        if ($basic_id > 0) {
            $ids[] = $basic_id;
        }

        // 2. Allow filters (in case you need to add legacy forms)
        return apply_filters('yardlii_tv_auto_publish_forms', array_filter($ids));
    }

    public function handleDecision(int $request_id, string $action, int $user_id, int $actor_id): void {
        // 1. Only run on Approval
        // FIX: Use literal 'approve' to avoid undefined constant error
        if ($action !== 'approve') {
            return;
        }

        // 2. Security Check
        if ($user_id < 1) {
            return;
        }

        // 3. Get Safe Forms
        $allowed_forms = $this->get_allowed_form_ids();
        
        // Safety: If no forms are defined, DO NOT run.
        if (empty($allowed_forms)) {
            return;
        }

        // 4. Find Target Posts
        // We look for PENDING posts created by the allowed forms.
        $args = [
            'post_type'      => ['post', 'listing', 'job_listing', 'product'], // Adjust CPTs as needed
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

        if (empty($query->posts)) {
            return;
        }

        // 5. Publish Them
        $published_count = 0;
        
        /** @var array<int> $posts Help PHPStan see these as integers */
        $posts = $query->posts;

        foreach ($posts as $post_id) {
            $update = [
                'ID'          => $post_id,
                'post_status' => 'publish'
            ];
            
            wp_update_post($update);
            $published_count++;
        }

        // 6. Log the action
        // Note: We removed "if ($published_count > 0)" because strictly speaking,
        // if $query->posts was not empty (checked above), count is guaranteed > 0.
        if (class_exists(Meta::class)) {
            Meta::appendLog($request_id, 'auto_publish', $actor_id, [
                'count' => $published_count,
                // FIX: Cast ints to strings for implode to satisfy strict types
                'ids'   => implode(',', array_map('strval', $posts))
            ]);
        }
    }
}