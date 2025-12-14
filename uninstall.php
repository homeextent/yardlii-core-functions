<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://yardlii.com
 * @since      3.5.0
 * @package    Yardlii_Core
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// 1. Check for the safety toggle.
// Default to 'false' (do not purge).
$purge_data = (bool) get_option('yardlii_remove_data_on_delete', false);

// 2. If the toggle is not explicitly enabled, do nothing.
if (!$purge_data) {
	return;
}

// 3. If enabled, proceed with data removal.
global $wpdb;

// 3b. Delete user meta
// We use the meta key from the badge settings default
$meta_keys = [
	'user_badge', 
];
foreach ($meta_keys as $key) {
	// $wpdb->delete is more direct for a bulk operation.
	$wpdb->delete($wpdb->usermeta, ['meta_key' => $key]);
}

// 3c. Delete options
$opts_to_delete = [
	// Feature flags & settings
	'yardlii_debug_mode',
	'yardlii_enable_acf_user_sync',
	'yardlii_enable_role_control',
	'yardlii_enable_wpuf_dropdown',
	'yardlii_enable_role_control_submit',
	'yardlii_enable_custom_roles',
	'yardlii_enable_badge_assignment',

	// Role Control settings
	'yardlii_custom_roles',
	'yardlii_rc_badges',
	'yardlii_role_control_allowed_roles',
	'yardlii_role_control_denied_action',
	'yardlii_role_control_denied_message',
	'yardlii_role_control_target_page',

	// Other settings from groups
	'yardlii_primary_taxonomy',
	'yardlii_google_map_key',
	'yardlii_featured_image_field',
	
	// The setting itself
	'yardlii_remove_data_on_delete',
];

foreach ($opts_to_delete as $opt_name) {
	delete_option($opt_name);
}

// Wildcard deletes for any other stragglers
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM $wpdb->options WHERE option_name LIKE %s",
		'yardlii\_%' // Escaped wildcard
	)
);