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

$oli_bis_custom = Oli_BIS_Email_Content::custom_body(
	'signup_body',
	array(
		'{product_name}' => $product ? $product->get_name() : '',
		'{product_url}'  => $product_url,
		'{site_name}'    => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
	)
);

if ( $oli_bis_custom ) {
	echo esc_html( wp_strip_all_tags( $oli_bis_custom ) ) . "\n\n";
} else {
	echo esc_html__( 'You asked us to let you know when this product is available again.', 'oli-back-in-stock-notifications' ) . "\n\n";
	echo esc_html( $product ? $product->get_name() : '' ) . "\n";
	echo esc_url( $product_url ) . "\n\n";
}

echo esc_html__( 'Unsubscribe:', 'oli-back-in-stock-notifications' ) . ' ' . esc_url( $unsubscribe_url ) . "\n";
