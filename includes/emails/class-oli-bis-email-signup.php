<?php
/**
 * Signup confirmation email.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sent after a successful signup when double opt-in is off.
 *
 * @since 0.1.0
 */
class Oli_BIS_Email_Signup extends Oli_BIS_Email {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->id             = 'oli_bis_signup';
		$this->customer_email = true;
		$this->title          = __( 'Back in stock signup confirmation', 'oli-back-in-stock-notifications' );
		$this->description    = __( 'Sent to customers after they sign up to be notified when a product is restocked.', 'oli-back-in-stock-notifications' );
		$this->template_html  = 'emails/oli-bis-signup.php';
		$this->template_plain = 'emails/plain/oli-bis-signup.php';
		$this->setup_templates();

		add_action( 'oli_bis_send_signup', array( $this, 'trigger' ), 10, 1 );

		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'You will be notified when {product_name} is back in stock', 'oli-back-in-stock-notifications' );
	}

	/**
	 * Default heading.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Stock notification saved', 'oli-back-in-stock-notifications' );
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
