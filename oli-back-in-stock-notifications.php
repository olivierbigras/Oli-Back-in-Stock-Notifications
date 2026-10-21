<?php
/**
 * Plugin Name:       Oli Back in Stock Notifications for WooCommerce
 * Plugin URI:        https://github.com/olivierbigras/Oli-Back-in-Stock-Notifications
 * Description:       Let customers sign up to be notified when out-of-stock WooCommerce products are available again.
 * Version:           0.1.4
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Requires Plugins:  woocommerce
 * Author:            Olivier Bigras
 * Author URI:        https://olivierbigras.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       oli-back-in-stock-notifications
 * Domain Path:       /languages
 * WC requires at least: 9.0
 * WC tested up to:   11.2
 *
 * @package Oli_BIS
 */

defined( 'ABSPATH' ) || exit;

define( 'OLI_BIS_VERSION', '0.1.4' );
define( 'OLI_BIS_FILE', __FILE__ );
define( 'OLI_BIS_PATH', plugin_dir_path( __FILE__ ) );
define( 'OLI_BIS_URL', plugin_dir_url( __FILE__ ) );
define( 'OLI_BIS_SLUG', 'oli-back-in-stock-notifications' );

require_once OLI_BIS_PATH . 'includes/class-oli-bis-autoloader.php';

Oli_BIS_Autoloader::register();

/**
 * Returns the main plugin instance.
 *
 * @since 0.1.0
 * @return Oli_BIS_Plugin
 */
function oli_bis() {
	return Oli_BIS_Plugin::instance();
}

register_activation_hook( __FILE__, array( 'Oli_BIS_Installer', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'Oli_BIS_Installer', 'deactivate' ) );

add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( '\Automattic\WooCommerce\Utilities\FeaturesUtil' ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', OLI_BIS_FILE, true );
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', OLI_BIS_FILE, true );
		}
	}
);

add_action( 'plugins_loaded', 'oli_bis_boot', 20 );

/**
 * Boots the plugin after WooCommerce is available.
 *
 * @since 0.1.0
 * @return void
 */
function oli_bis_boot() {
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'oli_bis_missing_woocommerce_notice' );
		return;
	}

	oli_bis()->init();
}

/**
 * Shows an admin notice when WooCommerce is missing.
 *
 * @since 0.1.0
 * @return void
 */
function oli_bis_missing_woocommerce_notice() {
	if ( ! current_user_can( 'activate_plugins' ) ) {
		return;
	}

	echo '<div class="notice notice-error"><p>';
	echo esc_html__( 'Oli Back in Stock Notifications for WooCommerce requires WooCommerce to be installed and active.', 'oli-back-in-stock-notifications' );
	echo '</p></div>';
}
