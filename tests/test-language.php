<?php
/**
 * Language helper tests.
 *
 * @package Oli_BIS
 */

use PHPUnit\Framework\TestCase;

/**
 * Tests locale normalization used for TranslatePress.
 */
class Oli_BIS_Language_Test extends TestCase {

	public function test_normalizes_short_codes() {
		$this->assertSame( 'fr_FR', Oli_BIS_Language::normalize( 'fr' ) );
		$this->assertSame( 'en_US', Oli_BIS_Language::normalize( 'en' ) );
		$this->assertSame( 'fr_FR', Oli_BIS_Language::normalize( 'fr-FR' ) );
	}
}
