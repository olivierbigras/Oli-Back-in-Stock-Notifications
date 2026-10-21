<?php
/**
 * Notify me form.
 *
 * @package Oli_BIS
 * @since   0.1.0
 *
 * @var WC_Product $product Product.
 * @var int        $product_id Product ID.
 * @var int        $variation_id Variation ID.
 * @var string     $email Email.
 * @var bool       $is_logged_in Logged in.
 * @var string     $source Source.
 * @var int        $count Subscriber count.
 */

defined( 'ABSPATH' ) || exit;

$oli_bis_action = admin_url( 'admin-ajax.php' );
?>
<div class="oli-bis-form-wrap">
	<p class="oli-bis-form-text"><?php echo esc_html( (string) Oli_BIS_Settings::get( 'form_text' ) ); ?></p>
	<form class="oli-bis-form" method="post" action="<?php echo esc_url( $oli_bis_action ); ?>">
		<input type="hidden" name="action" value="oli_bis_signup" />
		<input type="hidden" name="nonce" value="<?php echo esc_attr( wp_create_nonce( 'oli_bis_signup' ) ); ?>" />
		<input type="hidden" name="product_id" value="<?php echo esc_attr( (string) $product_id ); ?>" />
		<input type="hidden" name="variation_id" value="<?php echo esc_attr( (string) $variation_id ); ?>" />
		<input type="hidden" name="source" value="<?php echo esc_attr( $source ); ?>" />
		<input type="text" name="oli_bis_company" class="oli-bis-honeypot" value="" tabindex="-1" autocomplete="off" aria-hidden="true" />
		<?php if ( ! $is_logged_in ) : ?>
			<label class="oli-bis-email-label">
				<span class="screen-reader-text"><?php esc_html_e( 'Email address', 'oli-back-in-stock-notifications' ); ?></span>
				<input type="email" name="email" class="oli-bis-email" required placeholder="<?php esc_attr_e( 'Email address', 'oli-back-in-stock-notifications' ); ?>" />
			</label>
		<?php endif; ?>
		<?php if ( Oli_BIS_Settings::enabled( 'gdpr_checkbox' ) ) : ?>
			<label class="oli-bis-consent">
				<input type="checkbox" name="consent" value="1" required />
				<span><?php echo esc_html( (string) Oli_BIS_Settings::get( 'gdpr_text' ) ); ?></span>
			</label>
		<?php endif; ?>
		<button type="submit" class="oli-bis-submit button"><?php echo esc_html( (string) Oli_BIS_Settings::get( 'button_text' ) ); ?></button>
		<p class="oli-bis-message" role="status" hidden></p>
	</form>
	<?php if ( Oli_BIS_Settings::enabled( 'show_count' ) && $count > 0 ) : ?>
		<p class="oli-bis-count">
			<?php
			echo esc_html(
				sprintf(
					/* translators: %d: number of customers already signed up */
					_n( '%d customer is waiting for this product.', '%d customers are waiting for this product.', $count, 'oli-back-in-stock-notifications' ),
					$count
				)
			);
			?>
		</p>
	<?php endif; ?>
</div>
