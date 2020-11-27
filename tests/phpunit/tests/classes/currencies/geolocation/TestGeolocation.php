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
		\WP_Mock::passthruFunction( 'wc_clean' );
		\WP_Mock::passthruFunction( 'wp_unslash' );

		$expected_code = 'UAH';

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [ 'country' => 'UA' ] );

		\WP_Mock::userFunction( 'get_current_user_id', [
			'return' => false,
			'times' => 2
		]);

		$code = Geolocation::getCurrencyCodeByUserCountry();

		$this->assertEquals( $expected_code, $code );
	}

	/**
	 * @test
	 */
	public function itShouldGetCurrencyCodeByUserBillingAddress() {
		\WP_Mock::passthruFunction( 'wc_clean' );
		\WP_Mock::passthruFunction( 'wp_unslash' );

		$expected_code = 'UAH';
		$user_id = 1;

		$wc_customer = \Mockery::mock( 'overload:WC_Customer' );

		$wc_customer->shouldReceive( 'get_billing_country' )
		                 ->andReturn( 'UA' );

		$wc_customer->shouldReceive( 'get_shipping_country' )
		                 ->andReturn( 'UA' );

		\WP_Mock::userFunction( 'get_current_user_id', [
			'return' => $user_id,
			'times' => 2
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
	public function itShouldGetCurrencyCodeByUserBillingAddressFromAjax() {
		\WP_Mock::passthruFunction( 'wc_clean' );
		\WP_Mock::passthruFunction( 'wp_unslash' );

		$expected_code = 'EUR';

		$_GET['wc-ajax']  = 'update_order_review';
		$_POST['country'] = 'ES';

		$code = Geolocation::getCurrencyCodeByUserCountry();

		$this->assertEquals( $expected_code, $code );

		unset( $_GET['wc-ajax'], $_POST['country'] );
	}

	/**
	 * @test
	 */
	public function itShouldGetCurrencyCodeByUserBillingAddressFromCheckoutAjax() {
		\WP_Mock::passthruFunction( 'wc_clean' );
		\WP_Mock::passthruFunction( 'wp_unslash' );

		$expected_code = 'EUR';

		$_GET['wc-ajax']  = 'checkout';
		$_POST['billing_country'] = 'ES';
		$_POST['shipping_country'] = 'UA';

		\WP_Mock::onFilter( 'wcml_geolocation_get_user_country' )
		        ->with( 'ES', [ 'billing' => 'ES', 'shipping' => 'UA', 'geolocation' => '' ] )
		        ->reply( 'ES' );

		$code = Geolocation::getCurrencyCodeByUserCountry();

		$this->assertEquals( $expected_code, $code );

		unset( $_GET['wc-ajax'], $_POST['billing_country'], $_POST['shipping_country'] );
	}

	/**
	 * @test
	 */
	public function itShouldNotGetCurrencyCodeByUserCountryIfCountryNotFound() {

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [] );

		\WP_Mock::userFunction( 'get_current_user_id', [
			'return' => false,
			'times' => 2
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
			'times' => 2
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
			'times' => 2
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
	public function currencyNotAvailableForCountryLocationModeNotSet() {

		$currencySettings = [];

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
