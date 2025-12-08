<?php
declare(strict_types=1);

namespace Yardlii\Core\Features;

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
        // Ensure TV settings are registered as early as possible in this request.
        if (did_action('plugins_loaded')) {
            self::ensureTvSettingsRegistered();
        } else {
            add_action('plugins_loaded', [__CLASS__, 'ensureTvSettingsRegistered'], 0);
        }
        add_action('admin_init', [__CLASS__, 'ensureTvSettingsRegistered'], 0);

        // Register non-TV features.
        $this->register_features();

        // Trust & Verification module behind feature flag/constant override.
        $tv_master = (bool) get_option('yardlii_enable_trust_verification', true);
        if (defined('YARDLII_ENABLE_TRUST_VERIFICATION')) {
            $tv_master = (bool) YARDLII_ENABLE_TRUST_VERIFICATION;
        }

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

        $el_enabled = (bool) get_option('yardlii_enable_elementor_query_mods', false);
        if (defined('YARDLII_ENABLE_ELEMENTOR_QUERY_MODS')) {
            $el_enabled = (bool) YARDLII_ENABLE_ELEMENTOR_QUERY_MODS;
        }
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

        $geo_enabled = (bool) get_option('yardlii_enable_wpuf_geocoding', false);
        if (defined('YARDLII_ENABLE_WPUF_GEOCODING')) {
            $geo_enabled = (bool) constant('YARDLII_ENABLE_WPUF_GEOCODING');
        }
        if ($geo_enabled && class_exists(__NAMESPACE__ . '\\WPUF\\WpufGeocoding')) {
            (new WPUF\WpufGeocoding())->register();
        }

        $loc_enabled = (bool) get_option('yardlii_enable_wpuf_city_autocomplete', false);
        if (defined('YARDLII_ENABLE_WPUF_CITY_AUTOCOMPLETE')) {
            $loc_enabled = (bool) YARDLII_ENABLE_WPUF_CITY_AUTOCOMPLETE;
        }
        if ($loc_enabled && class_exists(__NAMESPACE__ . '\\WPUF\\WpufCityAutocomplete')) {
            $coreUrl = defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL : plugin_dir_url(__DIR__ . '/../');
            $coreVer = defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0.0';
            (new WPUF\WpufCityAutocomplete($coreUrl, $coreVer))->register();
        }

        if (class_exists(__NAMESPACE__ . '\\WPUF\\ProfileFormSwitcher')) {
            (new WPUF\ProfileFormSwitcher())->register();
        }
        if (class_exists(__NAMESPACE__ . '\\WPUF\\PostingLogic')) {
            (new WPUF\PostingLogic())->register();
        }
        if (class_exists(__NAMESPACE__ . '\\WPUF\\SmartFormOverrides')) {
            (new WPUF\SmartFormOverrides())->register();
        }
        if (class_exists(__NAMESPACE__ . '\\WPUF\\SubmitFormSwitcher')) {
            (new WPUF\SubmitFormSwitcher())->register();
        }

        // === User Directory ===
        $directory_enabled = (bool) get_option('yardlii_enable_business_directory', false);
        if (defined('YARDLII_ENABLE_BUSINESS_DIRECTORY')) {
            $directory_enabled = (bool) YARDLII_ENABLE_BUSINESS_DIRECTORY;
        }
        if ($directory_enabled && class_exists(__NAMESPACE__ . '\\Directory\\Renderer')) {
            $coreUrl = defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL : plugin_dir_url(__DIR__ . '/../');
            $coreVer = defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0.0';
            (new Directory\Renderer($coreUrl, $coreVer))->register();
        }

        // === General Features ===
        if (class_exists(__NAMESPACE__ . '\\FeaturedImage')) {
            (new FeaturedImage())->register();
        }

        if (get_option('yardlii_enable_featured_listings', false)) {
            if (class_exists(__NAMESPACE__ . '\\FeaturedListings')) {
                (new FeaturedListings())->register();
            }
        }

        // === Media Cleanup ===
        $media_cleanup_enabled = (bool) get_option('yardlii_enable_media_cleanup', false);
        if (defined('YARDLII_ENABLE_MEDIA_CLEANUP')) {
            $media_cleanup_enabled = (bool) YARDLII_ENABLE_MEDIA_CLEANUP;
        }
        if ($media_cleanup_enabled && class_exists(__NAMESPACE__ . '\\MediaCleanup')) {
            (new MediaCleanup())->register();
        }

        // === ACF User Sync ===
        $acf_sync_enabled = (bool) get_option('yardlii_enable_acf_user_sync', false);
        if (defined('YARDLII_ENABLE_ACF_USER_SYNC')) {
            $acf_sync_enabled = (bool) YARDLII_ENABLE_ACF_USER_SYNC;
        }
        if ($acf_sync_enabled && class_exists(__NAMESPACE__ . '\\ACFUserSync')) {
            (new ACFUserSync())->register();
        }

        // === Role Control & Dashboard ===
        $rc_master = (bool) get_option('yardlii_enable_role_control', false);
        if (defined('YARDLII_ENABLE_ROLE_CONTROL')) {
            $rc_master = (bool) YARDLII_ENABLE_ROLE_CONTROL;
        }

        if (class_exists(__NAMESPACE__ . '\\UserDashboard')) {
            $coreUrl = defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL : plugin_dir_url(__DIR__ . '/../');
            $coreVer = defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0.0';
            (new UserDashboard($coreUrl, $coreVer))->register();
        }

        $rc_submit_enabled = $rc_master && (bool) get_option('yardlii_enable_role_control_submit', false);
        if ($rc_submit_enabled && class_exists(__NAMESPACE__ . '\\RoleControlSubmitAccess')) {
            (new RoleControlSubmitAccess())->register();
        }

        $cur_enabled = $rc_master && (bool) get_option('yardlii_enable_custom_roles', true);
        if ($cur_enabled && class_exists(__NAMESPACE__ . '\\CustomUserRoles')) {
            (new CustomUserRoles())->register();
        }

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