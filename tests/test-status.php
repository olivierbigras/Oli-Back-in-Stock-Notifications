<?php
/**
 * Status transition tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests status machine.
 */
class Oli_BIS_Status_Test extends TestCase {

	public function test_allowed_transitions() {
		$this->assertTrue( Oli_BIS_Status::can_transition( 'pending', 'active' ) );
		$this->assertTrue( Oli_BIS_Status::can_transition( 'active', 'queued' ) );
		$this->assertTrue( Oli_BIS_Status::can_transition( 'queued', 'notified' ) );
		$this->assertTrue( Oli_BIS_Status::can_transition( 'notified', 'converted' ) );
		$this->assertTrue( Oli_BIS_Status::can_transition( 'active', 'unsubscribed' ) );
		$this->assertTrue( Oli_BIS_Status::can_transition( 'queued', 'active' ) );
	}

	public function test_rejected_transitions() {
		$this->assertFalse( Oli_BIS_Status::can_transition( 'pending', 'notified' ) );
		$this->assertFalse( Oli_BIS_Status::can_transition( 'unsubscribed', 'queued' ) );
		$this->assertFalse( Oli_BIS_Status::can_transition( 'converted', 'queued' ) );
	}

	public function test_waiting() {
		$this->assertTrue( Oli_BIS_Status::is_waiting( 'active' ) );
		$this->assertTrue( Oli_BIS_Status::is_waiting( 'queued' ) );
		$this->assertFalse( Oli_BIS_Status::is_waiting( 'notified' ) );
		$this->assertFalse( Oli_BIS_Status::is_waiting( 'pending' ) );
	}

	public function test_signup_restock_conversion_path() {
		$path = array( 'pending', 'active', 'queued', 'notified', 'converted' );

		for ( $i = 0; $i < count( $path ) - 1; $i++ ) {
			$this->assertTrue( Oli_BIS_Status::can_transition( $path[ $i ], $path[ $i + 1 ] ) );
		}
	}
}
