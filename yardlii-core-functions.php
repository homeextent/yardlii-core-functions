<?php
/**
 * Plugin Name:  YARDLII: Core Functions
 * Description:  Centralized modular functionality for the YARDLII platform.
 * Version:      3.30.0
 * Author:       The Innovative Group
 * Text Domain:  yardlii-core
 * License:      GPLv2 or later
 */

defined('ABSPATH') || exit;

/* =====================================================
 * Constants
 * ===================================================== */
if (!defined('YARDLII_CORE_FILE'))    define('YARDLII_CORE_FILE', __FILE__);
if (!defined('YARDLII_CORE_PATH'))    define('YARDLII_CORE_PATH', plugin_dir_path(__FILE__));
if (!defined('YARDLII_CORE_URL'))     define('YARDLII_CORE_URL',  plugin_dir_url(__FILE__));
if (!defined('YARDLII_CORE_VERSION')) define('YARDLII_CORE_VERSION', '3.30.0');

/* =====================================================
 * i18n
 * ===================================================== */
add_action('plugins_loaded', static function () {
    // Looks for /languages/yardlii-core-LOCALE.mo
    load_plugin_textdomain('yardlii-core', false, dirname(plugin_basename(__FILE__)) . '/languages');
});

/* =====================================================
 * Autoloaders
 * ===================================================== */

// 1. Load Composer Dependencies
$autoload_path = YARDLII_CORE_PATH . 'vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
}

// 2. Initialize Action Scheduler
// Composer autoloads the classes, but does not "boot" the library or aliases.
$as_init_path = YARDLII_CORE_PATH . 'vendor/woocommerce/action-scheduler/action-scheduler.php';
if (file_exists($as_init_path)) {
    require_once $as_init_path;
}

// Lightweight PSR-4 for Yardlii\Core\* (always)
spl_autoload_register(static function ($class) {
    $prefix   = 'Yardlii\\Core\\';
    $base_dir = __DIR__ . '/includes/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;

    $relative = substr($class, $len);
    $file     = $base_dir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require $file;
});



// Toggle verbose plugin logs. Set to true in dev, false in prod.
if (!defined('YARDLII_DEBUG')) define('YARDLII_DEBUG', false);

// Convenience logger
if (!function_exists('yardlii_log')) {
    function yardlii_log($msg): void {
        if (defined('YARDLII_DEBUG') && YARDLII_DEBUG) {
            if (!is_string($msg)) { $msg = wp_json_encode($msg); }
            error_log('[YARDLII] ' . $msg);
        }
    }
}

/* =====================================================
 * Universal Location Engine & Global Assets
 * Added per v3.25.3 Spec
 * ===================================================== */
add_action('wp_enqueue_scripts', static function (): void {
    // 1. Enqueue the Global Controller (Mobile Fix)
    wp_enqueue_script(
        'yardlii-frontend-js',
        YARDLII_CORE_URL . 'assets/js/frontend.js',
        ['jquery'], 
        YARDLII_CORE_VERSION,
        true // Load in footer
    );

    // 2. Enqueue the Visibility Layer (Z-Index fixes)
    wp_enqueue_style(
        'yardlii-frontend-css',
        YARDLII_CORE_URL . 'assets/css/frontend.css',
        [],
        YARDLII_CORE_VERSION
    );

    // 3. CONFLICT RESOLUTION: Dequeue the "Old" CSS from the other plugin
    // FIX: Removing '-css' suffix. WP adds this to the HTML ID, but the handle is likely 'yardlii-core-frontend'.
    wp_dequeue_style('yardlii-core-frontend');
    wp_deregister_style('yardlii-core-frontend');

}, 20); // Priority 20 ensures this runs AFTER the other plugin has loaded/* 

/* =====================================================
 * Optional feature flags (code-locked defaults)
 * Define in wp-config.php or here BEFORE init if desired.
 * ===================================================== */
// if (!defined('YARDLII_ENABLE_ACF_USER_SYNC')) {
//     define('YARDLII_ENABLE_ACF_USER_SYNC', false);
// }

/* =====================================================
 * Bootstrap Core
 * ===================================================== */
use Yardlii\Core\Core;

/**
 * Add a Settings link to the plugin action links on the plugins page.
 *
 * @param array<string, string> $links
 * @return array<string, string>
 */
function yardlii_core_settings_link(array $links): array
{
    $settings_link = sprintf(
        '<a href="%s">%s</a>',
        esc_url(admin_url('admin.php?page=yardlii-core-settings')),
        esc_html__('Settings', 'yardlii-core')
    );
    // Insert the link at the beginning of the array.
    array_unshift($links, $settings_link);
    
    return $links;
}

// Hook it up (using the base name of the plugin file)
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'yardlii_core_settings_link');

add_action('plugins_loaded', static function () {
    static $initialized = false;
    if ($initialized) return;
    $initialized = true;

    if (class_exists(Core::class)) {
        (new Core())->init();
    } else {
        error_log('[YARDLII] Core class not found — check autoloader.');
    }
});
