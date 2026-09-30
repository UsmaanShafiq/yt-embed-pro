<?php
/**
 * Runs when the plugin is deleted from WP Admin → Plugins.
 *
 * @package YT_Shorts_Embed
 */

// WordPress defines this constant before loading uninstall.php.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

delete_option( 'yse_settings' );
delete_option( 'yse_cache_version' );

// License options from versions before 1.3.0.
delete_option( 'yse_license_key' );
delete_option( 'yse_license_token' );

// Cached API responses. Normal cache clearing bumps a version number instead
// (see YSE_API::flush_cache()); on uninstall the rows are removed for good.
global $wpdb;
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_yse_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_yse_' ) . '%'
	)
);
