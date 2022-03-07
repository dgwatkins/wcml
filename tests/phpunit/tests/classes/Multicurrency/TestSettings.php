<?php

namespace WCML\MultiCurrency;

use WCML\PHPUnit\SettingsMock;
use function WCML\functions\updateSetting;

/**
 * @group multicurrency
 * @group multicurrency-settings
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

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testIsDisplayOnlyCustomPrices() {
		updateSetting( 'display_custom_prices', true );
		$this->assertTrue( Settings::isDisplayOnlyCustomPrices() );
		updateSetting( 'display_custom_prices', false );
		$this->assertFalse( Settings::isDisplayOnlyCustomPrices() );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testIsActiveCurrency() {
		$currencyOptions = [
			'EUR' => [],
			'USD' => [],
		];

		updateSetting( 'currency_options', $currencyOptions );

		$this->assertTrue( Settings::isActiveCurrency( 'EUR' ) );
		$this->assertTrue( Settings::isActiveCurrency( 'USD' ) );
		$this->assertFalse( Settings::isActiveCurrency( 'BRL' ) );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testGetActiveCurrencyCodes() {
		$currencyOptions = [
			'EUR' => [],
			'USD' => [],
		];

		updateSetting( 'currency_options', $currencyOptions );

		$this->assertEquals( [ 'EUR', 'USD' ], Settings::getActiveCurrencyCodes() );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testGetCurrenciesOptions() {
		$currencyOptions = [
			'EUR' => [],
			'USD' => [],
		];

		updateSetting( 'currency_options', $currencyOptions );

		$this->assertEquals( $currencyOptions, Settings::getCurrenciesOptions() );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testGetCurrenciesOption() {
		$eurOptions = [ 'foo' => 'bar' ];
		$usdOptions = [ 'bar' => 'baz' ];
		$default    = [ 'default' => 'default' ];

		$currencyOptions = [
			'EUR' => $eurOptions,
			'USD' => $usdOptions,
		];

		updateSetting( 'currency_options', $currencyOptions );

		$this->assertEquals( $eurOptions, Settings::getCurrenciesOption( 'EUR' ) );
		$this->assertEquals( $usdOptions, Settings::getCurrenciesOption( 'USD' ) );
		$this->assertNull( Settings::getCurrenciesOption( 'UNKNOWN' ) );
		$this->assertEquals( $default, Settings::getCurrenciesOption( 'UNKNOWN', $default ) );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testIsValidCurrencyByCountry() {
		$currencyOptions = [
			'EUR' => [
				'location_mode' => 'all',
				'countries'     => [ 'FR', 'GB' ],
			],
			'GBP' => [
				'location_mode' => 'include',
				'countries'     => [ 'FR', 'GB' ],
			],
			'USD' => [
				'location_mode' => 'exclude',
				'countries'     => [ 'FR', 'GB' ],
			],
		];

		updateSetting( 'currency_options', $currencyOptions );

		// EUR is available in all countries
		$this->assertTrue( Settings::isValidCurrencyByCountry( 'EUR', 'FR' ) );
		$this->assertTrue( Settings::isValidCurrencyByCountry( 'EUR', 'GB' ) );
		$this->assertTrue( Settings::isValidCurrencyByCountry( 'EUR', 'US' ) );

		// GBP is available only in FR and GB
		$this->assertTrue( Settings::isValidCurrencyByCountry( 'GBP', 'FR' ) );
		$this->assertTrue( Settings::isValidCurrencyByCountry( 'GBP', 'GB' ) );
		$this->assertFalse( Settings::isValidCurrencyByCountry( 'GBP', 'US' ) );

		// USD is available everywhere except FR and GB
		$this->assertFalse( Settings::isValidCurrencyByCountry( 'USD', 'FR' ) );
		$this->assertFalse( Settings::isValidCurrencyByCountry( 'USD', 'GB' ) );
		$this->assertTrue( Settings::isValidCurrencyByCountry( 'USD', 'US' ) );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testGetFirstAvailableCurrencyByCountry() {
		$currencyOptions = [
			'EUR' => [
				'location_mode' => 'include',
				'countries'     => [ 'FR', 'DE' ],
			],
			'GBP' => [
				'location_mode' => 'include',
				'countries'     => [ 'GB' ],
			],
			'USD' => [
				'location_mode' => 'include',
				'countries'     => [ 'GB', 'US', 'FR', 'DE' ],
			],
		];

		updateSetting( 'currency_options', $currencyOptions );

		$this->assertEquals( 'EUR', Settings::getFirstAvailableCurrencyByCountry( 'FR' ) );
		$this->assertEquals( 'EUR', Settings::getFirstAvailableCurrencyByCountry( 'DE' ) );
		$this->assertEquals( 'GBP', Settings::getFirstAvailableCurrencyByCountry( 'GB' ) );
		$this->assertEquals( 'USD', Settings::getFirstAvailableCurrencyByCountry( 'US' ) );
		$this->assertNull( Settings::getFirstAvailableCurrencyByCountry( 'BR' ) );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testGetDefaultCurrencies() {
		$default_currencies = [
			'en' => '0',
			'fr' => 'location',
			'de' => 'EUR',
		];

		updateSetting( 'default_currencies', $default_currencies );

		$this->assertSame( $default_currencies, Settings::getDefaultCurrencies() );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testGetOrderedCurrencyCodes() {
		$currencyOptions = [
			'EUR' => [],
			'GBP' => [],
			'USD' => [],
		];

		$currenciesOrder = [
			'USD',
			'EUR',
			'GBP',
		];

		// Test when order is set
		updateSetting( 'currency_options', $currencyOptions );
		updateSetting( 'currencies_order', $currenciesOrder );
		$this->assertSame( $currenciesOrder, Settings::getOrderedCurrencyCodes() );

		// Test fallback to currency options
		updateSetting( 'currencies_order', null );
		$this->assertSame( array_keys( $currencyOptions ), Settings::getOrderedCurrencyCodes() );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testGetDefaultCurrencyForLang() {
		$default_currencies = [
			'en' => 0, // i.e. keep
			'fr' => 'location',
			'de' => 'EUR',
		];

		updateSetting( 'default_currencies', $default_currencies );

		$this->assertSame( '0', Settings::getDefaultCurrencyForLang( 'en' ) );
		$this->assertFalse( (bool) Settings::getDefaultCurrencyForLang( 'en' ) );
		$this->assertSame( 'location', Settings::getDefaultCurrencyForLang( 'fr' ) );
		$this->assertSame( 'EUR', Settings::getDefaultCurrencyForLang( 'de' ) );
		$this->assertSame( '', Settings::getDefaultCurrencyForLang( 'undefined' ) );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testIsDefaultCurrencyByLocationForLang() {
		$default_currencies = [
			'en' => 0, // i.e. keep
			'fr' => 'location',
			'de' => 'EUR',
		];

		updateSetting( 'default_currencies', $default_currencies );

		$this->assertFalse( Settings::isDefaultCurrencyByLocationForLang( 'en' ) );
		$this->assertTrue( Settings::isDefaultCurrencyByLocationForLang( 'fr' ) );
		$this->assertFalse( Settings::isDefaultCurrencyByLocationForLang( 'de' ) );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testIsValidCurrencyForLang() {
		$currencyOptions = [
			'EUR' => [
				'languages' => [
					'fr' => 1,
					'en' => 0,
					'de' => 1,
				],
			],
			'GBP' => [
				'languages' => [
					'en' => 1,
				],
			],
		];

		updateSetting( 'currency_options', $currencyOptions );

		$this->assertTrue( Settings::isValidCurrencyForLang( 'EUR', 'fr' ) );
		$this->assertFalse( Settings::isValidCurrencyForLang( 'EUR', 'en' ) );
		$this->assertTrue( Settings::isValidCurrencyForLang( 'EUR', 'de' ) );

		$this->assertFalse( Settings::isValidCurrencyForLang( 'GBP', 'fr' ) );
		$this->assertTrue( Settings::isValidCurrencyForLang( 'GBP', 'en' ) );
		$this->assertFalse( Settings::isValidCurrencyForLang( 'GBP', 'de' ) );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testGetFirstAvailableCurrencyForLang() {
		$currencyOptions = [
			'EUR' => [
				'languages' => [
					'fr' => 1,
					'en' => 0,
					'br' => 0,
				],
			],
			'GBP' => [
				'languages' => [
					'en' => 1,
				],
			],
			'BRL' => [
				'languages' => [
					'fr'    => 1,
					'en'    => 1,
					'pt-br' => 1,
				],
			],
		];

		updateSetting( 'currency_options', $currencyOptions );

		$this->assertEquals( 'EUR', Settings::getFirstAvailableCurrencyForLang( 'fr' ) );
		$this->assertEquals( 'GBP', Settings::getFirstAvailableCurrencyForLang( 'en' ) );
		$this->assertEquals( 'BRL', Settings::getFirstAvailableCurrencyForLang( 'pt-br' ) );
	}
}
