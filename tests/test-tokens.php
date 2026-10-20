<?php
/**
 * Token unit tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests HMAC token create/verify.
 */
class Oli_BIS_Tokens_Test extends TestCase {

	public function test_roundtrip() {
		$token   = Oli_BIS_Tokens::create( array( 'sid' => 12, 'act' => 'confirm' ), 'secret', 0 );
		$payload = Oli_BIS_Tokens::verify( $token, 'secret' );

		$this->assertIsArray( $payload );
		$this->assertSame( 12, $payload['sid'] );
		$this->assertSame( 'confirm', $payload['act'] );
	}

	public function test_rejects_tampered_token() {
		$token = Oli_BIS_Tokens::create( array( 'sid' => 1 ), 'secret', 0 );
		$this->assertNull( Oli_BIS_Tokens::verify( $token . 'x', 'secret' ) );
		$this->assertNull( Oli_BIS_Tokens::verify( $token, 'other' ) );
		$this->assertNull( Oli_BIS_Tokens::verify( 'not-a-token', 'secret' ) );
	}

	public function test_expired_token() {
		$token = Oli_BIS_Tokens::create( array( 'sid' => 9 ), 'secret', -10 );
		$this->assertNull( Oli_BIS_Tokens::verify( $token, 'secret' ) );
	}

	public function test_base64url_roundtrip() {
		$raw = '{"p":{"a":1}}';
		$this->assertSame( $raw, Oli_BIS_Tokens::base64url_decode( Oli_BIS_Tokens::base64url_encode( $raw ) ) );
	}
}
