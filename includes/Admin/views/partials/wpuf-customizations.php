<?php
/**
 * Yardlii Admin Settings → General Tab → WPUF Customisations
 * -----------------------------------------------------------
 * Configuration for WPUF frontend styling and listing logic.
 *
 * @since 3.10.0
 */
?>

<h2>🔧 WPUF Customisations</h2>
<p class="description">
  Configure front-end listing form behavior and listing status logic.
</p>

<form method="post" action="options.php" class="yardlii-settings-form">
  <?php
    settings_fields('yardlii_general_group');
    
    // Retrieve options
    $dropdown_enabled = (bool) get_option('yardlii_enable_wpuf_dropdown', true);
    $card_enabled     = (bool) get_option('yardlii_wpuf_card_layout', false);
    $uploader_enabled = (bool) get_option('yardlii_wpuf_modern_uploader', false);
    $featured_enabled = (bool) get_option('yardlii_enable_featured_listings', false);
  ?>

  <table class="form-table yardlii-table">
    
    <tr valign="top">
      <th scope="row">
        <h3>🎨 Frontend Styling</h3>
        <p class="description">Controls the look of forms on the site.</p>
      </th>
      <td>
        <div style="background: #f9f9f9; border: 1px solid #e5e5e5; padding: 15px; border-radius: 4px; margin-bottom: 20px;">
            <label for="yardlii_wpuf_target_pages" style="display:block; margin-bottom:5px;">
                <strong><?php esc_html_e('Target Pages (Scope)', 'yardlii-core'); ?></strong>
            </label>
            <input 
                type="text" 
                id="yardlii_wpuf_target_pages" 
                name="yardlii_wpuf_target_pages" 
                value="<?php echo esc_attr(get_option('yardlii_wpuf_target_pages', 'submit-a-post')); ?>" 
                class="regular-text"
                style="width: 100%;"
                placeholder="e.g. submit-a-post, edit-listing, 123"
            />
            <p class="description" style="margin-top: 5px;">
                <?php esc_html_e('Enter Page Slugs or IDs (comma-separated). The visual features below will ONLY load on these pages.', 'yardlii-core'); ?>
            </p>
        </div>

        <div class="yardlii-setting-row" style="margin-bottom: 15px;">
            <label class="yardlii-toggle">
                <input type="checkbox" name="yardlii_wpuf_card_layout" value="1" <?php checked($card_enabled, true); ?> />
                <span class="yardlii-toggle-slider"></span>
            </label>
            <div style="display:inline-block; vertical-align:top; margin-left: 10px;">
                <strong>Card-Style Layout</strong>
                <p class="description" style="margin-top: 2px;">
                    Groups form fields into modern "Cards" based on Section Breaks.
                </p>
            </div>
        </div>

        <div class="yardlii-setting-row" style="margin-bottom: 15px;">
            <label class="yardlii-toggle">
                <input type="checkbox" name="yardlii_wpuf_modern_uploader" value="1" <?php checked($uploader_enabled, true); ?> />
                <span class="yardlii-toggle-slider"></span>
            </label>
            <div style="display:inline-block; vertical-align:top; margin-left: 10px;">
                <strong>Modern Uploader Skin</strong>
                <p class="description" style="margin-top: 2px;">
                    Transforms the standard upload button into a drag-and-drop "Dropzone".
                </p>
            </div>
        </div>

        <div class="yardlii-setting-row">
            <label class="yardlii-toggle">
                <input type="checkbox" name="yardlii_enable_wpuf_dropdown" value="1" <?php checked($dropdown_enabled, true); ?> />
                <span class="yardlii-toggle-slider"></span>
            </label>
            <div style="display:inline-block; vertical-align:top; margin-left: 10px;">
                <strong>Enhanced Taxonomy Dropdown</strong>
                <p class="description" style="margin-top: 2px;">
                    Replaces standard Category select fields with the YARDLII interactive menu.
                </p>
            </div>
        </div>

      </td>
    </tr>

    <tr><td colspan="2"><hr style="border: 0; border-top: 1px solid #ddd;"></td></tr>

    <tr valign="top">
      <th scope="row">
        <h3>🧠 Listing Logic</h3>
        <p class="description">Backend data handling.</p>
      </th>
      <td>
        <div class="yardlii-setting-row">
            <label class="yardlii-toggle">
                <input type="checkbox" name="yardlii_enable_featured_listings" value="1" <?php checked($featured_enabled, true); ?> />
                <span class="yardlii-toggle-slider"></span>
            </label>
            <div style="display:inline-block; vertical-align:top; margin-left: 10px;">
                <strong>Enable Featured Listing Logic</strong>
                <p class="description" style="margin-top: 2px;">
                    Synchronizes "Featured" status between ACF, WPUF, and WordPress Sticky posts.<br>
                    Enables the <code>[yardlii_featured_badge]</code> shortcode and Admin Filters.
                </p>



            </div>
        </div>
      </td>
    </tr>
