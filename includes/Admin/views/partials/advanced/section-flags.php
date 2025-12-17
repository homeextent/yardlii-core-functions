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

$rc_flag_value  = (bool) get_option('yardlii_enable_role_control', false);
$rc_flag_locked = defined('YARDLII_ENABLE_ROLE_CONTROL');
if ($rc_flag_locked) {
    $rc_flag_value = (bool) constant('YARDLII_ENABLE_ROLE_CONTROL');
}

$acf_sync_value  = (bool) get_option('yardlii_enable_acf_user_sync', false);
$acf_sync_locked = defined('YARDLII_ENABLE_ACF_USER_SYNC');
if ($acf_sync_locked) {
    $acf_sync_value = (bool) constant('YARDLII_ENABLE_ACF_USER_SYNC');
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
      <label>
        <input type="hidden" name="yardlii_debug_mode" value="0" />
        <input
          type="checkbox"
          name="yardlii_debug_mode"
          value="1"
          <?php checked((bool) get_option('yardlii_debug_mode', false)); ?>
          <?php disabled(defined('YARDLII_DEBUG') && YARDLII_DEBUG); ?>
        />
        <?php esc_html_e('Enable Debug Mode (logging to debug.log)', 'yardlii-core'); ?>
      </label>
      <?php if (defined('YARDLII_DEBUG') && YARDLII_DEBUG) : ?>
        <em style="opacity:.8;margin-left:.5rem;"><?php esc_html_e('Locked by code', 'yardlii-core'); ?></em>
      <?php endif; ?>
      <?php submit_button(__('Save Debug Setting', 'yardlii-core')); ?>
    </form>
  </div>


  <div class="yardlii-card">
    <h2 style="display:flex;align-items:center;gap:.5rem;margin-top:0;">
      <?php esc_html_e('Feature Flags', 'yardlii-core'); ?>
      <span title="<?php echo esc_attr__('Toggles for optional modules. If a flag is locked by code, the UI is disabled.', 'yardlii-core'); ?>">ℹ️</span>
    </h2>

    <form method="post" action="options.php">
      <?php settings_fields($group_flags); ?>

      
	<div class="yardlii-checkbox-row">
    <label for="yardlii_enable_media_cleanup">
        <input name="yardlii_enable_media_cleanup" type="checkbox" id="yardlii_enable_media_cleanup" value="1"
            <?php checked(1, get_option('yardlii_enable_media_cleanup', 0)); ?>
            <?php disabled(defined('YARDLII_ENABLE_MEDIA_CLEANUP')); ?> 
        />
        <span class="yardlii-toggle-label">
            <strong><?php esc_html_e('Enable Automated Media Cleanup', 'yardlii-core'); ?></strong>
            <p class="description">
                <?php esc_html_e('Danger Zone: Automatically permanently deletes attached images when a listing is deleted. Compatible with PixRefiner.', 'yardlii-core'); ?>
            </p>
        </span>
    </label>
</div>

      <div style="display:flex;align-items:center;gap:.5rem;margin:.5rem 0;">
        <input type="hidden" name="yardlii_enable_role_control" value="0" />
        <input
          type="checkbox"
          id="yardlii_enable_role_control"
          name="yardlii_enable_role_control"
          value="1"
          <?php checked($rc_flag_value); ?>
          <?php disabled($rc_flag_locked); ?>
        />
        <strong><?php esc_html_e('Role Control', 'yardlii-core'); ?></strong>
        <?php if ($rc_flag_locked) : ?>
          <em style="opacity:.8;margin-left:.5rem;"><?php esc_html_e('Locked by code', 'yardlii-core'); ?></em>
        <?php endif; ?>
      </div>

<div style="display:flex;align-items:center;gap:.5rem;margin:.5rem 0;">
    <?php
        // Define variables for this scope
        $geo_val = (bool) get_option('yardlii_enable_wpuf_geocoding', false);
        $geo_locked = defined('YARDLII_ENABLE_WPUF_GEOCODING');
        
        if ($geo_locked) {
             // FIX: Use constant() string access
             $geo_val = (bool) constant('YARDLII_ENABLE_WPUF_GEOCODING');
        }
    ?>
    <input type="hidden" name="yardlii_enable_wpuf_geocoding" value="0" />
    <input
        type="checkbox"
        id="yardlii_enable_wpuf_geocoding"
        name="yardlii_enable_wpuf_geocoding"
        value="1"
        <?php checked($geo_val); ?>
        <?php disabled($geo_locked); ?>
    />
    <strong><?php esc_html_e('WPUF Privacy Geocoding', 'yardlii-core'); ?></strong>
    <?php if ($geo_locked) : ?>
        <em style="opacity:.8;margin-left:.5rem;"><?php esc_html_e('Locked by code', 'yardlii-core'); ?></em>
    <?php endif; ?>
</div>

<div style="display:flex;align-items:center;gap:.5rem;margin:.5rem 0;">
    <?php
    $bd_val = (bool) get_option('yardlii_enable_business_directory', false);
    $bd_locked = defined('YARDLII_ENABLE_BUSINESS_DIRECTORY');
    if ($bd_locked) {
        $bd_val = (bool) constant('YARDLII_ENABLE_BUSINESS_DIRECTORY');
    }
    ?>
    <input type="hidden" name="yardlii_enable_business_directory" value="0" />
    <input
        type="checkbox"
        id="yardlii_enable_business_directory"
        name="yardlii_enable_business_directory"
        value="1"
        <?php checked($bd_val); ?>
        <?php disabled($bd_locked); ?>
    />
    <strong><?php esc_html_e('Dynamic Directory Shortcode', 'yardlii-core'); ?></strong>
    <?php if ($bd_locked) : ?>
        <em style="opacity:.8;margin-left:.5rem;"><?php esc_html_e('Locked by code', 'yardlii-core'); ?></em>
    <?php endif; ?>
</div>
<p class="description" style="margin-left: 24px; margin-top: 0; color: #666;">
    Enables the <code>[yardlii_directory]</code> shortcode for listing users by role (e.g., businesses, contractors).
</p>

<div style="display:flex;align-items:center;gap:.5rem;margin:.5rem 0;">
    <?php
    $loc_val = (bool) get_option('yardlii_enable_wpuf_city_autocomplete', false);
    $loc_locked = defined('YARDLII_ENABLE_WPUF_CITY_AUTOCOMPLETE');
    if ($loc_locked) {
        $loc_val = (bool) constant('YARDLII_ENABLE_WPUF_CITY_AUTOCOMPLETE');
    }
    ?>
    <input type="hidden" name="yardlii_enable_wpuf_city_autocomplete" value="0" />
    <input
        type="checkbox"
        id="yardlii_enable_wpuf_city_autocomplete"
        name="yardlii_enable_wpuf_city_autocomplete"
        value="1"
        <?php checked($loc_val); ?>
        <?php disabled($loc_locked); ?>
    />
    <strong><?php esc_html_e('Universal Location Engine', 'yardlii-core'); ?></strong>
    <?php if ($loc_locked) : ?>
        <em style="opacity:.8;margin-left:.5rem;"><?php esc_html_e('Locked by code', 'yardlii-core'); ?></em>
    <?php endif; ?>
</div>
<p class="description" style="margin-left: 24px; margin-top: 0; color: #666;">
    Enables Google Places Autocomplete (Cities Only) on all forms with the <code>yardlii-city-autocomplete</code> class.
</p>

<div style="display:flex;align-items:center;gap:.5rem;margin:.5rem 0;">
    <?php
    $el_val = (bool) get_option('yardlii_enable_elementor_query_mods', false);
    $el_locked = defined('YARDLII_ENABLE_ELEMENTOR_QUERY_MODS');
    if ($el_locked) {
        $el_val = (bool) constant('YARDLII_ENABLE_ELEMENTOR_QUERY_MODS');
    }
    ?>
    <input type="hidden" name="yardlii_enable_elementor_query_mods" value="0" />
    <input
        type="checkbox"
        id="yardlii_enable_elementor_query_mods"
        name="yardlii_enable_elementor_query_mods"
        value="1"
        <?php checked($el_val); ?>
        <?php disabled($el_locked); ?>
    />
    <strong><?php esc_html_e('Elementor Query Utilities', 'yardlii-core'); ?></strong>
    <?php if ($el_locked) : ?>
        <em style="opacity:.8;margin-left:.5rem;"><?php esc_html_e('Locked by code', 'yardlii-core'); ?></em>
    <?php endif; ?>
</div>
<p class="description" style="margin-left: 24px; margin-top: 0; color: #666;">
    Enables custom query IDs like <code>yardlii_author_listings</code> for Elementor Loop Grids.
</p>
<div style="display:flex;align-items:center;gap:.5rem;margin:.5rem 0;">
    <?php
    $cmw_val = (bool) get_option('yardlii_enable_custom_map_widget', false);
    $cmw_locked = defined('YARDLII_ENABLE_CUSTOM_MAP_WIDGET');
    if ($cmw_locked) {
        $cmw_val = (bool) constant('YARDLII_ENABLE_CUSTOM_MAP_WIDGET');
    }
    ?>
    <input type="hidden" name="yardlii_enable_custom_map_widget" value="0" />
    <input
        type="checkbox"
        id="yardlii_enable_custom_map_widget"
        name="yardlii_enable_custom_map_widget"
        value="1"
        <?php checked($cmw_val); ?>
        <?php disabled($cmw_locked); ?>
    />
    <strong><?php esc_html_e('Elementor Custom Map Widget', 'yardlii-core'); ?></strong>
    <?php if ($cmw_locked) : ?>
        <em style="opacity:.8;margin-left:.5rem;"><?php esc_html_e('Locked by code', 'yardlii-core'); ?></em>
    <?php endif; ?>
</div>
<p class="description" style="margin-left: 24px; margin-top: 0; color: #666;">
    Enables the <code>Custom Google Map</code> widget in Elementor. Requires Google Maps API Key to be configured in General Settings.
</p>

      
      <p style="margin-top:1rem;">
        <button class="button button-primary" type="submit">
          <?php esc_html_e('Save Feature Flags', 'yardlii-core'); ?>
        </button>
      </p>
    </form>
  </div>

</div>