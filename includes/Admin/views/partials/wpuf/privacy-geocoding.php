<?php defined('ABSPATH') || exit; ?>
<div class="form-config-block">
    <h2>📍 Privacy Geocoding</h2>
    <p class="description">Convert Postal Codes to City/Province + Lat/Lng for privacy-safe location search.</p>

    <form method="post" action="options.php" class="yardlii-settings-form">
        <?php settings_fields('yardlii_general_group'); ?>
        
        <?php 
            $geo_enabled = (bool) get_option('yardlii_enable_wpuf_geocoding', false);
            $geo_mapping = get_option('yardlii_wpuf_geo_mapping', '');
        ?>
        
        <div class="yardlii-setting-row" style="margin-bottom: 15px;">
            <label class="yardlii-toggle">
                <input type="checkbox" name="yardlii_enable_wpuf_geocoding" value="1" <?php checked($geo_enabled, true); ?> />
                <span class="yardlii-toggle-slider"></span>
            </label>
            <div style="display:inline-block; vertical-align:top; margin-left: 10px;">
                <strong>Enable Geocoding Engine</strong>
            </div>
        </div>

        <div style="background: #f9f9f9; border: 1px solid #e5e5e5; padding: 15px; border-radius: 4px;">
            <label for="yardlii_wpuf_geo_mapping" style="display:block; margin-bottom:5px;">
                <strong><?php esc_html_e('Form ID Mapping', 'yardlii-core'); ?></strong>
            </label>
            <textarea 
                id="yardlii_wpuf_geo_mapping" 
                name="yardlii_wpuf_geo_mapping" 
                rows="5" 
                class="large-text code"
                placeholder="FormID : MetaKey&#10;125 : yardlii_listing_postal_code&#10;128 : yardlii_zip_code"
            ><?php echo esc_textarea($geo_mapping); ?></textarea>
            <p class="description">
                <?php esc_html_e('Enter one mapping per line in the format: FormID : InputMetaKey', 'yardlii-core'); ?>
            </p>
        </div>

        <?php submit_button('Save Geocoding Settings'); ?>
    </form>
</div>