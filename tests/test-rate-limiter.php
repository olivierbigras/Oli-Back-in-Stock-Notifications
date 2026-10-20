<?php
/**
 * Rate limiter tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests rate-limit decisions.
 */
class Oli_BIS_Rate_Limiter_Test extends TestCase {

	public function test_is_allowed_until_max() {
		$this->assertTrue( Oli_BIS_Rate_Limiter::is_allowed( 0, 8 ) );
		$this->assertTrue( Oli_BIS_Rate_Limiter::is_allowed( 7, 8 ) );
		$this->assertFalse( Oli_BIS_Rate_Limiter::is_allowed( 8, 8 ) );
		$this->assertFalse( Oli_BIS_Rate_Limiter::is_allowed( 12, 8 ) );
	}

	public function test_key_is_prefixed_and_stable() {
		$this->assertSame( Oli_BIS_Rate_Limiter::key( '1.2.3.4' ), Oli_BIS_Rate_Limiter::key( '1.2.3.4' ) );
		$this->assertStringStartsWith( 'oli_bis_rl_', Oli_BIS_Rate_Limiter::key( '1.2.3.4' ) );
		$this->assertNotSame( Oli_BIS_Rate_Limiter::key( '1.2.3.4' ), Oli_BIS_Rate_Limiter::key( '5.6.7.8' ) );
	}
}
