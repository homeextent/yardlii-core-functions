<?php
/**
 * Partial: Google Map Loading Strategy
 * Isolated settings group to protect API keys.
 */
defined('ABSPATH') || exit;

$target_pages = get_option('yardlii_gmap_target_pages', '');
?>

<div class="form-config-block">
    <h2>🚀 Loading Strategy</h2>
    <p class="description">
        Restrict Google Maps API loading to specific pages to improve site performance.
    </p>

    <div class="yardlii-banner yardlii-banner--info" style="margin: 15px 0;">
        <p><strong>Auto-Detection Active:</strong> The map loads automatically if we detect:<br>
        <code>[yardlii_directory]</code>, <code>[yardlii_search_form]</code>, <code>[wpuf_form]</code>, or <code>[facetwp]</code>.</p>
    </div>

    <form method="post" action="options.php">
        <?php settings_fields('yardlii_google_map_loading_group'); ?>

        <table class="form-table" role="presentation">
            <tr valign="top">
                <th scope="row">
                    <label for="yardlii_gmap_target_pages">Target Pages (Slugs or IDs)</label>
                </th>
                <td>
                    <textarea 
                        name="yardlii_gmap_target_pages" 
                        id="yardlii_gmap_target_pages" 
                        rows="4" 
                        class="large-text code"
                        placeholder="contact-us, 125, submit-listing"
                    ><?php echo esc_textarea($target_pages); ?></textarea>
                    <p class="description">
                        <strong>Critical:</strong> If you use FacetWP maps on a page that isn't auto-detected (e.g. in a Widget or Elementor Template), you <strong>MUST</strong> add its Slug or ID here.<br>
                        <em>Leave empty to load globally (default).</em>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button('Save Loading Settings', 'primary', 'submit', true); ?>
    </form>
</div>