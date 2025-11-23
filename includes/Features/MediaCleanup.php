<?php
namespace Yardlii\Core\Features;

use WP_Query;

/**
 * Class MediaCleanup
 * Handles 3 types of cleanup:
 * 1. Post Deletion (Listing removed -> Images removed)
 * 2. Edit Updates (Image removed from gallery -> Image deleted)
 * 3. Ghost Files (Abandoned uploads older than 24h -> Deleted)
 * * UPGRADE: Includes explicit logic to remove PixRefiner custom sizes (-400, -768, etc.)
 */
class MediaCleanup {

    /**
     * @var string[] List of Post Types to target for cleanup.
     */
    private const TARGET_CPTS = ['listings']; 

    /**
     * @var string The meta key used by WPUF to store gallery image IDs.
     */
    private const GALLERY_META_KEY = 'yardlii_listing_images'; 

    /**
     * @var string Cron hook name
     */
    private const CRON_HOOK = 'yardlii_daily_media_cleanup';

    /**
     * @var int[] The specific widths PixRefiner generates that WP might miss.
     * Derived from PixRefiner Manual Profile: 1920, 1200, 768, 400
     */
    private const PIXREFINER_WIDTHS = [1920, 1200, 768, 400];

    public function register(): void {
        // 1. Post Deletion Handler
        add_action('before_delete_post', [$this, 'handle_post_deletion']);

        // 2. Edit/Update Handler (WPUF specific)
        add_action('wpuf_update_post_after_submit', [$this, 'handle_wpuf_update'], 10, 4);

        // 3. Ghost File Handler (Cron)
        add_action('admin_init', [$this, 'ensure_schedule']);
        add_action(self::CRON_HOOK, [$this, 'cleanup_ghost_files']);
    }

    /**
     * HANDLER A: Listing Deletion
     *
     * @param int $post_id
     */
    public function handle_post_deletion(int $post_id): void {
        $post = get_post($post_id);
        if (!$post || !in_array($post->post_type, self::TARGET_CPTS, true)) {
            return;
        }

        // Get all images attached to this parent
        $attachments = get_children([
            'post_parent'    => $post_id,
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'numberposts'    => -1,
            'fields'         => 'ids',
        ]);

        if (empty($attachments)) {
            return;
        }

        foreach ($attachments as $att_id) {
            $this->process_attachment_deletion((int) $att_id);
        }

        $this->log(sprintf('Deleted %d images for Post #%d', count($attachments), $post_id));
    }

    /**
     * HANDLER B: WPUF Updates (The Edit Leak)
     *
     * @param int          $post_id
     * @param int          $form_id
     * @param array<mixed> $form_settings
     * @param array<mixed> $form_vars
     */
    public function handle_wpuf_update(int $post_id, int $form_id, array $form_settings, array $form_vars): void {
        if (get_post_type($post_id) !== self::TARGET_CPTS[0]) { 
            return; 
        }

        // A. Get all images currently attached
        $attached_images = get_children([
            'post_parent'    => $post_id,
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'fields'         => 'ids',
            'numberposts'    => -1
        ]);

        if (empty($attached_images)) {
            return;
        }

        // B. Get IDs submitted in the form
        $submitted_gallery = [];
        if (isset($form_vars[self::GALLERY_META_KEY])) {
            $raw = $form_vars[self::GALLERY_META_KEY];
            if (is_array($raw)) {
                $submitted_gallery = array_map('intval', $raw);
            } elseif (is_string($raw)) {
                $submitted_gallery = array_map('intval', explode(',', $raw));
            }
        }

        // C. Calculate Diff
        $to_delete = array_diff($attached_images, $submitted_gallery);

        if (!empty($to_delete)) {
            foreach ($to_delete as $att_id) {
                $this->process_attachment_deletion((int) $att_id);
            }
            $this->log(sprintf('Cleanup (Edit): Removed %d orphaned images from Post #%d', count($to_delete), $post_id));
        }
    }

    /**
     * HANDLER C: Ghost Files (Cron Janitor)
     */
    public function cleanup_ghost_files(): void {
        $query = new WP_Query([
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'post_parent'    => 0,
            'posts_per_page' => 50, 
            'fields'         => 'ids',
            'date_query'     => [
                ['before' => '24 hours ago'],
            ],
        ]);

        if (!$query->have_posts()) {
            return;
        }

        $count = 0;
        foreach ($query->posts as $att_id) {
            $this->process_attachment_deletion((int) $att_id);
            $count++;
        }

        if ($count > 0) {
            $this->log(sprintf('[Janitor] Swept up %d ghost files.', $count));
        }
    }

    /**
     * Core Deletion Logic (PixRefiner Compatible)
     *
     * @param int $att_id
     */
    private function process_attachment_deletion(int $att_id): void {
        $file_path = get_attached_file($att_id);
        
        if ($file_path) {
            // 1. Clean up .orig backup
            $backup_path = $file_path . '.orig';
            if (file_exists($backup_path)) {
                @unlink($backup_path);
            }

            // 2. Clean up "Unregistered" PixRefiner Variants (-400, -768, etc)
            $this->cleanup_pixrefiner_variants($file_path);
        }

        // 3. Force Delete (Removes DB row + standard sizes)
        wp_delete_attachment($att_id, true);
    }

    /**
     * Explicitly deletes files that match PixRefiner's custom width patterns.
     * * @param string $main_file_path The full path to the main image (e.g. /uploads/2025/11/image.webp)
     */
    private function cleanup_pixrefiner_variants(string $main_file_path): void {
        $path_info = pathinfo($main_file_path);
        
        if (!isset($path_info['dirname'], $path_info['filename'], $path_info['extension'])) {
            return;
        }

        $dir = $path_info['dirname'];
        $name = $path_info['filename'];
        $ext = $path_info['extension'];

        foreach (self::PIXREFINER_WIDTHS as $width) {
            // Pattern: name-WIDTH.ext (e.g., test_image-5-400.webp)
            $variant_path = $dir . DIRECTORY_SEPARATOR . $name . '-' . $width . '.' . $ext;
            
            if (file_exists($variant_path)) {
                @unlink($variant_path);
            }
        }
    }

    /**
     * Scheduler
     */
    public function ensure_schedule(): void {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'daily', self::CRON_HOOK);
        }
    }
    
    /**
     * Internal logger
     *
     * @param string $msg
     */
    private function log(string $msg): void {
        if (function_exists('yardlii_log')) {
            yardlii_log('[MediaCleanup] ' . $msg);
        }
    }
}