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

    public function __construct(string $coreUrl, string $coreVersion)
    {
        $this->coreUrl = $coreUrl;
        $this->coreVersion = $coreVersion;
    }

    public function register(): void {
        // New generic shortcode [cite: 3]
        add_shortcode('yardlii_directory', [$this, 'render_directory']);
        // Alias for backward compatibility
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

        // Extract Attributes (Defaults) [cite: 6]
        $a = shortcode_atts([
            'role'  => 'verified_business', // Default role
            'limit' => '100', 
        ], $atts);

        // Sanitize
        $role_slug = sanitize_key($a['role']);
        $limit     = (int) $a['limit'];

        // 1. Query Users (Dynamic Role) [cite: 9]
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
            return '<div class="yardlii-no-results">No profiles found for this category.</div>';
        }

        ob_start();
        
        // Wrapper for JS scoping (Instance isolation)
        echo '<div class="yardlii-directory-wrapper">';

        // --- Search Bar ---
        // Note: Using class 'yardlii-dir-search-input' for JS targeting 
        ?>
        <div class="yardlii-dir-search-container">
            <i class="fas fa-search yardlii-dir-search-icon" aria-hidden="true"></i>
            <input type="text" 
                   class="yardlii-dir-search-input" 
                   placeholder="Search..." 
                   aria-label="Search Directory">
        </div>

        <div class="yardlii-directory-grid role-<?php echo esc_attr($role_slug); ?>">
        <?php

        // --- The Loop ---
        foreach ($users as $user) {
            $user_id = $user->ID;
            $acf_id  = 'user_' . $user_id;

            /** @var int|false $logo_id */
            $logo_id = $this->safelyGetAcf('yardlii_business_logo', $acf_id);
            /** @var string $company */
            $company = $this->safelyGetAcf('yardlii_company_name', $acf_id);
            /** @var string $trade */
            $trade   = $this->safelyGetAcf('yardlii_primary_trade', $acf_id);
            /** @var string $city */
            $city    = get_user_meta($user_id, 'billing_city', true); 

            // Fallbacks
            $d_company = !empty($company) ? $company : $user->display_name;
            $d_trade   = !empty($trade) ? $trade : ucwords(str_replace('_', ' ', $role_slug));
            $d_city    = !empty($city) ? $city : '';
            $link      = get_author_posts_url($user_id);

            $search_terms = strtolower($d_company . ' ' . $d_trade . ' ' . $d_city);
            ?>
            <div class="yardlii-business-card" data-search="<?php echo esc_attr($search_terms); ?>">
                <div class="ybc-header">
                    <?php if ($logo_id): ?>
                        <?php echo wp_get_attachment_image($logo_id, 'thumbnail', false, ['class' => 'ybc-logo']); ?>
                    <?php elseif ($avatar = get_avatar_url($user_id, ['size' => 150])): ?>
                        <img src="<?php echo esc_url($avatar); ?>" class="ybc-logo" alt="<?php echo esc_attr($d_company); ?>" />
                    <?php else: ?>
                        <div class="ybc-logo-placeholder"><i class="fas fa-building" aria-hidden="true"></i></div>
                    <?php endif; ?>
                </div>

                <div class="ybc-body">
                    <h3 class="ybc-title">
                        <a href="<?php echo esc_url((string)$link); ?>">
                            <?php echo esc_html($d_company); ?>
                        </a>
                    </h3>
                    <div class="ybc-meta">
                        <span class="ybc-badge">
                            <i class="fas fa-hammer" aria-hidden="true"></i> 
                            <?php echo esc_html($d_trade); ?>
                        </span>
                        <?php if ($d_city): ?>
                            <span class="ybc-location">
                                <i class="fas fa-map-marker-alt" aria-hidden="true"></i> 
                                <?php echo esc_html($d_city); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="ybc-footer">
                    <a href="<?php echo esc_url((string)$link); ?>" class="ybc-btn">View Profile</a>
                </div>
            </div>
            <?php
        }
        echo '</div>'; // End Grid
        echo '</div>'; // End Wrapper

        return (string) ob_get_clean();
    }

    /**
     * Helper to wrap ACF calls so PHPStan doesn't fail if ACF isn't loaded in CI.
     * @param string $key
     * @param string|int $post_id
     * @return mixed
     */
    private function safelyGetAcf(string $key, $post_id) {
        if (function_exists('get_field')) {
            return get_field($key, $post_id);
        }
        return false;
    }
}