<?php
namespace Yardlii\Core\Admin;

use Yardlii\Core\Helpers\Notices;

defined('ABSPATH') || exit;

/**
 * YARDLII Core Settings — Tabs Shell
 */
final class SettingsPageTabs
{
    /** Group keys */
    private const GROUP_DEBUG          = 'yardlii_debug_group';
    private const GROUP_FEATURE_FLAGS  = 'yardlii_feature_flags_group';

    private const GROUP_SEARCH         = 'yardlii_search_group';
    private const GROUP_GOOGLE_MAP     = 'yardlii_google_map_group';
    private const GROUP_FEATURED_IMAGE = 'yardlii_featured_image_group';
    private const GROUP_DIRECTORY      = 'yardlii_directory_group';

    // --- NEW WPUF GROUPS ---
    private const GROUP_WPUF_STYLING   = 'yardlii_wpuf_styling_group';
    private const GROUP_WPUF_LOGIC     = 'yardlii_wpuf_logic_group';
    private const GROUP_WPUF_GEO       = 'yardlii_wpuf_geo_group';
    private const GROUP_WPUF_PROFILE   = 'yardlii_wpuf_profile_group';
    // -----------------------

    private const GROUP_ROLE_CONTROL   = 'yardlii_role_control_group';
    private const GROUP_ROLE_BADGES    = 'yardlii_role_control_badges_group';

    public function register(): void
    {
        add_action('admin_menu',  [$this, 'add_menu']);
        add_action('admin_init',  [$this, 'register_settings']);
        
        // REMOVED: pre-emptive notice clearing hooks. 
        // We want standard WP behavior now.
    }

