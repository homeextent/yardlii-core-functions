<?php
/**
 * Partial: General -> User Directory Configuration (Role-Based Repeater)
 */
defined('ABSPATH') || exit;

// Fetch saved config
$configs = get_option('yardlii_directory_role_config', []);
if (!is_array($configs)) $configs = [];

// Fetch all available roles for the dropdown
$editable_roles = array_reverse(get_editable_roles()); // Reverse to put custom roles usually at top
?>

<div class="yardlii-card">
    <h2 style="margin-top:0;">📂 Role-Based Directory Mapping</h2>
    <p class="description">
        Configure how the "Business Card" looks for each specific user role.
        <br>The shortcode <code>[yardlii_directory role="contractor"]</code> will look for the "contractor" row below.
    </p>

    <form method="post" action="options.php">
        <?php settings_fields('yardlii_directory_group'); ?>
        
        <table class="widefat striped" id="yardlii-dir-repeater" style="margin-top:15px; border:1px solid #ddd;">
            <thead>
                <tr>
                    <th style="width: 20%;">User Role</th>
                    <th style="width: 20%;">Image Source <span class="dashicons dashicons-info" title="ACF Field Name. Leave empty for Avatar."></span></th>
                    <th style="width: 20%;">Title Source <span class="dashicons dashicons-info" title="ACF Field Name. Leave empty for Display Name."></span></th>
                    <th style="width: 20%;">Badge Source <span class="dashicons dashicons-info" title="ACF Field Name. Leave empty for Role Name."></span></th>
                    <th style="width: 15%;">Location Source <span class="dashicons dashicons-info" title="ACF or Meta Key (e.g. billing_city)."></span></th>
                    <th style="width: 5%;"></th>
                </tr>
            </thead>
            <tbody id="yardlii-dir-rows">
                <?php 
                // Ensure at least one empty row if none exist
                if (empty($configs)) {
                    $configs = [['role' => '', 'image' => '', 'title' => '', 'badge' => '', 'location' => '']];
                }
                
                foreach ($configs as $index => $row): 
                ?>
                <tr class="yardlii-dir-row">
                    <td>
                        <select name="yardlii_directory_role_config[<?php echo $index; ?>][role]" style="width:100%">
                            <option value="">-- Select Role --</option>
                            <?php foreach ($editable_roles as $slug => $details): ?>
                                <option value="<?php echo esc_attr($slug); ?>" <?php selected($row['role'] ?? '', $slug); ?>>
                                    <?php echo esc_html($details['name']); ?> (<?php echo esc_html($slug); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][image]" value="<?php echo esc_attr($row['image'] ?? ''); ?>" placeholder="e.g. business_logo" style="width:100%">
                    </td>
                    <td>
                        <input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][title]" value="<?php echo esc_attr($row['title'] ?? ''); ?>" placeholder="e.g. company_name" style="width:100%">
                    </td>
                    <td>
                        <input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][badge]" value="<?php echo esc_attr($row['badge'] ?? ''); ?>" placeholder="e.g. primary_trade" style="width:100%">
                    </td>
                    <td>
                        <input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][location]" value="<?php echo esc_attr($row['location'] ?? ''); ?>" placeholder="e.g. billing_city" style="width:100%">
                    </td>
                    <td>
                        <button type="button" class="button yardlii-remove-row" aria-label="Remove Row">&times;</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 10px; display:flex; gap:10px;">
            <button type="button" id="yardlii-add-row" class="button button-secondary">Add Configuration</button>
            <?php submit_button('Save Mappings', 'primary', 'submit', false); ?>
        </div>
    </form>
</div>

<script>
(function($) {
    // Simple Repeater Logic
    const container = document.getElementById('yardlii-dir-rows');
    const addBtn    = document.getElementById('yardlii-add-row');

    addBtn.addEventListener('click', function() {
        const rows = container.querySelectorAll('tr');
        const clone = rows[0].cloneNode(true);
        const newIndex = rows.length;

        // Reset values and update names
        const inputs = clone.querySelectorAll('input, select');
        inputs.forEach(input => {
            input.value = '';
            // Update index in name="yardlii_directory_role_config[X][field]"
            if (input.name) {
                input.name = input.name.replace(/\[\d+\]/, '[' + newIndex + ']');
            }
        });

        container.appendChild(clone);
    });

    // Delegation for Remove
    container.addEventListener('click', function(e) {
        if (e.target.classList.contains('yardlii-remove-row')) {
            if (container.querySelectorAll('tr').length > 1) {
                e.target.closest('tr').remove();
            } else {
                // Clear inputs if it's the last row
                e.target.closest('tr').querySelectorAll('input, select').forEach(i => i.value = '');
            }
        }
    });
})(jQuery);
</script>