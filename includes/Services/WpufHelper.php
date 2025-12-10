<?php
declare(strict_types=1);

namespace Yardlii\Core\Services;

/**
 * Service to centralize WPUF form ID lookups and related post metadata resolution.
 */
final class WpufHelper
{
    /**
     * Retrieves the WPUF Form ID associated with a post.
     * This method is the single source of truth for post-to-form resolution.
     * * @param int $postId
     * @return int The WPUF form ID, or 0 if not found.
     */
    public static function getFormIdForPost(int $postId): int
    {
        if ($postId <= 0) {
            return 0;
        }

        // Standard WPUF meta key
        $formId = get_post_meta($postId, '_wpuf_form_id', true);

        return is_numeric($formId) ? (int) $formId : 0;
    }
}