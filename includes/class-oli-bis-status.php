<?php
/**
 * Subscription status transitions.
 *
 * @package Oli_BIS
 * @since   0.1.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Validates status changes without WordPress.
 *
 * @since 0.1.0
 */
class Oli_BIS_Status {

	/**
	 * Allowed transitions map.
	 *
	 * @since 0.1.0
	 * @return array<string,array<int,string>>
	 */
	public static function map() {
		return array(
			'pending'      => array( 'active', 'cancelled', 'unsubscribed' ),
			'active'       => array( 'queued', 'unsubscribed', 'cancelled' ),
			'queued'       => array( 'notified', 'active', 'cancelled', 'unsubscribed' ),
			'notified'     => array( 'converted', 'active', 'unsubscribed' ),
			'unsubscribed' => array( 'pending', 'active' ),
			'cancelled'    => array( 'pending', 'active' ),
			'converted'    => array( 'active' ),
		);
	}

	/**
	 * Whether a transition is allowed.
	 *
	 * @since 0.1.0
	 * @param string $from Current status.
	 * @param string $to   Target status.
	 * @return bool
	 */
	public static function can_transition( $from, $to ) {
		if ( $from === $to ) {
			return true;
		}

		$map = self::map();

		return isset( $map[ $from ] ) && in_array( $to, $map[ $from ], true );
	}

	/**
	 * Whether the status is waiting for stock.
	 *
	 * @since 0.1.0
	 * @param string $status Status.
	 * @return bool
	 */
	public static function is_waiting( $status ) {
		return in_array( $status, array( 'active', 'queued' ), true );
	}
}
