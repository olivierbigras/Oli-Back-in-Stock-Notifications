<?php
/**
 * Signup form renderer.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds the Notify me form markup.
 *
 * @since 0.1.0
 */
class Oli_BIS_Form {

	/**
	 * Renders the form for a product.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product Product.
	 * @param string     $source  Source slug.
	 * @return string
	 */
	public static function render( $product, $source = 'form' ) {
		if ( ! Oli_BIS_Compatibility::should_show_form( $product ) ) {
			return '';
		}

		$ids     = Oli_BIS_Compatibility::ids_for_product( $product );
		$user    = wp_get_current_user();
		$email   = ( $user && $user->exists() ) ? $user->user_email : '';
		$already = false;

		if ( $email ) {
			$existing = Oli_BIS_Repository::find( $email, $ids['product_id'], $ids['variation_id'] );
			$already  = $existing && Oli_BIS_Status::is_waiting( $existing->get_status() );
		}

		ob_start();

		$template = $already ? 'already-signed-up.php' : 'form.php';
		$path     = OLI_BIS_PATH . 'templates/' . $template;

		if ( is_readable( $path ) ) {
			$args = array(
				'product'      => $product,
				'product_id'   => $ids['product_id'],
				'variation_id' => $ids['variation_id'],
				'email'        => $email,
				'is_logged_in' => (bool) $email,
				'source'       => $source,
				'count'        => Oli_BIS_Repository::count_for_product( $product->get_id() ),
			);

			include $path;
		}

		return (string) ob_get_clean();
	}
}
