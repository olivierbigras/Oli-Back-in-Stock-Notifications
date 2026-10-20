<?php
/**
 * Subscription repository.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * CRUD helpers for the subscriptions table.
 *
 * @since 0.1.0
 */
class Oli_BIS_Repository {

	/**
	 * Table name.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public static function table() {
		global $wpdb;

		return Oli_BIS_Schema::subscriptions_table( $wpdb->prefix );
	}

	/**
	 * Finds a subscription by ID.
	 *
	 * @since 0.1.0
	 * @param int $id Subscription ID.
	 * @return Oli_BIS_Subscription|null
	 */
	public static function get( $id ) {
		global $wpdb;

		$table = self::table();
		$row   = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return is_array( $row ) ? new Oli_BIS_Subscription( $row ) : null;
	}

	/**
	 * Finds a subscription by email and product.
	 *
	 * @since 0.1.0
	 * @param string $email        Email.
	 * @param int    $product_id   Product ID.
	 * @param int    $variation_id Variation ID.
	 * @return Oli_BIS_Subscription|null
	 */
	public static function find( $email, $product_id, $variation_id = 0 ) {
		global $wpdb;

		$table = self::table();
		$row   = $wpdb->get_row( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE email = %s AND product_id = %d AND variation_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				self::normalize_email( $email ),
				absint( $product_id ),
				absint( $variation_id )
			),
			ARRAY_A
		);

