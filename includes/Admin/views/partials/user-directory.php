<?php
/**
 * Partial: General -> User Directory Configuration (Smart Builder UI - Fixed Persistence)
 */
defined('ABSPATH') || exit;

$configs = get_option('yardlii_directory_role_config', []);
if (!is_array($configs)) $configs = [];

// Global Settings
$trade_field = get_option('yardlii_dir_trade_field', 'primary_trade'); 
$def_trigger = get_option('yardlii_dir_default_trigger', 'instant');
$def_width   = get_option('yardlii_dir_default_width', '280');

$editable_roles = array_reverse(get_editable_roles());
?>

<div class="yardlii-card">
    <h2 style="margin-top:0;">📂 Directory Configuration</h2>
    
    <div style="background:#f9f9f9; padding:15px; border:1px solid #ddd; border-radius:4px; margin-bottom:20px;">
        <h3 style="margin-top:0;">Global Defaults</h3>
        <form method="post" action="options.php">
            <?php settings_fields('yardlii_directory_group'); ?>
            
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:20px;">
                <div>
                    <label for="yardlii_dir_trade_field"><strong>Trade Dropdown Source:</strong></label>
                    <input name="yardlii_dir_trade_field" type="text" id="yardlii_dir_trade_field" value="<?php echo esc_attr($trade_field); ?>" class="regular-text code" style="width:100%">
                    <p class="description">ACF Field Name (e.g. <code>primary_trade</code>).</p>
                </div>
                <div>
                    <label for="yardlii_dir_default_trigger"><strong>Default Search Behavior:</strong></label>
                    <select name="yardlii_dir_default_trigger" id="yardlii_dir_default_trigger" style="width:100%">
                        <option value="instant" <?php selected($def_trigger, 'instant'); ?>>Instant (Type to Filter)</option>
                        <option value="button" <?php selected($def_trigger, 'button'); ?>>Button (Click 'Search')</option>
                    </select>
                    <p class="description">Can be overridden per shortcode.</p>
                </div>
                <div>
                    <label for="yardlii_dir_default_width"><strong>Default Card Min-Width (px):</strong></label>
                    <input name="yardlii_dir_default_width" type="number" id="yardlii_dir_default_width" value="<?php echo esc_attr($def_width); ?>" class="regular-text" style="width:100%">
                    <p class="description">Default: 280. Higher = fewer columns.</p>
                </div>
            </div>
    </div>

    <h3 style="margin-top:0;">Role-Based Card Mapping & Shortcode Builder</h3>
    
    <div id="yardlii-dir-repeater">
        <?php 
        if (empty($configs)) {
            $configs = [['role' => '', 'image' => '', 'title' => '', 'badge' => '', 'location' => '']];
        }
        foreach ($configs as $index => $row): 
            // Load saved UI states
            $ui_decoupled = !empty($row['ui_decoupled']);
            $ui_button    = !empty($row['ui_button']);
            $ui_width     = $row['ui_width'] ?? '';
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
                    <label>Image Source <span title="ACF Field. Leave empty for Avatar." class="dashicons dashicons-info"></span></label>
                    <input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][image]" value="<?php echo esc_attr($row['image'] ?? ''); ?>" class="yardlii-tech-input" placeholder="e.g. business_logo">
                </div>
                <div class="yardlii-field-group">
                    <label>Title Source <span title="ACF Field." class="dashicons dashicons-info"></span></label>
                    <input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][title]" value="<?php echo esc_attr($row['title'] ?? ''); ?>" class="yardlii-tech-input" placeholder="e.g. company_name">
                </div>
                <div class="yardlii-field-group">
                    <label>Badge Source <span title="ACF Field." class="dashicons dashicons-info"></span></label>
                    <input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][badge]" value="<?php echo esc_attr($row['badge'] ?? ''); ?>" class="yardlii-tech-input" placeholder="e.g. primary_trade">
                </div>
                <div class="yardlii-field-group">
                    <label>Location Source <span title="Meta key." class="dashicons dashicons-info"></span></label>
                    <input type="text" name="yardlii_directory_role_config[<?php echo $index; ?>][location]" value="<?php echo esc_attr($row['location'] ?? ''); ?>" class="yardlii-tech-input" placeholder="e.g. billing_city">
                </div>
            </div>

            <div class="yardlii-shortcode-box" style="display:block;">
                <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:10px;">
                    <strong><span class="dashicons dashicons-shortcode"></span> Shortcode Generator</strong>
                    <label style="font-size:12px;">
                        <input type="checkbox" 
                               name="yardlii_directory_role_config[<?php echo $index; ?>][ui_decoupled]" 
                               value="1" 
                               class="yardlii-toggle-decoupled"
                               <?php checked($ui_decoupled); ?>> 
                        Use Decoupled Mode (Split Search & Grid)
                    </label>
                </div>

                <div class="yardlii-builder-standard" style="<?php echo $ui_decoupled ? 'display:none' : ''; ?>">
                    <div style="display:flex; gap:10px;">
                        <input type="text" readonly class="yardlii-shortcode-preview sc-standard" value="..." style="width:100%;">
                        <button type="button" class="button button-small yardlii-copy-btn">Copy</button>
                    </div>
                </div>

                <div class="yardlii-builder-decoupled" style="<?php echo $ui_decoupled ? '' : 'display:none'; ?>; margin-top:10px; padding-top:10px; border-top:1px dashed #bce0ed;">
                    <p style="margin:0 0 5px 0; font-size:11px; color:#666;">Step 1: Place Search Bar (e.g. Hero)</p>
                    <div style="display:flex; gap:10px; margin-bottom:10px;">
                        <input type="text" readonly class="yardlii-shortcode-preview sc-search" value="..." style="width:100%;">
                        <button type="button" class="button button-small yardlii-copy-btn">Copy</button>
                    </div>

                    <p style="margin:0 0 5px 0; font-size:11px; color:#666;">Step 2: Place Grid (e.g. Content)</p>
                    <div style="display:flex; gap:10px;">
                        <input type="text" readonly class="yardlii-shortcode-preview sc-grid" value="..." style="width:100%;">
                        <button type="button" class="button button-small yardlii-copy-btn">Copy</button>
                    </div>
                </div>
                
                <div style="margin-top:10px; font-size:11px; display:flex; gap:15px; color:#555;">
                    <label>
                        <input type="checkbox" 
                               name="yardlii_directory_role_config[<?php echo $index; ?>][ui_button]" 
                               value="1" 
                               class="yardlii-opt-button"
                               <?php checked($ui_button); ?>> 
                        Force Button Search
                    </label>
                    <label>
                        Width: 
                        <input type="number" 
                               name="yardlii_directory_role_config[<?php echo $index; ?>][ui_width]" 
                               value="<?php echo esc_attr($ui_width); ?>" 
                               class="yardlii-opt-width" 
                               placeholder="280" 
                               style="width:50px; padding:0 5px; height:20px; font-size:11px;">
                    </label>
                </div>
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

