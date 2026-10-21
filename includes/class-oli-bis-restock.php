<?php
/**
 * Restock detection.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Queues notification batches when stock returns.
 *
 * @since 0.1.0
 */
class Oli_BIS_Restock {

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_action( 'woocommerce_product_set_stock_status', array( $this, 'on_status' ), 20, 3 );
		add_action( 'woocommerce_variation_set_stock_status', array( $this, 'on_status' ), 20, 3 );
		add_action( 'woocommerce_product_set_stock', array( $this, 'on_stock' ), 20, 1 );
		add_action( 'woocommerce_variation_set_stock', array( $this, 'on_stock' ), 20, 1 );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'admin_notice_for_product' ) );
	}

	/**
	 * Handles stock status changes.
	 *
	 * @since 0.1.0
	 * @param int        $product_id Product ID.
	 * @param string     $status     New status.
	 * @param WC_Product $product    Product.
	 * @return void
	 */
	public function on_status( $product_id, $status, $product = null ) {
		unset( $product_id );

		if ( ! $product instanceof WC_Product ) {
			return;
		}

		if ( 'instock' === $status || ( Oli_BIS_Settings::enabled( 'allow_backorder' ) && 'onbackorder' === $status ) ) {
			$this->maybe_queue( $product );
			return;
		}

		if ( 'outofstock' === $status ) {
			$ids = Oli_BIS_Compatibility::ids_for_product( $product );
			Oli_BIS_Repository::unqueue( $ids['product_id'], $ids['variation_id'] );
		}
	}

	/**
	 * Handles quantity changes that may cross the minimum threshold.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product Product.
	 * @return void
	 */
	public function on_stock( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		if ( $product->is_in_stock() ) {
			$this->maybe_queue( $product );
		}
	}

	/**
	 * Queues a restock job when thresholds are met.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product Product.
	 * @return void
	 */
	public function maybe_queue( $product ) {
		if ( ! Oli_BIS_Compatibility::is_supported_product( $product ) || ! Oli_BIS_Compatibility::is_enabled_for_product( $product ) ) {
			return;
		}

		if ( $product->is_type( 'variable' ) ) {
			return;
		}

		$min = absint( Oli_BIS_Settings::get( 'min_stock', 0 ) );

		if ( $min > 0 && $product->managing_stock() && $product->get_stock_quantity() < $min ) {
			return;
		}

		$ids   = Oli_BIS_Compatibility::ids_for_product( $product );
		$count = Oli_BIS_Repository::count_waiting_for_product( $ids['product_id'], $ids['variation_id'] );

		if ( $count < 1 ) {
			return;
		}

		Oli_BIS_Scheduler::schedule_prepare( $ids['product_id'], $ids['variation_id'] );
	}

	/**
	 * Admin notice after saving a restocked product.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product Product.
	 * @return void
	 */
	public function admin_notice_for_product( $product ) {
		if ( ! $product instanceof WC_Product || ! $product->is_in_stock() ) {
			return;
		}

		$ids   = Oli_BIS_Compatibility::ids_for_product( $product );
		$count = Oli_BIS_Repository::count_waiting_for_product( $ids['product_id'], $ids['variation_id'] );

		if ( $count < 1 ) {
			return;
		}

		/* translators: %d: subscriber count */
		$message = sprintf( _n( '%d customer will be notified that this product is back in stock.', '%d customers will be notified that this product is back in stock.', $count, 'oli-back-in-stock-notifications' ), $count );

		WC_Admin_Meta_Boxes::add_error( $message );
	}
}
