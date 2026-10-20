<?php
/**
 * PixM and generic legacy data import.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Detects Olivier's live PixM plugin storage and imports it safely.
 *
 * Source plugin: Back in Stock Notifications 1.0.0, slug
 * wc-back-in-stock-notifications, table
 * {$wpdb->prefix}wc_back_in_stock_notifications. Source rows and options
 * are never deleted.
 *
 * @since 0.1.0
 */
class Oli_BIS_Migrator {

	const PIXM_TABLE    = 'wc_back_in_stock_notifications';
	const PIXM_SOURCE   = 'pixm';
	const OPTION_EMAIL  = 'wc_bis_email_template';
	const OPTION_SIGNUP = 'wc_bis_confirmation_email_template';
	const OPTION_CLEAN  = 'wc_bis_auto_cleanup';

	/**
	 * Candidate legacy table suffixes to inspect.
	 *
	 * @since 0.1.0
	 * @return array<int,string>
	 */
	public static function candidate_suffixes() {
		return array(
			self::PIXM_TABLE,
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
	 * PixM table name for a prefix.
	 *
	 * @since 0.1.1
	 * @param string $prefix Table prefix.
	 * @return string
	 */
	public static function pixm_table( $prefix ) {
		return $prefix . self::PIXM_TABLE;
	}

	/**
	 * Whether a table name is the PixM subscriber table.
	 *
	 * @since 0.1.1
	 * @param string $table Table name.
	 * @param string $prefix Prefix.
	 * @return bool
	 */
	public static function is_pixm_table( $table, $prefix ) {
		return self::pixm_table( $prefix ) === $table;
	}

	/**
	 * Detects PixM table and options without writing.
	 *
	 * @since 0.1.1
	 * @return array<string,mixed>
	 */
	public static function detect() {
		global $wpdb;

		$table  = self::pixm_table( $wpdb->prefix );
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count  = 0;

		if ( $table === $exists ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
		}

		return array(
			'table'        => $table,
			'table_exists' => ( $table === $exists ),
			'row_count'    => $count,
			'options'      => array(
				self::OPTION_EMAIL  => get_option( self::OPTION_EMAIL, '' ),
				self::OPTION_SIGNUP => get_option( self::OPTION_SIGNUP, '' ),
				self::OPTION_CLEAN  => get_option( self::OPTION_CLEAN, null ),
			),
			'other_tables' => self::detect_legacy_tables(),
		);
	}

	/**
	 * Maps a PixM row into the current schema.
	 *
	 * PixM stores the subscribed product or variation ID in `product_id`
	 * only (no variation column). Pass $parent_id when that ID is a variation.
	 *
	 * @since 0.1.1
	 * @param array<string,mixed> $row       PixM row.
	 * @param int                 $parent_id Parent product ID when $row product is a variation.
	 * @return array<string,mixed>|null
	 */
	public static function map_pixm_row( array $row, $parent_id = 0 ) {
		$email = Oli_BIS_Repository::normalize_email( isset( $row['email'] ) ? (string) $row['email'] : '' );
		$raw   = absint( isset( $row['product_id'] ) ? $row['product_id'] : 0 );

		if ( ! $email || ! $raw ) {
			return null;
		}

		$ids  = self::resolve_product_ids( $raw, absint( $parent_id ) );
		$sent = ! empty( $row['is_sent'] );

		$created = self::normalize_datetime( isset( $row['date_added'] ) ? $row['date_added'] : '' );
		$sent_at = self::normalize_datetime( isset( $row['date_sent'] ) ? $row['date_sent'] : '' );

		return array(
			'email'         => $email,
			'product_id'    => $ids['product_id'],
			'variation_id'  => $ids['variation_id'],
			'user_id'       => 0,
			'status'        => $sent ? 'notified' : 'active',
			'language'      => '',
			'source'        => self::PIXM_SOURCE,
			'consent'       => true,
			'created_at'    => $created,
			'confirmed_at'  => $created,
			'notified_at'   => $sent ? $sent_at : '',
			'legacy_source' => self::PIXM_SOURCE,
			'legacy_id'     => absint( isset( $row['id'] ) ? $row['id'] : 0 ),
		);
	}

	/**
	 * Resolves parent/variation IDs from a single PixM product_id.
	 *
	 * @since 0.1.1
	 * @param int $product_id Stored PixM product ID.
	 * @param int $parent_id  Parent ID when the stored ID is a variation.
	 * @return array{product_id:int,variation_id:int}
	 */
	public static function resolve_product_ids( $product_id, $parent_id = 0 ) {
		$product_id = absint( $product_id );
		$parent_id  = absint( $parent_id );

		if ( $parent_id && $parent_id !== $product_id ) {
			return array(
				'product_id'   => $parent_id,
				'variation_id' => $product_id,
			);
		}

		return array(
			'product_id'   => $product_id,
			'variation_id' => 0,
		);
	}

	/**
	 * Maps PixM wp_options into current settings keys.
	 *
	 * @since 0.1.1
	 * @param array<string,mixed> $options Raw option values.
	 * @return array<string,mixed>
	 */
	public static function map_pixm_options( array $options ) {
		$mapped = array();

		if ( array_key_exists( self::OPTION_CLEAN, $options ) && null !== $options[ self::OPTION_CLEAN ] ) {
			$mapped['auto_cleanup_notified'] = empty( $options[ self::OPTION_CLEAN ] ) ? 'no' : 'yes';
			$mapped['cleanup_notified_days'] = 30;
		}

		if ( ! empty( $options[ self::OPTION_EMAIL ] ) && is_string( $options[ self::OPTION_EMAIL ] ) ) {
			$mapped['notification_body'] = $options[ self::OPTION_EMAIL ];
		}

		if ( ! empty( $options[ self::OPTION_SIGNUP ] ) && is_string( $options[ self::OPTION_SIGNUP ] ) ) {
			$mapped['signup_body'] = $options[ self::OPTION_SIGNUP ];
		}

		return $mapped;
	}

	/**
	 * Classifies a mapped row for import vs skip.
	 *
	 * @since 0.1.1
	 * @param array<string,mixed>|null $mapped             Mapped row.
	 * @param array<string,int>        $existing_keys      email|product|variation => id.
	 * @param array<int,int>           $imported_legacy_ids Already imported PixM IDs.
	 * @return string import|skip|invalid
	 */
	public static function classify_mapped( $mapped, array $existing_keys, array $imported_legacy_ids ) {
		if ( ! is_array( $mapped ) || empty( $mapped['email'] ) || empty( $mapped['product_id'] ) ) {
			return 'invalid';
		}

		$legacy_id = absint( isset( $mapped['legacy_id'] ) ? $mapped['legacy_id'] : 0 );

		if ( $legacy_id && in_array( $legacy_id, $imported_legacy_ids, true ) ) {
			return 'skip';
		}

		$key = self::unique_key( $mapped['email'], $mapped['product_id'], isset( $mapped['variation_id'] ) ? $mapped['variation_id'] : 0 );

		if ( isset( $existing_keys[ $key ] ) ) {
			return 'skip';
		}

		return 'import';
	}

	/**
	 * Unique lookup key for a subscriber.
	 *
	 * @since 0.1.1
	 * @param string $email        Email.
	 * @param int    $product_id   Product ID.
	 * @param int    $variation_id Variation ID.
	 * @return string
	 */
	public static function unique_key( $email, $product_id, $variation_id = 0 ) {
		return Oli_BIS_Repository::normalize_email( $email ) . '|' . absint( $product_id ) . '|' . absint( $variation_id );
	}

	/**
	 * Plans an import without writing. Never deletes source data.
	 *
	 * Duplicate PixM rows in the same batch are counted as skip after the first.
	 *
	 * @since 0.1.1
	 * @param array<int,array<string,mixed>> $rows                PixM-shaped rows.
	 * @param array<string,int>              $existing_keys       email|product|variation => id.
	 * @param array<int,int>                 $imported_legacy_ids Already imported PixM IDs.
	 * @param callable|null                  $parent_resolver     Optional fn(product_id): parent_id.
	 * @return array<string,mixed>
	 */
	public static function plan_rows( array $rows, array $existing_keys = array(), array $imported_legacy_ids = array(), $parent_resolver = null ) {
		$report = array(
			'import'  => 0,
			'skip'    => 0,
			'invalid' => 0,
			'mapped'  => array(),
		);

		foreach ( $rows as $row ) {
			$raw    = absint( isset( $row['product_id'] ) ? $row['product_id'] : 0 );
			$parent = 0;

			if ( is_callable( $parent_resolver ) ) {
				$parent = absint( call_user_func( $parent_resolver, $raw ) );
			} elseif ( isset( $row['parent_id'] ) ) {
				$parent = absint( $row['parent_id'] );
			}

			$mapped = self::map_pixm_row( $row, $parent );
			$action = self::classify_mapped( $mapped, $existing_keys, $imported_legacy_ids );

			if ( 'invalid' === $action ) {
				++$report['invalid'];
				continue;
			}

			if ( 'skip' === $action ) {
				++$report['skip'];
				continue;
			}

			++$report['import'];
			$report['mapped'][] = $mapped;

			$key                   = self::unique_key( $mapped['email'], $mapped['product_id'], $mapped['variation_id'] );
			$existing_keys[ $key ] = 1;

			if ( ! empty( $mapped['legacy_id'] ) ) {
				$imported_legacy_ids[] = absint( $mapped['legacy_id'] );
			}
		}

		return $report;
	}

	/**
	 * Runs a dry-run or import. Never deletes PixM rows or options.
	 *
	 * @since 0.1.1
	 * @param bool $dry_run When true, only report counts.
	 * @return array<string,mixed>
	 */
	public static function run( $dry_run = true ) {
		$detect = self::detect();

		$report = array(
			'dry_run'  => (bool) $dry_run,
			'detected' => $detect,
			'import'   => 0,
			'skip'     => 0,
			'invalid'  => 0,
			'options'  => 0,
			'error'    => '',
		);

		if ( empty( $detect['table_exists'] ) ) {
			$report['error'] = 'not_found';
			return $report;
		}

		global $wpdb;

		$table = $detect['table'];
		$rows  = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		if ( ! is_array( $rows ) ) {
			return $report;
		}

		$plan = self::plan_rows(
			$rows,
			Oli_BIS_Repository::existing_unique_keys(),
			Oli_BIS_Repository::legacy_ids( self::PIXM_SOURCE ),
			array( __CLASS__, 'lookup_parent_id' )
		);

		$report['import']  = $plan['import'];
		$report['skip']    = $plan['skip'];
		$report['invalid'] = $plan['invalid'];

		if ( ! $dry_run ) {
			$imported = 0;

			foreach ( $plan['mapped'] as $mapped ) {
				if ( Oli_BIS_Repository::import_mapped( $mapped ) ) {
					++$imported;
				} else {
					++$report['skip'];
				}
			}

			$report['import']  = $imported;
			$report['options'] = self::import_options( $detect['options'] ) ? 1 : 0;
		} elseif ( self::map_pixm_options( $detect['options'] ) ) {
			$report['options'] = 1;
		}

		return $report;
	}

	/**
	 * Copies PixM email/cleanup options into empty local settings.
	 *
	 * @since 0.1.1
	 * @param array<string,mixed> $options Detected options.
	 * @return bool
	 */
	public static function import_options( array $options ) {
		$mapped = self::map_pixm_options( $options );

		if ( empty( $mapped ) ) {
			return false;
		}

		$current = Oli_BIS_Settings::all();
		$changed = false;

		foreach ( $mapped as $key => $value ) {
			if ( 'notification_body' === $key || 'signup_body' === $key ) {
				if ( '' === (string) $current[ $key ] ) {
					$current[ $key ] = $value;
					$changed         = true;
				}
				continue;
			}

			$current[ $key ] = $value;
			$changed         = true;
		}

		if ( $changed ) {
			Oli_BIS_Settings::update( $current );
		}

		return $changed;
	}

	/**
	 * Maps a generic legacy row into the current schema.
	 *
	 * @since 0.1.0
	 * @param array<string,mixed> $row Legacy row.
	 * @return array<string,mixed>|null
	 */
	public static function map_legacy_row( array $row ) {
		if ( isset( $row['is_sent'] ) || ( isset( $row['email'] ) && isset( $row['product_id'] ) && isset( $row['date_added'] ) ) ) {
			$parent = absint( isset( $row['parent_id'] ) ? $row['parent_id'] : 0 );
			return self::map_pixm_row( $row, $parent );
		}

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

	/**
	 * Looks up a variation parent ID when WooCommerce is available.
	 *
	 * @since 0.1.1
	 * @param int $product_id Product ID.
	 * @return int
	 */
	public static function lookup_parent_id( $product_id ) {
		if ( ! $product_id || ! function_exists( 'wc_get_product' ) ) {
			return 0;
		}

		$product = wc_get_product( $product_id );

		if ( $product && ( $product->is_type( 'variation' ) || $product->is_type( 'subscription_variation' ) ) ) {
			return absint( $product->get_parent_id() );
		}

		return 0;
	}

	/**
	 * Normalizes a MySQL datetime string.
	 *
	 * @since 0.1.1
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function normalize_datetime( $value ) {
		$value = is_string( $value ) ? trim( $value ) : '';

		if ( ! $value || '0000-00-00 00:00:00' === $value ) {
			return '';
		}

		if ( preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value ) ) {
			return $value;
		}

		$ts = strtotime( $value );

		return $ts ? gmdate( 'Y-m-d H:i:s', $ts ) : '';
	}
}
