<?php
/**
 * Signup rate limiter.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Limits signup attempts per identifier.
 *
 * @since 0.1.0
 */
class Oli_BIS_Rate_Limiter {

	/**
	 * Whether another attempt is allowed.
	 *
	 * @since 0.1.0
	 * @param int $hits Current hit count.
	 * @param int $max  Maximum allowed hits.
	 * @return bool
	 */
	public static function is_allowed( $hits, $max ) {
		return (int) $hits < (int) $max;
	}

	/**
	 * Transient key for an identifier.
	 *
	 * @since 0.1.0
	 * @param string $identifier IP or hashed identifier.
	 * @return string
	 */
	public static function key( $identifier ) {
		return 'oli_bis_rl_' . md5( (string) $identifier );
	}

	/**
	 * Checks and increments the rate limit for the current request.
	 *
	 * @since 0.1.0
	 * @param string $identifier Identifier (usually IP).
	 * @return bool
	 */
	public static function consume( $identifier ) {
		$max    = (int) Oli_BIS_Settings::get( 'rate_limit_max', 8 );
		$window = (int) Oli_BIS_Settings::get( 'rate_limit_window', 900 );
		$key    = self::key( $identifier );
		$hits   = (int) get_transient( $key );

		if ( ! self::is_allowed( $hits, $max ) ) {
			return false;
		}

		set_transient( $key, $hits + 1, $window );

		return true;
	}

	/**
	 * Best-effort client IP, sanitized.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public static function request_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		if ( $ip && function_exists( 'rest_is_ip_address' ) && ! rest_is_ip_address( $ip ) ) {
			return '';
		}

		return $ip;
	}
}
