<?php
/**
 * Signup confirmation email (plain).
 *
 * @package Oli_BIS
 * @since   0.1.0
 *
 * @var WC_Product|null      $product Product.
 * @var string               $product_url Product URL.
 * @var string               $unsubscribe_url Unsubscribe URL.
 */

defined( 'ABSPATH' ) || exit;

echo esc_html__( 'You asked us to let you know when this product is available again.', 'oli-back-in-stock-notifications' ) . "\n\n";
echo esc_html( $product ? $product->get_name() : '' ) . "\n";
echo esc_url( $product_url ) . "\n\n";
echo esc_html__( 'Unsubscribe:', 'oli-back-in-stock-notifications' ) . ' ' . esc_url( $unsubscribe_url ) . "\n";
