<?php
/**
 * PHPUnit bootstrap for standalone unit tests.
 *
 * @package Oli_BIS
 */

if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/../' );
}

if ( ! defined( 'OLI_BIS_PATH' ) ) {
	define( 'OLI_BIS_PATH', dirname( __DIR__ ) . '/' );
}

if ( ! function_exists( 'absint' ) ) {
	/**
	 * Test stub for absint.
	 *
	 * @param mixed $value Value.
	 * @return int
	 */
	function absint( $value ) {
		return abs( (int) $value );
	}
}

if ( ! function_exists( 'sanitize_text_field' ) ) {
	/**
	 * Test stub for sanitize_text_field.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	function sanitize_text_field( $value ) {
		return trim( wp_strip_all_tags( (string) $value ) );
	}
}

if ( ! function_exists( 'wp_strip_all_tags' ) ) {
	/**
	 * Test stub for wp_strip_all_tags.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	function wp_strip_all_tags( $value ) {
		return trim( strip_tags( $value ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
	}
}

if ( ! function_exists( 'sanitize_key' ) ) {
	/**
	 * Test stub for sanitize_key.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	function sanitize_key( $value ) {
		$value = strtolower( (string) $value );

		return preg_replace( '/[^a-z0-9_\-]/', '', $value );
	}
}

if ( ! function_exists( 'apply_filters' ) ) {
	/**
	 * Test stub for apply_filters.
	 *
	 * @param string $hook  Hook name.
	 * @param mixed  $value Value.
	 * @return mixed
	 */
	function apply_filters( $hook, $value ) {
		if ( isset( $GLOBALS['oli_bis_test_filters'][ $hook ] ) && is_callable( $GLOBALS['oli_bis_test_filters'][ $hook ] ) ) {
			return call_user_func( $GLOBALS['oli_bis_test_filters'][ $hook ], $value );
		}

		return $value;
	}
}

if ( ! function_exists( 'sanitize_email' ) ) {
	/**
	 * Test stub for sanitize_email.
	 *
	 * @param mixed $value Value.
	 * @return string
	 */
	function sanitize_email( $value ) {
		return strtolower( trim( (string) filter_var( $value, FILTER_SANITIZE_EMAIL ) ) );
	}
}

require_once dirname( __DIR__ ) . '/includes/class-oli-bis-tokens.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-bis-status.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-bis-settings.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-bis-csv.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-bis-rate-limiter.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-bis-schema.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-bis-subscription.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-bis-repository.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-bis-migrator.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-bis-email-content.php';
require_once dirname( __DIR__ ) . '/includes/class-oli-bis-language.php';
