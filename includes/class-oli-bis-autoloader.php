<?php
/**
 * Plugin autoloader.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Maps Oli_BIS_* classes to files.
 *
 * @since 0.1.0
 */
class Oli_BIS_Autoloader {

	/**
	 * Registers the autoloader.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public static function register() {
		spl_autoload_register( array( __CLASS__, 'load' ) );
	}

	/**
	 * Loads a class file when needed.
	 *
	 * @since 0.1.0
	 * @param string $class Class name.
	 * @return void
	 */
	public static function load( $class ) {
		if ( 0 !== strpos( $class, 'Oli_BIS_' ) ) {
			return;
		}

		$relative = 'class-' . str_replace( '_', '-', strtolower( $class ) ) . '.php';
		$paths    = array(
			OLI_BIS_PATH . 'includes/' . $relative,
			OLI_BIS_PATH . 'includes/admin/' . $relative,
			OLI_BIS_PATH . 'includes/emails/' . $relative,
		);

		foreach ( $paths as $path ) {
			if ( is_readable( $path ) ) {
				require_once $path;
				return;
			}
		}
	}
}
