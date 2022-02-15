<?php

namespace WCML\MultiCurrency\Resolver;

use tad\FunctionMocker\FunctionMocker;
use WCML\MultiCurrency\Settings;
use WCML_Multi_Currency;
use WP_Mock;

/**
 * @group multicurrency
 * @group multicurrency-resolver
 * @group temp
 */
class TestResolverForModeLanguage extends \OTGS_TestCase {

	const CURRENT_LANG = 'fr';
	const OTHER_LANG   = 'en';

	const DEFAULT_CURRENCY_FOR_LANG = 'EUR';
	const OTHER_CURRENCY = 'USD';
	const WC_DEFAULT_CURRENCY = 'GBP';

	public function setUp() {
		parent::setUp();

		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option' )->andReturn( self::WC_DEFAULT_CURRENCY );
	}

	public function tearDown() {
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itShouldGetCurrencyFromReInitCurrencyIfLangHasChanged() {
		$preventSwitchingArg = 'something';

		self::mockContext( self::CURRENT_LANG, self::OTHER_CURRENCY, self::OTHER_LANG );
		self::mockInitialCurrencyForLang( self::CURRENT_LANG, true, self::DEFAULT_CURRENCY_FOR_LANG );

		WP_Mock::onFilter( 'wcml_switch_currency_exception' )
			->with( false, self::OTHER_CURRENCY, self::DEFAULT_CURRENCY_FOR_LANG, true )
			->reply( [ 'prevent_switching' => $preventSwitchingArg ] );

		WP_Mock::expectAction( 'wcml_multi_currency_set_switching_currency_html', $preventSwitchingArg );

		$this->assertEquals( self::DEFAULT_CURRENCY_FOR_LANG, self::getSubject()->getClientCurrency() );
	}

	/**
	 * @test
	 */
	public function itShouldGetCurrencyFromStoredCurrency() {
		self::mockContext( self::CURRENT_LANG, self::DEFAULT_CURRENCY_FOR_LANG, self::CURRENT_LANG );
		self::mockIsValidCurrencyForLang( self::DEFAULT_CURRENCY_FOR_LANG, self::CURRENT_LANG );

		$this->assertEquals( self::DEFAULT_CURRENCY_FOR_LANG, self::getSubject()->getClientCurrency() );
	}

	/**
	 * @test
	 */
	public function itShouldGetCurrencyFromInitialCurrencyForLang() {
		self::mockContext( self::CURRENT_LANG, null, null );
		self::mockIsValidCurrencyForLang( null, self::CURRENT_LANG );
		self::mockInitialCurrencyForLang( self::CURRENT_LANG, false, self::DEFAULT_CURRENCY_FOR_LANG );

		$this->assertEquals( self::DEFAULT_CURRENCY_FOR_LANG, self::getSubject()->getClientCurrency() );
	}

	/**
	 * @test
	 */
	public function itShouldGetCurrencyFromDefault() {
		self::mockContext( self::CURRENT_LANG, null, null );
		self::mockIsValidCurrencyForLang( self::WC_DEFAULT_CURRENCY, self::CURRENT_LANG );
		self::mockInitialCurrencyForLang( self::CURRENT_LANG, false, null );

		$this->assertEquals( self::WC_DEFAULT_CURRENCY, self::getSubject()->getClientCurrency() );
	}

	/**
	 * @test
	 */
	public function itShouldGetCurrencyFromFromFirstAvailableForLang() {
		self::mockContext( self::CURRENT_LANG, null, null );
		self::mockIsValidCurrencyForLang( self::OTHER_CURRENCY, self::CURRENT_LANG );
		self::mockInitialCurrencyForLang( self::CURRENT_LANG, false, null );

		FunctionMocker::replace( Settings::class . '::getFirstAvailableCurrencyForLang', function( $langParam ) {
			return self::CURRENT_LANG === $langParam ? self::OTHER_CURRENCY : null;
		} );

		$this->assertEquals( self::OTHER_CURRENCY, self::getSubject()->getClientCurrency() );
	}

	private static function getSubject() {
		return new ResolverForModeLanguage();
	}

	/**
	 * @param string      $currentLang
	 * @param string|null $storedCurrency
	 * @param string|null $storedCurrencyLang
	 *
	 * @return void
	 */
	private static function mockContext( $currentLang, $storedCurrency, $storedCurrencyLang ) {
		FunctionMocker::replace( HelperByLanguage::class . '::getCurrentLanguage', $currentLang );

		WP_Mock::userFunction( 'wcml_user_store_get' )
		       ->with( WCML_Multi_Currency::CURRENCY_STORAGE_KEY )
		       ->andReturn( $storedCurrency );

		WP_Mock::userFunction( 'wcml_user_store_get' )
		       ->with( WCML_Multi_Currency::CURRENCY_LANGUAGE_STORAGE_KEY )
		       ->andReturn( $storedCurrencyLang );
	}

	/**
	 * @param string $lang
	 * @param bool   $isByLocation
	 * @param string $currency
	 *
	 * @return void
	 */
	private static function mockInitialCurrencyForLang( $lang, $isByLocation, $currency ) {
		FunctionMocker::replace( Settings::class . '::isDefaultCurrencyByLocationForLang', function( $langParam ) use ( $lang, $isByLocation ) {
			return $lang === $langParam && $isByLocation;
		} );

		$getCurrency = function( $langParam ) use ( $lang, $currency ) {
			return $lang === $langParam ? $currency : null;
		};

		if ( $isByLocation ) {
			FunctionMocker::replace( HelperByLanguage::class . '::getCurrencyByUserCountry', $getCurrency );
		} else {
			FunctionMocker::replace( Settings::class . '::getDefaultCurrencyForLang', $getCurrency );
		}
	}

	/**
	 * @param string $currency
	 * @param string $lang
	 *
	 * @return void
	 */
	private static function mockIsValidCurrencyForLang( $currency, $lang ) {
		FunctionMocker::replace( Settings::class . '::isValidCurrencyForLang', function( $currencyParam, $langParam ) use ( $currency, $lang ) {
			return $currency === $currencyParam && $lang === $langParam;
		} );
	}
}
