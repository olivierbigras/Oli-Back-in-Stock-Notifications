<?php
/**
 * Uninstall handler.
 *
 * @package Oli_BIS
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$oli_bis_settings = get_option( 'oli_bis_settings', array() );

if ( empty( $oli_bis_settings['delete_data_on_uninstall'] ) || 'yes' !== $oli_bis_settings['delete_data_on_uninstall'] ) {
	return;
}

global $wpdb;

// Never drop source tables from other plugins — only this plugin's tables.
$oli_bis_tables = array(
	$wpdb->prefix . 'oli_bis_subscriptions',
	$wpdb->prefix . 'oli_bis_stats_daily',
);

foreach ( $oli_bis_tables as $oli_bis_table ) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS {$oli_bis_table}" );
}

delete_option( 'oli_bis_settings' );
delete_option( 'oli_bis_db_version' );
delete_option( 'oli_bis_flush_rewrites' );

$oli_bis_cron_hooks = array(
	'oli_bis_cleanup_unverified',
	'oli_bis_process_restock_batch',
	'oli_bis_prepare_restock',
);

if ( function_exists( 'as_unschedule_all_actions' ) ) {
	foreach ( $oli_bis_cron_hooks as $oli_bis_hook ) {
		as_unschedule_all_actions( $oli_bis_hook );
	}
}

wp_clear_scheduled_hook( 'oli_bis_cleanup_unverified' );