<hr style="margin: 30px 0;">

    <h3>🧩 Elementor Integration</h3>
    <div class="card" style="margin:0; max-width:none; background:#fff border-left:4px solid #0073aa;">
        <h4 style="margin-top:0;">Author Archive Loop Fix</h4>
        <p>To display a user's <strong>Listings</strong> on their Elementor Profile Template:</p>
        <ol style="margin-left:20px; font-size:13px;">
            <li>Add a <strong>Loop Grid</strong> widget to the Author Archive template.</li>
            <li>Set <strong>Query Source</strong> to "Listings" (or Posts).</li>
            <li>In the <strong>Query ID</strong> field, enter: <code>yardlii_author_listings</code></li>
        </ol>
        <p class="description">This forces the grid to show listings belonging <em>only</em> to the user currently being viewed.</p>
    </div>

<script>
(function($) {
    const container = document.getElementById('yardlii-dir-repeater');
    const addBtn    = document.getElementById('yardlii-add-row');

    function updateBuilders() {
        const rows = container.querySelectorAll('.yardlii-dir-repeater-container');
        rows.forEach(row => {
            const roleSelect = row.querySelector('.yardlii-role-select');
            const roleSlug   = roleSelect.value || 'ROLE_SLUG';
            
            const useButton  = row.querySelector('.yardlii-opt-button').checked;
            const widthVal   = row.querySelector('.yardlii-opt-width').value;
            const useDecoupled = row.querySelector('.yardlii-toggle-decoupled').checked;

            let atts = `role="${roleSlug}"`;
            if(useButton) atts += ` trigger="button"`;
            if(widthVal)  atts += ` card_width="${widthVal}"`;

            // 1. Standard
            const scStandard = row.querySelector('.sc-standard');
            scStandard.value = `[yardlii_directory ${atts}]`;

            // 2. Decoupled
            const scSearch = row.querySelector('.sc-search');
            const scGrid   = row.querySelector('.sc-grid');
            const uniqueId = 'dir-' + roleSlug; 

            let searchAtts = `target="${uniqueId}"`;
            if(useButton) searchAtts += ` trigger="button"`;

            let gridAtts   = `id="${uniqueId}" hide_search="true" ` + atts;

            scSearch.value = `[yardlii_directory_search ${searchAtts}]`;
            scGrid.value   = `[yardlii_directory ${gridAtts}]`;

            // Toggle Visibility
            const stdBox = row.querySelector('.yardlii-builder-standard');
            const decBox = row.querySelector('.yardlii-builder-decoupled');
            
            if(useDecoupled) {
                stdBox.style.display = 'none';
                decBox.style.display = 'block';
            } else {
                stdBox.style.display = 'block';
                decBox.style.display = 'none';
            }
        });
    }

    if(addBtn && container) {
        container.addEventListener('change', updateBuilders);
        container.addEventListener('input', updateBuilders);

        addBtn.addEventListener('click', function() {
            const rows = container.querySelectorAll('.yardlii-dir-repeater-container');
            const clone = rows[0].cloneNode(true);
            const newIndex = rows.length;
            
            clone.querySelectorAll('input[type="text"]').forEach(i => i.value = '');
            clone.querySelector('select').selectedIndex = 0;
            clone.querySelector('.yardlii-toggle-decoupled').checked = false;
            clone.querySelector('.yardlii-opt-button').checked = false;
            clone.querySelector('.yardlii-opt-width').value = '';

            clone.querySelectorAll('input, select').forEach(input => {
                if (input.name) input.name = input.name.replace(/\[\d+\]/, '[' + newIndex + ']');
            });

            container.appendChild(clone);
            updateBuilders();
        });

        container.addEventListener('click', function(e) {
            if (e.target.classList.contains('yardlii-remove-row-btn')) {
                if (container.querySelectorAll('.yardlii-dir-repeater-container').length > 1) e.target.closest('.yardlii-dir-repeater-container').remove();
            }
            if (e.target.classList.contains('yardlii-copy-btn')) {
                const input = e.target.previousElementSibling;
                input.select();
                document.execCommand('copy');
                const originalText = e.target.innerText;
                e.target.innerText = 'Copied!';
                setTimeout(() => e.target.innerText = originalText, 1500);
            }
        });

        updateBuilders();
    }
})(jQuery);
</script>