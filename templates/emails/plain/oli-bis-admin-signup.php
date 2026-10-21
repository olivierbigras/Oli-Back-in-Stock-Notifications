<?php
/**
 * Admin signup email (plain).
 *
 * @package Oli_BIS
 * @since   0.1.0
 *
 * @var WC_Product|null      $product Product.
 * @var Oli_BIS_Subscription $subscription Subscription.
 */

defined( 'ABSPATH' ) || exit;

echo esc_html__( 'A customer signed up for a back-in-stock notification.', 'oli-back-in-stock-notifications' ) . "\n\n";
echo esc_html( $product ? $product->get_name() : '' ) . "\n";
echo esc_html( $subscription ? $subscription->get_email() : '' ) . "\n";
