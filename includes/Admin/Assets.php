<?php
namespace Yardlii\Core\Admin;

/**
 * Admin Assets Handler
 * Enqueues CSS and JS for the settings page.
 */
class Assets
{
    public function register(): void
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
    }

    public function enqueue_admin_assets(): void
    {
        $screen = get_current_screen();

        // 1. Screen Check
        if (empty($screen) || strpos($screen->id, 'yardlii-core') === false) {
            return;
        }

        // 2. PHPStan Safe Constants (Fix for CI errors)
        // We define local variables with fallbacks so static analysis doesn't crash
        $coreUrl  = defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL : '';
        $coreVer  = defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0.0';
        $coreFile = defined('YARDLII_CORE_FILE') ? YARDLII_CORE_FILE : __FILE__;

        // 3. Global CSS
        // Core Admin CSS
        wp_enqueue_style(
            'yardlii-admin',
            plugins_url('/assets/css/admin.css', $coreFile),
            [],
            $coreVer
        );

        // Directory Settings CSS (Card Layout)
        wp_enqueue_style(
            'yardlii-admin-directory',
            $coreUrl . 'assets/admin/css/admin-directory.css',
            [],
            $coreVer
        );

        // 4. Global JS
        wp_enqueue_script(
            'yardlii-admin',
            plugins_url('/assets/js/admin.js', $coreFile),
            ['jquery'],
            $coreVer,
            true
        );

        // 5. Data Localization (Diagnostics & ACF)
        if (class_exists('\Yardlii\Core\Features\ACFUserSync')) {
            $acf_sync = new \Yardlii\Core\Features\ACFUserSync();
            $registered_handlers = $acf_sync->get_registered_special_handlers();

            $special_options = ['' => __('None', 'yardlii-core')];
            foreach ($registered_handlers as $key => $handler) {
                $special_options[$key] = $handler['label'];
            }

            wp_localize_script('yardlii-admin', 'YARDLII_ADMIN', [
                'nonce'              => wp_create_nonce('yardlii_admin_nonce'),
                'nonce_badge_sync'   => wp_create_nonce('yardlii_diag_badge_sync_nonce'),
                'nonce_search_cache' => wp_create_nonce('yardlii_diag_search_cache_nonce'),
                'ajaxurl'            => admin_url('admin-ajax.php'),
                'specialOptions'     => $special_options,
            ]);
        }

            }
}