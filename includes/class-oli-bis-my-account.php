<?php
/**
 * My Account endpoint.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Adds the Stock notifications tab.
 *
 * @since 0.1.0
 */
class Oli_BIS_My_Account {

	const ENDPOINT = 'stock-notifications';

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'add_endpoint' ) );
		add_filter( 'woocommerce_account_menu_items', array( $this, 'menu_items' ) );
		add_action( 'woocommerce_account_' . self::ENDPOINT . '_endpoint', array( $this, 'render' ) );
		add_action( 'template_redirect', array( $this, 'handle_actions' ) );
		add_filter( 'woocommerce_get_query_vars', array( $this, 'query_vars' ) );
	}

	/**
	 * Registers the rewrite endpoint.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function add_endpoint() {
		add_rewrite_endpoint( self::ENDPOINT, EP_ROOT | EP_PAGES );
	}

	/**
	 * Adds the query var.
	 *
	 * @since 0.1.0
	 * @param array<string,string> $vars Query vars.
	 * @return array<string,string>
	 */
	public function query_vars( $vars ) {
		$vars[ self::ENDPOINT ] = self::ENDPOINT;
		return $vars;
	}

	/**
	 * Inserts the menu item.
	 *
	 * @since 0.1.0
	 * @param array<string,string> $items Menu items.
	 * @return array<string,string>
	 */
	public function menu_items( $items ) {
		$new = array();

		foreach ( $items as $key => $label ) {
			$new[ $key ] = $label;

			if ( 'orders' === $key ) {
				$new[ self::ENDPOINT ] = __( 'Stock notifications', 'oli-back-in-stock-notifications' );
			}
		}

		if ( ! isset( $new[ self::ENDPOINT ] ) ) {
			$new[ self::ENDPOINT ] = __( 'Stock notifications', 'oli-back-in-stock-notifications' );
		}

		return $new;
	}

	/**
	 * Handles unsubscribe/resend from My Account.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function handle_actions() {
		if ( empty( $_POST['oli_bis_account_action'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			return;
		}

		if ( ! is_user_logged_in() ) {
			return;
		}

		check_admin_referer( 'oli_bis_account', 'oli_bis_account_nonce' );

		$action = sanitize_key( wp_unslash( $_POST['oli_bis_account_action'] ) );
		$id     = isset( $_POST['subscription_id'] ) ? absint( wp_unslash( $_POST['subscription_id'] ) ) : 0;
		$item   = Oli_BIS_Repository::get( $id );
		$user   = wp_get_current_user();

		if ( ! $item || (int) $item->get( 'user_id' ) !== (int) $user->ID ) {
			wc_add_notice( __( 'This notification could not be updated.', 'oli-back-in-stock-notifications' ), 'error' );
			return;
		}

		if ( 'unsubscribe' === $action ) {
			Oli_BIS_Repository::set_status(
				$id,
				'unsubscribed',
				array( 'unsubscribed_at' => Oli_BIS_Repository::now() )
			);
			wc_add_notice( __( 'Notification cancelled.', 'oli-back-in-stock-notifications' ), 'success' );
		}

		if ( 'resend' === $action && 'pending' === $item->get_status() ) {
			do_action( 'oli_bis_send_verification', $item );
			wc_add_notice( __( 'Verification email sent.', 'oli-back-in-stock-notifications' ), 'success' );
		}
	}

	/**
	 * Renders the endpoint template.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render() {
		$user   = wp_get_current_user();
		$result = Oli_BIS_Repository::query(
			array(
				'user_id'  => (int) $user->ID,
				'per_page' => 100,
			)
		);

		$path = OLI_BIS_PATH . 'templates/my-account.php';

		if ( is_readable( $path ) ) {
			$subscriptions = $result['items'];
			include $path;
		}
	}
}
