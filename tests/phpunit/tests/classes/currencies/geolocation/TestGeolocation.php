<?php

namespace WCML\MultiCurrency;

use tad\FunctionMocker\FunctionMocker;

/**
 * @group geolocation
 */
class TestGeolocation extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldGetCurrencyCodeByUserCountry() {
		$expected_code = 'UAH';

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [ 'country' => 'UA' ] );

		\WP_Mock::userFunction( 'get_current_user_id', [
			'return' => false,
			'times' => 1
		]);

		$code = Geolocation::getCurrencyCodeByUserCountry();

		$this->assertEquals( $expected_code, $code );
	}

	/**
	 * @test
	 */
	public function itShouldGetCurrencyCodeByUserBillingAddress() {
		$expected_code = 'UAH';
		$user_id = 1;

		$wc_customer = \Mockery::mock( 'overload:WC_Customer' );

		$wc_customer->shouldReceive( 'get_billing_country' )
		                 ->andReturn( 'UA' );

		\WP_Mock::userFunction( 'get_current_user_id', [
			'return' => $user_id,
			'times' => 1
		]);

		$WC          = $this->getMockBuilder( 'WC' )->disableOriginalConstructor()->getMock();
		$WC->session = $this->getMockBuilder( 'WC_Session_Handler' )->disableOriginalConstructor()->getMock();

		\WP_Mock::userFunction( 'WC', [
			'return' => $WC
		] );

		$code = Geolocation::getCurrencyCodeByUserCountry();

		$this->assertEquals( $expected_code, $code );
	}

	/**
	 * @test
	 */
	public function itShouldNotGetCurrencyCodeByUserCountryIfCountryNotFound() {

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [] );

		\WP_Mock::userFunction( 'get_current_user_id', [
			'return' => false,
			'times' => 1
		]);

		$code = Geolocation::getCurrencyCodeByUserCountry();

		$this->assertFalse( $code );
	}

	/**
	 * @test
	 */
	public function itShouldNotGetCurrencyCodeByUserCountryIfCountryNotInConfig() {

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [ 'country' => 'UAU' ] );

		$code = Geolocation::getCurrencyCodeByUserCountry();

		$this->assertFalse( $code );
	}

	/**
	 * @test
	 */
	public function currencyAvailableForCountryWhenEnabledForAllCountries() {

		$currencySettings['location_mode'] = 'all';

		$this->assertTrue( Geolocation::isCurrencyAvailableForCountry( $currencySettings ) );
	}

	/**
	 * @test
	 */
	public function currencyAvailableForCountryWhenCountryIncluded() {

		$currencySettings['location_mode'] = 'include';
		$currencySettings['countries']     = [ 'UA', 'DE' ];

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [ 'country' => 'UA' ] );

		\WP_Mock::userFunction( 'get_current_user_id', [
			'return' => false,
			'times' => 1
		]);

		$this->assertTrue( Geolocation::isCurrencyAvailableForCountry( $currencySettings ) );
	}

	/**
	 * @test
	 */
	public function currencyAvailableForCountryWhenCountryNotInExcludedList() {

		$currencySettings['location_mode'] = 'exclude';
		$currencySettings['countries']     = [ 'DE' ];

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [ 'country' => 'UA' ] );

		\WP_Mock::userFunction( 'get_current_user_id', [
			'return' => false,
			'times' => 1
		]);

		$this->assertTrue( Geolocation::isCurrencyAvailableForCountry( $currencySettings ) );
	}

	/**
	 * @test
	 */
	public function currencyNotAvailableForCountry() {

		$currencySettings['location_mode'] = '';

		$this->assertFalse( Geolocation::isCurrencyAvailableForCountry( $currencySettings ) );
	}

	/**
	 * @test
	 */
	public function itShouldGetFirstAvailableCountryCurrencyFromSettingsAllMode() {

		$currencySettings['EUR']['location_mode'] = 'all';

		$this->assertEquals( 'EUR', Geolocation::getFirstAvailableCountryCurrencyFromSettings( $currencySettings ) );
	}

	/**
	 * @test
	 */
	public function itShouldGetFirstAvailableCountryCurrencyFromSettingsIncludeMode() {

		$currencySettings['EUR']['location_mode'] = 'include';
		$currencySettings['EUR']['countries']     = [ 'UA' ];

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [ 'country' => 'UA' ] );

		$this->assertEquals( 'EUR', Geolocation::getFirstAvailableCountryCurrencyFromSettings( $currencySettings ) );
	}

	/**
	 * @test
	 */
	public function itShouldNotGetFirstAvailableCountryCurrencyFromSettings() {

		$currencySettings['EUR']['location_mode'] = 'include';
		$currencySettings['EUR']['countries']     = [ 'DE' ];

		$this->assertFalse( Geolocation::getFirstAvailableCountryCurrencyFromSettings( $currencySettings ) );
	}

}
