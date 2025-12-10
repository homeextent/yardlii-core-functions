<?php
declare(strict_types=1);

namespace Yardlii\Core\Features;

use Yardlii\Core\Admin\SettingsPageTabs;
use Yardlii\Core\Admin\Assets;
use Yardlii\Core\Features\Loader;
use Yardlii\Core\Services\Logger;
use Yardlii\Core\Services\FeatureFlagManager; // Import the new Manager

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Boots feature modules and ensures TV settings are registered early
 * so options.php can process them on save.
 */
final class Loader
{
    /** Guard: ensure we only register TV settings once per request */
    private static bool $tvSettingsRegistered = false;

    /**
     * Register Trust & Verification settings EARLY for options.php.
     * Runs in wp-admin (incl. admin-ajax). Safe to call multiple times.
     */
    public static function ensureTvSettingsRegistered(): void
    {
        if (self::$tvSettingsRegistered || ! is_admin()) {
            return;
        }
        self::$tvSettingsRegistered = true;

        if (class_exists(\Yardlii\Core\Features\TrustVerification\Settings\GlobalSettings::class)) {
            (new \Yardlii\Core\Features\TrustVerification\Settings\GlobalSettings())->registerSettings();
        }
        if (class_exists(\Yardlii\Core\Features\TrustVerification\Settings\FormConfigs::class)) {
            (new \Yardlii\Core\Features\TrustVerification\Settings\FormConfigs())->registerSettings();
        }
    }

    /**
     * Entry point: wire early settings, then register all feature modules.
     */
    public function register(): void
    {
        // ... (existing ensureTvSettingsRegistered logic) ...

        // Register non-TV features.
        $this->register_features();

        // Trust & Verification module behind feature flag/constant override.
        $tv_master = FeatureFlagManager::isEnabled('trust_verification'); // [MODIFICATION] Use Manager

        if (
            $tv_master
            && class_exists(\Yardlii\Core\Features\TrustVerification\Module::class)
        ) {
            (new \Yardlii\Core\Features\TrustVerification\Module(
                defined('YARDLII_CORE_FILE') ? YARDLII_CORE_FILE : __FILE__,
                defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : null
            ))->register();
        }
    }

