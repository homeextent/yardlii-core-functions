<?php
declare(strict_types=1);

namespace Yardlii\Core\Services;

/**
 * Service to manage the status and priority of all feature flags, caching
 * database reads to optimize bootstrap performance.
 */
final class FeatureFlagManager
{
    /** @var array<string, bool> Cached effective state: feature_key => status */
    private static array $cache = [];

    /**
     * Retrieves the effective status of a feature, checking the constant first, 
     * then the database (with caching).
     *
     * @param string $featureKey The suffix of the constant/option (e.g., 'role_control').
     * @return bool The effective status (true for enabled, false for disabled).
     */
    public static function isEnabled(string $featureKey): bool
    {
        // Normalize the key (e.g., 'trust_verification' or 'role_control')
        $featureKey = strtoupper($featureKey);
        $optionKey  = 'yardlii_enable_' . strtolower($featureKey);
        $constantName = 'YARDLII_ENABLE_' . $featureKey;

        // 1. Check Static Cache
        if (isset(self::$cache[$featureKey])) {
            return self::$cache[$featureKey];
        }

        // 2. Check Constant Override (highest priority)
        if (defined($constantName)) {
            $status = (bool) constant($constantName);
            self::$cache[$featureKey] = $status;
            return $status;
        }

        // 3. Check Database Option (lowest priority - uses get_option which is cached by WP)
        // Note: WP's object cache handles caching the option value itself, but we still cache the *final effective status*.
        $status = (bool) get_option($optionKey, false);

        // Special handling for legacy flags that defaulted ON but were simplified to OFF in the database.
        if ($featureKey === 'TRUST_VERIFICATION' && $status === false) {
             // For TV, the default is true, unless explicitly disabled by the option
             $status = (bool) get_option($optionKey, true);
        }
        
        self::$cache[$featureKey] = $status;
        return $status;
    }
}