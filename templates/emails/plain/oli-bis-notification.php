<?php
/**
 * Restock notification email (plain).
 *
 * @package Oli_BIS
 * @since   0.1.0
 *
 * @var WC_Product|null $product Product.
 * @var string          $product_url Product URL.
 * @var string          $unsubscribe_url Unsubscribe URL.
 */

defined( 'ABSPATH' ) || exit;

echo esc_html__( 'Good news — this product is available again.', 'oli-back-in-stock-notifications' ) . "\n\n";
echo esc_html( $product ? $product->get_name() : '' ) . "\n";
echo esc_html__( 'Shop now:', 'oli-back-in-stock-notifications' ) . ' ' . esc_url( $product_url ) . "\n\n";
echo esc_html__( 'Unsubscribe from all stock notifications:', 'oli-back-in-stock-notifications' ) . ' ' . esc_url( $unsubscribe_url ) . "\n";
