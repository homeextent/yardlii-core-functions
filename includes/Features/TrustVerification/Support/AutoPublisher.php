<?php

namespace Yardlii\Core\Features\TrustVerification\Support;

use WP_Query;

/**
 * Class AutoPublisher
 * Automatically publishes pending listings when a user is verified.
 * Includes Debug Logging.
 */
class AutoPublisher {

    public function register(): void {
        add_action('yardlii_tv_decision_made', [$this, 'handleDecision'], 10, 4);
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

    public function handleDecision(int $request_id, string $action, int $user_id, int $actor_id): void {
        // 1. Only run on Approval
        if ($action !== 'approve') {
            return;
        }

        // 2. Debug Log Start
        error_log( sprintf( '[YARDLII] AutoPublisher: Triggered for User %d (Request %d)', $user_id, $request_id ) );

        // 3. Get Safe Forms
        $allowed_forms = $this->get_allowed_form_ids();
        if (empty($allowed_forms)) {
            error_log( '[YARDLII] AutoPublisher: Aborting. No "Basic Form ID" configured in settings.' );
            return;
        }

        // 4. Define Post Types
        // Check if "CPT Listings" uses a slug other than these defaults
        $post_types = apply_filters('yardlii_tv_auto_publish_post_types', ['post', 'listing', 'job_listing', 'product']);

        // 5. Query
        $args = [
            'post_type'      => $post_types,
            'post_status'    => 'pending', // Only pending posts
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
            '[YARDLII] AutoPublisher: Searching for PENDING posts. Form IDs: [%s]. Found: %d posts.', 
            implode(',', $allowed_forms), 
            count($query->posts)
        ));

        if (empty($query->posts)) {
            return;
        }

        // 6. Publish
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

        error_log( "[YARDLII] AutoPublisher: Successfully published $published_count posts." );

        // 7. Log to TV History
        if (class_exists(Meta::class)) {
            Meta::appendLog($request_id, 'auto_publish', $actor_id, [
                'count' => $published_count,
                'ids'   => implode(',', array_map('strval', $posts))
            ]);
        }
    }
}