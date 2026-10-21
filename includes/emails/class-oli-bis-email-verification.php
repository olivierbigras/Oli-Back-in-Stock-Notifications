<?php
/**
 * Double opt-in verification email.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sent when double opt-in is required.
 *
 * @since 0.1.0
 */
class Oli_BIS_Email_Verification extends Oli_BIS_Email {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->id             = 'oli_bis_verification';
		$this->customer_email = true;
		$this->title          = __( 'Back in stock signup verification', 'oli-back-in-stock-notifications' );
		$this->description    = __( 'Sent to customers who must confirm their stock notification signup.', 'oli-back-in-stock-notifications' );
		$this->template_html  = 'emails/oli-bis-verification.php';
		$this->template_plain = 'emails/plain/oli-bis-verification.php';
		$this->setup_templates();

		add_action( 'oli_bis_send_verification', array( $this, 'trigger' ), 10, 1 );

		parent::__construct();
	}

	/**
	 * Default subject.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'Please confirm your stock notification for {product_name}', 'oli-back-in-stock-notifications' );
	}

	/**
	 * Default heading.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'Confirm your notification', 'oli-back-in-stock-notifications' );
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
