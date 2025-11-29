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
        <div class="yardlii-setting-row" style="margin-bottom: 20px;">
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

       <div style="background: #f9f9f9; border: 1px solid #e5e5e5; padding: 15px; border-radius: 4px;">
            <strong style="display:block; margin-bottom: 10px; border-bottom: 1px solid #eee; padding-bottom: 5px;">
                🔄 Smart Posting Logic
            </strong>
            <p class="description" style="margin-bottom: 15px;">
                Configure the forms used for the "Basic to Pro" upgrade workflow.
            </p>

            <div style="display: flex; gap: 20px;">
                <div style="flex: 1;">
                    <label for="yardlii_posting_logic_basic_form"><strong>Basic Member Form ID</strong></label><br>
                    <input 
                        type="number" 
                        id="yardlii_posting_logic_basic_form" 
                        name="yardlii_posting_logic_basic_form" 
                        value="<?php echo esc_attr(get_option('yardlii_posting_logic_basic_form', '')); ?>" 
                        class="small-text"
                    />
                    <p class="description">
                        Used by Basic & Pending Users.<br>
                        <em>(Pending users get "Smart Overrides" applied here).</em>
                    </p>
                </div>

                <div style="flex: 1;">
                    <label for="yardlii_posting_logic_pro_form"><strong>Verified/Pro Form ID</strong></label><br>
                    <input 
                        type="number" 
                        id="yardlii_posting_logic_pro_form" 
                        name="yardlii_posting_logic_pro_form" 
                        value="<?php echo esc_attr(get_option('yardlii_posting_logic_pro_form', '')); ?>" 
                        class="small-text"
                    />
                    <p class="description">
                        Used by Verified Contractors & Businesses.<br>
                        <em>(Auto-swaps on Edit).</em>
                    </p>
                </div>
            </div>
        </div>
      </td>
    </tr>

<tr><td colspan="2"><hr style="border: 0; border-top: 1px solid #ddd;"></td></tr>

    <tr valign="top">
      <th scope="row">
        <h3>📍 Privacy Geocoding</h3>
        <p class="description">Convert Postal Codes to City/Province + Lat/Lng.</p>
      </th>
      <td>
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
      </td>
    </tr>




  </table>

  <?php submit_button('Save WPUF Settings'); ?>

<hr style="margin: 30px 0;">

<div class="yardlii-card">
    <h3 style="margin-top:0;">👤 Dynamic Profile Form Mapping</h3>
    <p class="description">
        By default, WPUF uses one global form for "Edit Profile". 
        Use these settings to override that ID based on the user's role.
    </p>

    <table class="form-table" role="presentation">
        <tr>
            <th scope="row"><label for="yardlii_profile_form_admin">Administrator Form ID</label></th>
            <td>
                <input name="yardlii_profile_form_admin" type="number" id="yardlii_profile_form_admin" value="<?php echo esc_attr(get_option('yardlii_profile_form_admin')); ?>" class="regular-text">
                <p class="description">Usually the "Master" form with all fields.</p>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="yardlii_profile_form_business">Verified Business Form ID</label></th>
            <td>
                <input name="yardlii_profile_form_business" type="number" id="yardlii_profile_form_business" value="<?php echo esc_attr(get_option('yardlii_profile_form_business')); ?>" class="regular-text">
                <p class="description">Target Role: <code>verified_business</code></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="yardlii_profile_form_contractor">Contractor / Pending Form ID</label></th>
            <td>
                <input name="yardlii_profile_form_contractor" type="number" id="yardlii_profile_form_contractor" value="<?php echo esc_attr(get_option('yardlii_profile_form_contractor')); ?>" class="regular-text">
                <p class="description">Target Roles: <code>verified_contractor</code>, <code>pending_verification</code>, <code>verified_pro_employee</code></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="yardlii_profile_form_supplier">Supplier Form ID</label></th>
            <td>
                <input name="yardlii_profile_form_supplier" type="number" id="yardlii_profile_form_supplier" value="<?php echo esc_attr(get_option('yardlii_profile_form_supplier')); ?>" class="regular-text">
                <p class="description">Target Role: <code>verified_supplier</code></p>
            </td>
        </tr>

        <tr>
            <th scope="row"><label for="yardlii_profile_form_basic">Basic Member Form ID</label></th>
            <td>
                <input name="yardlii_profile_form_basic" type="number" id="yardlii_profile_form_basic" value="<?php echo esc_attr(get_option('yardlii_profile_form_basic')); ?>" class="regular-text">
                <p class="description">Target Roles: <code>subscriber</code> (and anyone else not matched above).</p>
            </td>
        </tr>
    </table>
    
    <?php submit_button('Save Mapping Settings'); ?>
</div>
</form>