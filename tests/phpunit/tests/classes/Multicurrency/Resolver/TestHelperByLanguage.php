<?php

namespace WCML\MultiCurrency\Resolver;

use tad\FunctionMocker\FunctionMocker;
use WCML\MultiCurrency\Geolocation;
use WCML\MultiCurrency\Settings;
use WP_Mock;

/**
 * @group multicurrency
 * @group multicurrency-resolver
 */
class TestHelperByLanguage extends \OTGS_TestCase {

	const USER_COUNTRY = 'FR';
	const VALID_CURRENCY = 'EUR';
	const INVALID_CURRENCY = 'GBP';

	const DEFAULT_LANG = 'en';
	const USER_LANG = 'fr';

	public function setUp() {
		parent::setUp();

		FunctionMocker::replace( Geolocation::class . '::getUserCountry', self::USER_COUNTRY );
		FunctionMocker::replace( Settings::class . '::isValidCurrencyForLang', function( $currency, $lang ) {
			return self::VALID_CURRENCY === $currency && self::USER_LANG === $lang;
		} );
		FunctionMocker::replace( Settings::class . '::getFirstAvailableCurrencyForLang', function( $lang ) {
			return self::USER_LANG === $lang ? self::VALID_CURRENCY : null;
		} );
	}

	public function tearDown() {
		$getCurrency = ( new \ReflectionClass( HelperByLanguage::class ) )->getProperty( 'getCurrency' );
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

		$this->assertEquals( self::VALID_CURRENCY, HelperByLanguage::getCurrencyByUserCountry( self::USER_LANG ) );
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldGetFirstAvailableCurrency() {
		self::mockOfficialCurrencyByCountry( self::INVALID_CURRENCY );

		$this->assertEquals( self::VALID_CURRENCY, HelperByLanguage::getCurrencyByUserCountry( self::USER_LANG ) );
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


	/**
	 * @test
	 * @dataProvider dpShouldGetCurrentLanguage
	 *
	 * @param mixed  $sitePressCurrentLang
	 * @param string $expectedResult
	 *
	 * @return void
	 */
	public function itShouldGetCurrentLanguage( $sitePressCurrentLang, $expectedResult ) {
		$sitepress = $this->getMockBuilder( \SitePress::class )
		                  ->setMethods( [ 'get_current_language', 'get_default_language' ] )
		                  ->getMock();
		$sitepress->method( 'get_current_language' )->willReturn( $sitePressCurrentLang );
		$sitepress->method( 'get_default_language' )->willReturn( self::DEFAULT_LANG );

		WP_Mock::userFunction( 'WCML\functions\getSitePress' )->andReturn( $sitepress );

		$this->assertSame( $expectedResult, HelperByLanguage::getCurrentLanguage() );
	}

	public function dpShouldGetCurrentLanguage() {
		return [
			[ 'fr', 'fr' ],
			[ 'all', self::DEFAULT_LANG ],
			[ null, self::DEFAULT_LANG ],
		];
	}
}
