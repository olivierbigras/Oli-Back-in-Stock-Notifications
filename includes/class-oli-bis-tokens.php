<?php
/**
 * Signed token helpers.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Creates and verifies HMAC tokens for confirm/unsubscribe links.
 *
 * @since 0.1.0
 */
class Oli_BIS_Tokens {

	/**
	 * Creates a signed token for a payload.
	 *
	 * @since 0.1.0
	 * @param array  $payload Data to sign.
	 * @param string $secret  HMAC secret.
	 * @param int    $ttl     Lifetime in seconds, or 0 for no expiry.
	 * @return string
	 */
	public static function create( array $payload, $secret, $ttl = 0 ) {
		$data = array(
			'p' => $payload,
			'e' => $ttl > 0 ? time() + (int) $ttl : 0,
		);

		$json = self::encode( $data );
		$sig  = hash_hmac( 'sha256', $json, (string) $secret );

		return self::base64url_encode( $json ) . '.' . $sig;
	}

	/**
	 * Verifies a token and returns its payload, or null on failure.
	 *
	 * @since 0.1.0
	 * @param string $token  Token string.
	 * @param string $secret HMAC secret.
	 * @return array|null
	 */
	public static function verify( $token, $secret ) {
		$parts = explode( '.', (string) $token, 2 );

		if ( 2 !== count( $parts ) || '' === $parts[0] || '' === $parts[1] ) {
			return null;
		}

		$json = self::base64url_decode( $parts[0] );

		if ( false === $json ) {
			return null;
		}

		$expected = hash_hmac( 'sha256', $json, (string) $secret );

		if ( ! hash_equals( $expected, $parts[1] ) ) {
			return null;
		}

		$data = self::decode( $json );

		if ( ! is_array( $data ) || ! isset( $data['p'] ) || ! is_array( $data['p'] ) ) {
			return null;
		}

		if ( ! empty( $data['e'] ) && time() > (int) $data['e'] ) {
			return null;
		}

		return $data['p'];
	}

	/**
	 * Encodes data as JSON.
	 *
	 * @since 0.1.0
	 * @param array $data Data.
	 * @return string
	 */
	private static function encode( array $data ) {
		if ( function_exists( 'wp_json_encode' ) ) {
			$json = wp_json_encode( $data );
			return is_string( $json ) ? $json : '{}';
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- WP-independent unit tests.
		$json = json_encode( $data );

		return is_string( $json ) ? $json : '{}';
	}

	/**
	 * Decodes a JSON string.
	 *
	 * @since 0.1.0
	 * @param string $json JSON.
	 * @return mixed
	 */
	private static function decode( $json ) {
		return json_decode( $json, true );
	}

	/**
	 * Base64 URL-safe encode.
	 *
	 * @since 0.1.0
	 * @param string $value Raw value.
	 * @return string
	 */
	public static function base64url_encode( $value ) {
		return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
	}

	/**
	 * Base64 URL-safe decode.
	 *
	 * @since 0.1.0
	 * @param string $value Encoded value.
	 * @return string|false
	 */
	public static function base64url_decode( $value ) {
		$remainder = strlen( $value ) % 4;

		if ( $remainder ) {
			$value .= str_repeat( '=', 4 - $remainder );
		}

		return base64_decode( strtr( $value, '-_', '+/' ) ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
	}
}
