<?php
/**
 * Storefront form integration.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prints the form on product and catalog pages.
 *
 * @since 0.1.0
 */
class Oli_BIS_Frontend {

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_filter( 'woocommerce_get_stock_html', array( $this, 'append_to_stock_html' ), 20, 2 );
		add_filter( 'render_block_woocommerce/product-stock-indicator', array( $this, 'append_to_stock_block' ), 20, 3 );
		add_action( 'woocommerce_after_shop_loop_item', array( $this, 'catalog_prompt' ), 15 );
	}

	/**
	 * Enqueues frontend assets.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function enqueue() {
		wp_register_style(
			'oli-bis-frontend',
			OLI_BIS_URL . 'assets/css/frontend.css',
			array(),
			OLI_BIS_VERSION
		);

		wp_register_script(
			'oli-bis-frontend',
			OLI_BIS_URL . 'assets/js/frontend.js',
			array(),
			OLI_BIS_VERSION,
			true
		);

		wp_localize_script(
			'oli-bis-frontend',
			'oliBisFrontend',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'oli_bis_signup' ),
				'i18n'    => array(
					'success' => __( 'You will be notified when this product is back in stock.', 'oli-back-in-stock-notifications' ),
					'pending' => __( 'Please check your email to confirm this notification.', 'oli-back-in-stock-notifications' ),
					'error'   => __( 'Unable to save your notification request. Please try again.', 'oli-back-in-stock-notifications' ),
				),
			)
		);

		if ( is_product() || is_shop() || is_product_taxonomy() || has_block( 'oli-bis/notify-form' ) ) {
			wp_enqueue_style( 'oli-bis-frontend' );
			wp_enqueue_script( 'oli-bis-frontend' );
		}
	}

	/**
	 * Appends the form after availability HTML.
	 *
	 * @since 0.1.0
	 * @param string     $html    Stock HTML.
	 * @param WC_Product $product Product.
	 * @return string
	 */
	public function append_to_stock_html( $html, $product ) {
		return $html . Oli_BIS_Form::render( $product, 'form' );
	}

	/**
	 * Appends the form after the stock indicator block.
	 *
	 * @since 0.1.0
	 * @param string   $content Block HTML.
	 * @param array    $block   Block.
	 * @param WP_Block $instance Instance.
	 * @return string
	 */
	public function append_to_stock_block( $content, $block, $instance ) {
		unset( $block );

		$product = null;

		if ( isset( $instance->context['postId'] ) ) {
			$product = wc_get_product( absint( $instance->context['postId'] ) );
		}

		if ( ! $product && function_exists( 'wc_get_product' ) ) {
			$product = wc_get_product();
		}

		if ( ! $product ) {
			return $content;
		}

		return $content . Oli_BIS_Form::render( $product, 'block' );
	}

	/**
	 * Catalog sign-up prompt.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function catalog_prompt() {
		if ( ! Oli_BIS_Settings::enabled( 'catalog_prompt' ) ) {
			return;
		}

		$product = wc_get_product( get_the_ID() );

		if ( ! $product || $product->is_in_stock() ) {
			return;
		}

		if ( ! Oli_BIS_Compatibility::is_enabled_for_product( $product ) ) {
			return;
		}

		$path = OLI_BIS_PATH . 'templates/catalog-prompt.php';

		if ( is_readable( $path ) ) {
			include $path;
		}
	}
}
