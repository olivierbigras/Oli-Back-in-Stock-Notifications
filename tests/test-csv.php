<?php
/**
 * CSV tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests CSV export formatting.
 */
class Oli_BIS_CSV_Test extends TestCase {

	public function test_exports_headers_and_rows() {
		$csv = Oli_BIS_CSV::to_string(
			array(
				array(
					'id'         => 1,
					'email'      => 'a@example.com',
					'product_id' => 9,
					'status'     => 'active',
				),
			),
			array( 'id', 'email', 'product_id', 'status' )
		);

		$lines = array_values( array_filter( explode( "\n", str_replace( "\r", '', $csv ) ) ) );

		$this->assertSame( 'id,email,product_id,status', $lines[0] );
		$this->assertStringContainsString( 'a@example.com', $lines[1] );
		$this->assertStringContainsString( 'active', $lines[1] );
	}

	public function test_empty_rows_still_have_headers() {
		$csv = Oli_BIS_CSV::to_string( array() );
		$this->assertStringStartsWith( 'id,email,product_id', $csv );
	}
}
