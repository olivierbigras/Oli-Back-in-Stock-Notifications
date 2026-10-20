<?php
/**
 * Catalog sign-up prompt.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;
?>
<p class="oli-bis-catalog-prompt">
	<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( (string) Oli_BIS_Settings::get( 'catalog_text' ) ); ?></a>
</p>
