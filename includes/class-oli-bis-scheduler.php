<?php
/**
 * Action Scheduler batching.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Prepares and sends notification batches.
 *
 * @since 0.1.0
 */
class Oli_BIS_Scheduler {

	const PREPARE_HOOK = 'oli_bis_prepare_restock';
	const BATCH_HOOK   = 'oli_bis_process_restock_batch';

	/**
	 * Registers hooks.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register() {
		add_action( self::PREPARE_HOOK, array( $this, 'prepare' ), 10, 2 );
		add_action( self::BATCH_HOOK, array( $this, 'process_batch' ), 10, 2 );
		add_action( 'oli_bis_cleanup_unverified', array( $this, 'cleanup_unverified' ) );
	}

	/**
	 * Schedules a prepare job if none is pending.
	 *
	 * @since 0.1.0
	 * @param int $product_id   Product ID.
	 * @param int $variation_id Variation ID.
	 * @return void
	 */
	public static function schedule_prepare( $product_id, $variation_id = 0 ) {
		$args = array( absint( $product_id ), absint( $variation_id ) );

		if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::PREPARE_HOOK, $args, 'oli-bis' ) ) {
			return;
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::PREPARE_HOOK, $args, 'oli-bis' );
			return;
		}

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time(), self::PREPARE_HOOK, $args, 'oli-bis' );
			return;
		}

		do_action( 'oli_bis_prepare_restock', $product_id, $variation_id );
	}

	/**
	 * Marks active subscribers as queued and starts batches.
	 *
	 * @since 0.1.0
	 * @param int $product_id   Product ID.
	 * @param int $variation_id Variation ID.
	 * @return void
	 */
	public function prepare( $product_id, $variation_id = 0 ) {
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );

		if ( ! $product || ! $this->is_restocked_enough( $product ) ) {
			Oli_BIS_Repository::unqueue( $product_id, $variation_id );
			return;
		}

		Oli_BIS_Repository::queue_active( $product_id, $variation_id );
		$this->schedule_batch( $product_id, $variation_id );
	}

	/**
	 * Sends one batch of queued emails.
	 *
	 * @since 0.1.0
	 * @param int $product_id   Product ID.
	 * @param int $variation_id Variation ID.
	 * @return void
	 */
	public function process_batch( $product_id, $variation_id = 0 ) {
		$product = wc_get_product( $variation_id ? $variation_id : $product_id );

		if ( ! $product || ! $this->is_restocked_enough( $product ) ) {
			Oli_BIS_Repository::unqueue( $product_id, $variation_id );
			return;
		}

		$limit = absint( Oli_BIS_Settings::get( 'batch_size', 50 ) );
		$items = Oli_BIS_Repository::get_queued( $product_id, $variation_id, $limit );
		$sent  = 0;

		foreach ( $items as $subscription ) {
			do_action( 'oli_bis_send_notification', $subscription );

			Oli_BIS_Repository::set_status(
				$subscription->get_id(),
				'notified',
				array( 'notified_at' => Oli_BIS_Repository::now() )
			);
			++$sent;
		}

		if ( $sent ) {
			Oli_BIS_Stats::increment( 'notifications', $sent );
		}

		if ( count( $items ) >= $limit ) {
			$this->schedule_batch( $product_id, $variation_id );
		}
	}

	/**
	 * Deletes stale pending signups.
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function cleanup_unverified() {
		if ( ! Oli_BIS_Settings::enabled( 'double_optin' ) ) {
			return;
		}

		$days = absint( Oli_BIS_Settings::get( 'delete_unverified_days', 7 ) );
		Oli_BIS_Repository::delete_unverified_older_than( $days );
	}

	/**
	 * Schedules a batch job.
	 *
	 * @since 0.1.0
	 * @param int $product_id   Product ID.
	 * @param int $variation_id Variation ID.
	 * @return void
	 */
	private function schedule_batch( $product_id, $variation_id ) {
		$args = array( absint( $product_id ), absint( $variation_id ) );

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::BATCH_HOOK, $args, 'oli-bis' );
			return;
		}

		if ( function_exists( 'as_schedule_single_action' ) ) {
			as_schedule_single_action( time() + 5, self::BATCH_HOOK, $args, 'oli-bis' );
			return;
		}

		do_action( 'oli_bis_process_restock_batch', $product_id, $variation_id );
	}

	/**
	 * Whether the product currently qualifies as restocked.
	 *
	 * @since 0.1.0
	 * @param WC_Product $product Product.
	 * @return bool
	 */
	private function is_restocked_enough( $product ) {
		if ( ! Oli_BIS_Compatibility::is_enabled_for_product( $product ) ) {
			return false;
		}

		if ( ! $product->is_in_stock() && ! ( Oli_BIS_Settings::enabled( 'allow_backorder' ) && $product->is_on_backorder() ) ) {
			return false;
		}

		$min = absint( Oli_BIS_Settings::get( 'min_stock', 0 ) );

		if ( $min > 0 && $product->managing_stock() && $product->get_stock_quantity() < $min ) {
			return false;
		}

		return true;
	}

	/**
	 * Queues a single subscription for a manual notify.
	 *
	 * @since 0.1.0
	 * @param Oli_BIS_Subscription $subscription Subscription.
	 * @return bool
	 */
	public static function notify_one( $subscription ) {
		$product = wc_get_product( $subscription->get_target_product_id() );

		if ( ! $product ) {
			return false;
		}

		do_action( 'oli_bis_send_notification', $subscription );

		$ok = Oli_BIS_Repository::set_status(
			$subscription->get_id(),
			'notified',
			array( 'notified_at' => Oli_BIS_Repository::now() )
		);

		if ( $ok ) {
			Oli_BIS_Stats::increment( 'notifications' );
		}

		return $ok;
	}
}
