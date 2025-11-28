<?php
/**
 * Partial: General -> User Directory Configuration
 */
defined('ABSPATH') || exit;

// Fetch options
$configs = get_option('yardlii_directory_role_config', []);
if (!is_array($configs)) $configs = [];

$trade_field = get_option('yardlii_dir_trade_field', 'primary_trade'); 
$editable_roles = array_reverse(get_editable_roles());
?>

<div class="yardlii-card">
    <h2 style="margin-top:0;">📂 Directory Configuration</h2>
    
    <div style="background:#f9f9f9; padding:15px; border:1px solid #ddd; border-radius:4px; margin-bottom:20px;">
        <h3 style="margin-top:0;">Global Search Filters</h3>
        <form method="post" action="options.php">
            <?php settings_fields('yardlii_directory_group'); ?>
            
            <table class="form-table" role="presentation" style="margin-top:0;">
                <tr>
                    <th scope="row"><label for="yardlii_dir_trade_field">Trade Dropdown Source</label></th>
                    <td>
                        <input name="yardlii_dir_trade_field" type="text" id="yardlii_dir_trade_field" value="<?php echo esc_attr($trade_field); ?>" class="regular-text">
                        <p class="description">
                            Enter the <strong>ACF Field Name</strong> (e.g., <code>primary_trade</code>) used for the Trade selection.<br>
                            The directory will automatically pull the choices (e.g., "Plumber", "Electrician") from this field definition.
                        </p>
                    </td>
                </tr>
            </table>
    </div>

    <h3 style="margin-top:0;">Role-Based Card Mapping</h3>
    <p class="description">
        Configure how the "Business Card" looks for each specific user role.
    </p>

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
                    <td><input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][image]" value="<?php echo esc_attr($row['image'] ?? ''); ?>" style="width:100%"></td>
                    <td><input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][title]" value="<?php echo esc_attr($row['title'] ?? ''); ?>" style="width:100%"></td>
                    <td><input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][badge]" value="<?php echo esc_attr($row['badge'] ?? ''); ?>" style="width:100%"></td>
                    <td><input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][location]" value="<?php echo esc_attr($row['location'] ?? ''); ?>" style="width:100%"></td>
                    <td><button type="button" class="button yardlii-remove-row">&times;</button></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div style="margin-top: 10px; display:flex; gap:10px; margin-bottom: 30px;">
            <button type="button" id="yardlii-add-row" class="button button-secondary">Add Configuration</button>
            <?php submit_button('Save Directory Settings', 'primary', 'submit', false); ?>
        </div>
    </form>

    <hr style="margin: 30px 0;">

    <h3>📘 Shortcode Reference</h3>
    
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div class="card" style="margin:0; max-width:none;">
            <h4 style="margin-top:0;">1. Standard Directory</h4>
            <p>Displays the grid and search bar together.</p>
            <code>[yardlii_directory role="verified_business"]</code>
            <p><strong>Parameters:</strong></p>
            <ul style="list-style:disc; margin-left:20px; font-size:12px;">
                <li><code>role</code>: The user role slug (e.g., <code>verified_contractor</code>).</li>
                <li><code>limit</code>: Max users to show (default: <code>100</code>).</li>
                <li><code>card_width</code>: Minimum width of cards in pixels (default: <code>280</code>). Increase this to make cards wider/fewer columns.</li>
            </ul>
        </div>

        <div class="card" style="margin:0; max-width:none;">
            <h4 style="margin-top:0;">2. Decoupled Search</h4>
            <p>Place the search bar in one area (e.g., Hero) and the grid in another.</p>
            <p><strong>Step A (The Search Bar):</strong></p>
            <code>[yardlii_directory_search target="my-grid-1"]</code>
            
            <p><strong>Step B (The Grid):</strong></p>
            <code>[yardlii_directory id="my-grid-1" hide_search="true"]</code>
            
            <p><em>Note: The <code>target</code> in Step A must match the <code>id</code> in Step B.</em></p>
        </div>
    </div>

</div>

<script>
(function($) {
    const container = document.getElementById('yardlii-dir-rows');
    const addBtn    = document.getElementById('yardlii-add-row');

    if(addBtn && container) {
        addBtn.addEventListener('click', function() {
            const rows = container.querySelectorAll('tr');
            const clone = rows[0].cloneNode(true);
            const newIndex = rows.length;
            const inputs = clone.querySelectorAll('input, select');
            inputs.forEach(input => {
                input.value = '';
                if (input.name) input.name = input.name.replace(/\[\d+\]/, '[' + newIndex + ']');
            });
            container.appendChild(clone);
        });

        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('yardlii-remove-row')) {
                if (container.querySelectorAll('tr').length > 1) e.target.closest('tr').remove();
                else e.target.closest('tr').querySelectorAll('input, select').forEach(i => i.value = '');
            }
        });
    }
})(jQuery);
</script>