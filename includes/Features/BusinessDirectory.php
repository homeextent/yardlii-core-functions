<?php

declare(strict_types=1);

namespace Yardlii\Core\Features;

use WP_User;
use WP_User_Query;

/**
 * Feature: Dynamic User Directory (v3.22.1)
 * Usage: [yardlii_directory role="verified_business" limit="100"]
 */
class BusinessDirectory {

    private string $coreUrl;
    private string $coreVersion;

    /** @var array<int, array<string, string>> */
    private array $roleConfigs = [];

    public function __construct(string $coreUrl, string $coreVersion)
    {
        $this->coreUrl = $coreUrl;
        $this->coreVersion = $coreVersion;
    }

    public function register(): void {
        // Main Directory Grid
        add_shortcode('yardlii_directory', [$this, 'render_directory']);
        add_shortcode('yardlii_business_directory', [$this, 'render_directory']);
        
        // Standalone Search Bar
        add_shortcode('yardlii_directory_search', [$this, 'render_search_bar_only']);

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
     * Standalone Search Bar Shortcode
     * Usage: [yardlii_directory_search target="my-grid-1"]
     * @param array<string, mixed>|string|null $atts
     * @return string
     */
    public function render_search_bar_only($atts): string {
        wp_enqueue_style('yardlii-business-directory');
        wp_enqueue_script('yardlii-business-directory-js');

        // Cast to array safely for shortcode_atts
        $safe_atts = (array) $atts;

        $a = shortcode_atts([
            'target' => '', // ID of the grid to control
        ], $safe_atts);

        $target = sanitize_html_class($a['target']);
        $tradesList = $this->getTradesList();

        ob_start();
        ?>
        <div class="yardlii-dir-filters yardlii-standalone-search" data-target="<?php echo esc_attr($target); ?>">
            <div class="yardlii-filter-group">
                <select class="yardlii-filter-trade">
                    <option value="">Select a Trade...</option>
                    <?php if (!empty($tradesList)): ?>
                        <?php foreach ($tradesList as $key => $label): ?>
                            <option value="<?php echo esc_attr((string)$label); ?>"><?php echo esc_html((string)$label); ?></option>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </select>
            </div>
            <div class="yardlii-filter-group">
                <input type="text" class="yardlii-filter-location" placeholder="Location (City)...">
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    /**
     * Main Directory Shortcode
     * @param array<string, mixed>|string|null $atts
     * @return string
     */
    public function render_directory($atts): string {
        wp_enqueue_style('yardlii-business-directory');
        wp_enqueue_script('yardlii-business-directory-js');

        $loadedConfigs = get_option('yardlii_directory_role_config', []);
        if (is_array($loadedConfigs)) {
            /** @var array<int, array<string, string>> $loadedConfigs */
            $this->roleConfigs = $loadedConfigs;
        }

        // Cast to array safely
        $safe_atts = (array) $atts;

        $a = shortcode_atts([
            'role'        => 'verified_business', 
            'limit'       => '100',
            'hide_search' => 'false',
            'id'          => '',    // Custom ID for targeting
            'card_width'  => '280'  // Grid layout control
        ], $safe_atts);

        $role_slug   = sanitize_key($a['role']);
        $limit       = (int) $a['limit'];
        $hide_search = filter_var($a['hide_search'], FILTER_VALIDATE_BOOLEAN);
        $custom_id   = sanitize_html_class($a['id']);
        $card_width  = (int) $a['card_width'];

        // Ensure valid width
        if ($card_width < 150) $card_width = 150;

        $config = $this->findConfigForRole($role_slug);

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

        $tradesList = $this->getTradesList();

        ob_start();
        echo '<div class="yardlii-directory-wrapper">';
        
        // Render Internal Search (if not hidden)
        if (!$hide_search) {
            ?>
            <div class="yardlii-dir-filters">
                <div class="yardlii-filter-group">
                    <select class="yardlii-filter-trade">
                        <option value="">Select a Trade...</option>
                        <?php if (!empty($tradesList)): ?>
                            <?php foreach ($tradesList as $key => $label): ?>
                                <option value="<?php echo esc_attr((string)$label); ?>"><?php echo esc_html((string)$label); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="yardlii-filter-group">
                    <input type="text" class="yardlii-filter-location" placeholder="Location (City)...">
                </div>
            </div>
            <?php
        }

        // Apply ID and CSS Variable for Width
        $id_attr = $custom_id ? 'id="' . esc_attr($custom_id) . '"' : '';
        $style_attr = 'style="--yardlii-card-width: ' . $card_width . 'px;"';

        echo '<div class="yardlii-directory-grid role-' . esc_attr($role_slug) . '" ' . $id_attr . ' ' . $style_attr . '>';

        foreach ($users as $user) {
            $user_id = $user->ID;

            $logo_id = $this->fetch_dynamic_value($user, $config['image'] ?? '');
            $avatar  = get_avatar_url($user_id, ['size' => 300]); 

            $company = (string) $this->fetch_dynamic_value($user, $config['title'] ?? '');
            $d_company = !empty($company) ? $company : $user->display_name;

            $trade_raw = (string) $this->fetch_dynamic_value($user, $config['badge'] ?? '');
            $d_trade_display = $trade_raw;
            if (isset($tradesList[$trade_raw])) {
                $d_trade_display = (string) $tradesList[$trade_raw];
            } elseif (empty($trade_raw)) {
                $d_trade_display = ucwords(str_replace('_', ' ', $role_slug));
            }

            $d_city = (string) $this->fetch_dynamic_value($user, $config['location'] ?? '');
            $link = get_author_posts_url($user_id);

            $filter_trade = strtolower(strip_tags($d_trade_display));
            $filter_loc   = strtolower(strip_tags($d_city));
            
            ?>
            <div class="yardlii-business-card" 
                 data-trade="<?php echo esc_attr($filter_trade); ?>"
                 data-location="<?php echo esc_attr($filter_loc); ?>">
                 
                <div class="ybc-header">
                    <?php if (is_numeric($logo_id) && $logo_id > 0): ?>
                        <?php echo wp_get_attachment_image((int)$logo_id, 'medium', false, ['class' => 'ybc-logo']); ?>
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
                        <?php if(!empty($d_trade_display)): ?>
                            <span class="ybc-badge"><i class="fas fa-hammer"></i> <?php echo esc_html($d_trade_display); ?></span>
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
     * @return array<string, string>
     */
    private function getTradesList(): array
    {
        $field_name = get_option('yardlii_dir_trade_field', 'primary_trade');
        if (function_exists('acf_get_field')) {
            $field = acf_get_field($field_name);
            if (is_array($field) && isset($field['choices']) && is_array($field['choices'])) {
                /** @var array<string, string> */
                return $field['choices'];
            }
        }
        return [];
    }

    /**
     * @param string $role
     * @return array<string, string>
     */
    private function findConfigForRole(string $role): array
    {
        foreach ($this->roleConfigs as $cfg) {
            if (isset($cfg['role']) && $cfg['role'] === $role) {
                return $cfg;
            }
        }
        return [];
    }

    /**
     * @param WP_User $user
     * @param string $key
     * @return mixed
     */
    private function fetch_dynamic_value(WP_User $user, string $key) {
        if (empty($key)) return '';
        if (function_exists('get_field')) {
            $val = get_field($key, 'user_' . $user->ID);
            if (is_array($val)) return isset($val['label']) ? $val['label'] : (string) reset($val);
            if (!empty($val)) return $val;
        }
        $meta = get_user_meta($user->ID, $key, true);
        if (!empty($meta)) return $meta;
        if (isset($user->$key)) return $user->$key;
        return '';
    }
}