<?php
namespace Yardlii\Tests\Integration\Features;

use Yardlii\Core\Features\MediaCleanup;

class MediaCleanupTest extends \WP_UnitTestCase {
    
    public function set_up() {
        parent::set_up();
        // 1. Force enable the feature for this test
        update_option('yardlii_enable_media_cleanup', 1);
        
        // 2. Manually register the class (Hooks don't auto-fire in test setup unless we call register)
        if (class_exists(MediaCleanup::class)) {
            (new MediaCleanup())->register();
        }
    }

    public function test_deleting_listing_deletes_attachments_and_orphaned_variants() {
        // A. Create a parent "Listing"
        // IMPORTANT: We use the 'listings' CPT you defined in config
        $post_id = self::factory()->post->create(['post_type' => 'listings']);
        
        // B. Create a dummy image file in the uploads directory
        $upload_dir = wp_upload_dir();
        $filename   = 'test-janitor.jpg';
        $filepath   = $upload_dir['path'] . '/' . $filename;
        file_put_contents($filepath, 'dummy content');
        
        // C. Create the Attachment Post in DB linked to the Listing
        $attachment_id = self::factory()->attachment->create_object(
            $filepath,
            $post_id,
            [
                'post_mime_type' => 'image/jpeg',
            ]
        );
        
        // D. Simulate a "PixRefiner" leftover file (e.g. -400.webp)
        // This file is NOT in the DB, only on the disk.
        $variant_name = 'test-janitor-400.webp';
        $variant_path = $upload_dir['path'] . '/' . $variant_name;
        file_put_contents($variant_path, 'dummy webp content');
        
        // Verify setup
        $this->assertFileExists($filepath, 'Main file should exist before test');
        $this->assertFileExists($variant_path, 'Variant file should exist before test');
        $this->assertNotNull(get_post($attachment_id), 'Attachment DB row should exist');
        
        // E. ACT: Delete the Parent Listing
        // We use force_delete = true to skip Trash and trigger cleanup immediately
        wp_delete_post($post_id, true);
        
        // F. ASSERT: Everything should be gone
        $this->assertNull(get_post($attachment_id), 'Attachment DB row should be deleted');
        $this->assertFileDoesNotExist($filepath, 'Main image file should be deleted');
        $this->assertFileDoesNotExist($variant_path, 'Orphaned PixRefiner variant should be deleted');
    }
}