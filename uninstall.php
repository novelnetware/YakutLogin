<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider all of셔 the Datalab
 * options, custom database tables, and other data that were
 * created by this plugin.
 *
 * @link       https://example.com/
 * @since      1.0.0
 *
 * @package    Sms_Login_Register
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check if the user has opted to remove all data.
// It's good practice to have a setting in your plugin like 'slr_delete_all_data_on_uninstall'
// For now, we will assume we always delete plugin-specific options.
// Deleting user-specific data like user meta should ideally be an explicit choice.

// Delete plugin options
delete_option( 'slr_plugin_options' );

// --- Potentially Destructive Data Deletion ---
// The following sections delete data that might be associated with users.
// It's often better to give the site administrator an option within the plugin settings
// to "Remove all data on uninstall". If that option is not checked, you might skip
// deleting user meta or other potentially valuable data.

// For this example, we'll include the code to delete user meta,
// but you should carefully consider if this is the desired default behavior.
// If you want to make this conditional, you would check an option like:
// $options = get_option( 'slr_plugin_options' );
// $delete_user_data = isset( $options['delete_all_user_data_on_uninstall'] ) && $options['delete_all_user_data_on_uninstall'];
// if ( $delete_user_data ) { ... proceed with user meta deletion ... }

// Delete transients
// OTP transients are short-lived and prefixed. It's hard to delete all by prefix without iteration.
// WordPress cron usually cleans up expired transients.
// However, if you have specific, known, long-lived transients, delete them here.
// Example: delete_transient( 'slr_some_long_lived_transient' );

// If you created custom database tables, drop them here using $wpdb.
global $wpdb;
// Example: $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}my_custom_table" );

// Clear any scheduled cron events if your plugin added them.
// Example: wp_clear_scheduled_hook( 'slr_my_custom_cron_hook' );

// Note: This uninstall script is executed when a user clicks "Delete" for the plugin
// from the WordPress admin area. It does not run on deactivation.