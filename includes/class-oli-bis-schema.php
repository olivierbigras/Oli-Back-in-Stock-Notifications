<?php
/**
 * Database schema helpers.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds dbDelta-compatible table SQL.
 *
 * @since 0.1.0
 */
class Oli_BIS_Schema {

	const DB_VERSION = '0.1.0';

	/**
	 * Returns the subscriptions table name for a prefix.
	 *
	 * @since 0.1.0
	 * @param string $prefix Table prefix.
	 * @return string
	 */
	public static function subscriptions_table( $prefix ) {
		return $prefix . 'oli_bis_subscriptions';
	}

	/**
	 * Returns the daily stats table name for a prefix.
	 *
	 * @since 0.1.0
	 * @param string $prefix Table prefix.
	 * @return string
	 */
	public static function stats_table( $prefix ) {
		return $prefix . 'oli_bis_stats_daily';
	}

	/**
	 * Returns CREATE TABLE SQL for subscriptions.
	 *
	 * @since 0.1.0
	 * @param string $prefix        Table prefix.
	 * @param string $charset_collate Charset collate clause.
	 * @return string
	 */
	public static function subscriptions_sql( $prefix, $charset_collate ) {
		$table = self::subscriptions_table( $prefix );

		return "CREATE TABLE {$table} (
	id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	product_id bigint(20) unsigned NOT NULL,
	variation_id bigint(20) unsigned NOT NULL DEFAULT 0,
	user_id bigint(20) unsigned NOT NULL DEFAULT 0,
	email varchar(191) NOT NULL,
	status varchar(20) NOT NULL DEFAULT 'pending',
	language varchar(32) NOT NULL DEFAULT '',
	attributes longtext NULL,
	source varchar(32) NOT NULL DEFAULT 'form',
	consent tinyint(1) NOT NULL DEFAULT 0,
	consent_at datetime NULL,
	created_at datetime NOT NULL,
	confirmed_at datetime NULL,
	queued_at datetime NULL,
	notified_at datetime NULL,
	unsubscribed_at datetime NULL,
	converted_at datetime NULL,
	converted_order_id bigint(20) unsigned NOT NULL DEFAULT 0,
	token_key varchar(64) NOT NULL DEFAULT '',
	PRIMARY KEY  (id),
	UNIQUE KEY email_product (email, product_id, variation_id),
	KEY status (status),
	KEY product_id (product_id),
	KEY user_id (user_id),
	KEY created_at (created_at)
) {$charset_collate};";
	}

	/**
	 * Returns CREATE TABLE SQL for daily stats.
	 *
	 * @since 0.1.0
	 * @param string $prefix        Table prefix.
	 * @param string $charset_collate Charset collate clause.
	 * @return string
	 */
	public static function stats_sql( $prefix, $charset_collate ) {
		$table = self::stats_table( $prefix );

		return "CREATE TABLE {$table} (
	stat_date date NOT NULL,
	signups int(10) unsigned NOT NULL DEFAULT 0,
	notifications int(10) unsigned NOT NULL DEFAULT 0,
	conversions int(10) unsigned NOT NULL DEFAULT 0,
	PRIMARY KEY  (stat_date)
) {$charset_collate};";
	}

	/**
	 * Known statuses used by the plugin.
	 *
	 * @since 0.1.0
	 * @return array<string,string>
	 */
	public static function statuses() {
		return array(
			'pending'      => 'pending',
			'active'       => 'active',
			'queued'       => 'queued',
			'notified'     => 'notified',
			'unsubscribed' => 'unsubscribed',
			'cancelled'    => 'cancelled',
			'converted'    => 'converted',
		);
	}
}
