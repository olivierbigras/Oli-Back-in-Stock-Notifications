<?php
/**
 * Notify form shortcode.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers [oli_bis_notify_form].
 *
 * @since 0.1.0
 */
class Oli_BIS_Shortcode {

	/**
	 * Registers the shortcode.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_shortcode( 'oli_bis_notify_form', array( $this, 'render' ) );
		add_shortcode( 'wc_back_in_stock_form', array( $this, 'render_legacy' ) );
	}

	/**
	 * Renders the PixM shortcode alias [wc_back_in_stock_form].
	 *
	 * @since 0.1.1
	 * @param array<string,mixed>|string $atts Attributes.
	 * @return string
	 */
	public function render_legacy( $atts ) {
		$atts = shortcode_atts(
			array(
				'product_id'             => 0,
				'show_only_out_of_stock' => 'true',
			),
			is_array( $atts ) ? $atts : array(),
			'wc_back_in_stock_form'
		);

		return $this->render( $atts );
	}

	/**
	 * Renders the shortcode.
	 *
	 * @since 0.1.0
	 * @param array<string,mixed>|string $atts Attributes.
	 * @return string
	 */
	public function render( $atts ) {
		$atts = shortcode_atts(
			array(
				'product_id'             => 0,
				'show_only_out_of_stock' => 'true',
			),
			is_array( $atts ) ? $atts : array(),
			'oli_bis_notify_form'
		);

		$product_id = absint( $atts['product_id'] );

		if ( ! $product_id && function_exists( 'wc_get_product' ) ) {
			$current    = wc_get_product();
			$product_id = $current ? $current->get_id() : 0;
		}

		$product = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! $product ) {
			return '';
		}

		$only_oos = ! isset( $atts['show_only_out_of_stock'] ) || 'false' !== (string) $atts['show_only_out_of_stock'];

		wp_enqueue_style( 'oli-bis-frontend' );
		wp_enqueue_script( 'oli-bis-frontend' );

		return Oli_BIS_Form::render( $product, 'shortcode', ! $only_oos );
	}
}
