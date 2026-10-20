<?php
/**
 * Daily stats and dashboard queries.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Increments counters and builds dashboard datasets.
 *
 * @since 0.1.0
 */
class Oli_BIS_Stats {

	/**
	 * Daily stats table.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public static function table() {
		global $wpdb;

		return Oli_BIS_Schema::stats_table( $wpdb->prefix );
	}

	/**
	 * Increments a daily counter.
	 *
	 * @since 0.1.0
	 * @param string $column Column name.
	 * @param int    $amount Amount.
	 * @return void
	 */
	public static function increment( $column, $amount = 1 ) {
		$allowed = array( 'signups', 'notifications', 'conversions' );

		if ( ! in_array( $column, $allowed, true ) ) {
			return;
		}

		global $wpdb;

		$table  = self::table();
		$date   = gmdate( 'Y-m-d' );
		$amount = absint( $amount );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table} (stat_date, {$column}) VALUES (%s, %d) ON DUPLICATE KEY UPDATE {$column} = {$column} + %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$date,
				$amount,
				$amount
			)
		);
	}

	/**
	 * Totals for a number of days.
	 *
	 * @since 0.1.0
	 * @param int $days Days.
	 * @return array<string,int>
	 */
	public static function totals( $days = 30 ) {
		global $wpdb;

		$table = self::table();
		$from  = gmdate( 'Y-m-d', time() - ( absint( $days ) * DAY_IN_SECONDS ) );
		$row   = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COALESCE(SUM(signups),0) AS signups, COALESCE(SUM(notifications),0) AS notifications, COALESCE(SUM(conversions),0) AS conversions FROM {$table} WHERE stat_date >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$from
			),
			ARRAY_A
		);

		return array(
			'signups'       => isset( $row['signups'] ) ? (int) $row['signups'] : 0,
			'notifications' => isset( $row['notifications'] ) ? (int) $row['notifications'] : 0,
			'conversions'   => isset( $row['conversions'] ) ? (int) $row['conversions'] : 0,
		);
	}

	/**
	 * Daily series for charts.
	 *
	 * @since 0.1.0
	 * @param int $days Days.
	 * @return array<int,array<string,mixed>>
	 */
	public static function series( $days = 30 ) {
		global $wpdb;

		$table = self::table();
		$from  = gmdate( 'Y-m-d', time() - ( absint( $days ) * DAY_IN_SECONDS ) );
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT stat_date, signups, notifications, conversions FROM {$table} WHERE stat_date >= %s ORDER BY stat_date ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$from
			),
			ARRAY_A
		);

		$indexed = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$indexed[ $row['stat_date'] ] = $row;
			}
		}

		$series = array();

		for ( $i = absint( $days ) - 1; $i >= 0; $i-- ) {
			$date     = gmdate( 'Y-m-d', time() - ( $i * DAY_IN_SECONDS ) );
			$series[] = array(
				'stat_date'     => $date,
				'signups'       => isset( $indexed[ $date ]['signups'] ) ? (int) $indexed[ $date ]['signups'] : 0,
				'notifications' => isset( $indexed[ $date ]['notifications'] ) ? (int) $indexed[ $date ]['notifications'] : 0,
				'conversions'   => isset( $indexed[ $date ]['conversions'] ) ? (int) $indexed[ $date ]['conversions'] : 0,
			);
		}

		return $series;
	}

	/**
	 * Products with the most waiting subscribers.
	 *
	 * @since 0.1.0
	 * @param int $limit Limit.
	 * @return array<int,array<string,mixed>>
	 */
	public static function most_wanted( $limit = 5 ) {
		global $wpdb;

		$table = Oli_BIS_Repository::table();
		$limit = absint( $limit );
		$sql   = "SELECT product_id, variation_id, COUNT(*) AS total FROM {$table} WHERE status IN ('active','queued') GROUP BY product_id, variation_id ORDER BY total DESC LIMIT {$limit}";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Products waiting the longest.
	 *
	 * @since 0.1.0
	 * @param int $limit Limit.
	 * @return array<int,array<string,mixed>>
	 */
	public static function most_overdue( $limit = 5 ) {
		global $wpdb;

		$table = Oli_BIS_Repository::table();
		$limit = absint( $limit );
		$sql   = "SELECT product_id, variation_id, MIN(created_at) AS first_signup, COUNT(*) AS total FROM {$table} WHERE status IN ('active','queued') GROUP BY product_id, variation_id ORDER BY first_signup ASC LIMIT {$limit}";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		return is_array( $rows ) ? $rows : array();
	}
}
