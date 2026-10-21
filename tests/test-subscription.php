<?php
/**
 * Subscription object tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests subscription accessors.
 */
class Oli_BIS_Subscription_Test extends TestCase {

	public function test_target_product_prefers_variation() {
		$item = new Oli_BIS_Subscription(
			array(
				'id'           => 5,
				'product_id'   => 10,
				'variation_id' => 18,
				'email'        => 'a@b.com',
				'status'       => 'active',
				'attributes'   => '{"attribute_pa_color":"blue"}',
			)
		);

		$this->assertSame( 5, $item->get_id() );
		$this->assertSame( 18, $item->get_target_product_id() );
		$this->assertSame( 'blue', $item->get_attributes()['attribute_pa_color'] );
	}

	public function test_simple_product_target() {
		$item = new Oli_BIS_Subscription(
			array(
				'product_id'   => 7,
				'variation_id' => 0,
			)
		);

		$this->assertSame( 7, $item->get_target_product_id() );
		$this->assertSame( array(), $item->get_attributes() );
	}
}
