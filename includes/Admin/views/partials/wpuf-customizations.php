<?php
/**
 * Yardlii Admin Settings → General Tab → WPUF Customisations
 * -----------------------------------------------------------
 * Configuration for WPUF frontend styling, listing logic, and profile mapping.
 */
?>

<h2>🔧 WPUF Customisations</h2>
<p class="description">
  Configure front-end listing form behavior, listing status logic, and profile mapping.
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

    <hr style="margin: 30px 0;">

    <div class="yardlii-card">
        <h3 style="margin-top:0;">👤 Dynamic Profile Form Mapping</h3>
        <p class="description">
            Map specific <strong>User Roles</strong> to specific <strong>WPUF Profile Forms</strong>.
            <br>The system checks these rules from top to bottom. The first matching role determines the form.
        </p>

        <?php 
        $map = get_option('yardlii_profile_form_map', []);
        if (empty($map) || !is_array($map)) {
            $map = [['role' => '', 'form_id' => '']];
        }
        $roles = array_reverse(get_editable_roles());
        ?>

        <table class="widefat striped" id="yardlii-profile-repeater" style="margin-top:15px; border:1px solid #ddd;">
            <thead>
                <tr>
                    <th style="width: 40%;">User Role</th>
                    <th style="width: 40%;">Target Form ID</th>
                    <th style="width: 10%;"></th>
                </tr>
            </thead>
            <tbody id="yardlii-profile-rows">
                <?php foreach ($map as $index => $row): ?>
                <tr class="yardlii-profile-row">
                    <td>
                        <select name="yardlii_profile_form_map[<?php echo $index; ?>][role]" style="width:100%">
                            <option value="">-- Select Role --</option>
                            <?php foreach ($roles as $slug => $details): ?>
                                <option value="<?php echo esc_attr($slug); ?>" <?php selected($row['role'] ?? '', $slug); ?>>
                                    <?php echo esc_html($details['name']); ?> (<?php echo esc_html($slug); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="number" name="yardlii_profile_form_map[<?php echo $index; ?>][form_id]" value="<?php echo esc_attr($row['form_id'] ?? ''); ?>" placeholder="e.g. 1620" style="width:100%">
                    </td>
                    <td>
                        <button type="button" class="button yardlii-remove-profile-row">&times;</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 10px; display:flex; gap:10px;">
            <button type="button" id="yardlii-add-profile-row" class="button button-secondary">Add Role Rule</button>
        </div>
    </div>

    <?php submit_button('Save All WPUF Settings'); ?>

</form>

<hr style="margin: 30px 0;">

<h3>📘 Dashboard Shortcode Reference</h3>
<p class="description">Use these shortcodes to build your custom Elementor Dashboard tabs.</p>

<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
    <div class="yardlii-card" style="margin:0;">
        <h4 style="margin-top:0;">1. Dynamic Profile Form</h4>
        <p>Automatically renders the correct "Edit Profile" form (Basic vs Pro) based on the user's role mapping above.</p>
        <code>[yardlii_edit_profile]</code>
    </div>

    <div class="yardlii-card" style="margin:0;">
        <h4 style="margin-top:0;">2. Dynamic Submission</h4>
        <p>Renders the correct "Submit Listing" form. (Basic users get restricted form, Pros get full form).</p>
        <code>[yardlii_submit_listing]</code>
    </div>

    <div class="yardlii-card" style="margin:0;">
        <h4 style="margin-top:0;">3. My Listings Grid</h4>
        <p>Displays the current user's listings as visual cards with Edit/Delete buttons (Replalces <code>[wpuf_dashboard]</code>).</p>
        <code>[yardlii_user_dashboard]</code>
    </div>
</div>
<script>
(function($) {
    const container = document.getElementById('yardlii-profile-rows');
    const addBtn    = document.getElementById('yardlii-add-profile-row');

    if(addBtn && container) {
        addBtn.addEventListener('click', function() {
            const rows = container.querySelectorAll('tr');
            const clone = rows[0].cloneNode(true);
            const newIndex = rows.length;

            clone.querySelectorAll('input').forEach(i => i.value = '');
            clone.querySelector('select').selectedIndex = 0;

            clone.querySelectorAll('input, select').forEach(input => {
                if (input.name) input.name = input.name.replace(/\[\d+\]/, '[' + newIndex + ']');
            });

            container.appendChild(clone);
        });

        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('yardlii-remove-profile-row')) {
                if (container.querySelectorAll('tr').length > 1) e.target.closest('tr').remove();
            }
        });
    }
})(jQuery);
</script>