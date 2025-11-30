<div class="form-config-block">
  <h2>🔑 Google Maps API Keys</h2>
  <p class="description">
      Manage keys for frontend display and backend processing. 
      <a href="https://console.cloud.google.com/google/maps-apis/credentials" target="_blank">Manage Keys in Google Cloud Console ↗</a>
  </p>

  <form method="post" action="options.php">
    <?php
      settings_fields('yardlii_google_map_group');
      $api_key = get_option('yardlii_google_map_key', '');
      $server_key = get_option('yardlii_google_server_key', '');
    ?>
    
    <div style="margin-bottom: 20px;">
        <label style="font-weight:600; display:block; margin-bottom:5px;">Frontend Map Key (Browser):</label>
        <input type="text" name="yardlii_google_map_key" value="<?php echo esc_attr($api_key); ?>" placeholder="AIza..." class="regular-text code">
        <p class="description">Used by FacetWP and Elementor. Should be restricted by <strong>HTTP Referrer</strong> (e.g. <code>*.yardlii.com/*</code>).</p>
    </div>

    <div style="margin-bottom: 20px; padding: 15px; background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px;">
        <label style="font-weight:600; display:block; margin-bottom:5px;">Backend Geocoding Key (Server):</label>
        <input type="text" name="yardlii_google_server_key" value="<?php echo esc_attr($server_key); ?>" placeholder="AIza..." class="regular-text code">
        
        <div class="description" style="margin-top:8px; line-height:1.5;">
            Used by the WPUF Geocoding engine.<br>
            <strong>Security Configuration:</strong>
            <ol style="list-style:decimal; margin-left:20px; margin-top:5px;">
                <li>
                    <strong>Option A (Strict):</strong> Restrict by IP Address. Your server's <em>Incoming</em> IP is <code><?php echo $_SERVER['SERVER_ADDR'] ?? 'Unknown'; ?></code>.<br>
                    <em>Note:</em> On shared hosting (SiteGround), the <strong>Outgoing IP</strong> often differs. Check <code>debug.log</code> if requests fail to find the correct IP.
                </li>
                <li>
                    <strong>Option B (Recommended Fallback):</strong> Leave IP restrictions <strong>Blank (None)</strong> and enable a <strong>Quota Limit</strong> (e.g., 1,000/day) in Google Console to prevent overage costs.
                </li>
            </ol>
        </div>
    </div>

    <?php submit_button('Save API Keys'); ?>
  </form>

  <hr>
  <h3>🔍 Test & Diagnostics</h3>
  <p>Verify that your keys are valid and have the correct permissions enabled.</p>

  <div style="display:flex; gap:10px; margin-bottom:10px;">
      <button type="button" class="button button-secondary" id="yardlii-test-front-key">Test Frontend Key</button>
      <button type="button" class="button button-secondary" id="yardlii-test-back-key">Test Backend Key</button>
  </div>
  
  <div id="yardlii-test-result" style="margin-top:10px;font-weight:600; padding:10px; background:#fff; border:1px solid #ddd; display:none;"></div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const resultBox = document.getElementById('yardlii-test-result');
    
    function runTest(type) {
        resultBox.style.display = 'block';
        resultBox.textContent = 'Testing ' + type + ' key...';
        resultBox.style.color = '#555';
        resultBox.style.borderColor = '#ddd';

        fetch(ajaxurl, {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: new URLSearchParams({
            action: 'yardlii_test_google_map_key',
            key_type: type,
            _ajax_nonce: '<?php echo wp_create_nonce('yardlii_test_google_map_key'); ?>'
          })
        })
        .then(res => res.json())
        .then(data => {
          resultBox.textContent = data.data.message || data.message || 'Unknown response.';
          if(data.success) {
              resultBox.style.color = '#00a32a';
              resultBox.style.borderColor = '#00a32a';
          } else {
              resultBox.style.color = '#d63638';
              resultBox.style.borderColor = '#d63638';
          }
        })
        .catch(err => {
          resultBox.textContent = '❌ Critical Error: ' + err;
          resultBox.style.color = '#d63638';
        });
    }

    const btnFront = document.getElementById('yardlii-test-front-key');
    if (btnFront) btnFront.addEventListener('click', function() { runTest('frontend'); });

    const btnBack = document.getElementById('yardlii-test-back-key');
    if (btnBack) btnBack.addEventListener('click', function() { runTest('backend'); });
  });
  </script>
</div>