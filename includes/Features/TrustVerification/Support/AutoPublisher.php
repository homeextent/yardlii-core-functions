<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\TrustVerification\Support;

use Yardlii\Core\Features\TrustVerification\Support\Meta;

/**
 * AutoPublisher
 * * Listens for the 'approve' decision and automatically publishes any 
 * 'pending' listings belonging to the newly verified user.
 */
final class AutoPublisher
{
    public function register(): void
    {
        // Hook into the decision event (fired by TvDecisionService)
        add_action('yardlii_tv_decision_made', [$this, 'handleDecision'], 10, 4);
    }

    /**
     * @param int    $request_id The ID of the verification request.
     * @param string $action     The action taken ('approve', 'reject', etc).
     * @param int    $user_id    The User ID being verified.
     * @param int    $actor_id   The Admin ID who performed the action (or 0 for system).
     */
    public function handleDecision(int $request_id, string $action, int $user_id, int $actor_id): void
    {
        // 1. Safety Check: Only run on explicit approvals
        if ($action !== 'approve') {
            return;
        }

        // 2. Find 'Pending' listings for this user
        $pending_listings = get_posts([
            'post_type'      => 'listings',   // Locked to 'listings' CPT
            'post_status'    => 'pending',    // Locked to 'pending' status
            'author'         => $user_id,
            'posts_per_page' => -1,           // Process all of them
            'fields'         => 'ids',        // Return IDs only for speed
            'no_found_rows'  => true,         // Skip pagination calculation
        ]);

        if (empty($pending_listings)) {
            return;
        }

        $published_count = 0;
        $published_ids   = [];

        // 3. Publish Loop
        foreach ($pending_listings as $post_id) {
            $result = wp_update_post([
                'ID'          => $post_id,
                'post_status' => 'publish'
            ], true); // Return WP_Error on failure

            if (!is_wp_error($result)) {
                $published_count++;
                $published_ids[] = $post_id;
            }
        }

        // 4. Audit Log (Write to the Verification Request History)
        if ($published_count > 0) {
            Meta::appendLog(
                $request_id,
                'auto_publish', // New action key for the history log
                $actor_id,      // Attribute this action to the admin/system who approved
                [
                    'count' => $published_count,
                    'ids'   => implode(',', $published_ids)
                ]
            );

            // Optional: Server-side debug log
            if (defined('YARDLII_DEBUG') && YARDLII_DEBUG) {
                error_log(sprintf(
                    '[YARDLII TV] Auto-published %d listings for User %d (Req #%d).',
                    $published_count, 
                    $user_id, 
                    $request_id
                ));
            }
        }
    }
}