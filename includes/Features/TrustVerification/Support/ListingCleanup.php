<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\TrustVerification\Support;

/**
 * ListingCleanup
 * * Listens for the 'reject' decision.
 * * Trashes any 'pending' or 'draft' listings owned by the rejected user.
 */
final class ListingCleanup
{
    public function register(): void
    {
        // Hook into the decision event (fired by TvDecisionService)
        add_action('yardlii_tv_decision_made', [$this, 'handleRejection'], 10, 4);
    }

    /**
     * @param int    $request_id The ID of the verification request.
     * @param string $action     The decision made.
     * @param int    $user_id    The User ID.
     * @param int    $actor_id   The Actor ID (Admin or 0 for System).
     */
    public function handleRejection(int $request_id, string $action, int $user_id, int $actor_id): void
    {
        // 1. Only run on rejection
        if ($action !== 'reject') {
            return;
        }

        // 2. Find "Unsafe" Listings (Drafts/Pending) by this user
        // We strictly target 'listings' post type to avoid deleting other user data.
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

        // 3. Trash Them
        $count = 0;
        foreach ($unsafe_posts as $post_id) {
            // wp_trash_post() is safer than wp_delete_post() as it allows recovery.
            if (wp_trash_post($post_id)) {
                $count++;
            }
        }

        // 4. Log it to the Request History
        if ($count > 0) {
            Meta::appendLog(
                $request_id,
                'listings_trashed', // specific log key
                $actor_id,
                [
                    'count'  => $count,
                    'reason' => 'user_rejected'
                ]
            );

            if (defined('YARDLII_DEBUG') && YARDLII_DEBUG) {
                error_log(sprintf(
                    '[YARDLII TV] Trashed %d unverified listings for User %d (Req #%d) on rejection.',
                    $count,
                    $user_id,
                    $request_id
                ));
            }
        }
    }
}