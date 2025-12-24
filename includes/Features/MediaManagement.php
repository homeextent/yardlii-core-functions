<?php
declare(strict_types=1);

namespace Yardlii\Core\Features;

use Yardlii\Core\Services\Logger;

/**
 * Feature: Media Lifecycle Management
 * Handle Native Size Registration, Privacy Scrubbing, and Bloat Prevention.
 */
class MediaManagement {

    public function register(): void {
        // 1. Register Native Sizes (Internal)
        add_action('after_setup_theme', [$this, 'register_dynamic_sizes']);

        // 2. Add Names to Dropdowns (Elementor/Media Library UI)
        add_filter('image_size_names_choose', [$this, 'add_custom_size_names']);

        // 3. Privacy Shield (EXIF Stripping)
        add_filter('wp_handle_upload_prefilter', [$this, 'privacy_scrub']);

        // 4. Bloat Prevention (Unset Defaults)
        add_filter('intermediate_image_sizes_advanced', [$this, 'prevent_bloat']);
    }

    public function register_dynamic_sizes(): void {
        // Strategy: 400px (Mobile), 768px (Tablet), 1200px (Desktop), 1920px (Hero)
        $widths = [400, 768, 1200, 1920];

        foreach ($widths as $width) {
            // Soft Crop (9999 height) prevents cutting off vertical images
            add_image_size('yardlii-custom-' . $width, $width, 9999, false);
        }
    }

    /**
     * Makes the custom sizes appear in Elementor and Media Library selectors.
     * * @param array<string, string> $sizes
     * @return array<string, string>
     */
    public function add_custom_size_names(array $sizes): array {
        return array_merge($sizes, [
            'yardlii-custom-400'  => __('Yardlii Mobile (400px)', 'yardlii-core'),
            'yardlii-custom-768'  => __('Yardlii Tablet (768px)', 'yardlii-core'),
            'yardlii-custom-1200' => __('Yardlii Desktop (1200px)', 'yardlii-core'),
            'yardlii-custom-1920' => __('Yardlii Hero (1920px)', 'yardlii-core'),
        ]);
    }

    /**
     * @param array<string, mixed> $file
     * @return array<string, mixed>
     */
    public function privacy_scrub(array $file): array {
        if (function_exists('ini_set')) { @ini_set('memory_limit', '512M'); }
        if (function_exists('set_time_limit')) { @set_time_limit(300); }

        $type = $file['type'] ?? '';
        if (!in_array($type, ['image/jpeg', 'image/png', 'image/webp'], true)) {
            return $file;
        }

        if (extension_loaded('imagick') && class_exists('\Imagick')) {
            try {
                $image_path = (string) $file['tmp_name'];
                $imagick = new \Imagick($image_path);
                $imagick->stripImage();
                $imagick->writeImage($image_path);
                $imagick->clear();
                $imagick->destroy();
                Logger::log("Privacy scrubbed for: " . basename($image_path), 'MEDIA');
            } catch (\Throwable $e) {
                Logger::log("Privacy Scrub Failed: " . $e->getMessage(), 'MEDIA');
            }
        }
        return $file;
    }

    /**
     * @param array<string, mixed> $sizes
     * @return array<string, mixed>
     */
    public function prevent_bloat(array $sizes): array {
        $targets = ['1536x1536', '2048x2048', 'medium_large'];
        foreach ($targets as $target) {
            if (isset($sizes[$target])) {
                unset($sizes[$target]);
            }
        }
        return $sizes;
    }
}