<?php
/**
 * Order conversion attribution.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Tracks purchases that follow a notification click.
 *
 * @since 0.1.0
 */
class Oli_BIS_Attribution {

	const COOKIE = 'oli_bis_ref';
	const META   = '_oli_bis_subscription_ids';

	/**
	 * Registers storefront hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_action( 'template_redirect', array( $this, 'capture_ref' ) );
		add_action( 'woocommerce_checkout_create_order', array( $this, 'attach_to_order' ), 20, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'attach_to_order' ), 20, 1 );
		add_action( 'woocommerce_order_status_processing', array( $this, 'maybe_convert' ) );
		add_action( 'woocommerce_order_status_completed', array( $this, 'maybe_convert' ) );
	}

	/**
	 * Captures a signed notification click.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function capture_ref() {
		if ( empty( $_GET['oli_bis_ref'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		$token   = sanitize_text_field( wp_unslash( $_GET['oli_bis_ref'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$payload = Oli_BIS_Tokens::verify( $token, $this->secret() );

		if ( empty( $payload['sid'] ) ) {
			return;
		}

		$ids   = $this->cookie_ids();
		$ids[] = absint( $payload['sid'] );
		$ids   = array_values( array_unique( array_filter( $ids ) ) );
		$value = wp_json_encode( $ids );

		if ( ! is_string( $value ) ) {
			return;
		}

		wc_setcookie( self::COOKIE, $value, time() + WEEK_IN_SECONDS );
		$_COOKIE[ self::COOKIE ] = $value;
	}

	/**
	 * Stores referenced subscription IDs on the order.
	 *
	 * @since 0.1.0
	 * @param WC_Order $order Order.
	 * @return void
	 */
	public function attach_to_order( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$ids = $this->cookie_ids();

		if ( empty( $ids ) ) {
			return;
		}

		$order->update_meta_data( self::META, array_map( 'absint', $ids ) );
	}

	/**
	 * Marks matching subscriptions as converted.
	 *
	 * @since 0.1.0
	 * @param int $order_id Order ID.
	 * @return void
	 */
	public function maybe_convert( $order_id ) {
		$order = wc_get_order( $order_id );

		if ( ! $order ) {
			return;
		}

		$ids = $order->get_meta( self::META );

		if ( ! is_array( $ids ) || empty( $ids ) ) {
			$ids = $this->match_by_email( $order );
		}

		$converted = 0;

		foreach ( $ids as $id ) {
			$subscription = Oli_BIS_Repository::get( absint( $id ) );

			if ( ! $subscription ) {
				continue;
			}

			if ( $subscription->get_email() !== Oli_BIS_Repository::normalize_email( $order->get_billing_email() ) ) {
				continue;
			}

			if ( ! $this->order_contains_product( $order, $subscription ) ) {
				continue;
			}

			if ( Oli_BIS_Repository::set_status(
				$subscription->get_id(),
				'converted',
				array(
					'converted_at'       => Oli_BIS_Repository::now(),
					'converted_order_id' => $order->get_id(),
				)
			) ) {
				++$converted;
			}
		}

		if ( $converted ) {
			Oli_BIS_Stats::increment( 'conversions', $converted );
		}
	}

	/**
	 * Finds recent notified subscriptions matching the order email and items.
	 *
	 * @since 0.1.0
	 * @param WC_Order $order Order.
	 * @return array<int,int>
	 */
	private function match_by_email( $order ) {
		$result = Oli_BIS_Repository::query(
			array(
				'email'    => $order->get_billing_email(),
				'status'   => 'notified',
				'per_page' => 50,
			)
		);

		$ids = array();

		foreach ( $result['items'] as $subscription ) {
			if ( $this->order_contains_product( $order, $subscription ) ) {
				$ids[] = $subscription->get_id();
			}
		}

		return $ids;
	}

	/**
	 * Whether the order includes the subscribed product.
	 *
	 * @since 0.1.0
	 * @param WC_Order           $order        Order.
	 * @param Oli_BIS_Subscription $subscription Subscription.
	 * @return bool
	 */
	private function order_contains_product( $order, $subscription ) {
		$target = $subscription->get_target_product_id();
		$parent = $subscription->get_product_id();

		foreach ( $order->get_items() as $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}

			$item_id        = (int) $item->get_product_id();
			$item_variation = (int) $item->get_variation_id();

			if ( $target === $item_id || $target === $item_variation || $parent === $item_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * IDs stored in the attribution cookie.
	 *
	 * @since 0.1.0
	 * @return array<int,int>
	 */
	private function cookie_ids() {
		if ( empty( $_COOKIE[ self::COOKIE ] ) ) {
			return array();
		}

		$raw  = sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE ] ) );
		$data = json_decode( $raw, true );

		if ( ! is_array( $data ) ) {
			return array();
		}

		return array_map( 'absint', $data );
	}

	/**
	 * HMAC secret.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	private function secret() {
		return wp_salt( 'auth' );
	}

	/**
	 * Builds a signed product URL used in notification emails.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $subscription Subscription.
	 * @param string               $url          Product URL.
	 * @return string
	 */
	public static function product_url( $subscription, $url ) {
		$token = Oli_BIS_Tokens::create(
			array(
				'sid' => $subscription->get_id(),
			),
			wp_salt( 'auth' ),
			MONTH_IN_SECONDS
		);

		return add_query_arg( 'oli_bis_ref', rawurlencode( $token ), $url );
	}
}
