<?php
/**
 * YARDLII: Advanced -> Feature Flags & Debug
 *
 * This partial is included from SettingsPageTabs.php and
 * inherits the following variables from its scope:
 *
 * @var string $group_debug
 * @var string $group_flags
 * @var bool   $tv_flag_value
 * @var bool   $tv_flag_locked
 */
defined('ABSPATH') || exit;

// --- 1. INITIALIZE VARIABLES (Keep existing logic) ---

// Role Control
$rc_flag_value  = (bool) get_option('yardlii_enable_role_control', false);
$rc_flag_locked = defined('YARDLII_ENABLE_ROLE_CONTROL');
if ($rc_flag_locked) {
    $rc_flag_value = (bool) constant('YARDLII_ENABLE_ROLE_CONTROL');
}

// ACF Sync
$acf_sync_value  = (bool) get_option('yardlii_enable_acf_user_sync', false);
$acf_sync_locked = defined('YARDLII_ENABLE_ACF_USER_SYNC');
if ($acf_sync_locked) {
    $acf_sync_value = (bool) constant('YARDLII_ENABLE_ACF_USER_SYNC');
}

// Geocoding
$geo_val = (bool) get_option('yardlii_enable_wpuf_geocoding', false);
$geo_locked = defined('YARDLII_ENABLE_WPUF_GEOCODING');
if ($geo_locked) {
    $geo_val = (bool) constant('YARDLII_ENABLE_WPUF_GEOCODING');
}

// Business Directory
$bd_val = (bool) get_option('yardlii_enable_business_directory', false);
$bd_locked = defined('YARDLII_ENABLE_BUSINESS_DIRECTORY');
if ($bd_locked) {
    $bd_val = (bool) constant('YARDLII_ENABLE_BUSINESS_DIRECTORY');
}

// Location Engine
$loc_val = (bool) get_option('yardlii_enable_wpuf_city_autocomplete', false);
$loc_locked = defined('YARDLII_ENABLE_WPUF_CITY_AUTOCOMPLETE');
if ($loc_locked) {
    $loc_val = (bool) constant('YARDLII_ENABLE_WPUF_CITY_AUTOCOMPLETE');
}

// Elementor Queries
$el_val = (bool) get_option('yardlii_enable_elementor_query_mods', false);
$el_locked = defined('YARDLII_ENABLE_ELEMENTOR_QUERY_MODS');
if ($el_locked) {
    $el_val = (bool) constant('YARDLII_ENABLE_ELEMENTOR_QUERY_MODS');
}

// Custom Map Widget
$cmw_val = (bool) get_option('yardlii_enable_custom_map_widget', false);
$cmw_locked = defined('YARDLII_ENABLE_CUSTOM_MAP_WIDGET');
if ($cmw_locked) {
    $cmw_val = (bool) constant('YARDLII_ENABLE_CUSTOM_MAP_WIDGET');
}

// Login Persistence
$lp_val = (bool) get_option('yardlii_enable_login_persistence', false);
$lp_locked = defined('YARDLII_ENABLE_LOGIN_PERSISTENCE');
if ($lp_locked) {
    $lp_val = (bool) constant('YARDLII_ENABLE_LOGIN_PERSISTENCE');
}

// Media Cleanup
$mc_val = (bool) get_option('yardlii_enable_media_cleanup', 0);
$mc_locked = defined('YARDLII_ENABLE_MEDIA_CLEANUP');
if ($mc_locked) {
    $mc_val = (bool) constant('YARDLII_ENABLE_MEDIA_CLEANUP');
}
?>

