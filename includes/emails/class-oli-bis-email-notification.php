<?php
/**
 * Back-in-stock notification email.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sent when a subscribed product is restocked.
 *
 * @since 0.1.0
 */
class Oli_BIS_Email_Notification extends Oli_BIS_Email {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->id             = 'oli_bis_notification';
		$this->customer_email = true;
		$this->title          = __( 'Back in stock notification', 'oli-back-in-stock-notifications' );
		$this->description    = __( 'Sent to customers when a product they signed up for is back in stock.', 'oli-back-in-stock-notifications' );
		$this->template_html  = 'emails/oli-bis-notification.php';
		$this->template_plain = 'emails/plain/oli-bis-notification.php';
		$this->setup_templates();

		add_action( 'oli_bis_send_notification', array( $this, 'trigger' ), 10, 1 );

		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( '{product_name} is back in stock', 'oli-back-in-stock-notifications' );
	}

	/**
	 * Default heading.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Back in stock', 'oli-back-in-stock-notifications' );
	}

	/**
	 * Prepares placeholders including an attributed product URL.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $subscription Subscription.
	 * @return bool
	 */
	protected function prepare_subscription( $subscription ) {
		if ( ! parent::prepare_subscription( $subscription ) ) {
			return false;
		}

		$this->placeholders['{product_url}'] = Oli_BIS_Attribution::product_url( $subscription, $this->product->get_permalink() );
		$this->placeholders['{unsubscribe_url}'] = Oli_BIS_Public_Actions::url( $subscription, 'unsubscribe', true );

		$attributes = $subscription->get_attributes();

		if ( ! empty( $attributes ) ) {
			$this->product_url_with_attributes();
		}

		return true;
	}

	/**
	 * Adds variation attributes to the product URL.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function product_url_with_attributes() {
		$attributes = $this->subscription->get_attributes();
		$args       = array();

		foreach ( $attributes as $key => $value ) {
			$args[ 'attribute_' . $key ] = $value;
		}

		if ( $args ) {
			$this->placeholders['{product_url}'] = add_query_arg( $args, $this->placeholders['{product_url}'] );
		}
	}

	/**
	 * Triggers the email.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $subscription Subscription.
	 * @return void
	 */
	public function trigger( $subscription ) {
		$this->send_for_subscription( $subscription );
	}
}
