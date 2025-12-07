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
        Restrict Google Maps API loading to specific pages to improve site performance and reduce API costs.
    </p>

    <div class="yardlii-banner yardlii-banner--success" style="margin: 15px 0;">
        <p><strong>✅ Auto-Detection Active:</strong> You do NOT need to list pages that contain standard shortcodes in their main content area. The system automatically loads the map for:</p>
        <ul style="margin: 5px 0 0 20px; list-style: disc; font-size: 12px;">
            <li><code>[yardlii_directory]</code> (User Directory)</li>
            <li><code>[yardlii_search_form]</code> (Homepage Search)</li>
            <li><code>[wpuf_form]</code> (Submission/Profile Forms)</li>
            <li><code>[facetwp]</code> (Standard Maps)</li>
            <li>Inputs with class <code>.yardlii-city-autocomplete</code></li>
        </ul>
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
                        placeholder="contact-us, listings, 125"
                    ><?php echo esc_textarea($target_pages); ?></textarea>
                    
                    <div style="background: #f9f9f9; border: 1px solid #ccd0d4; padding: 10px; margin-top: 8px; border-radius: 4px;">
                        <strong>⚠️ When to use this field:</strong>
                        <p class="description" style="margin-top:5px;">
                            You <strong>MUST</strong> manually add the Page Slug (e.g. <code>listings</code>) or ID for:
                        </p>
                        <ul style="list-style: disc; margin-left: 20px; margin-top: 5px; font-size: 12px; color: #666;">
                            <li><strong>Archive Pages:</strong> (e.g. The "All Listings" archive which has no page content).</li>
                            <li><strong>Global Templates:</strong> Maps inside Elementor Headers, Footers, or Popups.</li>
                            <li><strong>Widgets:</strong> Maps loaded via Sidebar Widgets.</li>
                        </ul>
                    </div>
                    <p class="description" style="margin-top:8px;">
                        <em>Leave this field completely empty to load the API globally on every page (Default).</em>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button('Save Loading Settings', 'primary', 'submit', true); ?>
    </form>
</div>