<?php
/**
 * WooCommerce email registration.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers custom WC_Email classes.
 *
 * @since 0.1.0
 */
class Oli_BIS_Emails {

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_filter( 'woocommerce_email_classes', array( $this, 'register_emails' ) );
		add_filter( 'woocommerce_template_directory', array( $this, 'template_directory' ), 10, 2 );
	}

	/**
	 * Adds plugin emails to WooCommerce.
	 *
	 * @since 0.1.0
	 * @param array<string,WC_Email> $emails Emails.
	 * @return array<string,WC_Email>
	 */
	public function register_emails( $emails ) {
		$emails['Oli_BIS_Email_Signup']         = new Oli_BIS_Email_Signup();
		$emails['Oli_BIS_Email_Verification']   = new Oli_BIS_Email_Verification();
		$emails['Oli_BIS_Email_Notification']   = new Oli_BIS_Email_Notification();
		$emails['Oli_BIS_Email_Admin_Signup']   = new Oli_BIS_Email_Admin_Signup();

		return $emails;
	}

	/**
	 * Lets themes override plugin templates in woocommerce/emails/.
	 *
	 * @since 0.1.0
	 * @param string $directory Directory.
	 * @param string $template  Template.
	 * @return string
	 */
	public function template_directory( $directory, $template ) {
		if ( false !== strpos( $template, 'oli-bis-' ) ) {
			return 'woocommerce';
		}

		return $directory;
	}
}
