<?php
declare(strict_types=1);

namespace Yardlii\Core\Services;

/**
 * Centralized Logger for YARDLII Core.
 * strictly adheres to the 'yardlii_debug_mode' option.
 */
class Logger {

    /**
     * Cache the debug option to avoid DB hits on every log call.
     */
    private static ?bool $enabled = null;

    /**
     * @param string $message The message to log.
     * @param string $context The feature context (e.g., 'TV', 'GEO', 'CORE').
     * @param array<mixed> $data Optional data to JSON encode and append.
     */
    public static function log(string $message, string $context = 'CORE', array $data = []): void {
        if (!self::isEnabled()) {
            return;
        }

        $entry = sprintf('[YARDLII][%s] %s', strtoupper($context), $message);

        if (!empty($data)) {
            $entry .= ' | Data: ' . wp_json_encode($data);
        }

        error_log($entry);
    }

    /**
     * Internal check for the master debug switch.
     */
    private static function isEnabled(): bool {
        if (self::$enabled === null) {
            // Strict check: Only log if the plugin option is explicitly true.
            // We intentionally ignore WP_DEBUG to allow "quiet mode" on dev sites.
            self::$enabled = (bool) get_option('yardlii_debug_mode', false);
        }
        return self::$enabled;
    }
}