    public function register_settings(): void
    {
        $this->register_debug_settings();
        $this->register_feature_flags_settings();
        $this->register_search_settings();
        $this->register_google_map_settings();
        $this->register_featured_image_settings();

        // --- WPUF CUSTOMISATIONS: SPLIT GROUPS ---

        // 1. Frontend Styling Tab
        register_setting(self::GROUP_WPUF_STYLING, 'yardlii_enable_wpuf_dropdown', [
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
        register_setting(self::GROUP_WPUF_STYLING, 'yardlii_wpuf_target_pages', [
            'sanitize_callback' => 'sanitize_text_field',
            'default'           => 'submit-a-post',
        ]);
        register_setting(self::GROUP_WPUF_STYLING, 'yardlii_wpuf_card_layout', [
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => false,
        ]);
        register_setting(self::GROUP_WPUF_STYLING, 'yardlii_wpuf_modern_uploader', [
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => false,
        ]);

        // 2. Listing Logic Tab
        register_setting(self::GROUP_WPUF_LOGIC, 'yardlii_enable_featured_listings', [
            'sanitize_callback' => 'rest_sanitize_boolean',
            'default'           => false,
        ]);
        register_setting(self::GROUP_WPUF_LOGIC, 'yardlii_posting_logic_pro_form', [
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ]);
        register_setting(self::GROUP_WPUF_LOGIC, 'yardlii_posting_logic_basic_form', [
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ]);
        register_setting(self::GROUP_WPUF_LOGIC, 'yardlii_posting_logic_provisional_form', [
            'sanitize_callback' => 'absint',
            'default'           => 0,
        ]);

        // 3. Privacy Geocoding Tab
        register_setting(self::GROUP_WPUF_GEO, 'yardlii_wpuf_geo_mapping', [
            'sanitize_callback' => 'sanitize_textarea_field'
        ]);
        register_setting(self::GROUP_WPUF_GEO, 'yardlii_enable_wpuf_geocoding', [
            'sanitize_callback' => 'rest_sanitize_boolean'
        ]);
        
        // Keep Advanced Tab Flag Sync
        register_setting(self::GROUP_FEATURE_FLAGS, 'yardlii_enable_wpuf_geocoding', [
            'sanitize_callback' => 'rest_sanitize_boolean'
        ]);

        // 4. Profile Mapping Tab
        register_setting(self::GROUP_WPUF_PROFILE, 'yardlii_profile_form_map', [
            'sanitize_callback' => static function ($input) {
                if (!is_array($input)) return [];
                return array_map(function($row) {
                    return [
                        'role'    => sanitize_text_field($row['role'] ?? ''),
                        'form_id' => absint($row['form_id'] ?? 0),
                    ];
                }, $input);
            }
        ]);

        // === User Directory (Global Config) ===
        register_setting(self::GROUP_DIRECTORY, 'yardlii_dir_default_trigger', [
            'sanitize_callback' => 'sanitize_key'
        ]);
        register_setting(self::GROUP_DIRECTORY, 'yardlii_dir_default_width', [
            'sanitize_callback' => 'absint'
        ]);
        register_setting(self::GROUP_DIRECTORY, 'yardlii_dir_trade_field', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
        register_setting(self::GROUP_DIRECTORY, 'yardlii_directory_role_config', [
            'sanitize_callback' => static function ($input) {
                if (!is_array($input)) return [];
                return array_map(function($row) {
                    return [
                        'role'     => sanitize_text_field($row['role'] ?? ''),
                        'image'    => sanitize_text_field($row['image'] ?? ''),
                        'title'    => sanitize_text_field($row['title'] ?? ''),
                        'badge'    => sanitize_text_field($row['badge'] ?? ''),
                        'location' => sanitize_text_field($row['location'] ?? ''),
                        'ui_decoupled' => isset($row['ui_decoupled']) ? '1' : '0',
                        'ui_button'    => isset($row['ui_button']) ? '1' : '0',
                        'ui_width'     => sanitize_text_field($row['ui_width'] ?? ''),
                    ];
                }, $input);
            }
        ]);

        $this->register_role_control_settings();
        $this->register_role_badge_settings();
    }

    private function register_debug_settings(): void
    {
        register_setting(self::GROUP_DEBUG, 'yardlii_debug_mode', [
            'type'              => 'boolean',
            'default'           => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
        register_setting(self::GROUP_DEBUG, 'yardlii_remove_data_on_delete', [
            'type'              => 'boolean',
            'default'           => false,
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
    }

    private function register_feature_flags_settings(): void
    {
        register_setting(self::GROUP_FEATURE_FLAGS, 'yardlii_enable_role_control',       ['sanitize_callback' => 'rest_sanitize_boolean']);
        register_setting(self::GROUP_FEATURE_FLAGS, 'yardlii_enable_media_cleanup',      ['sanitize_callback' => 'rest_sanitize_boolean']);
        register_setting(self::GROUP_FEATURE_FLAGS, 'yardlii_enable_business_directory', ['sanitize_callback' => 'rest_sanitize_boolean']);
        
        // NEW Features
        register_setting(
            self::GROUP_FEATURE_FLAGS,
            'yardlii_enable_wpuf_city_autocomplete',
            ['sanitize_callback' => 'rest_sanitize_boolean']
        );
        register_setting(
            self::GROUP_FEATURE_FLAGS,
            'yardlii_enable_elementor_query_mods',
            ['sanitize_callback' => 'rest_sanitize_boolean']
        );

        // ...
        register_setting(
            self::GROUP_FEATURE_FLAGS,
            'yardlii_enable_media_management',
            ['sanitize_callback' => 'rest_sanitize_boolean']
        );

        register_setting(
            self::GROUP_FEATURE_FLAGS,
            'yardlii_enable_login_persistence',
            ['sanitize_callback' => 'rest_sanitize_boolean']
        );

        register_setting(
            self::GROUP_FEATURE_FLAGS,
            'yardlii_enable_custom_map_widget',
            ['sanitize_callback' => 'rest_sanitize_boolean']
        );
    }

    private function register_search_settings(): void
    {
        register_setting(self::GROUP_SEARCH, 'yardlii_primary_taxonomy',      ['sanitize_callback' => 'sanitize_text_field']);
        register_setting(self::GROUP_SEARCH, 'yardlii_primary_label',         ['sanitize_callback' => 'sanitize_text_field']);
        register_setting(self::GROUP_SEARCH, 'yardlii_primary_facet',         ['sanitize_callback' => 'sanitize_text_field']);
        register_setting(self::GROUP_SEARCH, 'yardlii_secondary_taxonomy',    ['sanitize_callback' => 'sanitize_text_field']);
        register_setting(self::GROUP_SEARCH, 'yardlii_secondary_label',       ['sanitize_callback' => 'sanitize_text_field']);
        register_setting(self::GROUP_SEARCH, 'yardlii_secondary_facet',       ['sanitize_callback' => 'sanitize_text_field']);
        register_setting(self::GROUP_SEARCH, 'yardlii_homepage_search_debug', ['sanitize_callback' => 'rest_sanitize_boolean']);
        register_setting(self::GROUP_SEARCH, 'yardlii_location_facet',        ['sanitize_callback' => 'sanitize_text_field']);
        register_setting(self::GROUP_SEARCH, 'yardlii_location_label',        ['sanitize_callback' => 'sanitize_text_field']);
        register_setting(self::GROUP_SEARCH, 'yardlii_enable_location_search',['sanitize_callback' => 'rest_sanitize_boolean']);
    }

    private function register_google_map_settings(): void
    {
        register_setting(self::GROUP_GOOGLE_MAP, 'yardlii_google_map_key', ['sanitize_callback' => 'sanitize_text_field']);
        register_setting(self::GROUP_GOOGLE_MAP, 'yardlii_google_server_key', ['sanitize_callback' => 'sanitize_text_field']); 
        
        // Map controls array sanitizer
        register_setting(self::GROUP_GOOGLE_MAP, 'yardlii_map_controls',   ['sanitize_callback' => function($input) {
             if (!is_array($input)) return [];
             return array_map('sanitize_text_field', $input);
        }]);

        // Loading Group
        register_setting('yardlii_google_map_loading_group', 'yardlii_gmap_target_pages', [
            'sanitize_callback' => 'sanitize_text_field'
        ]);
    }

    private function register_featured_image_settings(): void
    {
        register_setting(self::GROUP_FEATURED_IMAGE, 'yardlii_featured_image_field', ['sanitize_callback' => 'sanitize_text_field']);
        
        $sanitize_forms_cb = static function ($input) {
            if (!is_array($input)) $input = [];
            return array_values(array_filter(array_map('absint', $input)));
        };

        register_setting(self::GROUP_FEATURED_IMAGE, 'yardlii_listing_form_id', ['sanitize_callback' => $sanitize_forms_cb]);
        register_setting(self::GROUP_FEATURED_IMAGE, 'yardlii_featured_image_debug', ['sanitize_callback' => 'rest_sanitize_boolean']);
    }

    private function register_role_control_settings(): void
    {
        register_setting(self::GROUP_ROLE_CONTROL, 'yardlii_enable_role_control_submit', [
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
        register_setting(self::GROUP_ROLE_CONTROL, 'yardlii_role_control_allowed_roles', [
            'sanitize_callback' => static function ($v) {
                if (!is_array($v)) $v = [];
                $v = array_map('sanitize_text_field', $v);
                $editable = array_keys(get_editable_roles());
                return array_values(array_intersect($v, $editable));
            },
        ]);
        register_setting(self::GROUP_ROLE_CONTROL, 'yardlii_role_control_denied_action', [
            'sanitize_callback' => static function ($v) {
                $v = sanitize_text_field($v);
                return in_array($v, ['redirect_login', 'message'], true) ? $v : 'message';
            },
        ]);
        register_setting(self::GROUP_ROLE_CONTROL, 'yardlii_role_control_denied_message', [
            'sanitize_callback' => 'sanitize_textarea_field',
        ]);
        register_setting(self::GROUP_ROLE_CONTROL, 'yardlii_role_control_target_page', [
            'sanitize_callback' => 'sanitize_text_field',
        ]);
        register_setting(self::GROUP_ROLE_CONTROL, 'yardlii_enable_custom_roles', [
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
        register_setting(self::GROUP_ROLE_CONTROL, 'yardlii_custom_roles', [
            'sanitize_callback' => ['Yardlii\\Core\\Features\\CustomUserRoles', 'sanitize_settings'],
        ]);
    }

    private function register_role_badge_settings(): void
    {
        register_setting(self::GROUP_ROLE_BADGES, 'yardlii_enable_badge_assignment', [
            'sanitize_callback' => 'rest_sanitize_boolean',
        ]);
        register_setting(self::GROUP_ROLE_BADGES, 'yardlii_rc_badges', [
            'sanitize_callback' => ['Yardlii\\Core\\Features\\RoleControlBadgeAssignment', 'sanitize_settings'],
            'default'           => ['map' => [], 'meta_key' => 'user_badge', 'fallback_field' => ''],
        ]);
    }

    /* =========================================
     * Menu + Page
     * =======================================*/
    public function add_menu(): void
    {
        add_menu_page(
            __('YARDLII Core Settings', 'yardlii-core'), 
            __('YARDLII', 'yardlii-core'),               
            'manage_options',                            
            'yardlii-core-settings',                     
            [$this, 'render_page'],                      
            'dashicons-shield',                          
            50                                           
        );
    }

    public function render_page(): void
    {
        if (!current_user_can('manage_options')) return;

        // --- GLOBAL NOTICES START ---
        // We only call settings_errors() ONCE here.
        // This will catch standard WP "Settings Saved" messages AND
        // any custom errors added by our classes.
        settings_errors(); 
        // --- GLOBAL NOTICES END ---

        $role_control_master = (bool) get_option('yardlii_enable_role_control', false);
        if (defined('YARDLII_ENABLE_ROLE_CONTROL')) {
            $role_control_master = (bool) YARDLII_ENABLE_ROLE_CONTROL;
        }

        echo '<div class="wrap yardlii-wrap">';
        echo '<div class="yardlii-header">';
        echo '<img class="yardlii-logo" src="' . esc_url(plugins_url('/assets/images/logo.png', YARDLII_CORE_FILE)) . '" alt="YARDLII" width="40" height="40" />';
        echo '<h1 class="yardlii-title">' . esc_html__('YARDLII Core Settings', 'yardlii-core') . '</h1>';
        echo '</div>';

        if (class_exists(Notices::class)) {
            Notices::render(); // Custom Yardlii notices if any
        }
        ?>
        <nav class="yardlii-tabs" role="tablist" data-scope="main">
            <button type="button" class="yardlii-tab active" data-tab="general" aria-selected="true">🗺️ General</button>
            <button type="button" class="yardlii-tab" data-tab="role-control" aria-selected="false">🛡️ Role Control</button>
            <button type="button" class="yardlii-tab" data-tab="wpuf" aria-selected="false">🔧 WPUF Customisations</button>
            <button type="button" class="yardlii-tab" data-tab="advanced" aria-selected="false">⚙️ Advanced</button>
        </nav>

        <section id="yardlii-tab-general" class="yardlii-tabpanel" data-panel="general">
            <nav class="yardlii-tabs yardlii-general-subtabs" role="tablist" aria-label="General Sections">
                <button type="button" class="yardlii-tab active" data-gsection="gmap" aria-selected="true">🗺️ Google Map Settings</button>
                <button type="button" class="yardlii-tab"        data-gsection="fimg" aria-selected="false">🖼️ Featured Image Automation</button>
                <button type="button" class="yardlii-tab"        data-gsection="home" aria-selected="false">🔍 Homepage Search</button>
                <button type="button" class="yardlii-tab"        data-gsection="dir"  aria-selected="false">📂 User Directory</button>
                </nav>

            <details class="yardlii-section" id="gsec-gmap" data-gsection="gmap" open>
                <summary>🗺️ Google Map Settings</summary>
                <div class="yardlii-section-content">
                    <div class="yardlii-inner-tabs">
                        <nav class="yardlii-inner-tablist" role="tablist" aria-label="Google Map Settings">
                            <button type="button" class="yardlii-inner-tab active" data-tab="map-api" aria-selected="true">🔑 Google Maps API</button>
                            <button type="button" class="yardlii-inner-tab"        data-tab="map-options" aria-selected="false">⚙️ Map Display Options</button>
                            <button type="button" class="yardlii-inner-tab"        data-tab="map-loading" aria-selected="false">🚀 Loading Strategy</button>
                        </nav>
                        
                        <div class="yardlii-inner-tabcontent" data-panel="map-api" role="tabpanel">
                            <?php include __DIR__ . '/views/partials/google-map-key.php'; ?>
                        </div>
                        <div class="yardlii-inner-tabcontent hidden" data-panel="map-options" role="tabpanel">
                            <?php include __DIR__ . '/views/partials/google-map-controls.php'; ?>
                        </div>
                        <div class="yardlii-inner-tabcontent hidden" data-panel="map-loading" role="tabpanel">
                            <?php include __DIR__ . '/views/partials/google-map-loading.php'; ?>
                        </div>
                    </div>
                </div>
            </details>

            <details class="yardlii-section" id="gsec-fimg" data-gsection="fimg">
                <summary>🖼️ Featured Image Automation</summary>
                <div class="yardlii-section-content">
                    <?php include __DIR__ . '/views/partials/featured-image.php'; ?>
                </div>
            </details>

            <details class="yardlii-section" id="gsec-home" data-gsection="home">
                <summary>🔍 Homepage Search</summary>
                <div class="yardlii-section-content">
                    <?php include __DIR__ . '/views/partials/homepage-search.php'; ?>
                </div>
            </details>

            <details class="yardlii-section" id="gsec-dir" data-gsection="dir">
                <summary>📂 User Directory</summary>
                <div class="yardlii-section-content">
                    <?php include __DIR__ . '/views/partials/user-directory.php'; ?>
                </div>
            </details>
        </section>

        <section id="yardlii-tab-wpuf" class="yardlii-tabpanel hidden" data-panel="wpuf">
            <nav class="yardlii-tabs yardlii-wpuf-subtabs" role="tablist" aria-label="WPUF Sections">
                <button type="button" class="yardlii-tab active" data-wsection="styling" aria-selected="true">🎨 Frontend Styling</button>
                <button type="button" class="yardlii-tab"        data-wsection="logic"   aria-selected="false">🧠 Listing Logic</button>
                <button type="button" class="yardlii-tab"        data-wsection="geo"     aria-selected="false">📍 Privacy Geocoding</button>
                <button type="button" class="yardlii-tab"        data-wsection="profile" aria-selected="false">👤 Profile Mapping</button>
            </nav>

            <details class="yardlii-section" data-wsection="styling" open>
                <summary>🎨 Frontend Styling</summary>
                <div class="yardlii-section-content"><?php include __DIR__ . '/views/partials/wpuf/frontend-styling.php'; ?></div>
            </details>

            <details class="yardlii-section" data-wsection="logic">
                <summary>🧠 Listing Logic</summary>
                <div class="yardlii-section-content"><?php include __DIR__ . '/views/partials/wpuf/listing-logic.php'; ?></div>
            </details>

            <details class="yardlii-section" data-wsection="geo">
                <summary>📍 Privacy Geocoding</summary>
                <div class="yardlii-section-content"><?php include __DIR__ . '/views/partials/wpuf/privacy-geocoding.php'; ?></div>
            </details>

            <details class="yardlii-section" data-wsection="profile">
                <summary>👤 Profile Mapping</summary>
                <div class="yardlii-section-content"><?php include __DIR__ . '/views/partials/wpuf/profile-mapping.php'; ?></div>
            </details>
        </section>

        <section id="yardlii-tab-role-control" class="yardlii-tabpanel hidden" data-panel="role-control" aria-disabled="<?php echo $role_control_master ? 'false' : 'true'; ?>">
            <?php
            // FIX: Removed manual settings_errors() calls here
            ?>
            <?php if (!$role_control_master): ?>
                <div class="notice notice-warning" style="margin:12px 0;">
                    <p><strong><?php esc_html_e('Role Control is disabled.', 'yardlii-core'); ?></strong>
                        <?php esc_html_e('Turn it on in Advanced → Feature Flags to make changes.', 'yardlii-core'); ?></p>
                </div>
                <fieldset disabled aria-disabled="true" class="yardlii-locked">
            <?php endif; ?>
            <nav class="yardlii-tabs yardlii-role-subtabs" role="tablist" aria-label="Role Control Sections">
                <button type="button" class="yardlii-tab"        data-rsection="roles"  aria-selected="false">👥 Custom User Roles</button>
                <button type="button" class="yardlii-tab"        data-rsection="badges" aria-selected="false">🏷️ Badge Assignment</button>
                <button type="button" class="yardlii-tab active" data-rsection="submit" aria-selected="true">🛡️ Submit Access</button>
            </nav>
            <details class="yardlii-section" data-rsection="roles">
                <summary>👥 Custom User Roles</summary>
                <div class="yardlii-section-content"><?php include __DIR__ . '/views/partials/role-control-custom-roles.php'; ?></div>
            </details>
            <details class="yardlii-section" data-rsection="badges">
                <summary>🏷️ Badge Assignment</summary>
                <div class="yardlii-section-content"><?php include __DIR__ . '/views/partials/role-control-badge-assignment.php'; ?></div>
            </details>
            <details class="yardlii-section" data-rsection="submit" open>
                <summary>🛡️ Submit Access</summary>
                <div class="yardlii-section-content"><?php include __DIR__ . '/views/partials/role-control-submit.php'; ?></div>
            </details>
            <?php if (!$role_control_master): ?>
                </fieldset>
            <?php endif; ?>
        </section>

        <section id="yardlii-tab-advanced" class="yardlii-tabpanel hidden" data-panel="advanced">
          <?php
          // FIX: Removed manual settings_errors() calls here
          $adv_section = isset($_GET['advsection']) ? sanitize_key($_GET['advsection']) : 'flags';
          ?>
          <nav class="yardlii-tabs yardlii-advanced-subtabs" role="tablist" aria-label="<?php esc_attr_e('Advanced Sections', 'yardlii-core'); ?>">
            <button type="button" class="yardlii-tab <?php echo $adv_section === 'flags' ? 'active' : ''; ?>" data-asection="flags" aria-selected="<?php echo $adv_section === 'flags' ? 'true' : 'false'; ?>">
              <?php esc_html_e('Feature Flags & Debug', 'yardlii-core'); ?>
            </button>
            <button type="button" class="yardlii-tab <?php echo $adv_section === 'diagnostics' ? 'active' : ''; ?>" data-asection="diagnostics" aria-selected="<?php echo $adv_section === 'diagnostics' ? 'true' : 'false'; ?>">
              <?php esc_html_e('Diagnostics', 'yardlii-core'); ?>
            </button>
          </nav>
          <details class="yardlii-section" id="asec-flags" data-asection="flags" <?php if ($adv_section === 'flags') echo 'open'; ?>>
            <summary><?php esc_html_e('Feature Flags & Debug', 'yardlii-core'); ?></summary>
            <div class="yardlii-section-content">
              <?php
              $group_debug    = self::GROUP_DEBUG;
              $group_flags    = self::GROUP_FEATURE_FLAGS;
              include __DIR__ . '/views/partials/advanced/section-flags.php';
              ?>
            </div>
          </details>
          <details class="yardlii-section" id="asec-diagnostics" data-asection="diagnostics" <?php if ($adv_section === 'diagnostics') echo 'open'; ?>>
            <summary><?php esc_html_e('Diagnostics', 'yardlii-core'); ?></summary>
            <div class="yardlii-section-content"><?php include __DIR__ . '/views/partials/advanced/section-diagnostics.php'; ?></div>
          </details>
        </section>

        <footer class="yardlii-admin-footer">
            <?php
            echo 'YARDLII Core Functions v' . esc_html(YARDLII_CORE_VERSION) . ' — ';
            echo esc_html__('Last updated:', 'yardlii-core') . ' ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format')));
            ?>
        </footer>
        <?php
        echo '</div>';
    }
}