<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\TrustVerification\UI;

use Yardlii\Core\Features\TrustVerification\Requests\CPT;

/**
 * Trust & Verification — Admin Assets Bootstrap
 * - Enqueues TV CSS/JS only on YARDLII Core Settings screen.
 * - Provides YardliiTV globals (AJAX + REST + nonces).
 * - Registers the Dashboard Widget.
 */
final class AdminPage
{
    public const TAB_SLUG       = 'trust-verification';
    private const STYLE_HANDLE  = 'yardlii-tv';
    private const SCRIPT_HANDLE = 'yardlii-tv';

    public function __construct(
        private string $pluginFile,
        private ?string $version
    ) {}

    /** Public: register hooks */
    public function register(): void
    {
        // Only handle assets; panel is rendered from SettingsPageTabs
        add_action('admin_enqueue_scripts', [$this, 'enqueueAssets']);
        
        // [NEW] Dashboard Widget
        add_action('wp_dashboard_setup', [$this, 'registerDashboardWidget']);
    }

    /* =========================================
     * Enqueue
     * =======================================*/
    /**
     * @param string $hook Current admin screen hook suffix
     */
    public function enqueueAssets(string $hook): void
    {
        if (!$this->isOurSettingsPage($hook)) {
            return;
        }
        // inside enqueueAssets(), right after the isOurSettingsPage() check
        if (class_exists('\Yardlii\Core\Features\TrustVerification\Caps')) {
            if (! current_user_can(\Yardlii\Core\Features\TrustVerification\Caps::MANAGE)) {
                return; // optionally skip enqueuing for users who can't manage TV
            }
        } else {
            if (! current_user_can('manage_options')) {
                return;
            }
        }

        // Editor APIs for wp.editor.initialize() (TinyMCE/Quicktags)
        if (function_exists('wp_enqueue_editor')) {
            wp_enqueue_editor(); // registers/enqueues 'wp-editor'
        }
        if (function_exists('wp_enqueue_media')) {
            wp_enqueue_media();
        }

        $baseUrl = plugin_dir_url($this->pluginFile);
        $baseDir = plugin_dir_path($this->pluginFile);

        $cssRel = 'assets/admin/css/trust-verification.css';
        $jsRel  = 'assets/admin/js/admin-tv.js';

        $verCss = $this->assetVersion($baseDir . $cssRel);
        $verJs  = $this->assetVersion($baseDir . $jsRel);

        /* --- CSS --- */
        wp_enqueue_style(
            self::STYLE_HANDLE,
            $baseUrl . $cssRel,
            [],
            $verCss
        );

        /* --- JS (with deps) --- */
        // We must wait for the scripts loaded by wp_enqueue_editor()
        $deps = ['jquery', 'jquery-ui-sortable', 'editor', 'quicktags'];

        wp_enqueue_script(
            self::SCRIPT_HANDLE,
            $baseUrl . $jsRel,
            $deps,
            $verJs,
            true
        );

        /* --- Data for JS --- */
        wp_localize_script(self::SCRIPT_HANDLE, 'YardliiTV', [
            'ajax'          => admin_url('admin-ajax.php'),
            'noncePreview'  => wp_create_nonce('yardlii_tv_preview'),
            'nonceSend'     => wp_create_nonce('yardlii_tv_send_test'),
            'nonceHistory'  => wp_create_nonce('yardlii_tv_history'), 
            'restRoot'      => esc_url_raw(rest_url()),
            'restNonce'     => wp_create_nonce('wp_rest'),
        ]);
    }

    /* =========================================
     * Dashboard Widget
     * =======================================*/

    /**
     * Register the "Pending Verifications" dashboard widget.
     */
    public function registerDashboardWidget(): void
    {
        // Only show widget to admins who can manage TV
        if (class_exists('\Yardlii\Core\Features\TrustVerification\Caps')) {
            if (!current_user_can(\Yardlii\Core\Features\TrustVerification\Caps::MANAGE)) {
                return;
            }
        } else {
            if (!current_user_can('manage_options')) {
                return;
            }
        }

        wp_add_dashboard_widget(
            'yardlii_tv_dashboard_widget',
            __('YARDLII: Pending Verifications', 'yardlii-core'),
            [$this, 'renderDashboardWidget']
        );
    }

    /**
     * Render the dashboard widget content.
     */
    public function renderDashboardWidget(): void
    {
        // Count pending requests
        $count = (new \WP_Query([
            'post_type'      => CPT::POST_TYPE,
            'post_status'    => 'vp_pending',
            'posts_per_page' => 1, // Efficiency: we only need the count
            'fields'         => 'ids',
        ]))->found_posts;

        // Link to the Requests tab
        $url = admin_url('admin.php?page=yardlii-core-settings&tab=trust-verification&tvsection=requests');

        echo '<div class="yardlii-dashboard-widget" style="padding:10px 0; text-align:center;">';
        
        if ($count > 0) {
            printf(
                '<div style="font-size: 36px; font-weight: bold; color: #d63638; line-height: 1;">%d</div>',
                $count
            );
            echo '<p style="margin: 5px 0 15px; color: #666;">' . esc_html__('Pending Requests', 'yardlii-core') . '</p>';
            
            printf(
                '<a href="%s" class="button button-primary">%s</a>',
                esc_url($url),
                esc_html__('Review Queue', 'yardlii-core')
            );
        } else {
            echo '<div style="font-size: 36px; line-height: 1;">✅</div>';
            echo '<p style="margin-top:10px;">' . esc_html__('All caught up! No pending requests.', 'yardlii-core') . '</p>';
            printf(
                '<a href="%s" class="button">%s</a>',
                esc_url($url),
                esc_html__('View History', 'yardlii-core')
            );
        }
        
        echo '</div>';
    }

    /* =========================================
     * Helpers
     * =======================================*/
    /**
     * True only on the YARDLII Core Settings screen.
     */
    private function isOurSettingsPage(string $hook): bool
    {
        // 1) Fast path: hook contains our settings slug
        if (strpos($hook, 'yardlii-core-settings') !== false) {
            return true;
        }
        
        // [NEW] Also load CSS on the native "Verifications" list screen
        // The hook for a CPT list is 'edit.php' and we check the post_type param
        if ($hook === 'edit.php' && isset($_GET['post_type']) && $_GET['post_type'] === 'verification_request') {
            return true;
        }

        // 2) URL param fallback
        if (isset($_GET['page']) && $_GET['page'] === 'yardlii-core-settings') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
            return true;
        }

        // 3) Screen object fallback
        if (function_exists('get_current_screen')) {
            $scr = get_current_screen();
            if ($scr && !empty($scr->id) && strpos($scr->id, 'yardlii-core-settings') !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * Version from filemtime when possible; falls back to $this->version.
     */
    private function assetVersion(string $absPath): string|bool
    {
        if (is_file($absPath)) {
            $mt = @filemtime($absPath);
            if (is_int($mt)) {
                return (string) $mt;
            }
        }
        return $this->version ?: false;
    }
}