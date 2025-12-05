<?php

namespace Yardlii\Core\Features\TrustVerification\Support;

use WP_Query;
use Yardlii\Core\Services\Logger; // Added Import

/**
 * Class AutoPublisher
 * Automatically publishes pending listings when a user is verified.
 */
class AutoPublisher {

    public function register(): void {
        add_action('yardlii_tv_decision_made', [$this, 'handleDecision'], 10, 4);
        add_action('set_user_role', [$this, 'handleRoleChange'], 10, 3);
    }

    private function get_allowed_form_ids(): array {
        $ids = [];
        $basic_id = (int) get_option('yardlii_posting_logic_basic_form', 0);
        if ($basic_id > 0) {
            $ids[] = $basic_id;
        }
        return apply_filters('yardlii_tv_auto_publish_forms', array_filter($ids));
    }

    public function handleRoleChange(int $user_id, string $new_role, array $old_roles): void {
        if ($new_role !== 'pending_verification') {
            return;
        }
        $this->runPublishing($user_id, 0, 'role_switch');
    }

    public function handleDecision(int $request_id, string $action, int $user_id, int $actor_id): void {
        if ($action !== 'approve') {
            return;
        }
        $this->runPublishing($user_id, $actor_id, 'tv_decision', $request_id);
    }

    private function runPublishing(int $user_id, int $actor_id, string $context, int $request_id = 0): void {
        if ($user_id < 1) return;

        Logger::log(sprintf('AutoPublisher: Triggered for User %d (Context: %s)', $user_id, $context), 'TV');

        $allowed_forms = $this->get_allowed_form_ids();
        if (empty($allowed_forms)) {
            Logger::log('AutoPublisher: Aborting. No "Basic Form ID" configured.', 'TV');
            return;
        }

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

        Logger::log(sprintf('AutoPublisher: Found %d pending posts to publish.', count($query->posts)), 'TV');

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

        if ($request_id > 0 && $published_count > 0 && class_exists(Meta::class)) {
            Meta::appendLog($request_id, 'auto_publish', $actor_id, [
                'count' => $published_count,
                'ids'   => implode(',', array_map('strval', $posts))
            ]);
        }
    }
}