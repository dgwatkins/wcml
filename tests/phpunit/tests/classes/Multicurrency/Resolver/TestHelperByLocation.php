<?php

namespace WCML\MultiCurrency\Resolver;

use tad\FunctionMocker\FunctionMocker;
use WCML\MultiCurrency\Geolocation;
use WCML\MultiCurrency\Settings;

/**
 * @group multicurrency
 * @group multicurrency-resolver
 */
class TestHelperByLocation extends \OTGS_TestCase {

	const USER_COUNTRY = 'FR';
	const VALID_CURRENCY = 'EUR';
	const VALID_CURRENCY_BY_GEOLOCATION = 'USD';
	const INVALID_CURRENCY = 'GBP';

	public function setUp() {
		parent::setUp();

		FunctionMocker::replace( Geolocation::class . '::getUserCountry', self::USER_COUNTRY );
		FunctionMocker::replace( Settings::class . '::isValidCurrencyByCountry', function( $currency, $country ) {
			return self::VALID_CURRENCY === $currency && self::USER_COUNTRY === $country;
		} );
		FunctionMocker::replace( Settings::class . '::getFirstAvailableCurrencyByCountry', function( $country ) {
			return self::USER_COUNTRY === $country ? self::VALID_CURRENCY : null;
		} );
	}

	public function tearDown() {
		$getCurrency = ( new \ReflectionClass( HelperByLocation::class ) )->getProperty( 'getCurrency' );
		$getCurrency->setAccessible( true );
		$getCurrency->setValue( null );
		$getCurrency->setAccessible( false );

		parent::tearDown();
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldGetCountryOfficialCurrency() {
		self::mockOfficialCurrencyByCountry( self::VALID_CURRENCY );

		$this->assertEquals( self::VALID_CURRENCY, HelperByLocation::getCurrencyByUserCountry() );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldGetFirstAvailableCurrency() {
		self::mockOfficialCurrencyByCountry( self::INVALID_CURRENCY );

		$this->assertEquals( self::VALID_CURRENCY, HelperByLocation::getCurrencyByUserCountry() );
	}

	/**
	 * @param string $currency
	 *
	 * @return void
	 */
	private static function mockOfficialCurrencyByCountry( $currency ) {
		FunctionMocker::replace( Geolocation::class . '::getOfficialCurrencyCodeByCountry', function( $country ) use ( $currency ) {
			return self::USER_COUNTRY === $country ? $currency : null;
		} );
	}
}
