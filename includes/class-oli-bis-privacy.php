<?php
/**
 * Privacy exporter and eraser.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Integrates with the WordPress privacy tools.
 *
 * @since 0.1.0
 */
class Oli_BIS_Privacy {

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'register_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_eraser' ) );
	}

	/**
	 * Registers the exporter.
	 *
	 * @since 0.1.0
	 * @param array<string,array<string,mixed>> $exporters Exporters.
	 * @return array<string,array<string,mixed>>
	 */
	public function register_exporter( $exporters ) {
		$exporters['oli-bis'] = array(
			'exporter_friendly_name' => __( 'Stock notifications', 'oli-back-in-stock-notifications' ),
			'callback'               => array( $this, 'export' ),
		);

		return $exporters;
	}

	/**
	 * Registers the eraser.
	 *
	 * @since 0.1.0
	 * @param array<string,array<string,mixed>> $erasers Erasers.
	 * @return array<string,array<string,mixed>>
	 */
	public function register_eraser( $erasers ) {
		$erasers['oli-bis'] = array(
			'eraser_friendly_name' => __( 'Stock notifications', 'oli-back-in-stock-notifications' ),
			'callback'             => array( $this, 'erase' ),
		);

		return $erasers;
	}

	/**
	 * Exports subscriptions for an email.
	 *
	 * @since 0.1.0
	 * @param string $email Email.
	 * @param int    $page  Page.
	 * @return array<string,mixed>
	 */
	public function export( $email, $page = 1 ) {
		$result = Oli_BIS_Repository::query(
			array(
				'email'    => $email,
				'page'     => $page,
				'per_page' => 50,
			)
		);

		$items = array();

		foreach ( $result['items'] as $subscription ) {
			$items[] = array(
				'group_id'    => 'oli_bis_subscriptions',
				'group_label' => __( 'Stock notifications', 'oli-back-in-stock-notifications' ),
				'item_id'     => 'oli-bis-' . $subscription->get_id(),
				'data'        => array(
					array(
						'name'  => __( 'Email', 'oli-back-in-stock-notifications' ),
						'value' => $subscription->get_email(),
					),
					array(
						'name'  => __( 'Product ID', 'oli-back-in-stock-notifications' ),
						'value' => (string) $subscription->get_target_product_id(),
					),
					array(
						'name'  => __( 'Status', 'oli-back-in-stock-notifications' ),
						'value' => $subscription->get_status(),
					),
					array(
						'name'  => __( 'Created', 'oli-back-in-stock-notifications' ),
						'value' => (string) $subscription->get( 'created_at', '' ),
					),
				),
			);
		}

		return array(
			'data' => $items,
			'done' => count( $result['items'] ) < 50,
		);
	}

	/**
	 * Deletes subscriptions for an email.
	 *
	 * @since 0.1.0
	 * @param string $email Email.
	 * @param int    $page  Page.
	 * @return array<string,mixed>
	 */
	public function erase( $email, $page = 1 ) {
		unset( $page );

		$result  = Oli_BIS_Repository::query(
			array(
				'email'    => $email,
				'per_page' => 200,
			)
		);
		$removed = 0;

		foreach ( $result['items'] as $subscription ) {
			if ( Oli_BIS_Repository::delete( $subscription->get_id() ) ) {
				++$removed;
			}
		}

		return array(
			'items_removed'  => $removed > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