<tr><td colspan="2"><hr style="border: 0; border-top: 1px solid #ddd;"></td></tr>

    <tr valign="top">
      <th scope="row">
        <h3>🗺️ Geocoding Configuration</h3>
        <p class="description">
          Maps WPUF forms to the postal code field key for Lat/Lng conversion.
        </p>
      </th>
      <td>
        <?php 
          // CRITICAL: Use the fully qualified class name for option retrieval (requires WpufGeocoding.php class to be loaded)
          $geocoding_class = '\\Yardlii\\Core\\Features\\WpufGeocoding';
          
          if (class_exists($geocoding_class)) {
            $config_option_key = $geocoding_class::OPTION_CONFIG;
            $geocoding_config = get_option($config_option_key, []); 
            
            // Fallback to ensure at least one row is available for input
            if (empty($geocoding_config)) {
              $geocoding_config[] = ['form_id' => '', 'postal_code_key' => ''];
            }
          } else {
             // Safe fallback if the feature class is not loaded 
            $config_option_key = 'yardlii_wpuf_geocoding_forms'; // Hardcode fallback key
            $geocoding_config = [['form_id' => '', 'postal_code_key' => '']];
          }
        ?>

        <table class="widefat fixed" cellspacing="0" style="width: 100%; max-width: 600px; margin-bottom: 15px;">
          <thead>
            <tr>
              <th class="manage-column" style="width: 30%;">WPUF Form ID</th>
              <th class="manage-column">Postal Code Field Name</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            // Loop through existing configurations
            foreach ($geocoding_config as $index => $row) : 
            ?>
              <tr class="<?php echo ($index % 2 == 0) ? 'alternate' : ''; ?>">
                <td>
                  <input 
                    type="number" 
                    name="<?php echo esc_attr("{$config_option_key}[{$index}][form_id]"); ?>" 
                    value="<?php echo esc_attr($row['form_id']); ?>" 
                    placeholder="e.g., 345" 
                    style="width: 90px;"
                    min="1"
                  />
                </td>
                <td>
                  <input 
                    type="text" 
                    name="<?php echo esc_attr("{$config_option_key}[{$index}][postal_code_key]"); ?>" 
                    value="<?php echo esc_attr($row['postal_code_key']); ?>" 
                    placeholder="e.g., listing_postal_code" 
                    class="regular-text"
                  />
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        <p class="description">
          Add one row for each WPUF form that requires geocoding. The **Postal Code Field Name** must match the WPUF key (e.g., <code>listing_postal_code</code> or <code>wpuf_field_5</code>).
        </p>
        <p class="description">
          The resulting Lat/Lng data is saved to the following post meta keys: 
          <code>yardlii_listing_latitude</code>, <code>yardlii_listing_longitude</code>, 
          <code>yardlii_display_city_province</code>.
        </p>
      </td>
    </tr>
  </table>

  <?php submit_button('Save WPUF Settings'); ?>
</form>