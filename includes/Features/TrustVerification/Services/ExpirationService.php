<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\TrustVerification\Services;

use Yardlii\Core\Features\TrustVerification\TvDecisionService;
use Yardlii\Core\Features\TrustVerification\Emails\Mailer;
use Yardlii\Core\Features\TrustVerification\Requests\CPT;

/**
 * ExpirationService
 * * Runs a daily check for stale 'pending' requests (> 5 days).
 * * Auto-rejects them, which triggers role reversion and listing cleanup.
 */
final class ExpirationService
{
    public const CRON_HOOK = 'yardlii_tv_daily_expiration_check';

    public function register(): void
    {
        // Register the cron handler
        add_action(self::CRON_HOOK, [$this, 'process_stale_requests']);

        // Ensure the event is scheduled (Idempotent check on admin init)
        add_action('admin_init', [$this, 'ensure_schedule']);
    }

    public function ensure_schedule(): void
    {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'daily', self::CRON_HOOK);
        }
    }

    /**
     * Cron Handler: Expire Stale Requests
     */
    public function process_stale_requests(): void
    {
        // [NEW] Get dynamic days, default to 5
        $days = (int) get_option(\Yardlii\Core\Features\TrustVerification\Settings\GlobalSettings::OPT_EXPIRY_DAYS, 5);
        if ($days < 1) $days = 5; // Safety fallback

        $expiry_limit = $days * DAY_IN_SECONDS;
        $date_cutoff  = gmdate('Y-m-d H:i:s', time() - $expiry_limit);

        // 2. Find Stale Pending Requests
        $stale_requests = get_posts([
            'post_type'      => CPT::POST_TYPE,
            'post_status'    => 'vp_pending',
            'date_query'     => [
                [
                    'column' => 'post_date',
                    'before' => $date_cutoff,
                ],
            ],
            'fields'         => 'ids',
            'posts_per_page' => 20, // Process in batches to prevent timeouts
            'no_found_rows'  => true,
        ]);

        if (empty($stale_requests)) {
            return;
        }

        // 3. Process Each
        // We instantiate the service manually here since this runs in background context
        $decision_service = new TvDecisionService(new Mailer());

        foreach ($stale_requests as $request_id) {
            // Apply 'Reject' decision
            // actor_id = 0 implies System Action
            $success = $decision_service->applyDecision($request_id, 'reject', [
                'actor_id' => 0 
            ]);

            if ($success) {
                // Add specific note about expiration
                \Yardlii\Core\Features\TrustVerification\Support\Meta::appendLog(
                    $request_id, 
                    'auto_expired', 
                    0, 
                    ['limit' => '5_days']
                );

                if (defined('YARDLII_DEBUG') && YARDLII_DEBUG) {
                    error_log("[YARDLII TV] Auto-expired Request #{$request_id}");
                }
            }
        }
    }
}