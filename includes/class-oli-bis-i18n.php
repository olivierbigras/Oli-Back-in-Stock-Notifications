<?php
/**
 * Translations.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Loads the plugin text domain.
 *
 * @since 0.1.0
 */
class Oli_BIS_I18n {

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
	}

	/**
	 * Loads translations from /languages.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function load_textdomain() {
		load_plugin_textdomain(
			'oli-back-in-stock-notifications',
			false,
			dirname( plugin_basename( OLI_BIS_FILE ) ) . '/languages'
		);
	}
}
