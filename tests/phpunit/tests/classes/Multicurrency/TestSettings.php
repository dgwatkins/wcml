<?php

namespace WCML\MultiCurrency;

use WCML\PHPUnit\SettingsMock;

/**
 * @group multi-currency-settings
 */
class TestSettings extends \OTGS_TestCase {

	use SettingsMock;

	public function setUp() {
		parent::setUp();
		$this->setUpSettings();
	}

	/**
	 * @test
	 */
	public function testModeInFullMode() {
		\WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( false );

		Settings::setMode( null );
		$this->assertNull( Settings::getMode() );

		Settings::setMode( Settings::MODE_BY_LANGUAGE );
		$this->assertTrue( Settings::isModeByLanguage() );

		Settings::setMode( Settings::MODE_BY_LOCATION );
		$this->assertTrue( Settings::isModeByLocation() );
	}

	/**
	 * @test
	 */
	public function testModeInStandaloneMode() {
		\WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( true );

		Settings::setMode( null );
		$this->assertNull( Settings::getMode() );

		// Force mode to location at runtime.
		Settings::setMode( Settings::MODE_BY_LANGUAGE );
		$this->assertFalse( Settings::isModeByLanguage() );
		$this->assertTrue( Settings::isModeByLocation() );

		Settings::setMode( Settings::MODE_BY_LOCATION );
		$this->assertTrue( Settings::isModeByLocation() );
	}
}
