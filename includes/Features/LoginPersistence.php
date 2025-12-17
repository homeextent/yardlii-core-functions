<?php
declare(strict_types=1);

namespace Yardlii\Core\Features;

/**
 * Feature: Always-On Login Persistence
 * Forces authentication cookies to last for 1 Year and hides the "Remember Me" UI.
 */
class LoginPersistence
{
    private const YEAR_IN_SECONDS = 31556926;

    public function register(): void
    {
        // 1. Extend Cookie Duration
        add_filter('auth_cookie_expiration', [$this, 'extend_cookie_duration'], 10, 3);

        // 2. Force "Remember Me" behavior on login
        add_action('wp_login', [$this, 'force_remember_on_login'], 10, 2);

        // 3. Hide the UI
        add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
        add_action('login_enqueue_scripts', [$this, 'enqueue_styles']); // For wp-login.php
    }

    /**
     * Set auth cookie expiration to 1 year.
     *
     * @param int $seconds
     * @param int $user_id
     * @param bool $remember
     * @return int
     */
    public function extend_cookie_duration(int $seconds, int $user_id, bool $remember): int
    {
        return self::YEAR_IN_SECONDS;
    }

    /**
     * Force the auth cookie to be set with the "remember" flag true,
     * even if the user didn't check the box.
     *
     * @param string $user_login
     * @param \WP_User $user
     */
    public function force_remember_on_login(string $user_login, \WP_User $user): void
    {
        // Only force if it wasn't already requested (prevents double-setting)
        if (!isset($_POST['rememberme'])) {
            wp_set_auth_cookie($user->ID, true);
        }
    }

    public function enqueue_styles(): void
    {
        if (defined('YARDLII_CORE_URL') && defined('YARDLII_CORE_VERSION')) {
            wp_enqueue_style(
                'yardlii-login-persistence',
                YARDLII_CORE_URL . 'assets/css/login-persistence.css',
                [],
                YARDLII_CORE_VERSION
            );
        }
    }
}