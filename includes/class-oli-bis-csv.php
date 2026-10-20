<?php
/**
 * CSV export helpers.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Formats subscription rows as CSV without I/O.
 *
 * @since 0.1.0
 */
class Oli_BIS_CSV {

	/**
	 * Default column headers.
	 *
	 * @since 0.1.0
	 * @return array<int,string>
	 */
	public static function headers() {
		return array(
			'id',
			'email',
			'product_id',
			'variation_id',
			'user_id',
			'status',
			'language',
			'source',
			'created_at',
			'confirmed_at',
			'notified_at',
			'converted_order_id',
		);
	}

	/**
	 * Converts rows to a CSV string.
	 *
	 * @since 0.1.0
	 * @param array<int,array<string,mixed>> $rows    Data rows.
	 * @param array<int,string>              $headers Optional headers.
	 * @return string
	 */
	public static function to_string( array $rows, array $headers = array() ) {
		if ( empty( $headers ) ) {
			$headers = self::headers();
		}

		$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen

		if ( false === $handle ) {
			return '';
		}

		fputcsv( $handle, $headers );

		foreach ( $rows as $row ) {
			$line = array();

			foreach ( $headers as $header ) {
				$value  = isset( $row[ $header ] ) ? $row[ $header ] : '';
				$line[] = is_scalar( $value ) ? (string) $value : '';
			}

			fputcsv( $handle, $line );
		}

		rewind( $handle );
		$csv = stream_get_contents( $handle );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

		return is_string( $csv ) ? $csv : '';
	}
}
