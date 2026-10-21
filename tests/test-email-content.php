<?php
/**
 * Custom email body tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests placeholder replacement.
 */
class Oli_BIS_Email_Content_Test extends TestCase {

	public function test_replace_placeholders() {
		$out = Oli_BIS_Email_Content::replace(
			'Hello {product_name} at {product_url} from {site_name}',
			array(
				'{product_name}' => 'Blue Shirt',
				'{product_url}'  => 'https://shop.example/p/1',
				'{site_name}'    => 'Example Shop',
			)
		);

		$this->assertSame( 'Hello Blue Shirt at https://shop.example/p/1 from Example Shop', $out );
	}

	public function test_replace_empty_template() {
		$this->assertSame( '', Oli_BIS_Email_Content::replace( '', array( '{product_name}' => 'X' ) ) );
		$this->assertSame( '', Oli_BIS_Email_Content::replace( null, array() ) );
	}

	public function test_custom_body_reads_setting() {
		$this->assertSame( '', Oli_BIS_Email_Content::custom_body( 'notification_body', array( '{product_name}' => 'X' ) ) );
	}
}
