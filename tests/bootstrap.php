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

if ( ! function_exists( 'add_action' ) ) {
	/**
	 * Test stub for add_action.
	 *
	 * @param string   $hook          Hook name.
	 * @param callable $callback      Callback.
	 * @param int      $priority      Priority.
	 * @param int      $accepted_args Accepted args.
	 * @return void
	 */
	function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
		unset( $accepted_args );
		$GLOBALS['oli_bis_test_actions'][ $hook ][] = array(
			'callback' => $callback,
			'priority' => $priority,
		);
	}
}

if ( ! function_exists( 'did_action' ) ) {
	/**
	 * Test stub for did_action.
	 *
	 * @param string $hook Hook name.
	 * @return int
	 */
	function did_action( $hook ) {
		return isset( $GLOBALS['oli_bis_test_did_actions'][ $hook ] ) ? (int) $GLOBALS['oli_bis_test_did_actions'][ $hook ] : 0;
	}
}

if ( ! function_exists( 'add_shortcode' ) ) {
	/**
	 * Test stub for add_shortcode.
	 *
	 * @param string   $tag      Shortcode tag.
	 * @param callable $callback Callback.
	 * @return void
	 */
	function add_shortcode( $tag, $callback ) {
		$GLOBALS['shortcode_tags'][ $tag ] = $callback;
	}
}

if ( ! function_exists( 'shortcode_exists' ) ) {
	/**
	 * Test stub for shortcode_exists.
	 *
	 * @param string $tag Shortcode tag.
	 * @return bool
	 */
	function shortcode_exists( $tag ) {
		return isset( $GLOBALS['shortcode_tags'][ $tag ] );
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
require_once dirname( __DIR__ ) . '/includes/class-oli-bis-shortcode.php';
