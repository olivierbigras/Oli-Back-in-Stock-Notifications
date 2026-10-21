<?php
/**
 * AJAX signup endpoint.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Handles guest and logged-in signup requests.
 *
 * @since 0.1.0
 */
class Oli_BIS_Ajax {

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_action( 'wp_ajax_oli_bis_signup', array( $this, 'signup' ) );
		add_action( 'wp_ajax_nopriv_oli_bis_signup', array( $this, 'signup' ) );
	}

	/**
	 * Processes a signup.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function signup() {
		check_ajax_referer( 'oli_bis_signup', 'nonce' );

		$honeypot = isset( $_POST['oli_bis_company'] ) ? sanitize_text_field( wp_unslash( $_POST['oli_bis_company'] ) ) : '';

		if ( '' !== $honeypot ) {
			wp_send_json_success(
				array(
					'message' => __( 'You will be notified when this product is back in stock.', 'oli-back-in-stock-notifications' ),
				)
			);
		}

		$ip = Oli_BIS_Rate_Limiter::request_ip();

		if ( $ip && ! Oli_BIS_Rate_Limiter::consume( $ip ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Too many attempts. Please try again later.', 'oli-back-in-stock-notifications' ),
				),
				429
			);
		}

		$product_id   = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$variation_id = isset( $_POST['variation_id'] ) ? absint( wp_unslash( $_POST['variation_id'] ) ) : 0;
		$source       = isset( $_POST['source'] ) ? sanitize_key( wp_unslash( $_POST['source'] ) ) : 'form';
		$product      = wc_get_product( $variation_id ? $variation_id : $product_id );

		if ( ! $product || ! Oli_BIS_Compatibility::is_supported_product( $product ) || ! Oli_BIS_Compatibility::is_enabled_for_product( $product ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Notifications are not available for this product.', 'oli-back-in-stock-notifications' ),
				),
				400
			);
		}

		$user  = wp_get_current_user();
		$email = ( $user && $user->exists() ) ? $user->user_email : '';

		if ( ! $email ) {
			if ( Oli_BIS_Settings::enabled( 'require_account' ) ) {
				wp_send_json_error(
					array(
						'message'  => __( 'Please log in to sign up for stock notifications.', 'oli-back-in-stock-notifications' ),
						'redirect' => wc_get_page_permalink( 'myaccount' ),
					),
					401
				);
			}

			$email = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : '';
		}

		if ( ! is_email( $email ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please enter a valid email address.', 'oli-back-in-stock-notifications' ),
				),
				400
			);
		}

		$consent = ! empty( $_POST['consent'] );

		if ( Oli_BIS_Settings::enabled( 'gdpr_checkbox' ) && ! $consent ) {
			wp_send_json_error(
				array(
					'message' => __( 'Please confirm that you want to receive this notification.', 'oli-back-in-stock-notifications' ),
				),
				400
			);
		}

		$ids    = Oli_BIS_Compatibility::ids_for_product( $product );
		$status = Oli_BIS_Settings::enabled( 'double_optin' ) ? 'pending' : 'active';
		$result = Oli_BIS_Repository::upsert(
			array(
				'email'        => $email,
				'product_id'   => $ids['product_id'],
				'variation_id' => $ids['variation_id'],
				'user_id'      => ( $user && $user->exists() ) ? (int) $user->ID : 0,
				'status'       => $status,
				'language'     => Oli_BIS_Language::current(),
				'attributes'   => $product->is_type( 'variation' ) ? $product->get_attributes() : array(),
				'source'       => $source,
				'consent'      => $consent || ! Oli_BIS_Settings::enabled( 'gdpr_checkbox' ),
			)
		);

		if ( ! empty( $result['error'] ) && 'exists' === $result['error'] ) {
			wp_send_json_success(
				array(
					'message' => __( 'You are already signed up for this product.', 'oli-back-in-stock-notifications' ),
				)
			);
		}

		if ( empty( $result['subscription'] ) ) {
			wp_send_json_error(
				array(
					'message' => __( 'Unable to save your notification request. Please try again.', 'oli-back-in-stock-notifications' ),
				),
				500
			);
		}

		$subscription = $result['subscription'];

		if ( 'pending' === $subscription->get_status() ) {
			do_action( 'oli_bis_send_verification', $subscription );
			wp_send_json_success(
				array(
					'status'  => 'pending',
					'message' => __( 'Please check your email to confirm this notification.', 'oli-back-in-stock-notifications' ),
				)
			);
		}

		do_action( 'oli_bis_send_signup', $subscription );
		Oli_BIS_Stats::increment( 'signups' );

		if ( Oli_BIS_Settings::enabled( 'admin_signup_email' ) ) {
			do_action( 'oli_bis_send_admin_signup', $subscription );
		}

		wp_send_json_success(
			array(
				'status'  => 'active',
				'message' => __( 'You will be notified when this product is back in stock.', 'oli-back-in-stock-notifications' ),
			)
		);
	}
}
