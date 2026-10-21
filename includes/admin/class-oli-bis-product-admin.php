<?php
/**
 * Product admin extras.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Inventory checkbox and products list column.
 *
 * @since 0.1.0
 */
class Oli_BIS_Product_Admin {

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_action( 'woocommerce_product_options_inventory_product_data', array( $this, 'product_field' ) );
		add_action( 'woocommerce_admin_process_product_object', array( $this, 'save_product' ) );
		add_action( 'woocommerce_variation_options_inventory', array( $this, 'variation_field' ), 10, 3 );
		add_action( 'woocommerce_save_product_variation', array( $this, 'save_variation' ), 10, 2 );
		add_filter( 'manage_edit-product_columns', array( $this, 'column' ) );
		add_action( 'manage_product_posts_custom_column', array( $this, 'column_content' ), 10, 2 );
	}

	/**
	 * Product inventory checkbox.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function product_field() {
		woocommerce_wp_checkbox(
			array(
				'id'          => '_oli_bis_enabled',
				'label'       => __( 'Stock notifications', 'oli-back-in-stock-notifications' ),
				'description' => __( 'Allow customers to sign up when this product is out of stock.', 'oli-back-in-stock-notifications' ),
				'value'       => $this->enabled_value( get_post_meta( get_the_ID(), '_oli_bis_enabled', true ) ),
			)
		);
	}

	/**
	 * Saves product meta.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product Product.
	 * @return void
	 */
	public function save_product( $product ) {
		if ( ! $product instanceof WC_Product ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- WooCommerce product save already verifies.
		$value = isset( $_POST['_oli_bis_enabled'] ) ? 'yes' : 'no';
		$product->update_meta_data( '_oli_bis_enabled', $value );
	}

	/**
	 * Variation inventory checkbox.
	 *
	 * @since 0.1.0
	 * @param int     $loop           Loop index.
	 * @param array   $variation_data Variation data.
	 * @param WP_Post $variation      Variation post.
	 * @return void
	 */
	public function variation_field( $loop, $variation_data, $variation ) {
		unset( $variation_data );

		woocommerce_wp_checkbox(
			array(
				'id'            => "_oli_bis_enabled_var{$loop}",
				'name'          => "_oli_bis_enabled_var[{$loop}]",
				'label'         => __( 'Stock notifications', 'oli-back-in-stock-notifications' ),
				'description'   => __( 'Allow customers to sign up when this variation is out of stock.', 'oli-back-in-stock-notifications' ),
				'value'         => $this->enabled_value( get_post_meta( $variation->ID, '_oli_bis_enabled', true ) ),
				'wrapper_class' => 'form-row form-row-full',
			)
		);
	}

	/**
	 * Saves variation meta.
	 *
	 * @since 0.1.0
	 * @param int $variation_id Variation ID.
	 * @param int $loop         Loop index.
	 * @return void
	 */
	public function save_variation( $variation_id, $loop ) {
		$values = isset( $_POST['_oli_bis_enabled_var'] ) && is_array( $_POST['_oli_bis_enabled_var'] ) ? wp_unslash( $_POST['_oli_bis_enabled_var'] ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$value  = isset( $values[ $loop ] ) ? 'yes' : 'no';
		update_post_meta( $variation_id, '_oli_bis_enabled', $value );
	}

	/**
	 * Adds the subscriber count column.
	 *
	 * @since 0.1.0
	 * @param array<string,string> $columns Columns.
	 * @return array<string,string>
	 */
	public function column( $columns ) {
		$columns['oli_bis'] = __( 'Waitlist', 'oli-back-in-stock-notifications' );
		return $columns;
	}

	/**
	 * Renders the subscriber count.
	 *
	 * @since 0.1.0
	 * @param string $column  Column.
	 * @param int    $post_id Post ID.
	 * @return void
	 */
	public function column_content( $column, $post_id ) {
		if ( 'oli_bis' !== $column ) {
			return;
		}

		$count = Oli_BIS_Repository::count_for_product( $post_id );
		$url   = add_query_arg(
			array(
				'page'       => 'oli-bis',
				'product_id' => $post_id,
				'status'     => 'active',
			),
			admin_url( 'admin.php' )
		);

		echo '<a href="' . esc_url( $url ) . '">' . esc_html( (string) $count ) . '</a>';
	}

	/**
	 * Default enabled value.
	 *
	 * @since 0.1.0
	 * @param mixed $stored Stored meta.
	 * @return string
	 */
	private function enabled_value( $stored ) {
		return '' === $stored ? 'yes' : (string) $stored;
	}
}
