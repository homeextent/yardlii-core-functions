<?php
/**
 * Partial: General -> User Directory Configuration (Card Layout)
 */
defined('ABSPATH') || exit;

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
            <p>
                <label for="yardlii_dir_trade_field"><strong>Trade Dropdown Source:</strong></label>
                <input name="yardlii_dir_trade_field" type="text" id="yardlii_dir_trade_field" value="<?php echo esc_attr($trade_field); ?>" class="regular-text code">
                <span class="description">ACF Field Name (e.g., <code>primary_trade</code>).</span>
            </p>
    </div>

    <h3 style="margin-top:0;">Role-Based Card Mapping</h3>
    
    <div id="yardlii-dir-repeater">
        <?php 
        if (empty($configs)) {
            $configs = [['role' => '', 'image' => '', 'title' => '', 'badge' => '', 'location' => '']];
        }
        foreach ($configs as $index => $row): 
        ?>
        <div class="yardlii-dir-repeater-container">
            <button type="button" class="yardlii-remove-row-btn" title="Remove Configuration">&times;</button>
            
            <div class="yardlii-row-header">
                <select name="yardlii_directory_role_config[<?php echo $index; ?>][role]" class="yardlii-role-select">
                    <option value="">-- Select Target Role --</option>
                    <?php foreach ($editable_roles as $slug => $details): ?>
                        <option value="<?php echo esc_attr($slug); ?>" <?php selected($row['role'] ?? '', $slug); ?>>
                            <?php echo esc_html($details['name']); ?> (<?php echo esc_html($slug); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="yardlii-row-grid">
                <div class="yardlii-field-group">
                    <label>Image Source <span title="ACF Field Name for image. Leave empty for User Avatar." class="dashicons dashicons-info"></span></label>
                    <input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][image]" value="<?php echo esc_attr($row['image'] ?? ''); ?>" class="yardlii-tech-input" placeholder="e.g. business_logo">
                </div>
                <div class="yardlii-field-group">
                    <label>Title Source <span title="ACF Field for card title." class="dashicons dashicons-info"></span></label>
                    <input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][title]" value="<?php echo esc_attr($row['title'] ?? ''); ?>" class="yardlii-tech-input" placeholder="e.g. company_name">
                </div>
                <div class="yardlii-field-group">
                    <label>Badge Source <span title="ACF Field for badge text." class="dashicons dashicons-info"></span></label>
                    <input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][badge]" value="<?php echo esc_attr($row['badge'] ?? ''); ?>" class="yardlii-tech-input" placeholder="e.g. primary_trade">
                </div>
                <div class="yardlii-field-group">
                    <label>Location Source <span title="Meta key for city." class="dashicons dashicons-info"></span></label>
                    <input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][location]" value="<?php echo esc_attr($row['location'] ?? ''); ?>" class="yardlii-tech-input" placeholder="e.g. billing_city">
                </div>
            </div>

            <div class="yardlii-shortcode-box">
                <span class="dashicons dashicons-shortcode"></span>
                <input type="text" readonly class="yardlii-shortcode-preview" value="[yardlii_directory role='...' limit='100']">
                <button type="button" class="button button-small yardlii-copy-btn">Copy</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <div style="margin-top: 20px; display:flex; gap:10px; margin-bottom: 30px;">
        <button type="button" id="yardlii-add-row" class="button button-secondary">Add New Role Config</button>
        <?php submit_button('Save Directory Settings', 'primary', 'submit', false); ?>
    </div>
    </form>
</div>

<script>
(function($) {
    const container = document.getElementById('yardlii-dir-repeater');
    const addBtn    = document.getElementById('yardlii-add-row');

    // 1. Update Shortcode Preview Logic
    function updateShortcodes() {
        const rows = container.querySelectorAll('.yardlii-dir-repeater-container');
        rows.forEach(row => {
            const select = row.querySelector('.yardlii-role-select');
            const preview = row.querySelector('.yardlii-shortcode-preview');
            const role = select.value || 'ROLE_SLUG';
            preview.value = `[yardlii_directory role="${role}" limit="100"]`;
        });
    }

    // 2. Add Row
    if(addBtn) {
        addBtn.addEventListener('click', function() {
            const rows = container.querySelectorAll('.yardlii-dir-repeater-container');
            const clone = rows[0].cloneNode(true);
            const newIndex = rows.length;
            
            // Clear inputs
            clone.querySelectorAll('input').forEach(i => i.value = '');
            clone.querySelector('select').selectedIndex = 0;

            // Update Names
            clone.querySelectorAll('input, select').forEach(input => {
                if (input.name) input.name = input.name.replace(/\[\d+\]/, '[' + newIndex + ']');
            });

            container.appendChild(clone);
            updateShortcodes();
        });
    }

    // 3. Event Delegation
    container.addEventListener('click', function(e) {
        // Remove
        if (e.target.classList.contains('yardlii-remove-row-btn')) {
            if (container.querySelectorAll('.yardlii-dir-repeater-container').length > 1) {
                e.target.closest('.yardlii-dir-repeater-container').remove();
            }
        }
        // Copy
        if (e.target.classList.contains('yardlii-copy-btn')) {
            const input = e.target.previousElementSibling;
            input.select();
            document.execCommand('copy');
            const originalText = e.target.innerText;
            e.target.innerText = 'Copied!';
            setTimeout(() => e.target.innerText = originalText, 1500);
        }
    });

    // 4. Listen for Role Changes
    container.addEventListener('change', function(e) {
        if(e.target.classList.contains('yardlii-role-select')) {
            updateShortcodes();
        }
    });

    // Init
    updateShortcodes();

})(jQuery);
</script>