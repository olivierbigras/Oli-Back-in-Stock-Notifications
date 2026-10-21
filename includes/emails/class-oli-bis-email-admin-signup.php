<?php
/**
 * Admin new-signup email.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Optional notification sent to the store admin.
 *
 * @since 0.1.0
 */
class Oli_BIS_Email_Admin_Signup extends Oli_BIS_Email {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->id             = 'oli_bis_admin_signup';
		$this->customer_email = false;
		$this->title          = __( 'Admin stock notification signup', 'oli-back-in-stock-notifications' );
		$this->description    = __( 'Sent to the store admin when a customer signs up for a back-in-stock alert.', 'oli-back-in-stock-notifications' );
		$this->template_html  = 'emails/oli-bis-admin-signup.php';
		$this->template_plain = 'emails/plain/oli-bis-admin-signup.php';
		$this->setup_templates();

		add_action( 'oli_bis_send_admin_signup', array( $this, 'trigger' ), 10, 1 );

		parent::__construct();

		$this->recipient = $this->get_option( 'recipient', get_option( 'admin_email' ) );
	}

	/**
	 * Default subject.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_default_subject() {
		return __( 'New stock notification signup for {product_name}', 'oli-back-in-stock-notifications' );
	}

	/**
	 * Default heading.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_default_heading() {
		return __( 'New stock notification signup', 'oli-back-in-stock-notifications' );
	}

	/**
	 * Form fields including recipient.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function init_form_fields() {
		parent::init_form_fields();

		$this->form_fields['recipient'] = array(
			'title'       => __( 'Recipient(s)', 'oli-back-in-stock-notifications' ),
			'type'        => 'text',
			'description' => __( 'Enter recipients (comma separated) for this email. Defaults to the admin email.', 'oli-back-in-stock-notifications' ),
			'placeholder' => esc_attr( get_option( 'admin_email' ) ),
			'default'     => '',
			'desc_tip'    => true,
		);
	}

	/**
	 * Triggers the email.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $subscription Subscription.
	 * @return void
	 */
	public function trigger( $subscription ) {
		if ( ! $this->is_enabled() || ! $this->prepare_subscription( $subscription ) ) {
			return;
		}

		$this->placeholders['{customer_email}'] = $subscription->get_email();
		$this->recipient                        = $this->get_option( 'recipient', get_option( 'admin_email' ) );
		$this->setup_locale();
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		$this->restore_locale();
	}
}
