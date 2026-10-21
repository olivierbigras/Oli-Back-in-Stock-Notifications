<?php
/**
 * Generic legacy data import.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Maps previous waitlist plugin data into this plugin.
 *
 * No third-party names or keys are bundled. Sites provide mappings through
 * the `oli_bis_legacy_map` filter (empty by default). Already imported
 * subscriber rows keep working regardless of the stored legacy_source slug.
 * Source tables, options, and metas are never deleted.
 *
 * @since 0.1.0
 */
class Oli_BIS_Migrator {

	const DEFAULT_SOURCE = 'legacy';

	/**
	 * Returns the sanitized legacy map. Empty by default.
	 *
	 * @since 0.1.2
	 * @return array{options:array<string,string>,metas:array<string,string>,shortcodes:array<string,string>,tables:array<int,string>,source:string}
	 */
	public static function legacy_map() {
		$defaults = array(
			'options'    => array(),
			'metas'      => array(),
			'shortcodes' => array(),
			'tables'     => array(),
			'source'     => self::DEFAULT_SOURCE,
		);

		/**
		 * Filters the generic map of previous plugin keys to this plugin.
		 *
		 * Empty by default. Use it to list old option keys, post metas, and
		 * shortcodes (and optionally table suffixes) toward the current ones.
		 * Previously imported rows are not rewritten.
		 *
		 * @since 0.1.2
		 *
		 * @param array $map {
		 *     @type array<string,string> $options    Old option key => settings key.
		 *     @type array<string,string> $metas      Old post meta key => new post meta key.
		 *     @type array<string,string> $shortcodes Old shortcode tag => oli_bis_notify_form.
		 *     @type array<int,string>    $tables     Optional table suffixes without the site prefix.
		 *     @type string               $source     Optional slug stored on new imports. Default legacy.
		 * }
		 */
		$map = apply_filters( 'oli_bis_legacy_map', $defaults );

		if ( ! is_array( $map ) ) {
			return $defaults;
		}

		$clean = $defaults;

		foreach ( array( 'options', 'metas', 'shortcodes' ) as $group ) {
			$clean[ $group ] = self::sanitize_key_map( isset( $map[ $group ] ) ? $map[ $group ] : array() );
		}

		if ( ! empty( $map['tables'] ) && is_array( $map['tables'] ) ) {
			foreach ( $map['tables'] as $suffix ) {
				$suffix = sanitize_key( (string) $suffix );
				if ( $suffix ) {
					$clean['tables'][] = $suffix;
				}
			}
			$clean['tables'] = array_values( array_unique( $clean['tables'] ) );
		}

		if ( ! empty( $map['source'] ) ) {
			$source = sanitize_key( (string) $map['source'] );
			if ( $source ) {
				$clean['source'] = $source;
			}
		}

		return $clean;
	}

	/**
	 * Whether any legacy mapping is configured.
	 *
	 * @since 0.1.2
	 * @param array<string,mixed>|null $map Optional map.
	 * @return bool
	 */
	public static function is_configured( $map = null ) {
		$map = is_array( $map ) ? $map : self::legacy_map();

		return ! empty( $map['options'] ) || ! empty( $map['metas'] ) || ! empty( $map['shortcodes'] ) || ! empty( $map['tables'] );
	}

	/**
	 * Shortcode aliases that should render the notify form.
	 *
	 * @since 0.1.2
	 * @param array<string,mixed>|null $map Optional map.
	 * @return array<string,string>
	 */
	public static function shortcode_aliases( $map = null ) {
		$map     = is_array( $map ) ? $map : self::legacy_map();
		$aliases = array();

		if ( empty( $map['shortcodes'] ) || ! is_array( $map['shortcodes'] ) ) {
			return $aliases;
		}

		foreach ( $map['shortcodes'] as $old => $new ) {
			$old = sanitize_key( (string) $old );
			$new = sanitize_key( (string) $new );

			if ( $old && 'oli_bis_notify_form' === $new ) {
				$aliases[ $old ] = $new;
			}
		}

		return $aliases;
	}

