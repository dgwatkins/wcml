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
	public function testMode() {
		Settings::setMode( null );
		$this->assertNull( Settings::getMode() );

		Settings::setMode( Settings::MODE_BY_LANGUAGE );
		$this->assertTrue( Settings::isModeByLanguage() );

		Settings::setMode( Settings::MODE_BY_LOCATION );
		$this->assertTrue( Settings::isModeByLocation() );
	}
}
