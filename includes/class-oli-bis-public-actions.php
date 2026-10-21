<?php
/**
 * Public confirm and unsubscribe endpoints.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles signed one-click links.
 *
 * @since 0.1.0
 */
class Oli_BIS_Public_Actions {

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_action( 'template_redirect', array( $this, 'handle' ) );
	}

	/**
	 * Processes confirm/unsubscribe query args.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function handle() {
		if ( empty( $_GET['oli_bis_action'] ) || empty( $_GET['oli_bis_token'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$action  = sanitize_key( wp_unslash( $_GET['oli_bis_action'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$token   = sanitize_text_field( wp_unslash( $_GET['oli_bis_token'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$payload = Oli_BIS_Tokens::verify( $token, wp_salt( 'auth' ) );

		if ( ! $payload || empty( $payload['sid'] ) || empty( $payload['act'] ) || $payload['act'] !== $action ) {
			wc_add_notice( __( 'This link is invalid or has expired.', 'oli-back-in-stock-notifications' ), 'error' );
			wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
			exit;
		}

		$subscription = Oli_BIS_Repository::get( absint( $payload['sid'] ) );

		if ( ! $subscription ) {
			wc_add_notice( __( 'This notification request could not be found.', 'oli-back-in-stock-notifications' ), 'error' );
			wp_safe_redirect( wc_get_page_permalink( 'shop' ) );
			exit;
		}

		if ( 'confirm' === $action ) {
			$this->confirm( $subscription );
		} elseif ( 'unsubscribe' === $action ) {
			$this->unsubscribe( $subscription, ! empty( $payload['all'] ) );
		} else {
			return;
		}

		wp_safe_redirect( $this->redirect_url() );
		exit;
	}

	/**
	 * Confirms a pending subscription.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $subscription Subscription.
	 * @return void
	 */
	private function confirm( $subscription ) {
		if ( 'pending' === $subscription->get_status() ) {
			Oli_BIS_Repository::set_status(
				$subscription->get_id(),
				'active',
				array( 'confirmed_at' => Oli_BIS_Repository::now() )
			);
			Oli_BIS_Stats::increment( 'signups' );

			if ( Oli_BIS_Settings::enabled( 'admin_signup_email' ) ) {
				$fresh = Oli_BIS_Repository::get( $subscription->get_id() );
				if ( $fresh ) {
					do_action( 'oli_bis_send_admin_signup', $fresh );
				}
			}
		}

		wc_add_notice( __( 'Your stock notification is confirmed.', 'oli-back-in-stock-notifications' ), 'success' );
	}

	/**
	 * Unsubscribes one or all notifications.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $subscription Subscription.
	 * @param bool                 $all          Unsubscribe all for the email.
	 * @return void
	 */
	private function unsubscribe( $subscription, $all ) {
		if ( $all ) {
			Oli_BIS_Repository::unsubscribe_email( $subscription->get_email() );
			wc_add_notice( __( 'You have been unsubscribed from all stock notifications.', 'oli-back-in-stock-notifications' ), 'success' );
			return;
		}

		Oli_BIS_Repository::set_status(
			$subscription->get_id(),
			'unsubscribed',
			array( 'unsubscribed_at' => Oli_BIS_Repository::now() )
		);

		wc_add_notice( __( 'You have been unsubscribed from this stock notification.', 'oli-back-in-stock-notifications' ), 'success' );
	}

	/**
	 * Redirect target after a public action.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	private function redirect_url() {
		if ( is_user_logged_in() ) {
			return wc_get_account_endpoint_url( 'stock-notifications' );
		}

		return wc_get_page_permalink( 'shop' );
	}

	/**
	 * Builds a signed action URL.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $subscription Subscription.
	 * @param string               $action       Action.
	 * @param bool                 $all          Unsubscribe all.
	 * @param int                  $ttl          Lifetime.
	 * @return string
	 */
	public static function url( $subscription, $action, $all = false, $ttl = 0 ) {
		$payload = array(
			'sid' => $subscription->get_id(),
			'act' => $action,
			'key' => (string) $subscription->get( 'token_key', '' ),
		);

		if ( $all ) {
			$payload['all'] = 1;
		}

		$token = Oli_BIS_Tokens::create( $payload, wp_salt( 'auth' ), $ttl );

		return add_query_arg(
			array(
				'oli_bis_action' => rawurlencode( $action ),
				'oli_bis_token'  => rawurlencode( $token ),
			),
			home_url( '/' )
		);
	}
}
