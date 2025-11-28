<?php
/**
 * Partial: General -> User Directory Configuration
 */
defined('ABSPATH') || exit;

// Fetch current values or defaults
$map_image = get_option('yardlii_dir_map_image', 'yardlii_business_logo');
$map_title = get_option('yardlii_dir_map_title', 'yardlii_company_name');
$map_badge = get_option('yardlii_dir_map_badge', 'yardlii_primary_trade');
$map_loc   = get_option('yardlii_dir_map_location', 'billing_city');
?>

<div class="yardlii-card">
    <h2 style="margin-top:0;">📂 Directory Field Mapping</h2>
    <p class="description">
        Map the visual elements of the "Business Card" to your specific <strong>ACF Field Names</strong> or <strong>User Meta Keys</strong>.
        <br>Leave a field blank to disable that element on the card.
    </p>

    <form method="post" action="options.php">
        <?php settings_fields('yardlii_directory_group'); ?>
        
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="yardlii_dir_map_image">Card Image / Logo</label></th>
                <td>
                    <input name="yardlii_dir_map_image" type="text" id="yardlii_dir_map_image" value="<?php echo esc_attr($map_image); ?>" class="regular-text code">
                    <p class="description">Enter the ACF Image Field Name. (Fallback: User Avatar)</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="yardlii_dir_map_title">Card Title</label></th>
                <td>
                    <input name="yardlii_dir_map_title" type="text" id="yardlii_dir_map_title" value="<?php echo esc_attr($map_title); ?>" class="regular-text code">
                    <p class="description">Enter ACF Field Name (e.g., <code>company_name</code>). (Fallback: User Display Name)</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="yardlii_dir_map_badge">Badge / Subtitle</label></th>
                <td>
                    <input name="yardlii_dir_map_badge" type="text" id="yardlii_dir_map_badge" value="<?php echo esc_attr($map_badge); ?>" class="regular-text code">
                    <p class="description">e.g., <code>primary_trade</code> or <code>job_title</code>.</p>
                </td>
            </tr>

            <tr>
                <th scope="row"><label for="yardlii_dir_map_location">Location</label></th>
                <td>
                    <input name="yardlii_dir_map_location" type="text" id="yardlii_dir_map_location" value="<?php echo esc_attr($map_loc); ?>" class="regular-text code">
                    <p class="description">e.g., <code>billing_city</code> or an ACF text field.</p>
                </td>
            </tr>
        </table>

        <?php submit_button('Save Directory Mapping'); ?>
    </form>
</div>