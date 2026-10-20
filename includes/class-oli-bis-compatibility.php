<?php
/**
 * Third-party compatibility.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * TranslatePress, HPOS, and product-type helpers.
 *
 * @since 0.1.0
 */
class Oli_BIS_Compatibility {

	/**
	 * Product types that can show the form.
	 *
	 * @since 0.1.0
	 * @return array<int,string>
	 */
	public static function supported_types() {
		$types = array( 'simple', 'variation', 'variable' );

		if ( class_exists( 'WC_Subscriptions' ) || class_exists( 'WC_Product_Subscription' ) ) {
			$types[] = 'subscription';
			$types[] = 'variable-subscription';
			$types[] = 'subscription_variation';
		}

		/**
		 * Filters supported product types.
		 *
		 * @since 0.1.0
		 * @param array<int,string> $types Types.
		 */
		return apply_filters( 'oli_bis_supported_product_types', $types );
	}

	/**
	 * Whether a product can receive waitlist signups.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product Product.
	 * @return bool
	 */
	public static function is_supported_product( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		if ( $product->is_type( 'variable' ) ) {
			return true;
		}

		return in_array( $product->get_type(), self::supported_types(), true );
	}

	/**
	 * Whether notifications are enabled on a product.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product Product.
	 * @return bool
	 */
	public static function is_enabled_for_product( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return false;
		}

		$enabled = $product->get_meta( '_oli_bis_enabled' );

		if ( '' === $enabled ) {
			$enabled = 'yes';
		}

		return 'yes' === $enabled;
	}

	/**
	 * Whether the form should display for the product stock state.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product Product.
	 * @return bool
	 */
	public static function should_show_form( $product ) {
		if ( ! self::is_supported_product( $product ) || ! self::is_enabled_for_product( $product ) ) {
			return false;
		}

		if ( $product->is_type( 'variable' ) ) {
			return false;
		}

		if ( ! $product->is_in_stock() ) {
			return true;
		}

		return Oli_BIS_Settings::enabled( 'allow_backorder' ) && $product->is_on_backorder();
	}

	/**
	 * Parent/variation IDs for storage.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product Product.
	 * @return array{product_id:int,variation_id:int}
	 */
	public static function ids_for_product( $product ) {
		$product_id   = $product->get_id();
		$variation_id = 0;

		if ( $product->is_type( 'variation' ) || $product->is_type( 'subscription_variation' ) ) {
			$variation_id = $product->get_id();
			$product_id   = $product->get_parent_id();
		}

		return array(
			'product_id'   => absint( $product_id ),
			'variation_id' => absint( $variation_id ),
		);
	}
}
