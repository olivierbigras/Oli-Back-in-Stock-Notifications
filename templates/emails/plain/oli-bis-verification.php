<?php
/**
 * Verification email (plain).
 *
 * @package Oli_BIS
 * @since   0.1.0
 *
 * @var WC_Product|null $product Product.
 * @var string          $confirm_url Confirm URL.
 */

defined( 'ABSPATH' ) || exit;

echo esc_html__( 'Please confirm that you want to be notified when this product is back in stock.', 'oli-back-in-stock-notifications' ) . "\n\n";
echo esc_html( $product ? $product->get_name() : '' ) . "\n";
echo esc_html__( 'Confirm:', 'oli-back-in-stock-notifications' ) . ' ' . esc_url( $confirm_url ) . "\n";