		return is_array( $row ) ? new Oli_BIS_Subscription( $row ) : null;
	}

	/**
	 * Creates or reactivates a subscription.
	 *
	 * @since 0.1.0
	 * @param array<string,mixed> $data Subscription data.
	 * @return array{subscription:Oli_BIS_Subscription|null,created:bool,error?:string}
	 */
	public static function upsert( array $data ) {
		$email        = self::normalize_email( isset( $data['email'] ) ? $data['email'] : '' );
		$product_id   = absint( isset( $data['product_id'] ) ? $data['product_id'] : 0 );
		$variation_id = absint( isset( $data['variation_id'] ) ? $data['variation_id'] : 0 );

		if ( ! $email || ! $product_id ) {
			return array(
				'subscription' => null,
				'created'      => false,
				'error'        => 'invalid',
			);
		}

		$existing = self::find( $email, $product_id, $variation_id );

		if ( $existing && Oli_BIS_Status::is_waiting( $existing->get_status() ) ) {
			return array(
				'subscription' => $existing,
				'created'      => false,
				'error'        => 'exists',
			);
		}

		if ( $existing && 'pending' === $existing->get_status() ) {
			return array(
				'subscription' => $existing,
				'created'      => false,
				'error'        => 'pending',
			);
		}

		$now    = self::now();
		$status = isset( $data['status'] ) ? (string) $data['status'] : 'active';
		$row    = array(
			'product_id'         => $product_id,
			'variation_id'       => $variation_id,
			'user_id'            => absint( isset( $data['user_id'] ) ? $data['user_id'] : 0 ),
			'email'              => $email,
			'status'             => $status,
			'language'           => isset( $data['language'] ) ? sanitize_text_field( $data['language'] ) : '',
			'attributes'         => self::encode_attributes( isset( $data['attributes'] ) ? $data['attributes'] : array() ),
			'source'             => isset( $data['source'] ) ? sanitize_key( $data['source'] ) : 'form',
			'consent'            => ! empty( $data['consent'] ) ? 1 : 0,
			'consent_at'         => ! empty( $data['consent'] ) ? $now : null,
			'created_at'         => $now,
			'confirmed_at'       => 'active' === $status ? $now : null,
			'queued_at'          => null,
			'notified_at'        => null,
			'unsubscribed_at'    => null,
			'converted_at'       => null,
			'converted_order_id' => 0,
			'token_key'          => self::new_token_key(),
		);

		global $wpdb;

		if ( $existing ) {
			if ( ! Oli_BIS_Status::can_transition( $existing->get_status(), $status ) ) {
				return array(
					'subscription' => $existing,
					'created'      => false,
					'error'        => 'invalid_status',
				);
			}

			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				self::table(),
				$row,
				array( 'id' => $existing->get_id() ),
				self::formats(),
				array( '%d' )
			);

			$subscription = self::get( $existing->get_id() );
		} else {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				self::table(),
				$row,
				self::formats()
			);

			$subscription = self::get( (int) $wpdb->insert_id );
		}

		if ( $subscription ) {
			wp_cache_delete( 'oli_bis_count_' . $product_id, 'oli_bis' );
		}

		return array(
			'subscription' => $subscription,
			'created'      => (bool) $subscription,
		);
	}

	/**
	 * Updates status when the transition is valid.
	 *
	 * @since 0.1.0
	 * @param int    $id     Subscription ID.
	 * @param string $status New status.
	 * @param array  $extra  Extra columns.
	 * @return bool
	 */
	public static function set_status( $id, $status, array $extra = array() ) {
		$current = self::get( $id );

		if ( ! $current || ! Oli_BIS_Status::can_transition( $current->get_status(), $status ) ) {
			return false;
		}

		$data   = array_merge( $extra, array( 'status' => $status ) );
		$format = array();

		foreach ( $data as $value ) {
			$format[] = is_int( $value ) ? '%d' : '%s';
		}

		global $wpdb;

		$updated = $wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::table(),
			$data,
			array( 'id' => absint( $id ) ),
			$format,
			array( '%d' )
		);

		if ( false !== $updated ) {
			wp_cache_delete( 'oli_bis_count_' . $current->get_product_id(), 'oli_bis' );
		}

		return false !== $updated;
	}

	/**
	 * Deletes a subscription.
	 *
	 * @since 0.1.0
	 * @param int $id Subscription ID.
	 * @return bool
	 */
	public static function delete( $id ) {
		$current = self::get( $id );

		global $wpdb;

		$deleted = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::table(),
			array( 'id' => absint( $id ) ),
			array( '%d' )
		);

		if ( $current && false !== $deleted ) {
			wp_cache_delete( 'oli_bis_count_' . $current->get_product_id(), 'oli_bis' );
		}

		return false !== $deleted;
	}

	/**
	 * Counts waiting subscribers for a product (includes variations).
	 *
	 * @since 0.1.0
	 * @param int $product_id Product or variation ID.
	 * @return int
	 */
	public static function count_for_product( $product_id ) {
		$product_id = absint( $product_id );
		$cache_key  = 'oli_bis_count_' . $product_id;
		$cached     = wp_cache_get( $cache_key, 'oli_bis' );

		if ( false !== $cached ) {
			return (int) $cached;
		}

		global $wpdb;

		$table = self::table();
		$count = (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE (product_id = %d OR variation_id = %d) AND status IN ('active','pending','queued')", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$product_id,
				$product_id
			)
		);

		wp_cache_set( $cache_key, $count, 'oli_bis', 5 * MINUTE_IN_SECONDS );

		return $count;
	}

	/**
	 * Returns waiting subscriptions for a target product.
	 *
	 * @since 0.1.0
	 * @param int $product_id   Parent product ID.
	 * @param int $variation_id Variation ID.
	 * @param int $limit        Limit.
	 * @param int $offset       Offset.
	 * @return array<int,Oli_BIS_Subscription>
	 */
	public static function get_waiting_for_product( $product_id, $variation_id = 0, $limit = 50, $offset = 0 ) {
		global $wpdb;

		$table = self::table();
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE product_id = %d AND variation_id = %d AND status = %s ORDER BY id ASC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $product_id ),
				absint( $variation_id ),
				'active',
				absint( $limit ),
				absint( $offset )
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static function ( $row ) {
				return new Oli_BIS_Subscription( $row );
			},
			$rows
		);
	}

	/**
	 * Counts waiting subscriptions for a target product.
	 *
	 * @since 0.1.0
	 * @param int $product_id   Parent product ID.
	 * @param int $variation_id Variation ID.
	 * @return int
	 */
	public static function count_waiting_for_product( $product_id, $variation_id = 0 ) {
		global $wpdb;

		$table = self::table();

		return (int) $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(*) FROM {$table} WHERE product_id = %d AND variation_id = %d AND status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $product_id ),
				absint( $variation_id ),
				'active'
			)
		);
	}

	/**
	 * Queries subscriptions for admin and export.
	 *
	 * @since 0.1.0
	 * @param array<string,mixed> $args Query args.
	 * @return array{items:array<int,Oli_BIS_Subscription>,total:int}
	 */
	public static function query( array $args = array() ) {
		global $wpdb;

		$table    = self::table();
		$page     = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
		$per_page = max( 1, min( 200, absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ) ) );
		$offset   = ( $page - 1 ) * $per_page;
		$where    = array( '1=1' );
		$values   = array();

		if ( ! empty( $args['status'] ) ) {
			$where[]  = 'status = %s';
			$values[] = sanitize_key( $args['status'] );
		}

		if ( ! empty( $args['product_id'] ) ) {
			$where[]  = '(product_id = %d OR variation_id = %d)';
			$values[] = absint( $args['product_id'] );
			$values[] = absint( $args['product_id'] );
		}

		if ( ! empty( $args['user_id'] ) ) {
			$where[]  = 'user_id = %d';
			$values[] = absint( $args['user_id'] );
		}

		if ( ! empty( $args['email'] ) ) {
			$where[]  = 'email = %s';
			$values[] = self::normalize_email( $args['email'] );
		}

		if ( ! empty( $args['search'] ) ) {
			$like     = '%' . $wpdb->esc_like( sanitize_text_field( $args['search'] ) ) . '%';
			$where[]  = 'email LIKE %s';
			$values[] = $like;
		}

		$where_sql = implode( ' AND ', $where );
		$count_sql = "SELECT COUNT(*) FROM {$table} WHERE {$where_sql}";
		$list_sql  = "SELECT * FROM {$table} WHERE {$where_sql} ORDER BY id DESC LIMIT %d OFFSET %d";

		if ( ! empty( $values ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $values ) );
			$values[] = $per_page;
			$values[] = $offset;
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, $values ), ARRAY_A );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total = (int) $wpdb->get_var( $count_sql );
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rows = $wpdb->get_results( $wpdb->prepare( $list_sql, $per_page, $offset ), ARRAY_A );
		}

		$items = array();

		if ( is_array( $rows ) ) {
			foreach ( $rows as $row ) {
				$items[] = new Oli_BIS_Subscription( $row );
			}
		}

		return array(
			'items' => $items,
			'total' => $total,
		);
	}

	/**
	 * Returns queued subscriptions for a product batch.
	 *
	 * @since 0.1.0
	 * @param int $product_id   Product ID.
	 * @param int $variation_id Variation ID.
	 * @param int $limit        Limit.
	 * @return array<int,Oli_BIS_Subscription>
	 */
	public static function get_queued( $product_id, $variation_id, $limit ) {
		global $wpdb;

		$table = self::table();
		$rows  = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE product_id = %d AND variation_id = %d AND status = %s ORDER BY id ASC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				absint( $product_id ),
				absint( $variation_id ),
				'queued',
				absint( $limit )
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		return array_map(
			static function ( $row ) {
				return new Oli_BIS_Subscription( $row );
			},
			$rows
		);
	}

	/**
	 * Marks active rows as queued for a product.
	 *
	 * @since 0.1.0
	 * @param int $product_id   Product ID.
	 * @param int $variation_id Variation ID.
	 * @return int
	 */
	public static function queue_active( $product_id, $variation_id ) {
		global $wpdb;

		$table = self::table();
		$now   = self::now();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$updated = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET status = %s, queued_at = %s WHERE product_id = %d AND variation_id = %d AND status = %s AND (notified_at IS NULL OR notified_at < %s)", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'queued',
				$now,
				absint( $product_id ),
				absint( $variation_id ),
				'active',
				gmdate( 'Y-m-d H:i:s', time() - HOUR_IN_SECONDS )
			)
		);

		wp_cache_delete( 'oli_bis_count_' . absint( $product_id ), 'oli_bis' );

		return (int) $updated;
	}

	/**
	 * Reverts queued rows back to active (stock reversed).
	 *
	 * @since 0.1.0
	 * @param int $product_id   Product ID.
	 * @param int $variation_id Variation ID.
	 * @return void
	 */
	public static function unqueue( $product_id, $variation_id ) {
		global $wpdb;

		$table = self::table();

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$table} SET status = %s, queued_at = NULL WHERE product_id = %d AND variation_id = %d AND status = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'active',
				absint( $product_id ),
				absint( $variation_id ),
				'queued'
			)
		);

		wp_cache_delete( 'oli_bis_count_' . absint( $product_id ), 'oli_bis' );
	}

	/**
	 * Deletes stale pending double-opt-in rows.
	 *
	 * @since 0.1.0
	 * @param int $days Days.
	 * @return int
	 */
	public static function delete_unverified_older_than( $days ) {
		global $wpdb;

		$table = self::table();
		$cutoff = gmdate( 'Y-m-d H:i:s', time() - ( absint( $days ) * DAY_IN_SECONDS ) );

		$deleted = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE status = %s AND created_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'pending',
				$cutoff
			)
		);

		return (int) $deleted;
	}

	/**
	 * Unsubscribes every waiting row for an email.
	 *
	 * @since 0.1.0
	 * @param string $email Email.
	 * @return int
	 */
	public static function unsubscribe_email( $email ) {
		global $wpdb;

		$table = self::table();
		$now   = self::now();

		$updated = $wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"UPDATE {$table} SET status = %s, unsubscribed_at = %s WHERE email = %s AND status IN ('pending','active','queued')", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				'unsubscribed',
				$now,
				self::normalize_email( $email )
			)
		);

		return (int) $updated;
	}

	/**
	 * Normalizes an email address.
	 *
	 * @since 0.1.0
	 * @param string $email Email.
	 * @return string
	 */
	public static function normalize_email( $email ) {
		$email = is_string( $email ) ? strtolower( trim( $email ) ) : '';

		if ( function_exists( 'sanitize_email' ) ) {
			$email = sanitize_email( $email );
		}

		return $email;
	}

	/**
	 * Current GMT datetime.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public static function now() {
		return gmdate( 'Y-m-d H:i:s' );
	}

	/**
	 * Random token key stored on the row.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public static function new_token_key() {
		if ( function_exists( 'wp_generate_password' ) ) {
			return wp_generate_password( 32, false, false );
		}

		return bin2hex( random_bytes( 16 ) );
	}

	/**
	 * Encodes attributes to JSON.
	 *
	 * @since 0.1.0
	 * @param mixed $attributes Attributes.
	 * @return string
	 */
	private static function encode_attributes( $attributes ) {
		if ( ! is_array( $attributes ) ) {
			$attributes = array();
		}

		$clean = array();

		foreach ( $attributes as $key => $value ) {
			if ( is_scalar( $value ) ) {
				$clean[ sanitize_key( (string) $key ) ] = sanitize_text_field( (string) $value );
			}
		}

		$json = wp_json_encode( $clean );

		return is_string( $json ) ? $json : '{}';
	}

	/**
	 * Insert/update formats matching upsert row order.
	 *
	 * @since 0.1.0
	 * @return array<int,string>
	 */
	private static function formats() {
		return array( '%d', '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s' );
	}
}
