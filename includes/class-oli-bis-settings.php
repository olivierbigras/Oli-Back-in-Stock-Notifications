<?php
/**
 * Plugin settings.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads and sanitizes plugin options.
 *
 * @since 0.1.0
 */
class Oli_BIS_Settings {

	const OPTION_KEY = 'oli_bis_settings';

	/**
	 * Default settings.
	 *
	 * @since 0.1.0
	 * @return array<string,mixed>
	 */
	public static function defaults() {
		return array(
			'double_optin'             => 'no',
			'delete_unverified_days'   => 7,
			'require_account'          => 'no',
			'allow_backorder'          => 'no',
			'min_stock'                => 0,
			'gdpr_checkbox'            => 'no',
			'gdpr_text'                => 'I agree to receive a back-in-stock email for this product.',
			'show_count'               => 'no',
			'form_text'                => 'Notify me when this product is back in stock.',
			'form_text_signed_up'      => 'You will be notified when this product is back in stock.',
			'button_text'              => 'Notify me',
			'catalog_prompt'           => 'no',
			'catalog_text'             => 'Get notified when back in stock',
			'admin_signup_email'       => 'no',
			'batch_size'               => 50,
			'rate_limit_max'           => 8,
			'rate_limit_window'        => 900,
			'delete_data_on_uninstall' => 'no',
			'auto_cleanup_notified'    => 'no',
			'cleanup_notified_days'    => 30,
			'notification_body'        => '',
			'signup_body'              => '',
		);
	}

	/**
	 * Returns merged settings.
	 *
	 * @since 0.1.0
	 * @return array<string,mixed>
	 */
	public static function all() {
		$stored = array();

		if ( function_exists( 'get_option' ) ) {
			$option = get_option( self::OPTION_KEY, array() );
			if ( is_array( $option ) ) {
				$stored = $option;
			}
		}

		return array_merge( self::defaults(), $stored );
	}

	/**
	 * Returns one setting.
	 *
	 * @since 0.1.0
	 * @param string $key      Setting key.
	 * @param mixed  $fallback Fallback.
	 * @return mixed
	 */
	public static function get( $key, $fallback = null ) {
		$all = self::all();

		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}

		return $fallback;
	}

	/**
	 * Whether a yes/no setting is enabled.
	 *
	 * @since 0.1.0
	 * @param string $key Setting key.
	 * @return bool
	 */
	public static function enabled( $key ) {
		return 'yes' === self::get( $key, 'no' );
	}

	/**
	 * Sanitizes a settings array.
	 *
	 * @since 0.1.0
	 * @param array $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function sanitize( array $input ) {
		$defaults = self::defaults();
		$output   = $defaults;

		$yes_no = array(
			'double_optin',
			'require_account',
			'allow_backorder',
			'gdpr_checkbox',
			'show_count',
			'catalog_prompt',
			'admin_signup_email',
			'delete_data_on_uninstall',
			'auto_cleanup_notified',
		);

		foreach ( $yes_no as $key ) {
			$output[ $key ] = ( isset( $input[ $key ] ) && 'yes' === $input[ $key ] ) ? 'yes' : 'no';
		}

		$output['delete_unverified_days'] = isset( $input['delete_unverified_days'] ) ? max( 1, absint( $input['delete_unverified_days'] ) ) : $defaults['delete_unverified_days'];
		$output['min_stock']              = isset( $input['min_stock'] ) ? max( 0, absint( $input['min_stock'] ) ) : $defaults['min_stock'];
		$output['batch_size']             = isset( $input['batch_size'] ) ? min( 200, max( 1, absint( $input['batch_size'] ) ) ) : $defaults['batch_size'];
		$output['rate_limit_max']         = isset( $input['rate_limit_max'] ) ? min( 100, max( 1, absint( $input['rate_limit_max'] ) ) ) : $defaults['rate_limit_max'];
		$output['rate_limit_window']      = isset( $input['rate_limit_window'] ) ? min( 86400, max( 60, absint( $input['rate_limit_window'] ) ) ) : $defaults['rate_limit_window'];
		$output['cleanup_notified_days']  = isset( $input['cleanup_notified_days'] ) ? max( 1, absint( $input['cleanup_notified_days'] ) ) : $defaults['cleanup_notified_days'];

		$text_keys = array( 'gdpr_text', 'form_text', 'form_text_signed_up', 'button_text', 'catalog_text' );

		foreach ( $text_keys as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$output[ $key ] = self::sanitize_text( $input[ $key ] );
			}
		}

		foreach ( array( 'notification_body', 'signup_body' ) as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$output[ $key ] = self::sanitize_template( $input[ $key ] );
			}
		}

		return $output;
	}

	/**
	 * Sanitizes a text setting.
	 *
	 * @since 0.1.0
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_text( $value ) {
		$value = is_string( $value ) ? $value : '';

		if ( function_exists( 'sanitize_text_field' ) ) {
			return sanitize_text_field( $value );
		}

		return trim( strip_tags( $value ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
	}

	/**
	 * Sanitizes a multiline email template.
	 *
	 * @since 0.1.1
	 * @param mixed $value Raw value.
	 * @return string
	 */
	public static function sanitize_template( $value ) {
		$value = is_string( $value ) ? $value : '';

		if ( function_exists( 'wp_kses_post' ) ) {
			return wp_kses_post( $value );
		}

		return trim( $value );
	}

	/**
	 * Persists settings.
	 *
	 * @since 0.1.0
	 * @param array $input Raw input.
	 * @return array<string,mixed>
	 */
	public static function update( array $input ) {
		$clean = self::sanitize( $input );

		if ( function_exists( 'update_option' ) ) {
			update_option( self::OPTION_KEY, $clean );
		}

		return $clean;
	}
}
