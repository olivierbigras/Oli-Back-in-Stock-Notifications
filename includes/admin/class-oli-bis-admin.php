<?php
/**
 * Admin screens.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Registers WooCommerce submenu pages and handles list actions.
 *
 * @since 0.1.0
 */
class Oli_BIS_Admin {

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_action( 'admin_menu', array( $this, 'menu' ), 60 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_init', array( $this, 'handle_actions' ) );
		add_filter( 'set-screen-option', array( $this, 'set_screen_option' ), 10, 3 );
	}

	/**
	 * Adds WooCommerce submenu pages.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function menu() {
		$hook = add_submenu_page(
			'woocommerce',
			__( 'Stock Notifications', 'oli-back-in-stock-notifications' ),
			__( 'Stock Notifications', 'oli-back-in-stock-notifications' ),
			'manage_woocommerce',
			'oli-bis',
			array( $this, 'render_notifications' )
		);

		add_submenu_page(
			'woocommerce',
			__( 'Stock Notification dashboard', 'oli-back-in-stock-notifications' ),
			__( 'Stock dashboard', 'oli-back-in-stock-notifications' ),
			'manage_woocommerce',
			'oli-bis-dashboard',
			array( $this, 'render_dashboard' )
		);

		add_action( "load-{$hook}", array( $this, 'screen_options' ) );
	}

	/**
	 * Screen options for the list table.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function screen_options() {
		add_screen_option(
			'per_page',
			array(
				'label'   => __( 'Notifications per page', 'oli-back-in-stock-notifications' ),
				'default' => 20,
				'option'  => 'oli_bis_per_page',
			)
		);
	}

	/**
	 * Saves the screen option.
	 *
	 * @since 0.1.0
	 * @param mixed  $status Status.
	 * @param string $option Option.
	 * @param mixed  $value  Value.
	 * @return mixed
	 */
	public function set_screen_option( $status, $option, $value ) {
		if ( 'oli_bis_per_page' === $option ) {
			return absint( $value );
		}

		return $status;
	}

	/**
	 * Enqueues admin assets.
	 *
	 * @since 0.1.0
	 * @param string $hook Hook.
	 * @return void
	 */
	public function enqueue( $hook ) {
		if ( false === strpos( $hook, 'oli-bis' ) ) {
			return;
		}

		wp_enqueue_style(
			'oli-bis-admin',
			OLI_BIS_URL . 'assets/css/admin.css',
			array(),
			OLI_BIS_VERSION
		);
	}

	/**
	 * Handles export, delete, notify, and add-new actions.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function handle_actions() {
		if ( empty( $_REQUEST['page'] ) || 'oli-bis' !== sanitize_key( wp_unslash( $_REQUEST['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		if ( ! empty( $_GET['oli_bis_export'] ) ) {
			check_admin_referer( 'oli_bis_export' );
			$this->export_csv();
		}

		if ( ! empty( $_GET['oli_bis_row'] ) && ! empty( $_GET['subscription_id'] ) ) {
			$id = absint( wp_unslash( $_GET['subscription_id'] ) );
			check_admin_referer( 'oli_bis_row_' . $id );
			$this->apply_action( sanitize_key( wp_unslash( $_GET['oli_bis_row'] ) ), array( $id ) );
			wp_safe_redirect( admin_url( 'admin.php?page=oli-bis&oli_bis_updated=1' ) );
			exit;
		}

		$bulk = '';

		if ( ! empty( $_POST['oli_bis_admin_action'] ) ) {
			$bulk = sanitize_key( wp_unslash( $_POST['oli_bis_admin_action'] ) );
		} elseif ( ! empty( $_REQUEST['action'] ) && in_array( sanitize_key( wp_unslash( $_REQUEST['action'] ) ), array( 'delete', 'notify' ), true ) ) {
			$bulk = sanitize_key( wp_unslash( $_REQUEST['action'] ) );
		}

		if ( '' === $bulk ) {
			return;
		}

		check_admin_referer( 'oli_bis_admin', 'oli_bis_admin_nonce' );

		$action = $bulk;

		if ( 'add' === $action ) {
			$this->add_manual();
			return;
		}

		$ids = array();

		if ( ! empty( $_POST['subscription_ids'] ) && is_array( $_POST['subscription_ids'] ) ) {
			$ids = array_map( 'absint', wp_unslash( $_POST['subscription_ids'] ) );
		} elseif ( ! empty( $_POST['subscription_id'] ) ) {
			$ids = array( absint( wp_unslash( $_POST['subscription_id'] ) ) );
		}

		$this->apply_action( $action, $ids );

		wp_safe_redirect( admin_url( 'admin.php?page=oli-bis&oli_bis_updated=1' ) );
		exit;
	}

	/**
	 * Applies a delete or notify action to IDs.
	 *
	 * @since 0.1.0
	 * @param string     $action Action.
	 * @param array<int> $ids    IDs.
	 * @return void
	 */
	private function apply_action( $action, array $ids ) {
		foreach ( $ids as $id ) {
			$id = absint( $id );

			if ( ! $id ) {
				continue;
			}

			if ( 'delete' === $action ) {
				Oli_BIS_Repository::delete( $id );
			} elseif ( 'notify' === $action ) {
				$subscription = Oli_BIS_Repository::get( $id );
				if ( $subscription ) {
					Oli_BIS_Scheduler::notify_one( $subscription );
				}
			}
		}
	}

