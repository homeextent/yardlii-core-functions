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
        <p class="description">
            [cite_start]Used by the WPUF Geocoding engine[cite: 318].<br>
            <strong>Important:</strong> This key MUST NOT have "HTTP Referrer" restrictions. 
            Restrict it by <strong>IP Address</strong> (<?php echo $_SERVER['SERVER_ADDR'] ?? 'your server IP'; ?>) or leave unrestricted.
        </p>
    </div>

    <?php submit_button('Save API Keys'); ?>
  </form>

  <hr>
  <h3>🔍 Test & Diagnostics</h3>
  <p>Use this tool to verify that your saved <strong>Frontend</strong> key is valid and reachable from this domain.</p>

  <button type="button" class="button button-secondary" id="yardlii-test-api-key">Test Frontend Key</button>
  <div id="yardlii-test-result" style="margin-top:10px;font-weight:600;"></div>

  <script>
  document.addEventListener('DOMContentLoaded', function() {
    const btn = document.getElementById('yardlii-test-api-key');
    const result = document.getElementById('yardlii-test-result');
    if (btn) {
      btn.addEventListener('click', function() {
        result.textContent = 'Testing API key...';
        result.style.color = '#555';
        fetch(ajaxurl, {
          method: 'POST',
          headers: {'Content-Type': 'application/x-www-form-urlencoded'},
          body: new URLSearchParams({
            action: 'yardlii_test_google_map_key',
            _ajax_nonce: '<?php echo wp_create_nonce('yardlii_test_google_map_key'); ?>'
          })
        })
        .then(res => res.json())
        .then(data => {
          result.textContent = data.message || 'Unknown response.';
          result.style.color = data.success ? '#00a32a' : '#d63638';
        })
        .catch(() => {
          result.textContent = '❌ AJAX request failed.';
          result.style.color = '#d63638';
        });
      });
    }
  });
  </script>
</div>