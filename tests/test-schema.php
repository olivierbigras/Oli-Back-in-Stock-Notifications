<?php
/**
 * Schema tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests table SQL and statuses.
 */
class Oli_BIS_Schema_Test extends TestCase {

	public function test_table_names() {
		$this->assertSame( 'wp_oli_bis_subscriptions', Oli_BIS_Schema::subscriptions_table( 'wp_' ) );
		$this->assertSame( 'wp_oli_bis_stats_daily', Oli_BIS_Schema::stats_table( 'wp_' ) );
	}

	public function test_subscription_sql_has_compat_columns() {
		$sql = Oli_BIS_Schema::subscriptions_sql( 'wp_', 'DEFAULT CHARSET=utf8mb4' );

		$this->assertStringContainsString( 'email varchar(191)', $sql );
		$this->assertStringContainsString( 'UNIQUE KEY email_product', $sql );
		$this->assertStringContainsString( 'converted_order_id', $sql );
		$this->assertStringContainsString( 'language varchar(32)', $sql );
	}

	public function test_statuses() {
		$statuses = Oli_BIS_Schema::statuses();
		$this->assertArrayHasKey( 'active', $statuses );
		$this->assertArrayHasKey( 'queued', $statuses );
		$this->assertArrayHasKey( 'notified', $statuses );
	}
}