	/**
	 * Creates a notification from the admin form.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function add_manual() {
		$email      = isset( $_POST['email'] ) ? sanitize_email( wp_unslash( $_POST['email'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		$product    = $product_id ? wc_get_product( $product_id ) : null;

		if ( ! is_email( $email ) || ! $product ) {
			wp_safe_redirect( admin_url( 'admin.php?page=oli-bis&oli_bis_error=1' ) );
			exit;
		}

		$ids = Oli_BIS_Compatibility::ids_for_product( $product );
		Oli_BIS_Repository::upsert(
			array(
				'email'        => $email,
				'product_id'   => $ids['product_id'],
				'variation_id' => $ids['variation_id'],
				'status'       => 'active',
				'language'     => get_locale(),
				'source'       => 'admin',
				'consent'      => true,
			)
		);

		wp_safe_redirect( admin_url( 'admin.php?page=oli-bis&oli_bis_updated=1' ) );
		exit;
	}

	/**
	 * Streams a CSV export.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function export_csv() {
		$args = array(
			'per_page' => 5000,
			'page'     => 1,
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

		$result = Oli_BIS_Repository::query( $args );
		$rows   = array();

		foreach ( $result['items'] as $subscription ) {
			$rows[] = $subscription->to_array();
		}

		$csv      = Oli_BIS_CSV::to_string( $rows );
		$filename = 'oli-bis-subscriptions-' . gmdate( 'Y-m-d' ) . '.csv';

		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		echo $csv; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSV download.
		exit;
	}

	/**
	 * Renders the notifications list.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render_notifications() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'oli-back-in-stock-notifications' ) );
		}

		$table = new Oli_BIS_List_Table();
		$table->prepare_items();

		$export_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'           => 'oli-bis',
					'oli_bis_export' => '1',
					'status'         => isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					's'              => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '', // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'product_id'     => isset( $_GET['product_id'] ) ? absint( wp_unslash( $_GET['product_id'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				),
				admin_url( 'admin.php' )
			),
			'oli_bis_export'
		);

		echo '<div class="wrap">';
		echo '<h1 class="wp-heading-inline">' . esc_html__( 'Stock Notifications', 'oli-back-in-stock-notifications' ) . '</h1>';
		echo ' <a class="page-title-action" href="' . esc_url( $export_url ) . '">' . esc_html__( 'Export CSV', 'oli-back-in-stock-notifications' ) . '</a>';
		echo '<hr class="wp-header-end" />';

		if ( ! empty( $_GET['oli_bis_updated'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Notifications updated.', 'oli-back-in-stock-notifications' ) . '</p></div>';
		}

		echo '<h2>' . esc_html__( 'Add notification', 'oli-back-in-stock-notifications' ) . '</h2>';
		echo '<form method="post" class="oli-bis-add-form">';
		wp_nonce_field( 'oli_bis_admin', 'oli_bis_admin_nonce' );
		echo '<input type="hidden" name="oli_bis_admin_action" value="add" />';
		echo '<input type="email" name="email" class="regular-text" required placeholder="' . esc_attr__( 'Customer email', 'oli-back-in-stock-notifications' ) . '" /> ';
		echo '<input type="number" name="product_id" class="small-text" required min="1" placeholder="' . esc_attr__( 'Product ID', 'oli-back-in-stock-notifications' ) . '" /> ';
		submit_button( __( 'Add notification', 'oli-back-in-stock-notifications' ), 'secondary', 'submit', false );
		echo '</form>';

		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="oli-bis" />';
		if ( ! empty( $_GET['status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			echo '<input type="hidden" name="status" value="' . esc_attr( sanitize_key( wp_unslash( $_GET['status'] ) ) ) . '" />'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		echo '<label class="screen-reader-text" for="oli-bis-product-search">' . esc_html__( 'Product ID', 'oli-back-in-stock-notifications' ) . '</label>';
		echo '<input type="number" id="oli-bis-product-search" name="product_id" value="' . esc_attr( (string) ( isset( $_GET['product_id'] ) ? absint( wp_unslash( $_GET['product_id'] ) ) : 0 ) ) . '" placeholder="' . esc_attr__( 'Product ID', 'oli-back-in-stock-notifications' ) . '" /> '; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$table->search_box( __( 'Search emails', 'oli-back-in-stock-notifications' ), 'oli-bis' );
		echo '</form>';

		echo '<form method="post">';
		wp_nonce_field( 'oli_bis_admin', 'oli_bis_admin_nonce' );
		$table->display();
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Renders the dashboard.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function render_dashboard() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'oli-back-in-stock-notifications' ) );
		}

		( new Oli_BIS_Dashboard() )->render();
	}
}
