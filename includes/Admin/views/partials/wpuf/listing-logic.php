<?php defined('ABSPATH') || exit; ?>
<div class="form-config-block">
    <h2>🧠 Listing Logic</h2>
    <p class="description">Backend data handling and form switching logic.</p>

    <form method="post" action="options.php" class="yardlii-settings-form">
        <?php settings_fields('yardlii_general_group'); ?>

        <div class="yardlii-setting-row" style="margin-bottom: 20px;">
            <label class="yardlii-toggle">
                <input type="checkbox" name="yardlii_enable_featured_listings" value="1" <?php checked((bool)get_option('yardlii_enable_featured_listings', false), true); ?> />
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

        <?php submit_button('Save Logic Settings'); ?>
    </form>
    
    <div style="margin-top: 30px; border-top: 1px solid #ddd; padding-top: 20px;">
        <h3>📘 Dashboard Shortcodes</h3>
        <p>Use these shortcodes to build your custom Elementor Dashboard tabs.</p>
        <ul style="list-style:disc; margin-left:20px; color:#555;">
            <li><code>[yardlii_user_dashboard]</code> - My Listings Grid</li>
            <li><code>[yardlii_submit_listing]</code> - Smart Submit Wrapper</li>
            <li><code>[yardlii_edit_profile]</code> - Smart Profile Wrapper</li>
            <li><code>[yardlii_logout]</code> - Secure Logout Button</li>
        </ul>
    </div>
</div>