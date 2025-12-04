<?php
namespace Yardlii\Core\Features;

use WP_Query;

/**
 * Class MediaCleanup
 * The "Janitor" for Media Hygiene.
 * * 1. Post Deletion: Deletes attached images when a listing is deleted.
 * 2. Smart Edit: Deletes images removed from a gallery during edit.
 * 3. Ghost Sweeper: Daily deletion of abandoned files (older than 24h).
 * * SAFETY:
 * - Protects User Profile Photos & Logos via direct `usermeta` lookup.
 * - Respects PixRefiner variants for deep cleaning.
 */
class MediaCleanup {

    private const TARGET_CPTS = ['listings']; 
    private const GALLERY_META_KEY = 'yardlii_listing_images'; 
    private const CRON_HOOK = 'yardlii_daily_media_cleanup';
    private const PIXREFINER_WIDTHS = [1920, 1200, 768, 400];

    public function register(): void {
        add_action('before_delete_post', [$this, 'handle_post_deletion']);
        add_action('wpuf_update_post_after_submit', [$this, 'handle_wpuf_update'], 10, 4);
        add_action('admin_init', [$this, 'ensure_schedule']);
        add_action(self::CRON_HOOK, [$this, 'cleanup_ghost_files']);
    }

    /**
     * TIER 1: Listing Deletion
     */
    public function handle_post_deletion(int $post_id): void {
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, self::TARGET_CPTS, true)) {
            return;
        }

        $attachments = get_children([
            'post_parent'    => $post_id,
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'numberposts'    => -1,
            'fields'         => 'ids',
        ]);

        if (empty($attachments)) return;

        foreach ($attachments as $att_id) {
            $this->process_attachment_deletion((int) $att_id);
        }

        $this->log(sprintf('Deleted %d images for Post #%d', count($attachments), $post_id));
    }

    /**
     * TIER 2: Smart Edit (Gallery Cleanup)
     *
     * @param int $post_id
     * @param int $form_id
     * @param array<mixed> $form_settings
     * @param array<mixed> $form_vars
     */
    public function handle_wpuf_update(int $post_id, int $form_id, array $form_settings, array $form_vars): void {
        if (get_post_type($post_id) !== self::TARGET_CPTS[0]) return;

        // A. Get currently attached images
        $attached_images = get_children([
            'post_parent'    => $post_id,
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'fields'         => 'ids',
            'numberposts'    => -1
        ]);

        if (empty($attached_images)) return;

        // B. Get IDs submitted in the form gallery
        $submitted_gallery = [];
        if (isset($form_vars[self::GALLERY_META_KEY])) {
            $raw = $form_vars[self::GALLERY_META_KEY];
            if (is_array($raw)) {
                $submitted_gallery = array_map('intval', $raw);
            } elseif (is_string($raw)) {
                $submitted_gallery = array_map('intval', explode(',', $raw));
            }
        }

        // C. Delete the difference (Orphans)
        $to_delete = array_diff($attached_images, $submitted_gallery);

        if (!empty($to_delete)) {
            foreach ($to_delete as $att_id) {
                $this->process_attachment_deletion((int) $att_id);
            }
            $this->log(sprintf('Cleanup (Edit): Removed %d orphaned images from Post #%d', count($to_delete), $post_id));
        }
    }

    /**
     * TIER 3: Ghost File Sweeper (Daily Cron)
     */
    public function cleanup_ghost_files(): void {
        $query = new WP_Query([
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'post_parent'    => 0, // Unattached
            'posts_per_page' => 50, 
            'fields'         => 'ids',
            'date_query'     => [
                ['before' => '24 hours ago'], // Grace period
            ],
            'meta_query'     => [
                [
                    'key'     => '_yardlii_media_protected',
                    'compare' => 'NOT EXISTS'
                ]
            ]
        ]);

        if (!$query->have_posts()) return;

        $deleted_count = 0;
        $protected_count = 0;

        foreach ($query->posts as $att_id) {
            $att_id = (int) $att_id;

            // SAFETY NET: Check if this "Ghost" is actually a User Profile Picture or Logo
            if ($this->is_attachment_in_use($att_id)) {
                update_post_meta($att_id, '_yardlii_media_protected', 1);
                $protected_count++;
                continue;
            }

            $this->process_attachment_deletion($att_id);
            $deleted_count++;
        }

        if ($deleted_count > 0 || $protected_count > 0) {
            $this->log(sprintf('[Janitor] Deleted %d ghosts. Protected %d user profile images.', $deleted_count, $protected_count));
        }
    }

    /**
     * Checks if an attachment ID is referenced in the usermeta table.
     * This protects ALL user images (Logos, Avatars, etc.) automatically.
     */
    private function is_attachment_in_use(int $att_id): bool {
        global $wpdb;
        
        $sql = "SELECT user_id FROM {$wpdb->usermeta} 
                WHERE meta_value = %s 
                OR meta_value LIKE %s 
                LIMIT 1";
                
        $like_pattern = '%' . $wpdb->esc_like('"' . $att_id . '"') . '%';
        
        $result = $wpdb->get_var($wpdb->prepare($sql, $att_id, $like_pattern));
        
        return !empty($result);
    }

    /**
     * Physical Deletion + PixRefiner variant cleanup
     */
    private function process_attachment_deletion(int $att_id): void {
        $file_path = get_attached_file($att_id);
        
        if ($file_path) {
            $backup_path = $file_path . '.orig';
            if (file_exists($backup_path)) {
                @unlink($backup_path);
            }
            $this->cleanup_pixrefiner_variants($file_path);
        }

        wp_delete_attachment($att_id, true);
    }

    private function cleanup_pixrefiner_variants(string $main_file_path): void {
        $path_info = pathinfo($main_file_path);
        if (!isset($path_info['dirname'], $path_info['extension'])) return;

        $dir = $path_info['dirname'];
        $name = $path_info['filename'];
        $ext = $path_info['extension'];

        foreach (self::PIXREFINER_WIDTHS as $width) {
            $variant_path = $dir . DIRECTORY_SEPARATOR . $name . '-' . $width . '.' . $ext;
            if (file_exists($variant_path)) {
                @unlink($variant_path);
            }
        }
    }

    public function ensure_schedule(): void {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'daily', self::CRON_HOOK);
        }
    }
    
    private function log(string $msg): void {
        if (function_exists('yardlii_log')) {
            yardlii_log('[MediaCleanup] ' . $msg);
        }
    }
}