<?php defined('ABSPATH') || exit; ?>
<div class="form-config-block">
    <h2>🎨 Frontend Styling</h2>
    <p class="description">Controls the visual appearance of WPUF forms on the frontend.</p>
    
    <form method="post" action="options.php" class="yardlii-settings-form">
        <?php settings_fields('yardlii_general_group'); ?>
        
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
                <input type="checkbox" name="yardlii_wpuf_card_layout" value="1" <?php checked((bool)get_option('yardlii_wpuf_card_layout', false), true); ?> />
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
                <input type="checkbox" name="yardlii_wpuf_modern_uploader" value="1" <?php checked((bool)get_option('yardlii_wpuf_modern_uploader', false), true); ?> />
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
            <label class="yardlii-switch">
                <input type="checkbox" name="yardlii_enable_wpuf_dropdown" value="1" <?php checked((bool)get_option('yardlii_enable_wpuf_dropdown', true), true); ?> />
                <span class="slider round"></span>
            </label>
            <div style="display:inline-block; vertical-align:top; margin-left: 10px;">
                <strong>Enhanced Taxonomy Dropdown</strong>
                <p class="description" style="margin-top: 2px;">
                    Replaces standard Category select fields with the YARDLII interactive menu.
                </p>
            </div>
        </div>

        <?php submit_button('Save Frontend Settings'); ?>
    </form>
</div>