<div class="form-config-content">

  <?php
  if (function_exists('settings_errors')) {
      settings_errors($group_debug);
      settings_errors($group_flags);
  }
  ?>

  <div class="yardlii-card" style="margin-bottom: 2rem;">
    <h2 style="margin-top:0;"><?php esc_html_e('Debug Mode', 'yardlii-core'); ?></h2>
    <form method="post" action="options.php">
      <?php settings_fields($group_debug); ?>
      
      <div style="display:flex; align-items:center;">
          <label class="yardlii-switch">
            <input type="hidden" name="yardlii_debug_mode" value="0" />
            <input
              type="checkbox"
              name="yardlii_debug_mode"
              value="1"
              <?php checked((bool) get_option('yardlii_debug_mode', false)); ?>
              <?php disabled(defined('YARDLII_DEBUG') && YARDLII_DEBUG); ?>
            />
            <span class="slider round"></span>
          </label>
          
          <span style="font-weight:600;"><?php esc_html_e('Enable Debug Mode (logging to debug.log)', 'yardlii-core'); ?></span>
          
          <?php if (defined('YARDLII_DEBUG') && YARDLII_DEBUG) : ?>
            <em style="opacity:.8;margin-left:.5rem;"><?php esc_html_e('Locked by code', 'yardlii-core'); ?></em>
          <?php endif; ?>
      </div>

      <p style="margin-top:1rem;">
        <?php submit_button(__('Save Debug Setting', 'yardlii-core'), 'secondary', 'submit', false); ?>
      </p>
    </form>
  </div>


  <div class="yardlii-card">
    <h2 style="display:flex;align-items:center;gap:.5rem;margin-top:0;">
      <?php esc_html_e('Feature Flags', 'yardlii-core'); ?>
      <span title="<?php echo esc_attr__('Toggles for optional modules. If a flag is locked by code, the UI is disabled.', 'yardlii-core'); ?>">ℹ️</span>
    </h2>

    <form method="post" action="options.php">
      <?php settings_fields($group_flags); ?>

      <table class="form-table yardlii-flag-table" role="presentation" style="margin-top:0;">
        <tbody>
            
            <tr class="yardlii-flag-category">
                <th colspan="2" style="padding: 15px 0 10px; border-bottom: 2px solid #f0f0f1;">
                    <h3 style="margin:0; color:#2271b1;">Role Control & Access</h3>
                </th>
            </tr>
            <tr>
                <th scope="row" style="padding-left:10px;">Role Control</th>
                <td>
                    <label class="yardlii-switch">
                        <input type="hidden" name="yardlii_enable_role_control" value="0" />
                        <input type="checkbox" name="yardlii_enable_role_control" value="1" <?php checked($rc_flag_value); ?> <?php disabled($rc_flag_locked); ?>>
                        <span class="slider round"></span>
                    </label>
                    <?php if ($rc_flag_locked) : ?> <em style="opacity:.8;">Locked</em> <?php endif; ?>
                    <p class="description">Master switch for Role Management features.</p>
                </td>
            </tr>

            <tr class="yardlii-flag-category">
                <th colspan="2" style="padding: 25px 0 10px; border-bottom: 2px solid #f0f0f1;">
                    <h3 style="margin:0; color:#2271b1;">Geocoding & Maps</h3>
                </th>
            </tr>
            <tr>
                <th scope="row" style="padding-left:10px;">WPUF Privacy Geocoding</th>
                <td>
                    <label class="yardlii-switch">
                        <input type="hidden" name="yardlii_enable_wpuf_geocoding" value="0" />
                        <input type="checkbox" name="yardlii_enable_wpuf_geocoding" value="1" <?php checked($geo_val); ?> <?php disabled($geo_locked); ?>>
                        <span class="slider round"></span>
                    </label>
                    <?php if ($geo_locked) : ?> <em style="opacity:.8;">Locked</em> <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th scope="row" style="padding-left:10px;">Universal Location Engine</th>
                <td>
                    <label class="yardlii-switch">
                        <input type="hidden" name="yardlii_enable_wpuf_city_autocomplete" value="0" />
                        <input type="checkbox" name="yardlii_enable_wpuf_city_autocomplete" value="1" <?php checked($loc_val); ?> <?php disabled($loc_locked); ?>>
                        <span class="slider round"></span>
                    </label>
                    <p class="description">Enables Google Places Autocomplete (Cities Only) on forms.</p>
                </td>
            </tr>

            <tr class="yardlii-flag-category">
                <th colspan="2" style="padding: 25px 0 10px; border-bottom: 2px solid #f0f0f1;">
                    <h3 style="margin:0; color:#2271b1;">Elementor Integration</h3>
                </th>
            </tr>
            <tr>
                <th scope="row" style="padding-left:10px;">Elementor Query Utilities</th>
                <td>
                    <label class="yardlii-switch">
                        <input type="hidden" name="yardlii_enable_elementor_query_mods" value="0" />
                        <input type="checkbox" name="yardlii_enable_elementor_query_mods" value="1" <?php checked($el_val); ?> <?php disabled($el_locked); ?>>
                        <span class="slider round"></span>
                    </label>
                    <p class="description">Enables custom query IDs like <code>yardlii_author_listings</code>.</p>
                </td>
            </tr>
            <tr>
                <th scope="row" style="padding-left:10px;">Custom Map Widget</th>
                <td>
                    <label class="yardlii-switch">
                        <input type="hidden" name="yardlii_enable_custom_map_widget" value="0" />
                        <input type="checkbox" name="yardlii_enable_custom_map_widget" value="1" <?php checked($cmw_val); ?> <?php disabled($cmw_locked); ?>>
                        <span class="slider round"></span>
                    </label>
                    <p class="description">Enables <code>Custom Google Map</code> widget in Elementor.</p>
                </td>
            </tr>
            <tr>
                <th scope="row" style="padding-left:10px;">Dynamic Directory Shortcode</th>
                <td>
                    <label class="yardlii-switch">
                        <input type="hidden" name="yardlii_enable_business_directory" value="0" />
                        <input type="checkbox" name="yardlii_enable_business_directory" value="1" <?php checked($bd_val); ?> <?php disabled($bd_locked); ?>>
                        <span class="slider round"></span>
                    </label>
                    <p class="description">Enables <code>[yardlii_directory]</code> shortcode.</p>
                </td>
            </tr>

            <tr class="yardlii-flag-category">
                <th colspan="2" style="padding: 25px 0 10px; border-bottom: 2px solid #f0f0f1;">
                    <h3 style="margin:0; color:#2271b1;">System & PWA</h3>
                </th>
            </tr>
            <tr>
                <th scope="row" style="padding-left:10px;">Login Persistence (PWA)</th>
                <td>
                    <label class="yardlii-switch">
                        <input type="hidden" name="yardlii_enable_login_persistence" value="0" />
                        <input type="checkbox" name="yardlii_enable_login_persistence" value="1" <?php checked($lp_val); ?> <?php disabled($lp_locked); ?>>
                        <span class="slider round"></span>
                    </label>
                    <p class="description">Forces user sessions to last 1 year.</p>
                </td>
            </tr>
            <tr>
                <th scope="row" style="padding-left:10px; color:#d63638;">Media Cleanup</th>
                <td>
                    <label class="yardlii-switch">
                        <input type="hidden" name="yardlii_enable_media_cleanup" value="0" />
                        <input type="checkbox" name="yardlii_enable_media_cleanup" value="1" <?php checked($mc_val); ?> <?php disabled($mc_locked); ?>>
                        <span class="slider round"></span>
                    </label>
                    <p class="description"><strong>Danger:</strong> Deletes attached images when listing is deleted.</p>
                </td>
            </tr>

            <tr>
                <th scope="row" style="padding-left:10px;">Media Lifecycle Management</th>
                <td>
                    <label class="yardlii-switch">
                        <input type="hidden" name="yardlii_enable_media_management" value="0" />
                        <input type="checkbox" name="yardlii_enable_media_management" value="1" 
                            <?php checked((bool) get_option('yardlii_enable_media_management', false)); ?> 
                            <?php disabled(defined('YARDLII_ENABLE_MEDIA_MANAGEMENT')); ?>
                        >
                        <span class="slider round"></span>
                    </label>
                    <p class="description">Handles Size Registration, Privacy Scrubbing (EXIF), and Bloat Prevention. <em>(Replaces PixRefiner)</em>.</p>
                </td>
            </tr>

        </tbody>
      </table>

      <p style="margin-top:1rem;">
        <button class="button button-primary" type="submit">
          <?php esc_html_e('Save Feature Flags', 'yardlii-core'); ?>
        </button>
      </p>
    </form>
  </div>

</div>