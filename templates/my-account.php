<?php
/**
 * My Account stock notifications.
 *
 * @package Oli_BIS
 * @since   0.1.0
 *
 * @var array<int,Oli_BIS_Subscription> $subscriptions Subscriptions.
 */

defined( 'ABSPATH' ) || exit;
?>
<h2><?php esc_html_e( 'Stock notifications', 'oli-back-in-stock-notifications' ); ?></h2>

<?php if ( empty( $subscriptions ) ) : ?>
	<p><?php esc_html_e( 'You have no stock notifications.', 'oli-back-in-stock-notifications' ); ?></p>
<?php else : ?>
	<table class="woocommerce-orders-table shop_table shop_table_responsive">
		<thead>
			<tr>
				<th><?php esc_html_e( 'Product', 'oli-back-in-stock-notifications' ); ?></th>
				<th><?php esc_html_e( 'Status', 'oli-back-in-stock-notifications' ); ?></th>
				<th><?php esc_html_e( 'Signed up', 'oli-back-in-stock-notifications' ); ?></th>
				<th><?php esc_html_e( 'Actions', 'oli-back-in-stock-notifications' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $subscriptions as $subscription ) : ?>
				<?php
				$product = wc_get_product( $subscription->get_target_product_id() );
				$name    = $product ? $product->get_name() : (string) $subscription->get_target_product_id();
				?>
				<tr>
					<td data-title="<?php esc_attr_e( 'Product', 'oli-back-in-stock-notifications' ); ?>">
						<?php if ( $product ) : ?>
							<a href="<?php echo esc_url( $product->get_permalink() ); ?>"><?php echo esc_html( $name ); ?></a>
						<?php else : ?>
							<?php echo esc_html( $name ); ?>
						<?php endif; ?>
					</td>
					<td data-title="<?php esc_attr_e( 'Status', 'oli-back-in-stock-notifications' ); ?>"><?php echo esc_html( $subscription->get_status() ); ?></td>
					<td data-title="<?php esc_attr_e( 'Signed up', 'oli-back-in-stock-notifications' ); ?>"><?php echo esc_html( (string) $subscription->get( 'created_at', '' ) ); ?></td>
					<td data-title="<?php esc_attr_e( 'Actions', 'oli-back-in-stock-notifications' ); ?>">
						<?php if ( in_array( $subscription->get_status(), array( 'pending', 'active', 'queued' ), true ) ) : ?>
							<form method="post">
								<?php wp_nonce_field( 'oli_bis_account', 'oli_bis_account_nonce' ); ?>
								<input type="hidden" name="subscription_id" value="<?php echo esc_attr( (string) $subscription->get_id() ); ?>" />
								<button type="submit" name="oli_bis_account_action" value="unsubscribe" class="button"><?php esc_html_e( 'Unsubscribe', 'oli-back-in-stock-notifications' ); ?></button>
								<?php if ( 'pending' === $subscription->get_status() ) : ?>
									<button type="submit" name="oli_bis_account_action" value="resend" class="button"><?php esc_html_e( 'Resend verification', 'oli-back-in-stock-notifications' ); ?></button>
								<?php endif; ?>
							</form>
						<?php endif; ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>
<?php endif; ?>
