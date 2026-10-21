<?php
/**
 * Notify form block.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers the oli-bis/notify-form dynamic block.
 *
 * @since 0.1.0
 */
class Oli_BIS_Block {

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_action( 'init', array( $this, 'register_block' ) );
	}

	/**
	 * Registers the block type.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_block() {
		wp_register_script(
			'oli-bis-block',
			OLI_BIS_URL . 'assets/js/block.js',
			array( 'wp-blocks', 'wp-element', 'wp-i18n', 'wp-block-editor', 'wp-components' ),
			OLI_BIS_VERSION,
			true
		);

		register_block_type(
			'oli-bis/notify-form',
			array(
				'api_version'     => 3,
				'editor_script'   => 'oli-bis-block',
				'render_callback' => array( $this, 'render' ),
				'attributes'      => array(
					'productId' => array(
						'type'    => 'number',
						'default' => 0,
					),
				),
				'supports'        => array(
					'html' => false,
				),
			)
		);
	}

	/**
	 * Renders the block on the front end.
	 *
	 * @since 0.1.0
	 * @param array<string,mixed> $attributes Attributes.
	 * @return string
	 */
	public function render( $attributes ) {
		$product_id = isset( $attributes['productId'] ) ? absint( $attributes['productId'] ) : 0;

		if ( ! $product_id ) {
			$product = wc_get_product();
		} else {
			$product = wc_get_product( $product_id );
		}

		if ( ! $product ) {
			return '';
		}

		wp_enqueue_style( 'oli-bis-frontend' );
		wp_enqueue_script( 'oli-bis-frontend' );

		return Oli_BIS_Form::render( $product, 'block' );
	}
}