    /**
     * Register other feature modules (gated by their own flags).
     */
    public function register_features(): void
    {
        // === Integrations (Maps, Elementor, Search) ===
        if (class_exists(__NAMESPACE__ . '\\Integrations\\GoogleMapKey')) {
            (new Integrations\GoogleMapKey())->register();
        }

        // [MODIFICATION] Use Manager for Elementor flag
        $el_enabled = FeatureFlagManager::isEnabled('elementor_query_mods'); 
        if ($el_enabled && class_exists(__NAMESPACE__ . '\\Integrations\\ElementorQueryMods')) {
            (new Integrations\ElementorQueryMods())->register();
        }

        if (class_exists(__NAMESPACE__ . '\\Integrations\\HomepageSearch')) {
            (new Integrations\HomepageSearch())->register();
        }

        // === WPUF Logic ===
        if (class_exists(__NAMESPACE__ . '\\WPUF\\WPUFFrontendEnhancements')) {
            (new WPUF\WPUFFrontendEnhancements())->register();
        }

        // [MODIFICATION] Use Manager for WPUF Geocoding flag
        $geo_enabled = FeatureFlagManager::isEnabled('wpuf_geocoding'); 
        if ($geo_enabled && class_exists(__NAMESPACE__ . '\\WPUF\\WpufGeocoding')) {
            (new WPUF\WpufGeocoding())->register();
        }

        // [MODIFICATION] Use Manager for WPUF City Autocomplete flag
        $loc_enabled = FeatureFlagManager::isEnabled('wpuf_city_autocomplete'); 
        if ($loc_enabled && class_exists(__NAMESPACE__ . '\\WPUF\\WpufCityAutocomplete')) {
            $coreUrl = defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL : plugin_dir_url(__DIR__ . '/../');
            $coreVer = defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0.0';
            (new WPUF\WpufCityAutocomplete($coreUrl, $coreVer))->register();
        }

        if (class_exists(__NAMESPACE__ . '\\WPUF\\ProfileFormSwitcher')) {
            (new WPUF\ProfileFormSwitcher())->register();
        }
        if (class_exists(__NAMESPACE__ . '\\WPUF\\PostingLogic')) {
            (new WPUF\WPUF\\PostingLogic())->register();
        }
        if (class_exists(__NAMESPACE__ . '\\WPUF\\SmartFormOverrides')) {
            (new WPUF\\SmartFormOverrides())->register();
        }
        if (class_exists(__NAMESPACE__ . '\\WPUF\\SubmitFormSwitcher')) {
            (new WPUF\\SubmitFormSwitcher())->register();
        }

        // === User Directory ===
        // [MODIFICATION] Use Manager for Directory flag
        $directory_enabled = FeatureFlagManager::isEnabled('business_directory'); 
        if ($directory_enabled && class_exists(__NAMESPACE__ . '\\Directory\\Renderer')) {
            $coreUrl = defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL : plugin_dir_url(__DIR__ . '/../');
            $coreVer = defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0.0';
            (new Directory\Renderer($coreUrl, $coreVer))->register();
        }

        // === General Features ===
        if (class_exists(__NAMESPACE__ . '\\FeaturedImage')) {
            (new FeaturedImage())->register();
        }

        // Note: FeaturedListings is gated by a simple get_option('yardlii_enable_featured_listings', false)
        if (get_option('yardlii_enable_featured_listings', false)) {
            if (class_exists(__NAMESPACE__ . '\\FeaturedListings')) {
                (new FeaturedListings())->register();
            }
        }

        // === Media Cleanup ===
        // [MODIFICATION] Use Manager for Media Cleanup flag
        $media_cleanup_enabled = FeatureFlagManager::isEnabled('media_cleanup'); 
        if ($media_cleanup_enabled && class_exists(__NAMESPACE__ . '\\MediaCleanup')) {
            (new MediaCleanup())->register();
        }

        // === ACF User Sync ===
        // [MODIFICATION] Use Manager for ACF Sync flag
        $acf_sync_enabled = FeatureFlagManager::isEnabled('acf_user_sync'); 
        if ($acf_sync_enabled && class_exists(__NAMESPACE__ . '\\ACFUserSync')) {
            (new ACFUserSync())->register();
        }

        // === Role Control & Dashboard ===
        // [MODIFICATION] Use Manager for RC Master flag
        $rc_master = FeatureFlagManager::isEnabled('role_control'); 

        if (class_exists(__NAMESPACE__ . '\\UserDashboard')) {
            $coreUrl = defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL : plugin_dir_url(__DIR__ . '/../');
            $coreVer = defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0.0';
            (new UserDashboard($coreUrl, $coreVer))->register();
        }

        // [MODIFICATION] RC Sub-features now check the Manager
        $rc_submit_enabled = $rc_master && (bool) get_option('yardlii_enable_role_control_submit', false);
        if ($rc_submit_enabled && class_exists(__NAMESPACE__ . '\\RoleControlSubmitAccess')) {
            (new RoleControlSubmitAccess())->register();
        }

        // [MODIFICATION] RC Sub-features now check the Manager
        $cur_enabled = $rc_master && (bool) get_option('yardlii_enable_custom_roles', true);
        if ($cur_enabled && class_exists(__NAMESPACE__ . '\\CustomUserRoles')) {
            (new CustomUserRoles())->register();
        }

        // [MODIFICATION] RC Sub-features now check the Manager
        $badge_enabled = $rc_master && (bool) get_option('yardlii_enable_badge_assignment', true);
        if ($badge_enabled && class_exists(__NAMESPACE__ . '\\RoleControlBadgeAssignment')) {
            (new RoleControlBadgeAssignment())->register();
        }
    }

    /**
     * Optional convenience boot if Loader needs to self-wire.
     */
    public static function boot(): void
    {
        add_action('plugins_loaded', [__CLASS__, 'onPluginsLoaded'], 5);
    }

    public static function onPluginsLoaded(): void
    {
        (new self())->register();
    }
}