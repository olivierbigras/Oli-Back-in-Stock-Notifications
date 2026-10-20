<?php
/**
 * Migrator mapping tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests legacy row mapping for PixM compatibility.
 */
class Oli_BIS_Migrator_Test extends TestCase {

	public function test_maps_common_legacy_keys() {
		$mapped = Oli_BIS_Migrator::map_legacy_row(
			array(
				'user_email'               => 'Pat@Example.com',
				'cwginstock_product_id'    => '15',
				'cwginstock_variation_id'  => '22',
				'status'                   => 'subscribed',
				'language'                 => 'fr',
			)
		);

		$this->assertIsArray( $mapped );
		$this->assertSame( 'pat@example.com', $mapped['email'] );
		$this->assertSame( 15, $mapped['product_id'] );
		$this->assertSame( 22, $mapped['variation_id'] );
		$this->assertSame( 'active', $mapped['status'] );
		$this->assertSame( 'migration', $mapped['source'] );
	}

	public function test_rejects_incomplete_rows() {
		$this->assertNull( Oli_BIS_Migrator::map_legacy_row( array( 'email' => 'a@b.com' ) ) );
		$this->assertNull( Oli_BIS_Migrator::map_legacy_row( array( 'product_id' => 3 ) ) );
	}

	public function test_aliases_mailsent() {
		$mapped = Oli_BIS_Migrator::map_legacy_row(
			array(
				'email'      => 'a@b.com',
				'product_id' => 4,
				'status'     => 'mailsent',
			)
		);

		$this->assertSame( 'notified', $mapped['status'] );
	}
}
