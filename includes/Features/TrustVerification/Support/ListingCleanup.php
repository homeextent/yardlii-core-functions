<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\TrustVerification\Support;

use Yardlii\Core\Services\Logger; // Added Import

/**
 * ListingCleanup
 * Trashes pending listings on user rejection.
 */
final class ListingCleanup
{
    public function register(): void
    {
        add_action('yardlii_tv_decision_made', [$this, 'handleRejection'], 10, 4);
    }

    public function handleRejection(int $request_id, string $action, int $user_id, int $actor_id): void
    {
        if ($action !== 'reject') {
            return;
        }

        $unsafe_posts = get_posts([
            'post_type'      => 'listings',
            'post_status'    => ['pending', 'draft', 'pending_review', 'auto-draft'],
            'author'         => $user_id,
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'no_found_rows'  => true
        ]);

        if (empty($unsafe_posts)) {
            return;
        }

        $count = 0;
        foreach ($unsafe_posts as $post_id) {
            if (wp_trash_post($post_id)) {
                $count++;
            }
        }

        if ($count > 0) {
            Meta::appendLog(
                $request_id,
                'listings_trashed', 
                $actor_id,
                [
                    'count'  => $count,
                    'reason' => 'user_rejected'
                ]
            );

            Logger::log(sprintf(
                'Trashed %d unverified listings for User %d (Req #%d) on rejection.',
                $count,
                $user_id,
                $request_id
            ), 'TV');
        }
    }
}