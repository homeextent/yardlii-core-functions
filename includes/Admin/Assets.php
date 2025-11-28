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
        // 1. Screen Check: Only load on YARDLII Core settings page
        $screen = get_current_screen();
        if (empty($screen) || strpos($screen->id, 'yardlii-core') === false) {
            return;
        }

        // 2. Global CSS
        // Core Admin CSS
        wp_enqueue_style(
            'yardlii-admin',
            plugins_url('/assets/css/admin.css', YARDLII_CORE_FILE),
            [],
            YARDLII_CORE_VERSION
        );

        // Directory Settings CSS (Card Layout)
        wp_enqueue_style(
            'yardlii-admin-directory',
            YARDLII_CORE_URL . 'assets/admin/css/admin-directory.css',
            [],
            YARDLII_CORE_VERSION
        );

        // 3. Global JS (Tabs & UI)
        wp_enqueue_script(
            'yardlii-admin', // Handle: matches wp_localize_script below
            plugins_url('/assets/js/admin.js', YARDLII_CORE_FILE),
            ['jquery'],
            YARDLII_CORE_VERSION,
            true
        );

        // 4. Data Localization (Diagnostics & ACF)
        // This block is critical for the "Advanced -> Diagnostics" tab to function.
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

        // 5. Trust & Verification Assets (Conditional)
        // Only load heavy TV assets if we are actually on that tab
        if (isset($_GET['tab']) && $_GET['tab'] === 'trust-verification') {
            wp_enqueue_style(
                'yardlii-admin-tv',
                YARDLII_CORE_URL . 'assets/admin/css/trust-verification.css',
                ['yardlii-admin'],
                YARDLII_CORE_VERSION
            );

            wp_enqueue_script(
                'yardlii-admin-tv-js',
                YARDLII_CORE_URL . 'assets/admin/js/admin-tv.js',
                ['jquery', 'yardlii-admin'], // Depend on core admin JS
                YARDLII_CORE_VERSION,
                true
            );

            // Localize data specific to the TV App
            wp_localize_script('yardlii-admin-tv-js', 'yardliiTv', [
                'ajaxurl'      => admin_url('admin-ajax.php'),
                'noncePreview' => wp_create_nonce('yardlii_tv_preview_email'),
                'nonceSend'    => wp_create_nonce('yardlii_tv_send_test_email'),
                'nonceHistory' => wp_create_nonce('yardlii_tv_history_load'),
                'restNonce'    => wp_create_nonce('wp_rest'),
                'restUrl'      => rest_url('yardlii/v1/verification-requests/'),
            ]);
        }
    }
}