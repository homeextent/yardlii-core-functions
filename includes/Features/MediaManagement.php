<?php
declare(strict_types=1);

namespace Yardlii\Core\Features;

use Yardlii\Core\Services\Logger;

/**
 * Feature: Media Lifecycle Management
 * Handles Native Size Registration, Privacy Scrubbing, and Bloat Prevention.
 * Integrated from PixRefiner v2.1.0 architecture.
 */
class MediaManagement {

    public function register(): void {
        // 1. Register Native Sizes (Elementor Compatibility)
        add_action('after_setup_theme', [$this, 'register_dynamic_sizes']);

        // 2. Privacy Shield (EXIF Stripping)
        add_filter('wp_handle_upload_prefilter', [$this, 'privacy_scrub']);

        // 3. Bloat Prevention (Unset Defaults)
        add_filter('intermediate_image_sizes_advanced', [$this, 'prevent_bloat']);
    }

    public function register_dynamic_sizes(): void {
        // Strategy: 400px (Mobile Grid), 768px (Tablet), 1200px (Desktop), 1920px (Lightbox)
        $widths = [400, 768, 1200, 1920];

        foreach ($widths as $width) {
            // Naming: 'yardlii-custom-{width}'
            // Mode: 9999 height + false = Soft Crop (Resize by width, maintain aspect ratio)
            add_image_size('yardlii-custom-' . $width, $width, 9999, false);
        }
    }

    /**
     * Strips EXIF/GPS data from uploaded images using ImageMagick.
     *
     * @param array<string, mixed> $file The file array from wp_handle_upload.
     * @return array<string, mixed> The modified file array.
     */
    public function privacy_scrub(array $file): array {
        // 1. Stability Bump
        if (function_exists('ini_set')) {
            @ini_set('memory_limit', '512M');
        }
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }

        // 2. Validation
        $type = $file['type'] ?? '';
        // Strict check: only process standard image types
        if (!in_array($type, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return $file;
        }

        // 3. EXIF/GPS Stripping via ImageMagick
        if (extension_loaded('imagick') && class_exists('\Imagick')) {
            try {
                $image_path = (string) $file['tmp_name'];
                $imagick = new \Imagick($image_path);
                
                // Nondestructive: Removes metadata profiles only (GPS, Camera info)
                $imagick->stripImage();
                $imagick->writeImage($image_path);
                $imagick->clear();
                $imagick->destroy();
                
                Logger::log("Privacy scrubbed for file: " . basename($image_path), 'MEDIA');
            } catch (\Throwable $e) {
                // Fail silently to avoid blocking uploads, but log it
                Logger::log("Privacy Scrub Failed: " . $e->getMessage(), 'MEDIA');
            }
        }

        return $file;
    }

    /**
     * Unsets default WordPress sizes to prevent server bloat.
     *
     * @param array<string, mixed> $sizes Current registered image sizes.
     * @return array<string, mixed> Filtered image sizes.
     */
    public function prevent_bloat(array $sizes): array {
        $targets = [
            '1536x1536',    // WP 5.3+ 2x Medium Large
            '2048x2048',    // WP 5.3+ 2x Large
            'medium_large', // 768px (Redundant -> yardlii-custom-768)
        ];

        foreach ($targets as $target) {
            if (isset($sizes[$target])) {
                unset($sizes[$target]);
            }
        }

        return $sizes;
    }
}