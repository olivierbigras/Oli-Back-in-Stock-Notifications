<?php
/**
 * Migrator mapping and PixM import-planning tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests PixM row mapping, option mapping, and idempotent dry-run planning.
 */
class Oli_BIS_Migrator_Test extends TestCase {

	public function test_maps_common_legacy_keys() {
		$mapped = Oli_BIS_Migrator::map_legacy_row(
			array(
				'user_email'              => 'Pat@Example.com',
				'cwginstock_product_id'   => '15',
				'cwginstock_variation_id' => '22',
				'status'                  => 'subscribed',
				'language'                => 'fr',
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
		$this->assertNull( Oli_BIS_Migrator::map_pixm_row( array( 'email' => 'a@b.com' ) ) );
		$this->assertNull( Oli_BIS_Migrator::map_pixm_row( array( 'product_id' => 3 ) ) );
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

	public function test_maps_pixm_pending_row() {
		$mapped = Oli_BIS_Migrator::map_pixm_row(
			array(
				'id'         => '7',
				'product_id' => '42',
				'email'      => 'Alex@Pixm.com',
				'date_added' => '2024-03-01 10:00:00',
				'is_sent'    => '0',
				'date_sent'  => null,
			)
		);

		$this->assertIsArray( $mapped );
		$this->assertSame( 'alex@pixm.com', $mapped['email'] );
		$this->assertSame( 42, $mapped['product_id'] );
		$this->assertSame( 0, $mapped['variation_id'] );
		$this->assertSame( 'active', $mapped['status'] );
		$this->assertSame( 'pixm', $mapped['source'] );
		$this->assertSame( 'pixm', $mapped['legacy_source'] );
		$this->assertSame( 7, $mapped['legacy_id'] );
		$this->assertSame( '2024-03-01 10:00:00', $mapped['created_at'] );
		$this->assertSame( '2024-03-01 10:00:00', $mapped['confirmed_at'] );
		$this->assertSame( '', $mapped['notified_at'] );
		$this->assertTrue( $mapped['consent'] );
	}

	public function test_maps_pixm_sent_row() {
		$mapped = Oli_BIS_Migrator::map_pixm_row(
			array(
				'id'         => 3,
				'product_id' => 10,
				'email'      => 'done@example.com',
				'date_added' => '2024-01-02 03:04:05',
				'is_sent'    => 1,
				'date_sent'  => '2024-02-03 04:05:06',
			)
		);

		$this->assertSame( 'notified', $mapped['status'] );
		$this->assertSame( '2024-02-03 04:05:06', $mapped['notified_at'] );
	}

	public function test_map_legacy_row_uses_pixm_path() {
		$mapped = Oli_BIS_Migrator::map_legacy_row(
			array(
				'id'         => 9,
				'email'      => 'a@b.com',
				'product_id' => 11,
				'date_added' => '2025-05-05 05:05:05',
				'is_sent'    => 0,
				'parent_id'  => 8,
			)
		);

		$this->assertSame( 8, $mapped['product_id'] );
		$this->assertSame( 11, $mapped['variation_id'] );
		$this->assertSame( 'pixm', $mapped['legacy_source'] );
	}

	public function test_resolve_product_ids() {
		$this->assertSame(
			array(
				'product_id'   => 42,
				'variation_id' => 0,
			),
			Oli_BIS_Migrator::resolve_product_ids( 42 )
		);

		$this->assertSame(
			array(
				'product_id'   => 5,
				'variation_id' => 12,
			),
			Oli_BIS_Migrator::resolve_product_ids( 12, 5 )
		);

		$this->assertSame(
			array(
				'product_id'   => 12,
				'variation_id' => 0,
			),
			Oli_BIS_Migrator::resolve_product_ids( 12, 12 )
		);
	}

	public function test_unique_key_normalizes_email() {
		$this->assertSame(
			'pat@example.com|15|22',
			Oli_BIS_Migrator::unique_key( 'Pat@Example.com', 15, 22 )
		);
	}

	public function test_classify_mapped() {
		$mapped = Oli_BIS_Migrator::map_pixm_row(
			array(
				'id'         => 1,
				'email'      => 'a@b.com',
				'product_id' => 4,
				'date_added' => '2024-01-01 00:00:00',
				'is_sent'    => 0,
			)
		);

		$this->assertSame( 'import', Oli_BIS_Migrator::classify_mapped( $mapped, array(), array() ) );
		$this->assertSame( 'skip', Oli_BIS_Migrator::classify_mapped( $mapped, array( 'a@b.com|4|0' => 99 ), array() ) );
		$this->assertSame( 'skip', Oli_BIS_Migrator::classify_mapped( $mapped, array(), array( 1 ) ) );
		$this->assertSame( 'invalid', Oli_BIS_Migrator::classify_mapped( null, array(), array() ) );
		$this->assertSame( 'invalid', Oli_BIS_Migrator::classify_mapped( array( 'email' => '' ), array(), array() ) );
	}

	public function test_map_pixm_options() {
		$mapped = Oli_BIS_Migrator::map_pixm_options(
			array(
				'wc_bis_auto_cleanup'                => '1',
				'wc_bis_email_template'              => 'Hello {product_name}',
				'wc_bis_confirmation_email_template' => 'Thanks {site_name}',
			)
		);

		$this->assertSame( 'yes', $mapped['auto_cleanup_notified'] );
		$this->assertSame( 30, $mapped['cleanup_notified_days'] );
		$this->assertSame( 'Hello {product_name}', $mapped['notification_body'] );
		$this->assertSame( 'Thanks {site_name}', $mapped['signup_body'] );

		$off = Oli_BIS_Migrator::map_pixm_options( array( 'wc_bis_auto_cleanup' => '0' ) );
		$this->assertSame( 'no', $off['auto_cleanup_notified'] );
		$this->assertArrayNotHasKey( 'notification_body', $off );

		$this->assertSame( array(), Oli_BIS_Migrator::map_pixm_options( array() ) );
	}

	public function test_normalize_datetime() {
		$this->assertSame( '', Oli_BIS_Migrator::normalize_datetime( '' ) );
		$this->assertSame( '', Oli_BIS_Migrator::normalize_datetime( '0000-00-00 00:00:00' ) );
		$this->assertSame( '2024-03-01 10:00:00', Oli_BIS_Migrator::normalize_datetime( '2024-03-01 10:00:00' ) );
		$this->assertSame( '2024-03-01 10:11:12', Oli_BIS_Migrator::normalize_datetime( '2024-03-01T10:11:12+00:00' ) );
	}

	public function test_pixm_table_helpers() {
		$this->assertSame( 'wc_back_in_stock_notifications', Oli_BIS_Migrator::PIXM_TABLE );
		$this->assertSame( 'wp_wc_back_in_stock_notifications', Oli_BIS_Migrator::pixm_table( 'wp_' ) );
		$this->assertTrue( Oli_BIS_Migrator::is_pixm_table( 'wp_wc_back_in_stock_notifications', 'wp_' ) );
		$this->assertFalse( Oli_BIS_Migrator::is_pixm_table( 'wp_oli_bis_subscriptions', 'wp_' ) );
		$this->assertContains( 'wc_back_in_stock_notifications', Oli_BIS_Migrator::candidate_suffixes() );
	}

	public function test_plan_rows_is_idempotent_and_skips_duplicates() {
		$rows = array(
			array(
				'id'         => 1,
				'product_id' => 10,
				'email'      => 'one@example.com',
				'date_added' => '2024-01-01 00:00:00',
				'is_sent'    => 0,
			),
			array(
				'id'         => 2,
				'product_id' => 20,
				'email'      => 'two@example.com',
				'date_added' => '2024-01-02 00:00:00',
				'is_sent'    => 1,
				'date_sent'  => '2024-01-03 00:00:00',
			),
			array(
				'id'         => 3,
				'product_id' => 10,
				'email'      => 'one@example.com',
				'date_added' => '2024-01-04 00:00:00',
				'is_sent'    => 0,
			),
			array(
				'id'         => 4,
				'email'      => 'bad',
				'product_id' => 0,
			),
		);

		$first = Oli_BIS_Migrator::plan_rows( $rows );

		$this->assertSame( 2, $first['import'] );
		$this->assertSame( 1, $first['skip'] );
		$this->assertSame( 1, $first['invalid'] );
		$this->assertCount( 2, $first['mapped'] );
		$this->assertSame( 'active', $first['mapped'][0]['status'] );
		$this->assertSame( 'notified', $first['mapped'][1]['status'] );

		$existing = array(
			Oli_BIS_Migrator::unique_key( 'one@example.com', 10, 0 ) => 1,
			Oli_BIS_Migrator::unique_key( 'two@example.com', 20, 0 ) => 2,
		);

		$second = Oli_BIS_Migrator::plan_rows( $rows, $existing, array( 1, 2 ) );

		$this->assertSame( 0, $second['import'] );
		$this->assertSame( 3, $second['skip'] );
		$this->assertSame( 1, $second['invalid'] );
		$this->assertSame( array(), $second['mapped'] );
	}

	public function test_plan_rows_resolves_variations() {
		$rows = array(
			array(
				'id'         => 5,
				'product_id' => 88,
				'email'      => 'var@example.com',
				'date_added' => '2024-06-01 00:00:00',
				'is_sent'    => 0,
			),
		);

		$plan = Oli_BIS_Migrator::plan_rows(
			$rows,
			array(),
			array(),
			static function ( $product_id ) {
				return 88 === (int) $product_id ? 77 : 0;
			}
		);

		$this->assertSame( 1, $plan['import'] );
		$this->assertSame( 77, $plan['mapped'][0]['product_id'] );
		$this->assertSame( 88, $plan['mapped'][0]['variation_id'] );
	}

	public function test_source_never_deleted_in_migrator() {
		$src = file_get_contents( dirname( __DIR__ ) . '/includes/class-oli-bis-migrator.php' );

		$this->assertIsString( $src );
		$this->assertStringNotContainsString( 'DROP TABLE', $src );
		$this->assertDoesNotMatchRegularExpression( '/DELETE\\s+FROM\\s+/i', $src );
		$this->assertStringContainsString( 'Never deletes PixM rows or options', $src );
	}
}
