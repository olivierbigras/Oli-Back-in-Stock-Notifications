<?php
/**
 * Notifications list table.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Admin table of waitlist subscriptions.
 *
 * @since 0.1.0
 */
class Oli_BIS_List_Table extends WP_List_Table {

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		parent::__construct(
			array(
				'singular' => 'oli_bis_subscription',
				'plural'   => 'oli_bis_subscriptions',
				'ajax'     => false,
			)
		);
	}

	/**
	 * Columns.
	 *
	 * @since 0.1.0
	 * @return array<string,string>
	 */
	public function get_columns() {
		return array(
			'cb'      => '<input type="checkbox" />',
			'email'   => __( 'Email', 'oli-back-in-stock-notifications' ),
			'product' => __( 'Product', 'oli-back-in-stock-notifications' ),
			'status'  => __( 'Status', 'oli-back-in-stock-notifications' ),
			'created' => __( 'Signed up', 'oli-back-in-stock-notifications' ),
			'actions' => __( 'Actions', 'oli-back-in-stock-notifications' ),
		);
	}

	/**
	 * Sortable columns.
	 *
	 * @since 0.1.0
	 * @return array<string,array<int,mixed>>
	 */
	protected function get_sortable_columns() {
		return array(
			'created' => array( 'created_at', true ),
		);
	}

	/**
	 * Status views.
	 *
	 * @since 0.1.0
	 * @return array<string,string>
	 */
	protected function get_views() {
		$current = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$views   = array();
		$base    = admin_url( 'admin.php?page=oli-bis' );
		$all     = '<a href="' . esc_url( $base ) . '"' . ( '' === $current ? ' class="current"' : '' ) . '>' . esc_html__( 'All', 'oli-back-in-stock-notifications' ) . '</a>';

		$views['all'] = $all;

		foreach ( array( 'pending', 'active', 'queued', 'notified', 'unsubscribed' ) as $status ) {
			$url = add_query_arg( 'status', $status, $base );
			$views[ $status ] = '<a href="' . esc_url( $url ) . '"' . ( $current === $status ? ' class="current"' : '' ) . '>' . esc_html( ucfirst( $status ) ) . '</a>';
		}

		return $views;
	}

	/**
	 * Bulk actions.
	 *
	 * @since 0.1.0
	 * @return array<string,string>
	 */
	protected function get_bulk_actions() {
		return array(
			'delete' => __( 'Delete', 'oli-back-in-stock-notifications' ),
			'notify' => __( 'Send notification', 'oli-back-in-stock-notifications' ),
		);
	}

	/**
	 * Prepares items.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function prepare_items() {
		$per_page = (int) $this->get_items_per_page( 'oli_bis_per_page', 20 );
		$page     = $this->get_pagenum();
		$args     = array(
			'page'     => $page,
			'per_page' => $per_page,
		);

		if ( ! empty( $_GET['status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['status'] = sanitize_key( wp_unslash( $_GET['status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( ! empty( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['search'] = sanitize_text_field( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		if ( ! empty( $_GET['product_id'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$args['product_id'] = absint( wp_unslash( $_GET['product_id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		$result      = Oli_BIS_Repository::query( $args );
		$this->items = $result['items'];

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );

		$this->set_pagination_args(
			array(
				'total_items' => $result['total'],
				'per_page'    => $per_page,
			)
		);
	}

	/**
	 * Checkbox column.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $item Item.
	 * @return string
	 */
	protected function column_cb( $item ) {
		return '<input type="checkbox" name="subscription_ids[]" value="' . esc_attr( (string) $item->get_id() ) . '" />';
	}

	/**
	 * Default column.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $item        Item.
	 * @param string               $column_name Column.
	 * @return string
	 */
	protected function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'email':
				return esc_html( $item->get_email() );
			case 'status':
				return esc_html( $item->get_status() );
			case 'created':
				return esc_html( (string) $item->get( 'created_at', '' ) );
			default:
				return '';
		}
	}

	/**
	 * Product column.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $item Item.
	 * @return string
	 */
	protected function column_product( $item ) {
		$product = wc_get_product( $item->get_target_product_id() );

		if ( ! $product ) {
			return esc_html( (string) $item->get_target_product_id() );
		}

		$url = get_edit_post_link( $item->get_product_id() );

		return '<a href="' . esc_url( $url ? $url : $product->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a>';
	}

	/**
	 * Row actions.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $item Item.
	 * @return string
	 */
	protected function column_actions( $item ) {
		$notify = wp_nonce_url(
			add_query_arg(
				array(
					'page'            => 'oli-bis',
					'oli_bis_row'     => 'notify',
					'subscription_id' => $item->get_id(),
				),
				admin_url( 'admin.php' )
			),
			'oli_bis_row_' . $item->get_id()
		);

		$delete = wp_nonce_url(
			add_query_arg(
				array(
					'page'            => 'oli-bis',
					'oli_bis_row'     => 'delete',
					'subscription_id' => $item->get_id(),
				),
				admin_url( 'admin.php' )
			),
			'oli_bis_row_' . $item->get_id()
		);

		return '<a href="' . esc_url( $notify ) . '">' . esc_html__( 'Notify', 'oli-back-in-stock-notifications' ) . '</a> | <a href="' . esc_url( $delete ) . '">' . esc_html__( 'Delete', 'oli-back-in-stock-notifications' ) . '</a>';
	}

	/**
	 * No items text.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function no_items() {
		esc_html_e( 'No stock notifications found.', 'oli-back-in-stock-notifications' );
	}
}
