<?php
/**
 * Already signed-up message.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="oli-bis-form-wrap oli-bis-already">
	<p><?php echo esc_html( (string) Oli_BIS_Settings::get( 'form_text_signed_up' ) ); ?></p>
	<?php if ( is_user_logged_in() ) : ?>
		<p><a href="<?php echo esc_url( wc_get_account_endpoint_url( 'stock-notifications' ) ); ?>"><?php esc_html_e( 'Manage your stock notifications', 'oli-back-in-stock-notifications' ); ?></a></p>
	<?php endif; ?>
</div>
