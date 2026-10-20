<?php
/**
 * Restock notification email (HTML).
 *
 * @package Oli_BIS
 * @since   0.1.0
 *
 * @var string               $email_heading Heading.
 * @var WC_Product|null      $product Product.
 * @var WC_Email             $email Email object.
 * @var string               $product_url Product URL.
 * @var string               $unsubscribe_url Unsubscribe URL.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email );
?>
<p><?php esc_html_e( 'Good news — this product is available again.', 'oli-back-in-stock-notifications' ); ?></p>
<p>
	<strong><?php echo esc_html( $product ? $product->get_name() : '' ); ?></strong>
</p>
<p>
	<a class="link" href="<?php echo esc_url( $product_url ); ?>"><?php esc_html_e( 'Shop now', 'oli-back-in-stock-notifications' ); ?></a>
</p>
<p>
	<a href="<?php echo esc_url( $unsubscribe_url ); ?>"><?php esc_html_e( 'Unsubscribe from all stock notifications', 'oli-back-in-stock-notifications' ); ?></a>
</p>
<?php
do_action( 'woocommerce_email_footer', $email );
