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

        // These classes must define:
        //  - yardlii_tv_global_group
        //  - yardlii_tv_form_configs_group
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
        // === Google Maps ===
        if (class_exists(__NAMESPACE__ . '\\GoogleMapKey')) {
            (new GoogleMapKey())->register();
        }

        // === Featured Image Automation ===
        if (class_exists(__NAMESPACE__ . '\\FeaturedImage')) {
            (new FeaturedImage())->register();
        }

	// === Elementor Query Mods (Author Archives, etc.) ===
        if (class_exists(__NAMESPACE__ . '\\ElementorQueryMods')) {
            (new ElementorQueryMods())->register();
        }

        // === Feature: Business Directory ===
        $directory_enabled = (bool) get_option('yardlii_enable_business_directory', false);
        if (defined('YARDLII_ENABLE_BUSINESS_DIRECTORY')) {
            $directory_enabled = (bool) YARDLII_ENABLE_BUSINESS_DIRECTORY;
        }

        if ($directory_enabled && class_exists(__NAMESPACE__ . '\\BusinessDirectory')) {
            // Inject constants safely to satisfy PHPStan
            $coreUrl = defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL : plugin_dir_url(__DIR__ . '/../');
            $coreVer = defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0.0';
            
            (new BusinessDirectory($coreUrl, $coreVer))->register();
        }

        // Feature: Automated Media Cleanup
        $media_cleanup_enabled = (bool) get_option('yardlii_enable_media_cleanup', false);
        if (defined('YARDLII_ENABLE_MEDIA_CLEANUP')) {
            $media_cleanup_enabled = (bool) YARDLII_ENABLE_MEDIA_CLEANUP;
        }

        if ($media_cleanup_enabled && class_exists(__NAMESPACE__ . '\\MediaCleanup')) {
            (new MediaCleanup())->register();
        }

        // === Homepage Search ===
        if (class_exists(__NAMESPACE__ . '\\HomepageSearch')) {
            (new HomepageSearch())->register();
        }

       
        // === ACF User Sync ===
        $acf_sync_enabled = (bool) get_option('yardlii_enable_acf_user_sync', false);
        if (defined('YARDLII_ENABLE_ACF_USER_SYNC')) {
            $acf_sync_enabled = (bool) YARDLII_ENABLE_ACF_USER_SYNC;
        }
        if ($acf_sync_enabled && class_exists(__NAMESPACE__ . '\\ACFUserSync')) {
            (new ACFUserSync())->register();
        }

        // === WPUF Frontend Enhancements (Dropdown, Cards, etc.) ===
        // We load the class unconditionally if it exists; the class itself checks flags.
        if (class_exists(__NAMESPACE__ . '\\WPUFFrontendEnhancements')) {
            (new WPUFFrontendEnhancements())->register();
        }
        // WPUF Geocoding (Privacy Focused)
        $geo_enabled = (bool) get_option('yardlii_enable_wpuf_geocoding', false);
        if (defined('YARDLII_ENABLE_WPUF_GEOCODING')) {
            $geo_enabled = (bool) constant('YARDLII_ENABLE_WPUF_GEOCODING');
        }

	// === Profile Form Switcher ===
        if (class_exists(__NAMESPACE__ . '\\ProfileFormSwitcher')) {
            (new ProfileFormSwitcher())->register();
        }

	

        // === WPUF City Autocomplete (Helper) ===
        if (class_exists(__NAMESPACE__ . '\\WpufCityAutocomplete')) {
            // PHPStan Safe Definitions
            $coreUrl = defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL : plugin_dir_url(__DIR__ . '/../');
            $coreVer = defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0.0';

            (new WpufCityAutocomplete($coreUrl, $coreVer))->register();
        }

        // [DEBUG] Force log to verify loader
        if ($geo_enabled) {
             error_log('[YARDLII] Loader: Geocoding is ENABLED. Loading class...');
        } else {
             error_log('[YARDLII] Loader: Geocoding is DISABLED.');
        }

        if ($geo_enabled && class_exists(__NAMESPACE__ . '\\WpufGeocoding')) {
            (new WpufGeocoding())->register();
        }

        // === Featured Listings Logic (New) ===
        if (get_option('yardlii_enable_featured_listings', false)) {
            if (class_exists(__NAMESPACE__ . '\\FeaturedListings')) {
                (new FeaturedListings())->register();
            }
        }

        // === Dynamic Posting Logic ===
        if (class_exists(__NAMESPACE__ . '\\PostingLogic')) {
            (new PostingLogic())->register();
        }

        // === Smart Form Overrides (New) ===
        // Handles "Pending" user behavior on the Basic Form
        if (class_exists(__NAMESPACE__ . '\\SmartFormOverrides')) {
            (new SmartFormOverrides())->register();
        }

	// === Submit Form Switcher (Dashboard) ===
        if (class_exists(__NAMESPACE__ . '\\SubmitFormSwitcher')) {
            (new SubmitFormSwitcher())->register();
        }

        // === Role Control (master + subfeatures) ===
        $rc_master = (bool) get_option('yardlii_enable_role_control', false);
        if (defined('YARDLII_ENABLE_ROLE_CONTROL')) {
            $rc_master = (bool) YARDLII_ENABLE_ROLE_CONTROL;
        }

	// === User Dashboard (My Listings) ===
        if (class_exists(__NAMESPACE__ . '\\UserDashboard')) {
            // Inject constants for assets
            $coreUrl = defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL : plugin_dir_url(__DIR__ . '/../');
            $coreVer = defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0.0';
            
            (new UserDashboard($coreUrl, $coreVer))->register();
        }

        // Submit Access
        $rc_submit_enabled = $rc_master && (bool) get_option('yardlii_enable_role_control_submit', false);
        if ($rc_submit_enabled && class_exists(__NAMESPACE__ . '\\RoleControlSubmitAccess')) {
            (new RoleControlSubmitAccess())->register();
        }

        // Custom User Roles
        $cur_enabled = $rc_master && (bool) get_option('yardlii_enable_custom_roles', true);
        if ($cur_enabled && class_exists(__NAMESPACE__ . '\\CustomUserRoles')) {
            (new CustomUserRoles())->register();
        }

        // Badge Assignment
        $badge_enabled = $rc_master && (bool) get_option('yardlii_enable_badge_assignment', true);
        if ($badge_enabled && class_exists(__NAMESPACE__ . '\\RoleControlBadgeAssignment')) {
            (new RoleControlBadgeAssignment())->register();
        }

	// === WPUF City Autocomplete (Universal Location) ===
        $loc_enabled = (bool) get_option('yardlii_enable_wpuf_city_autocomplete', false);
        if (defined('YARDLII_ENABLE_WPUF_CITY_AUTOCOMPLETE')) {
            $loc_enabled = (bool) YARDLII_ENABLE_WPUF_CITY_AUTOCOMPLETE;
        }

        if ($loc_enabled && class_exists(__NAMESPACE__ . '\\WpufCityAutocomplete')) {
            // PHPStan Safe Definitions
            $coreUrl = defined('YARDLII_CORE_URL') ? YARDLII_CORE_URL : plugin_dir_url(__DIR__ . '/../');
            $coreVer = defined('YARDLII_CORE_VERSION') ? YARDLII_CORE_VERSION : '1.0.0';

            (new WpufCityAutocomplete($coreUrl, $coreVer))->register();
        }

	// Elementor Pro Query Modifications
        if ( did_action( 'elementor/loaded' ) && class_exists( __NAMESPACE__ . '\\ElementorQueryMods' ) ) {
            ( new ElementorQueryMods() )->register();
        }

        // --- NEW: Custom Map Widget (Migrated) ---
        // Loads the custom map widget into Elementor
        if ( did_action( 'elementor/loaded' ) && class_exists( __NAMESPACE__ . '\\ElementorMapWidget' ) ) {
            ( new ElementorMapWidget() )->register();
        }

        // === Elementor Query Mods ===
        $el_enabled = (bool) get_option('yardlii_enable_elementor_query_mods', false);
        if (defined('YARDLII_ENABLE_ELEMENTOR_QUERY_MODS')) {
            $el_enabled = (bool) YARDLII_ENABLE_ELEMENTOR_QUERY_MODS;
        }

        if ($el_enabled && class_exists(__NAMESPACE__ . '\\ElementorQueryMods')) {
            (new ElementorQueryMods())->register();
        }
    }

    /**
     * Optional convenience boot if Loader needs to self-wire.
     * Not required if Core::init() calls (new Loader())->register().
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