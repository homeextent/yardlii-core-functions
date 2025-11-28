<?php

declare(strict_types=1);

namespace Yardlii\Core\Features;

use WP_User;
use WP_User_Query;

/**
 * Feature: Dynamic User Directory
 * Usage: [yardlii_directory role="verified_business" limit="100"]
 */
class BusinessDirectory {

    private string $coreUrl;
    private string $coreVersion;

    // Config keys
    private string $keyImage;
    private string $keyTitle;
    private string $keyBadge;
    private string $keyLocation;

    public function __construct(string $coreUrl, string $coreVersion)
    {
        $this->coreUrl = $coreUrl;
        $this->coreVersion = $coreVersion;
    }

    public function register(): void {
        add_shortcode('yardlii_directory', [$this, 'render_directory']);
        add_shortcode('yardlii_business_directory', [$this, 'render_directory']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(): void {
        wp_register_style(
            'yardlii-business-directory',
            $this->coreUrl . 'assets/css/business-directory.css',
            [],
            $this->coreVersion
        );
        wp_register_script(
            'yardlii-business-directory-js',
            $this->coreUrl . 'assets/js/business-directory.js',
            [], 
            $this->coreVersion,
            true 
        );
    }

    /**
     * @param array<string, mixed>|string $atts
     */
    public function render_directory($atts): string {
        wp_enqueue_style('yardlii-business-directory');
        wp_enqueue_script('yardlii-business-directory-js');

        // Load Mapping Config (Defaults to previous hardcoded values)
        $this->keyImage    = get_option('yardlii_dir_map_image', 'yardlii_business_logo');
        $this->keyTitle    = get_option('yardlii_dir_map_title', 'yardlii_company_name');
        $this->keyBadge    = get_option('yardlii_dir_map_badge', 'yardlii_primary_trade');
        $this->keyLocation = get_option('yardlii_dir_map_location', 'billing_city');

        $a = shortcode_atts([
            'role'  => 'verified_business', 
            'limit' => '100', 
        ], $atts);

        $role_slug = sanitize_key($a['role']);
        $limit     = (int) $a['limit'];

        $args = [
            'role'    => $role_slug,
            'orderby' => 'display_name',
            'order'   => 'ASC',
            'number'  => $limit,
        ];
        
        $user_query = new WP_User_Query($args);
        
        /** @var array<WP_User> $users */
        $users = $user_query->get_results();

        if (empty($users)) {
            return '<div class="yardlii-no-results">No profiles found.</div>';
        }

        ob_start();
        echo '<div class="yardlii-directory-wrapper">';
        ?>
        <div class="yardlii-dir-search-container">
            <i class="fas fa-search yardlii-dir-search-icon" aria-hidden="true"></i>
            <input type="text" class="yardlii-dir-search-input" placeholder="Search..." aria-label="Search">
        </div>

        <div class="yardlii-directory-grid role-<?php echo esc_attr($role_slug); ?>">
        <?php

        foreach ($users as $user) {
            $user_id = $user->ID;

            // --- 1. Fetch Dynamic Values ---
            
            // Image Logic: Try Mapped Key -> Fallback to Avatar
            $logo_id = $this->fetch_dynamic_value($user, $this->keyImage);
            $avatar  = get_avatar_url($user_id, ['size' => 150]);

            // Title Logic: Try Mapped Key -> Fallback to Display Name
            $company = (string) $this->fetch_dynamic_value($user, $this->keyTitle);
            $d_company = !empty($company) ? $company : $user->display_name;

            // Badge Logic: Try Mapped Key -> Fallback to Role Name
            $trade = (string) $this->fetch_dynamic_value($user, $this->keyBadge);
            $d_trade = !empty($trade) ? $trade : ucwords(str_replace('_', ' ', $role_slug));

            // Location Logic: Try Mapped Key -> Empty fallback
            $d_city = (string) $this->fetch_dynamic_value($user, $this->keyLocation);
            
            $link = get_author_posts_url($user_id);
            $search_terms = strtolower($d_company . ' ' . $d_trade . ' ' . $d_city);
            ?>
            <div class="yardlii-business-card" data-search="<?php echo esc_attr($search_terms); ?>">
                <div class="ybc-header">
                    <?php if (is_numeric($logo_id) && $logo_id > 0): ?>
                        <?php echo wp_get_attachment_image((int)$logo_id, 'thumbnail', false, ['class' => 'ybc-logo']); ?>
                    <?php elseif (!empty($logo_id) && is_string($logo_id) && filter_var($logo_id, FILTER_VALIDATE_URL)): ?>
                         <img src="<?php echo esc_url($logo_id); ?>" class="ybc-logo" alt="" />
                    <?php elseif ($avatar): ?>
                        <img src="<?php echo esc_url($avatar); ?>" class="ybc-logo" alt="" />
                    <?php else: ?>
                        <div class="ybc-logo-placeholder"><i class="fas fa-building" aria-hidden="true"></i></div>
                    <?php endif; ?>
                </div>

                <div class="ybc-body">
                    <h3 class="ybc-title">
                        <a href="<?php echo esc_url((string)$link); ?>"><?php echo esc_html($d_company); ?></a>
                    </h3>
                    <div class="ybc-meta">
                        <?php if(!empty($d_trade)): ?>
                            <span class="ybc-badge"><i class="fas fa-hammer"></i> <?php echo esc_html($d_trade); ?></span>
                        <?php endif; ?>
                        
                        <?php if ($d_city): ?>
                            <span class="ybc-location"><i class="fas fa-map-marker-alt"></i> <?php echo esc_html($d_city); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="ybc-footer">
                    <a href="<?php echo esc_url((string)$link); ?>" class="ybc-btn">View Profile</a>
                </div>
            </div>
            <?php
        }
        echo '</div></div>';

        return (string) ob_get_clean();
    }

    /**
     * Smart Value Fetcher
     * 1. Checks ACF (user_{id})
     * 2. Checks User Meta
     * 3. Checks WP_User object properties (user_email, etc)
     */
    private function fetch_dynamic_value(WP_User $user, string $key): mixed {
        if (empty($key)) return '';

        // 1. Try ACF
        if (function_exists('get_field')) {
            $val = get_field($key, 'user_' . $user->ID);
            if (!empty($val)) return $val;
        }

        // 2. Try User Meta
        $meta = get_user_meta($user->ID, $key, true);
        if (!empty($meta)) return $meta;

        // 3. Try Object Property (e.g. 'user_email')
        if (isset($user->$key)) {
            return $user->$key;
        }

        return '';
    }
}