<?php
declare(strict_types=1);

namespace Yardlii\Core\Features\TrustVerification\Settings;

final class GlobalSettings
{
    public const OPT_GROUP          = 'yardlii_tv_global_group';
    public const OPT_EMAILS         = 'yardlii_tv_admin_emails';
    public const OPT_VERIFIED_ROLES = 'yardlii_tv_verified_roles';
    // [Fix 1] Define the missing constant
    public const OPT_EXPIRY_DAYS    = 'yardlii_tv_expiry_days'; 

    public function registerSettings(): void
    {
        register_setting(self::OPT_GROUP, self::OPT_EMAILS, [
            'type'              => 'string',
            'sanitize_callback' => [$this, 'sanitizeEmails'],
        ]);

        register_setting(self::OPT_GROUP, self::OPT_VERIFIED_ROLES, [
            'type'              => 'array',
            'default'           => [],    
            'sanitize_callback' => [$this, 'sanitizeRoleArray'],
        ]);
      
        // [Fix 2] Register Expiry Days
        register_setting(self::OPT_GROUP, self::OPT_EXPIRY_DAYS, [
            'type'              => 'integer',
            'default'           => 5,
            'sanitize_callback' => 'absint',
        ]);

        // [Fix 3] Removed the "junk" block that was here (referencing $some_error)
    }

    public function sanitizeEmails($raw): string
    {
        $parts = array_map('trim', explode(',', (string) $raw));
        $valid = array_values(array_filter(array_map('sanitize_email', $parts), 'is_email'));
        return implode(', ', array_unique($valid));
    }

    public function sanitizeRoleArray($input): array
    {
        if (!is_array($input)) return [];
        $known = array_keys(wp_roles()->roles ?? []);
        $out   = [];
        foreach ($input as $r) {
            $r = sanitize_key($r);
            if (in_array($r, $known, true)) $out[] = $r;
        }
        return array_values(array_unique($out));
    }
}