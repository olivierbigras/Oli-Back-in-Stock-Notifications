<?php
/**
 * Migrator mapping and generic import-planning tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests generic row mapping, option/meta maps, and idempotent dry-run planning.
 */
class Oli_BIS_Migrator_Test extends TestCase {

	protected function tearDown(): void {
		unset( $GLOBALS['oli_bis_test_filters']['oli_bis_legacy_map'] );
	}

	public function test_legacy_map_is_empty_by_default() {
		$map = Oli_BIS_Migrator::legacy_map();

		$this->assertSame( array(), $map['options'] );
		$this->assertSame( array(), $map['metas'] );
		$this->assertSame( array(), $map['shortcodes'] );
		$this->assertSame( array(), $map['tables'] );
		$this->assertSame( 'legacy', $map['source'] );
		$this->assertFalse( Oli_BIS_Migrator::is_configured() );
		$this->assertSame( array(), Oli_BIS_Migrator::candidate_suffixes() );
		$this->assertSame( array(), Oli_BIS_Migrator::shortcode_aliases() );
	}

	public function test_legacy_map_filter_sanitizes_keys() {
		$GLOBALS['oli_bis_test_filters']['oli_bis_legacy_map'] = static function ( $map ) {
			$map['options']    = array( 'Old-Email-Template!' => 'notification_body' );
			$map['metas']      = array( 'old_enabled' => '_oli_bis_enabled' );
			$map['shortcodes'] = array( 'old_notify_form' => 'oli_bis_notify_form' );
			$map['tables']     = array( 'old_waitlist_subscribers' );
			$map['source']     = 'custom-source';
			return $map;
		};

		$map = Oli_BIS_Migrator::legacy_map();

		$this->assertSame( array( 'old-email-template' => 'notification_body' ), $map['options'] );
		$this->assertSame( array( 'old_enabled' => '_oli_bis_enabled' ), $map['metas'] );
		$this->assertSame( array( 'old_notify_form' => 'oli_bis_notify_form' ), $map['shortcodes'] );
		$this->assertSame( array( 'old_waitlist_subscribers' ), $map['tables'] );
		$this->assertSame( 'custom-source', $map['source'] );
		$this->assertTrue( Oli_BIS_Migrator::is_configured() );
		$this->assertSame( array( 'old_notify_form' => 'oli_bis_notify_form' ), Oli_BIS_Migrator::shortcode_aliases() );
	}

	public function test_shortcode_aliases_ignore_other_targets() {
		$this->assertSame(
			array(),
			Oli_BIS_Migrator::shortcode_aliases(
				array(
					'shortcodes' => array(
						'other_tag' => 'something_else',
					),
				)
			)
		);
	}

	public function test_maps_common_legacy_keys() {
		$mapped = Oli_BIS_Migrator::map_legacy_row(
			array(
				'user_email'   => 'Pat@Example.com',
				'product_id'   => '15',
				'variation_id' => '22',
				'status'       => 'subscribed',
				'language'     => 'fr',
			)
		);

		$this->assertIsArray( $mapped );
		$this->assertSame( 'pat@example.com', $mapped['email'] );
		$this->assertSame( 15, $mapped['product_id'] );
		$this->assertSame( 22, $mapped['variation_id'] );
		$this->assertSame( 'active', $mapped['status'] );
		$this->assertSame( 'legacy', $mapped['source'] );
	}

