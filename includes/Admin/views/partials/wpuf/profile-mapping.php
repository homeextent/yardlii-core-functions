<?php defined('ABSPATH') || exit; ?>
<div class="form-config-block">
    <h2>👤 Dynamic Profile Form Mapping</h2>
    <p class="description">
        Map specific <strong>User Roles</strong> to specific <strong>WPUF Profile Forms</strong>.<br>
        The system checks these rules from top to bottom. The first matching role determines the form.
    </p>

    <form method="post" action="options.php" class="yardlii-settings-form">
        <?php settings_fields('yardlii_general_group'); ?>

        <?php 
        $map = get_option('yardlii_profile_form_map', []);
        if (empty($map) || !is_array($map)) {
            $map = [['role' => '', 'form_id' => '']];
        }
        
        // Ensure editable_roles is available
        if (!function_exists('get_editable_roles')) {
            require_once ABSPATH . 'wp-admin/includes/user.php';
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

        <?php submit_button('Save Profile Mapping'); ?>
    </form>

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
</div>