	/**
	 * Candidate legacy table suffixes to inspect.
	 *
	 * @since 0.1.0
	 * @return array<int,string>
	 */
	public static function candidate_suffixes() {
		return self::legacy_map()['tables'];
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
	 * Detects mapped legacy tables and options without writing.
	 *
	 * @since 0.1.1
	 * @return array<string,mixed>
	 */
	public static function detect() {
		$map    = self::legacy_map();
		$tables = self::detect_legacy_tables();
		$first  = isset( $tables[0] ) ? $tables[0] : '';
		$count  = 0;

		if ( $tables ) {
			global $wpdb;

			foreach ( $tables as $table ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$count += (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
			}
		}

		$options = array();

		foreach ( $map['options'] as $old_key => $new_key ) {
			unset( $new_key );
			$options[ $old_key ] = function_exists( 'get_option' ) ? get_option( $old_key, null ) : null;
		}

		return array(
			'table'        => $first,
			'tables'       => $tables,
			'table_exists' => ! empty( $tables ),
			'row_count'    => $count,
			'options'      => $options,
			'other_tables' => $tables,
			'source'       => $map['source'],
			'configured'   => self::is_configured( $map ),
			'map'          => $map,
		);
	}

	/**
	 * Maps a simple waitlist row (email, product_id, optional is_sent).
	 *
	 * Some previous plugins store the subscribed product or variation ID in
	 * `product_id` only. Pass $parent_id when that ID is a variation.
	 *
	 * @since 0.1.2
	 * @param array<string,mixed> $row       Source row.
	 * @param int                 $parent_id Parent product ID when $row product is a variation.
	 * @param string              $source    Legacy source slug.
	 * @return array<string,mixed>|null
	 */
	public static function map_simple_waitlist_row( array $row, $parent_id = 0, $source = self::DEFAULT_SOURCE ) {
		$email = Oli_BIS_Repository::normalize_email( isset( $row['email'] ) ? (string) $row['email'] : '' );
		$raw   = absint( isset( $row['product_id'] ) ? $row['product_id'] : 0 );

		if ( ! $email || ! $raw ) {
			return null;
		}

		$source = self::normalize_source( $source );
		$ids    = self::resolve_product_ids( $raw, absint( $parent_id ) );
		$sent   = ! empty( $row['is_sent'] );

		$created = self::normalize_datetime( isset( $row['date_added'] ) ? $row['date_added'] : '' );
		$sent_at = self::normalize_datetime( isset( $row['date_sent'] ) ? $row['date_sent'] : '' );

		return array(
			'email'         => $email,
			'product_id'    => $ids['product_id'],
			'variation_id'  => $ids['variation_id'],
			'user_id'       => 0,
			'status'        => $sent ? 'notified' : 'active',
			'language'      => '',
			'source'        => $source,
			'consent'       => true,
			'created_at'    => $created,
			'confirmed_at'  => $created,
			'notified_at'   => $sent ? $sent_at : '',
			'legacy_source' => $source,
			'legacy_id'     => absint( isset( $row['id'] ) ? $row['id'] : 0 ),
		);
	}

	/**
	 * Resolves parent/variation IDs from a single stored product ID.
	 *
	 * @since 0.1.1
	 * @param int $product_id Stored product ID.
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
	 * Maps old option values into current settings keys.
	 *
	 * @since 0.1.2
	 * @param array<string,mixed>       $options Raw option values keyed by the old name.
	 * @param array<string,string>|null $key_map Old option key => settings key.
	 * @return array<string,mixed>
	 */
	public static function map_options( array $options, $key_map = null ) {
		$key_map  = is_array( $key_map ) ? self::sanitize_key_map( $key_map ) : self::legacy_map()['options'];
		$mapped   = array();
		$defaults = Oli_BIS_Settings::defaults();

		foreach ( $key_map as $old_key => $new_key ) {
			if ( ! array_key_exists( $old_key, $options ) || ! array_key_exists( $new_key, $defaults ) ) {
				continue;
			}

			$value = $options[ $old_key ];

			if ( null === $value ) {
				continue;
			}

			if ( in_array( $defaults[ $new_key ], array( 'yes', 'no' ), true ) ) {
				$mapped[ $new_key ] = self::to_yes_no( $value );
				continue;
			}

			if ( is_int( $defaults[ $new_key ] ) ) {
				$mapped[ $new_key ] = absint( $value );
				continue;
			}

			if ( '' === $value || ! is_scalar( $value ) ) {
				continue;
			}

			$mapped[ $new_key ] = (string) $value;
		}

		return $mapped;
	}

	/**
	 * Maps old post meta values into current meta keys.
	 *
	 * @since 0.1.2
	 * @param array<string,mixed>       $metas   Raw meta values keyed by the old name.
	 * @param array<string,string>|null $key_map Old meta key => new meta key.
	 * @return array<string,mixed>
	 */
	public static function map_metas( array $metas, $key_map = null ) {
		$key_map = is_array( $key_map ) ? self::sanitize_key_map( $key_map ) : self::legacy_map()['metas'];
		$mapped  = array();

		foreach ( $key_map as $old_key => $new_key ) {
			if ( ! array_key_exists( $old_key, $metas ) ) {
				continue;
			}

			$mapped[ $new_key ] = $metas[ $old_key ];
		}

		return $mapped;
	}

	/**
	 * Classifies a mapped row for import vs skip.
	 *
	 * @since 0.1.1
	 * @param array<string,mixed>|null $mapped              Mapped row.
	 * @param array<string,int>        $existing_keys       email|product|variation => id.
	 * @param array<int,int>           $imported_legacy_ids Already imported legacy IDs (any source).
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
	 * Duplicate rows in the same batch are counted as skip after the first.
	 * Already imported rows (any stored legacy_source) stay skipped.
	 *
	 * @since 0.1.1
	 * @param array<int,array<string,mixed>> $rows                Source rows.
	 * @param array<string,int>              $existing_keys       email|product|variation => id.
	 * @param array<int,int>                 $imported_legacy_ids Already imported legacy IDs.
	 * @param callable|null                  $parent_resolver     Optional fn(product_id): parent_id.
	 * @param string                         $source              Legacy source slug for new rows.
	 * @return array<string,mixed>
	 */
	public static function plan_rows( array $rows, array $existing_keys = array(), array $imported_legacy_ids = array(), $parent_resolver = null, $source = self::DEFAULT_SOURCE ) {
		$report = array(
			'import'  => 0,
			'skip'    => 0,
			'invalid' => 0,
			'mapped'  => array(),
		);

		$source = self::normalize_source( $source );

		foreach ( $rows as $row ) {
			$raw    = absint( isset( $row['product_id'] ) ? $row['product_id'] : 0 );
			$parent = absint( isset( $row['parent_id'] ) ? $row['parent_id'] : 0 );

			if ( is_callable( $parent_resolver ) ) {
				$parent = absint( call_user_func( $parent_resolver, $raw ) );
			}

			$mapped = self::map_legacy_row( $row, $parent, $source );
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
	 * Runs a dry-run or import. Never deletes source rows, options, or metas.
	 *
	 * @since 0.1.1
	 * @param bool $dry_run When true, only report counts.
	 * @return array<string,mixed>
	 */
	public static function run( $dry_run = true ) {
		$detect = self::detect();
		$map    = isset( $detect['map'] ) && is_array( $detect['map'] ) ? $detect['map'] : self::legacy_map();

		$report = array(
			'dry_run'  => (bool) $dry_run,
			'detected' => $detect,
			'import'   => 0,
			'skip'     => 0,
			'invalid'  => 0,
			'options'  => 0,
			'metas'    => 0,
			'error'    => '',
		);

		if ( empty( $detect['configured'] ) ) {
			$report['error'] = 'not_configured';
			return $report;
		}

		global $wpdb;

		$rows = array();

		foreach ( $detect['tables'] as $table ) {
			$batch = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id ASC", ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared

			if ( is_array( $batch ) ) {
				$rows = array_merge( $rows, $batch );
			}
		}

		$plan = self::plan_rows(
			$rows,
			Oli_BIS_Repository::existing_unique_keys(),
			Oli_BIS_Repository::legacy_ids(),
			array( __CLASS__, 'lookup_parent_id' ),
			isset( $map['source'] ) ? $map['source'] : self::DEFAULT_SOURCE
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
			$report['options'] = self::import_options( $detect['options'], $map['options'] ) ? 1 : 0;
			$report['metas']   = self::import_metas( $map['metas'] );
		} else {
			if ( self::map_options( $detect['options'], $map['options'] ) ) {
				$report['options'] = 1;
			}
			if ( ! empty( $map['metas'] ) ) {
				$report['metas'] = 1;
			}
		}

		return $report;
	}

	/**
	 * Copies mapped options into empty or boolean local settings.
	 *
	 * @since 0.1.1
	 * @param array<string,mixed>       $options Detected options.
	 * @param array<string,string>|null $key_map Old option key => settings key.
	 * @return bool
	 */
	public static function import_options( array $options, $key_map = null ) {
		$mapped = self::map_options( $options, $key_map );

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
	 * Copies mapped post metas when the destination key is empty. Never deletes source metas.
	 *
	 * @since 0.1.2
	 * @param array<string,string>|null $key_map Old meta key => new meta key.
	 * @return int
	 */
	public static function import_metas( $key_map = null ) {
		$key_map = is_array( $key_map ) ? self::sanitize_key_map( $key_map ) : self::legacy_map()['metas'];

		if ( empty( $key_map ) || ! function_exists( 'get_post_meta' ) || ! function_exists( 'update_post_meta' ) ) {
			return 0;
		}

		global $wpdb;

		$changed = 0;

		foreach ( $key_map as $old_key => $new_key ) {
			if ( $old_key === $new_key ) {
				continue;
			}

			$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->prepare(
					"SELECT post_id, meta_value FROM {$wpdb->postmeta} WHERE meta_key = %s",
					$old_key
				),
				ARRAY_A
			);

			if ( ! is_array( $rows ) ) {
				continue;
			}

			foreach ( $rows as $row ) {
				$post_id  = absint( isset( $row['post_id'] ) ? $row['post_id'] : 0 );
				$existing = $post_id ? get_post_meta( $post_id, $new_key, true ) : '';

				if ( ! $post_id || ( '' !== $existing && false !== $existing ) ) {
					continue;
				}

				update_post_meta( $post_id, $new_key, $row['meta_value'] );
				++$changed;
			}
		}

		return $changed;
	}

	/**
	 * Maps a generic legacy row into the current schema.
	 *
	 * @since 0.1.0
	 * @param array<string,mixed> $row       Legacy row.
	 * @param int                 $parent_id Parent product ID when the stored ID is a variation.
	 * @param string              $source    Legacy source slug.
	 * @return array<string,mixed>|null
	 */
	public static function map_legacy_row( array $row, $parent_id = 0, $source = self::DEFAULT_SOURCE ) {
		$parent_id = absint( isset( $row['parent_id'] ) ? $row['parent_id'] : $parent_id );
		$source    = self::normalize_source( $source );

		if ( isset( $row['is_sent'] ) || ( isset( $row['email'] ) && isset( $row['product_id'] ) && isset( $row['date_added'] ) ) ) {
			return self::map_simple_waitlist_row( $row, $parent_id, $source );
		}

		$email = '';

		foreach ( array( 'email', 'user_email', 'subscriber_email' ) as $key ) {
			if ( ! empty( $row[ $key ] ) ) {
				$email = Oli_BIS_Repository::normalize_email( (string) $row[ $key ] );
				break;
			}
		}

		$product_id = 0;

		foreach ( array( 'product_id', 'post_id' ) as $key ) {
			if ( ! empty( $row[ $key ] ) ) {
				$product_id = absint( $row[ $key ] );
				break;
			}
		}

		if ( ! $email || ! $product_id ) {
			return null;
		}

		$variation_id = 0;

		foreach ( array( 'variation_id' ) as $key ) {
			if ( ! empty( $row[ $key ] ) ) {
				$variation_id = absint( $row[ $key ] );
			}
		}

		if ( $parent_id && $parent_id !== $product_id && ! $variation_id ) {
			$ids          = self::resolve_product_ids( $product_id, $parent_id );
			$product_id   = $ids['product_id'];
			$variation_id = $ids['variation_id'];
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
			'email'         => $email,
			'product_id'    => $product_id,
			'variation_id'  => $variation_id,
			'user_id'       => isset( $row['user_id'] ) ? absint( $row['user_id'] ) : 0,
			'status'        => $status,
			'language'      => isset( $row['language'] ) ? sanitize_text_field( (string) $row['language'] ) : '',
			'source'        => $source,
			'legacy_source' => $source,
			'legacy_id'     => absint( isset( $row['id'] ) ? $row['id'] : 0 ),
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

	/**
	 * Sanitizes an old => new key map.
	 *
	 * @since 0.1.2
	 * @param mixed $map Raw map.
	 * @return array<string,string>
	 */
	private static function sanitize_key_map( $map ) {
		$clean = array();

		if ( ! is_array( $map ) ) {
			return $clean;
		}

		foreach ( $map as $old => $new ) {
			$old = sanitize_key( (string) $old );
			$new = sanitize_key( (string) $new );

			if ( $old && $new ) {
				$clean[ $old ] = $new;
			}
		}

		return $clean;
	}

	/**
	 * Normalizes a legacy source slug.
	 *
	 * @since 0.1.2
	 * @param string $source Source.
	 * @return string
	 */
	private static function normalize_source( $source ) {
		$source = sanitize_key( (string) $source );

		return $source ? $source : self::DEFAULT_SOURCE;
	}

	/**
	 * Converts a raw value to yes/no.
	 *
	 * @since 0.1.2
	 * @param mixed $value Raw value.
	 * @return string
	 */
	private static function to_yes_no( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? 'yes' : 'no';
		}

		$value = strtolower( trim( (string) $value ) );

		if ( in_array( $value, array( '', '0', 'no', 'off', 'false' ), true ) ) {
			return 'no';
		}

		return 'yes';
	}
}
