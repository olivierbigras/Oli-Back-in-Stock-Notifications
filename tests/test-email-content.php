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

	public function test_site_name_aliases_site_title() {
		$out = Oli_BIS_Email_Content::replace(
			'From {site_name} and {site_title}',
			array(
				'{site_title}' => 'Example Shop',
			)
		);

		$this->assertSame( 'From Example Shop and Example Shop', $out );
	}

	public function test_site_title_filled_when_only_site_name_provided() {
		$out = Oli_BIS_Email_Content::replace(
			'From {site_name} and {site_title}',
			array(
				'{site_name}' => 'Example Shop',
			)
		);

		$this->assertSame( 'From Example Shop and Example Shop', $out );
	}

	public function test_with_site_aliases_does_not_override_explicit_values() {
		$placeholders = Oli_BIS_Email_Content::with_site_aliases(
			array(
				'{site_title}' => 'Title Shop',
				'{site_name}'  => 'Name Shop',
			)
		);

		$this->assertSame( 'Title Shop', $placeholders['{site_title}'] );
		$this->assertSame( 'Name Shop', $placeholders['{site_name}'] );
	}

	public function test_email_templates_pass_site_name_as_site_title_alias() {
		$files = array(
			'templates/emails/oli-bis-notification.php',
			'templates/emails/oli-bis-signup.php',
			'templates/emails/plain/oli-bis-notification.php',
			'templates/emails/plain/oli-bis-signup.php',
			'includes/emails/class-oli-bis-email.php',
		);

		foreach ( $files as $relative ) {
			$src = file_get_contents( dirname( __DIR__ ) . '/' . $relative );

			$this->assertIsString( $src, $relative );
			$this->assertStringContainsString( "'{site_title}'", $src, $relative );
			$this->assertStringContainsString( "'{site_name}'", $src, $relative );
		}
	}
}
