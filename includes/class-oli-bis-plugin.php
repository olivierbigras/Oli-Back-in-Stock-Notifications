<?php
/**
 * Main plugin controller.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers components after WooCommerce loads.
 *
 * @since 0.1.0
 */
class Oli_BIS_Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Oli_BIS_Plugin|null
	 */
	private static $instance = null;

	/**
	 * Returns the shared instance.
	 *
	 * @since 0.1.0
	 * @return Oli_BIS_Plugin
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks components.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function init() {
		Oli_BIS_Installer::maybe_upgrade();

		( new Oli_BIS_I18n() )->register();
		( new Oli_BIS_Frontend() )->register();
		( new Oli_BIS_Ajax() )->register();
		( new Oli_BIS_My_Account() )->register();
		( new Oli_BIS_Shortcode() )->register();
		( new Oli_BIS_Block() )->register();
		( new Oli_BIS_Restock() )->register();
		( new Oli_BIS_Scheduler() )->register();
		( new Oli_BIS_Emails() )->register();
		( new Oli_BIS_Privacy() )->register();
		( new Oli_BIS_Attribution() )->register();
		( new Oli_BIS_Public_Actions() )->register();

		if ( is_admin() ) {
			( new Oli_BIS_Admin() )->register();
			( new Oli_BIS_Settings_Page() )->register();
			( new Oli_BIS_Product_Admin() )->register();
		}
	}
}
