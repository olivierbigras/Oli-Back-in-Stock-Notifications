<?php
/**
 * Shortcode alias registration tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests that legacy shortcode aliases are registered on init.
 */
class Oli_BIS_Shortcode_Test extends TestCase {

	/**
	 * Resets hook and shortcode stubs.
	 *
	 * @return void
	 */
	protected function setUp(): void {
		$GLOBALS['shortcode_tags']           = array();
		$GLOBALS['oli_bis_test_actions']     = array();
		$GLOBALS['oli_bis_test_did_actions'] = array();
		unset( $GLOBALS['oli_bis_test_filters']['oli_bis_legacy_map'] );
	}

	/**
	 * Cleans the legacy map filter after each test.
	 *
	 * @return void
	 */
	protected function tearDown(): void {
		unset( $GLOBALS['oli_bis_test_filters']['oli_bis_legacy_map'] );
		$GLOBALS['shortcode_tags']           = array();
		$GLOBALS['oli_bis_test_actions']     = array();
		$GLOBALS['oli_bis_test_did_actions'] = array();
	}

	public function test_register_hooks_aliases_to_init_without_reading_the_map() {
		$GLOBALS['oli_bis_test_filters']['oli_bis_legacy_map'] = static function ( $map ) {
			$map['shortcodes']['old_notify_form'] = 'oli_bis_notify_form';
			return $map;
		};

		$shortcode = new Oli_BIS_Shortcode();
		$shortcode->register();

		$this->assertTrue( shortcode_exists( 'oli_bis_notify_form' ) );
		$this->assertFalse( shortcode_exists( 'old_notify_form' ) );
		$this->assertArrayHasKey( 'init', $GLOBALS['oli_bis_test_actions'] );
		$this->assertSame( array( $shortcode, 'register_aliases' ), $GLOBALS['oli_bis_test_actions']['init'][0]['callback'] );
		$this->assertSame( 20, $GLOBALS['oli_bis_test_actions']['init'][0]['priority'] );
	}

	public function test_theme_filter_added_after_register_is_honored_on_init() {
		$shortcode = new Oli_BIS_Shortcode();
		$shortcode->register();

		$GLOBALS['oli_bis_test_filters']['oli_bis_legacy_map'] = static function ( $map ) {
			$map['shortcodes']['old_notify_form'] = 'oli_bis_notify_form';
			return $map;
		};

		$this->assertFalse( shortcode_exists( 'old_notify_form' ) );

		$shortcode->register_aliases();

		$this->assertTrue( shortcode_exists( 'old_notify_form' ) );
		$this->assertSame( array( $shortcode, 'render' ), $GLOBALS['shortcode_tags']['old_notify_form'] );
	}

	public function test_skips_alias_when_another_plugin_already_registered_the_tag() {
		add_shortcode( 'old_notify_form', 'other_plugin_shortcode' );

		$GLOBALS['oli_bis_test_filters']['oli_bis_legacy_map'] = static function ( $map ) {
			$map['shortcodes']['old_notify_form'] = 'oli_bis_notify_form';
			return $map;
		};

		$shortcode = new Oli_BIS_Shortcode();
		$shortcode->register_aliases();

		$this->assertSame( 'other_plugin_shortcode', $GLOBALS['shortcode_tags']['old_notify_form'] );
	}

	public function test_register_aliases_immediately_when_init_already_ran() {
		$GLOBALS['oli_bis_test_did_actions']['init'] = 1;
		$GLOBALS['oli_bis_test_filters']['oli_bis_legacy_map'] = static function ( $map ) {
			$map['shortcodes']['old_notify_form'] = 'oli_bis_notify_form';
			return $map;
		};

		$shortcode = new Oli_BIS_Shortcode();
		$shortcode->register();

		$this->assertTrue( shortcode_exists( 'old_notify_form' ) );
		$this->assertArrayNotHasKey( 'init', $GLOBALS['oli_bis_test_actions'] );
	}

	public function test_register_aliases_does_nothing_when_map_is_empty() {
		$shortcode = new Oli_BIS_Shortcode();
		$shortcode->register();
		$shortcode->register_aliases();

		$this->assertSame( array( 'oli_bis_notify_form' ), array_keys( $GLOBALS['shortcode_tags'] ) );
	}
}
