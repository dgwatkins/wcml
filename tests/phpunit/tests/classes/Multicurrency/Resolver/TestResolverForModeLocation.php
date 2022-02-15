<?php

namespace WCML\MultiCurrency\Resolver;

use tad\FunctionMocker\FunctionMocker;
use WCML\MultiCurrency\Geolocation;
use WCML\MultiCurrency\Settings;
use WCML_Multi_Currency;

/**
 * @group multicurrency
 * @group multicurrency-resolver
 */
class TestResolverForModeLocation extends \OTGS_TestCase {

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

		FunctionMocker::replace( HelperByLocation::class . '::getCurrencyByUserCountry', self::VALID_CURRENCY_BY_GEOLOCATION );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldReturnStoredCurrency() {
		self::mockStoredUserCurrency( self::VALID_CURRENCY );

		$this->assertEquals( self::VALID_CURRENCY, self::getSubject()->getClientCurrency() );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldReturnCurrencyByGeolocationIfNoStoredCurrency() {
		self::mockStoredUserCurrency( null );

		$this->assertEquals( self::VALID_CURRENCY_BY_GEOLOCATION, self::getSubject()->getClientCurrency() );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldReturnCurrencyByGeolocationIfStoredCurrencyIsInvalid() {
		self::mockStoredUserCurrency( self::INVALID_CURRENCY );

		$this->assertEquals( self::VALID_CURRENCY_BY_GEOLOCATION, self::getSubject()->getClientCurrency() );
	}

	private static function getSubject() {
		return new ResolverForModeLocation();
	}

	/**
	 * @param mixed $currency
	 *
	 * @return void
	 */
	private static function mockStoredUserCurrency( $currency ) {
		\WP_Mock::userFunction( 'wcml_user_store_get' )
			->with( WCML_Multi_Currency::CURRENCY_STORAGE_KEY )
			->andReturn( $currency );
	}
}
