<?php defined('ABSPATH') || exit; ?>
<div class="form-config-block">
    <h2>🧠 Listing Logic</h2>
    <p class="description">Backend data handling and form switching logic.</p>

    <form method="post" action="options.php" class="yardlii-settings-form">
        <?php settings_fields('yardlii_wpuf_logic_group'); ?>

        <div class="yardlii-setting-row" style="margin-bottom: 20px;">
            <label class="yardlii-switch">
                <input type="checkbox" name="yardlii_enable_featured_listings" value="1" <?php checked((bool)get_option('yardlii_enable_featured_listings', false), true); ?> />
                <span class="slider round"></span>
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
    
    <div class="yardlii-info-box" style="background:#fff; border:1px solid #ccd0d4; padding:20px; border-left:4px solid #2271b1;">
    <h3 style="margin-top:0;">📘 Dashboard Shortcode Reference</h3>
    <p>Use these shortcodes to build your custom Elementor Dashboard tabs.</p>

    <hr>

    <h4>1. My Listings Grid</h4>
    <p>Displays the current user's listings as visual cards with Edit/Delete buttons (Replaces <code>[wpuf_dashboard]</code>).</p>
    <code>[yardlii_user_dashboard]</code>

    <h4>2. Dynamic Profile Form</h4>
    <p>Automatically renders the correct "Edit Profile" form (Basic vs Pro) based on the user's role mapping.</p>
    <code>[yardlii_edit_profile]</code>

    <h4>3. Dynamic Submission</h4>
    <p>Renders the correct "Submit Listing" form. (Basic users get restricted form, Pros get full form).</p>
    <code>[yardlii_submit_listing]</code>

    <h4>4. Secure Logout Button</h4>
    <p>Generates a secure, nonce-protected logout button. Prevents "Link Expired" errors.</p>
    <code>[yardlii_logout label="Log Out" redirect="/"]</code>
    <p><em>Tip: Place this in a dedicated "Log Out" tab to prevent accidental clicks.</em></p>

    <hr>

    <h4>🔗 Deep Linking (Smart Navigation)</h4>
    <p>You can link users directly to specific tabs on your custom dashboard using URL parameters.</p>
    <p><strong>Requirement:</strong> The Elementor Tabs widget must have the CSS ID: <code>yardlii-dashboard-tabs</code>.</p>
    <ul style="list-style:disc; margin-left:20px;">
        <li>Link to Tab 1 (My Listings): <code>/dashboard/?tab=1</code></li>
        <li>Link to Tab 2 (Edit Profile): <code>/dashboard/?tab=2</code></li>
        <li>Link to Tab 3 (Submit Post): <code>/dashboard/?tab=3</code></li>
    </ul>
</div>
</div>