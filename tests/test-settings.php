<?php
/**
 * Settings tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests settings defaults and sanitization.
 */
class Oli_BIS_Settings_Test extends TestCase {

	public function test_defaults_include_required_keys() {
		$defaults = Oli_BIS_Settings::defaults();

		$this->assertSame( 'no', $defaults['double_optin'] );
		$this->assertSame( 'Notify me', $defaults['button_text'] );
		$this->assertSame( 50, $defaults['batch_size'] );
		$this->assertArrayHasKey( 'allow_backorder', $defaults );
		$this->assertArrayHasKey( 'gdpr_checkbox', $defaults );
		$this->assertSame( 'no', $defaults['auto_cleanup_notified'] );
		$this->assertSame( 30, $defaults['cleanup_notified_days'] );
		$this->assertSame( '', $defaults['notification_body'] );
		$this->assertSame( '', $defaults['signup_body'] );
	}

	public function test_sanitize_clamps_and_yes_no() {
		$clean = Oli_BIS_Settings::sanitize(
			array(
				'double_optin'           => 'yes',
				'require_account'        => 'no',
				'batch_size'             => 999,
				'rate_limit_window'      => 10,
				'delete_unverified_days' => 0,
				'gdpr_text'              => '<b>Agree</b>',
				'auto_cleanup_notified'  => 'yes',
				'cleanup_notified_days'  => 0,
				'notification_body'      => 'Hi {product_name}',
			)
		);

		$this->assertSame( 'yes', $clean['double_optin'] );
		$this->assertSame( 'no', $clean['require_account'] );
		$this->assertSame( 200, $clean['batch_size'] );
		$this->assertSame( 60, $clean['rate_limit_window'] );
		$this->assertSame( 1, $clean['delete_unverified_days'] );
		$this->assertSame( 'Agree', $clean['gdpr_text'] );
		$this->assertSame( 'yes', $clean['auto_cleanup_notified'] );
		$this->assertSame( 1, $clean['cleanup_notified_days'] );
		$this->assertSame( 'Hi {product_name}', $clean['notification_body'] );
	}

	public function test_enabled_reads_sanitized_values() {
		$this->assertFalse( Oli_BIS_Settings::enabled( 'double_optin' ) );
	}
}
