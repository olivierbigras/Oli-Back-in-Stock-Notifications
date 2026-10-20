<?php
/**
 * Admin signup email (HTML).
 *
 * @package Oli_BIS
 * @since   0.1.0
 *
 * @var string                 $email_heading Heading.
 * @var WC_Product|null        $product Product.
 * @var Oli_BIS_Subscription   $subscription Subscription.
 * @var WC_Email               $email Email object.
 */

defined( 'ABSPATH' ) || exit;

do_action( 'woocommerce_email_header', $email_heading, $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
?>
<p><?php esc_html_e( 'A customer signed up for a back-in-stock notification.', 'oli-back-in-stock-notifications' ); ?></p>
<p>
	<strong><?php echo esc_html( $product ? $product->get_name() : '' ); ?></strong><br />
	<?php echo esc_html( $subscription ? $subscription->get_email() : '' ); ?>
</p>
<?php
do_action( 'woocommerce_email_footer', $email ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