	public function test_rejects_incomplete_rows() {
		$this->assertNull( Oli_BIS_Migrator::map_legacy_row( array( 'email' => 'a@b.com' ) ) );
		$this->assertNull( Oli_BIS_Migrator::map_legacy_row( array( 'product_id' => 3 ) ) );
		$this->assertNull( Oli_BIS_Migrator::map_simple_waitlist_row( array( 'email' => 'a@b.com' ) ) );
		$this->assertNull( Oli_BIS_Migrator::map_simple_waitlist_row( array( 'product_id' => 3 ) ) );
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

	public function test_maps_pending_simple_row() {
		$mapped = Oli_BIS_Migrator::map_simple_waitlist_row(
			array(
				'id'         => '7',
				'product_id' => '42',
				'email'      => 'Alex@Example.com',
				'date_added' => '2024-03-01 10:00:00',
				'is_sent'    => '0',
				'date_sent'  => null,
			)
		);

		$this->assertIsArray( $mapped );
		$this->assertSame( 'alex@example.com', $mapped['email'] );
		$this->assertSame( 42, $mapped['product_id'] );
		$this->assertSame( 0, $mapped['variation_id'] );
		$this->assertSame( 'active', $mapped['status'] );
		$this->assertSame( 'legacy', $mapped['source'] );
		$this->assertSame( 'legacy', $mapped['legacy_source'] );
		$this->assertSame( 7, $mapped['legacy_id'] );
		$this->assertSame( '2024-03-01 10:00:00', $mapped['created_at'] );
		$this->assertSame( '2024-03-01 10:00:00', $mapped['confirmed_at'] );
		$this->assertSame( '', $mapped['notified_at'] );
		$this->assertTrue( $mapped['consent'] );
	}

	public function test_maps_sent_simple_row() {
		$mapped = Oli_BIS_Migrator::map_simple_waitlist_row(
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

	public function test_map_legacy_row_resolves_parent_variation() {
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
		$this->assertSame( 'legacy', $mapped['legacy_source'] );
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
		$mapped = Oli_BIS_Migrator::map_simple_waitlist_row(
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

	public function test_map_options() {
		$key_map = array(
			'old_auto_cleanup'     => 'auto_cleanup_notified',
			'old_email_template'   => 'notification_body',
			'old_signup_template'  => 'signup_body',
			'old_cleanup_days'     => 'cleanup_notified_days',
		);

		$mapped = Oli_BIS_Migrator::map_options(
			array(
				'old_auto_cleanup'    => '1',
				'old_email_template'  => 'Hello {product_name}',
				'old_signup_template' => 'Thanks {site_name}',
				'old_cleanup_days'    => '14',
			),
			$key_map
		);

		$this->assertSame( 'yes', $mapped['auto_cleanup_notified'] );
		$this->assertSame( 'Hello {product_name}', $mapped['notification_body'] );
		$this->assertSame( 'Thanks {site_name}', $mapped['signup_body'] );
		$this->assertSame( 14, $mapped['cleanup_notified_days'] );

		$off = Oli_BIS_Migrator::map_options( array( 'old_auto_cleanup' => '0' ), $key_map );
		$this->assertSame( 'no', $off['auto_cleanup_notified'] );
		$this->assertArrayNotHasKey( 'notification_body', $off );

		$this->assertSame( array(), Oli_BIS_Migrator::map_options( array(), $key_map ) );
		$this->assertSame( array(), Oli_BIS_Migrator::map_options( array( 'old_email_template' => 'Hi' ), array() ) );
	}

	public function test_map_metas() {
		$mapped = Oli_BIS_Migrator::map_metas(
			array(
				'old_enabled' => 'yes',
				'ignored'     => 'no',
			),
			array(
				'old_enabled' => '_oli_bis_enabled',
			)
		);

		$this->assertSame( array( '_oli_bis_enabled' => 'yes' ), $mapped );
		$this->assertSame( array(), Oli_BIS_Migrator::map_metas( array(), array( 'old_enabled' => '_oli_bis_enabled' ) ) );
	}

	public function test_normalize_datetime() {
		$this->assertSame( '', Oli_BIS_Migrator::normalize_datetime( '' ) );
		$this->assertSame( '', Oli_BIS_Migrator::normalize_datetime( '0000-00-00 00:00:00' ) );
		$this->assertSame( '2024-03-01 10:00:00', Oli_BIS_Migrator::normalize_datetime( '2024-03-01 10:00:00' ) );
		$this->assertSame( '2024-03-01 10:11:12', Oli_BIS_Migrator::normalize_datetime( '2024-03-01T10:11:12+00:00' ) );
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
		$this->assertStringContainsString( 'Never deletes source rows, options, or metas', $src );
	}

	public function test_shortcode_registers_only_the_built_in_tag() {
		$src = file_get_contents( dirname( __DIR__ ) . '/includes/class-oli-bis-shortcode.php' );

		$this->assertIsString( $src );
		$this->assertStringContainsString( "add_shortcode( 'oli_bis_notify_form'", $src );
		$this->assertSame( 1, preg_match_all( "/add_shortcode\\(\\s*'[^']+'/", $src ) );
	}
}
