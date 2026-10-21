<?php
/**
 * Shared WooCommerce email helpers.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Base class for plugin emails.
 *
 * @since 0.1.0
 */
abstract class Oli_BIS_Email extends WC_Email {

	/**
	 * Current subscription.
	 *
	 * @var Oli_BIS_Subscription|null
	 */
	public $subscription = null;

	/**
	 * Current product.
	 *
	 * @var WC_Product|null
	 */
	public $product = null;

	/**
	 * Shared constructor bits.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	protected function setup_templates() {
		$this->template_base = OLI_BIS_PATH . 'templates/';
		$this->placeholders  = array_merge(
			array(
				'{product_name}'    => '',
				'{product_url}'     => '',
				'{unsubscribe_url}' => '',
				'{confirm_url}'     => '',
				'{site_title}'      => '',
				'{site_name}'       => '',
			),
			is_array( $this->placeholders ) ? $this->placeholders : array()
		);
	}

	/**
	 * Loads subscription, product, locale, and placeholders.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $subscription Subscription.
	 * @return bool
	 */
	protected function prepare_subscription( $subscription ) {
		if ( ! $subscription instanceof Oli_BIS_Subscription ) {
			return false;
		}

		$product = wc_get_product( $subscription->get_target_product_id() );

		if ( ! $product ) {
			return false;
		}

		$this->subscription                      = $subscription;
		$this->product                           = $product;
		$this->recipient                         = $subscription->get_email();
		$this->placeholders['{product_name}']    = $product->get_name();
		$this->placeholders['{product_url}']     = $product->get_permalink();
		$this->placeholders['{unsubscribe_url}'] = Oli_BIS_Public_Actions::url( $subscription, 'unsubscribe' );
		$this->placeholders['{confirm_url}']     = Oli_BIS_Public_Actions::url( $subscription, 'confirm', false, 7 * DAY_IN_SECONDS );
		$this->placeholders['{site_title}']      = $this->get_blogname();
		$this->placeholders['{site_name}']       = $this->placeholders['{site_title}'];

		return true;
	}

	/**
	 * Renders HTML content.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_content_html() {
		return wc_get_template_html(
			$this->template_html,
			$this->template_args(),
			'woocommerce/',
			$this->template_base
		);
	}

	/**
	 * Renders plain content.
	 *
	 * @since 0.1.0
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html(
			$this->template_plain,
			$this->template_args(),
			'woocommerce/',
			$this->template_base
		);
	}

	/**
	 * Template variables.
	 *
	 * @since 0.1.0
	 * @return array<string,mixed>
	 */
	protected function template_args() {
		return array(
			'email_heading'   => $this->get_heading(),
			'product'         => $this->product,
			'subscription'    => $this->subscription,
			'email'           => $this,
			'product_url'     => $this->placeholders['{product_url}'],
			'confirm_url'     => $this->placeholders['{confirm_url}'],
			'unsubscribe_url' => $this->placeholders['{unsubscribe_url}'],
		);
	}

	/**
	 * Sends the email in the subscriber language.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $subscription Subscription.
	 * @return void
	 */
	protected function send_for_subscription( $subscription ) {
		if ( ! $this->is_enabled() || ! $this->prepare_subscription( $subscription ) ) {
			return;
		}

		$previous = Oli_BIS_Language::switch_to( $subscription->get_language() );
		$this->setup_locale();
		$this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );
		$this->restore_locale();
		Oli_BIS_Language::restore( $previous );
	}
}
