<?php
namespace Yardlii\Core\Features;

use WP_Query;

/**
 * Class MediaCleanup
 * Handles 3 types of cleanup:
 * 1. Post Deletion (Listing removed -> Images removed)
 * 2. Edit Updates (Image removed from gallery -> Image deleted)
 * 3. Ghost Files (Abandoned uploads older than 24h -> Deleted)
 */
class MediaCleanup {

    /**
     * @var array Target CPTs for the 'Listing Deletion' logic.
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
     */
    public function handle_wpuf_update($post_id, $form_id, $form_settings, $form_vars): void {
        // 1. Security Check: Is this a Listing?
        if (get_post_type($post_id) !== self::TARGET_CPTS[0]) { 
            return; 
        }

        // A. Get all images currently attached to the post parent
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

        // B. Get the IDs explicitly submitted in the form now
        $submitted_gallery = [];
        if (isset($form_vars[self::GALLERY_META_KEY])) {
            $raw = $form_vars[self::GALLERY_META_KEY];
            // WPUF sometimes sends array, sometimes comma-separated string
            if (is_array($raw)) {
                $submitted_gallery = array_map('intval', $raw);
            } elseif (is_string($raw)) {
                $submitted_gallery = array_map('intval', explode(',', $raw));
            }
        }

        // C. Calculate the Diff
        // If an image is 'Attached' to the post, but NOT in the 'Submitted Gallery', it was removed by the user.
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
        // Find images uploaded > 24 hours ago that have NO parent.
        $query = new WP_Query([
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'post_status'    => 'inherit',
            'post_parent'    => 0, // <--- The definition of a Ghost
            'posts_per_page' => 50, // Process in chunks
            'fields'         => 'ids',
            'date_query'     => [
                [
                    'before' => '24 hours ago',
                ],
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
     */
    private function process_attachment_deletion(int $att_id): void {
        // 1. PixRefiner .orig Cleanup
        $file_path = get_attached_file($att_id);
        if ($file_path) {
            $backup_path = $file_path . '.orig';
            if (file_exists($backup_path)) {
                @unlink($backup_path);
            }
        }

        // 2. Force Delete
        // This triggers 'wp_delete_attachment' hook --> PixRefiner cleans up WebP.
        wp_delete_attachment($att_id, true);
    }

    /**
     * Scheduler
     */
    public function ensure_schedule(): void {
        if (!wp_next_scheduled(self::CRON_HOOK)) {
            wp_schedule_event(time(), 'daily', self::CRON_HOOK);
        }
    }
    
    private function log($msg): void {
        if (function_exists('yardlii_log')) {
            yardlii_log('[MediaCleanup] ' . $msg);
        }
    }
}