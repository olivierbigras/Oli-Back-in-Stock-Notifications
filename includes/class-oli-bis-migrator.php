<?php
/**
 * Legacy data compatibility hooks.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Placeholder for migrating the existing PixM back-in-stock plugin.
 *
 * When the live plugin source arrives, map its subscriber table/meta into
 * `{$wpdb->prefix}oli_bis_subscriptions` here (or keep the same table if it
 * already matches). This class only inspects and reports until then.
 *
 * @since 0.1.0
 */
class Oli_BIS_Migrator {

	/**
	 * Candidate legacy table suffixes to inspect.
	 *
	 * @since 0.1.0
	 * @return array<int,string>
	 */
	public static function candidate_suffixes() {
		return array(
			'oli_bis_subscriptions',
			'pixm_bis_subscribers',
			'pixm_back_in_stock',
			'wc_bis_notifications',
			'cwginstock_subscribers',
		);
	}

	/**
	 * Returns existing candidate tables on this site.
	 *
	 * @since 0.1.0
	 * @return array<int,string>
	 */
	public static function detect_legacy_tables() {
		global $wpdb;

		$found = array();

		foreach ( self::candidate_suffixes() as $suffix ) {
			$table  = $wpdb->prefix . $suffix;
			$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

			if ( $table === $exists ) {
				$found[] = $table;
			}
		}

		return $found;
	}

	/**
	 * Maps a generic legacy row into the current schema.
	 *
	 * @since 0.1.0
	 * @param array<string,mixed> $row Legacy row.
	 * @return array<string,mixed>|null
	 */
	public static function map_legacy_row( array $row ) {
		$email = '';

		foreach ( array( 'email', 'user_email', 'subscriber_email', 'cwginstock_subscriber_email' ) as $key ) {
			if ( ! empty( $row[ $key ] ) ) {
				$email = Oli_BIS_Repository::normalize_email( (string) $row[ $key ] );
				break;
			}
		}

		$product_id = 0;

		foreach ( array( 'product_id', 'post_id', 'cwginstock_product_id' ) as $key ) {
			if ( ! empty( $row[ $key ] ) ) {
				$product_id = absint( $row[ $key ] );
				break;
			}
		}

		if ( ! $email || ! $product_id ) {
			return null;
		}

		$variation_id = 0;

		foreach ( array( 'variation_id', 'cwginstock_variation_id' ) as $key ) {
			if ( ! empty( $row[ $key ] ) ) {
				$variation_id = absint( $row[ $key ] );
			}
		}

		$status = 'active';

		if ( ! empty( $row['status'] ) ) {
			$status = sanitize_key( (string) $row['status'] );
		}

		$alias = array(
			'subscribed'   => 'active',
			'confirmed'    => 'active',
			'unconfirmed'  => 'pending',
			'mailsent'     => 'notified',
			'subscribed_s' => 'active',
		);

		if ( isset( $alias[ $status ] ) ) {
			$status = $alias[ $status ];
		}

		if ( ! isset( Oli_BIS_Schema::statuses()[ $status ] ) ) {
			$status = 'active';
		}

		return array(
			'email'        => $email,
			'product_id'   => $product_id,
			'variation_id' => $variation_id,
			'user_id'      => isset( $row['user_id'] ) ? absint( $row['user_id'] ) : 0,
			'status'       => $status,
			'language'     => isset( $row['language'] ) ? sanitize_text_field( (string) $row['language'] ) : '',
			'source'       => 'migration',
		);
	}
}
