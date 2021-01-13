<?php

namespace WCML\MultiCurrency;

use tad\FunctionMocker\FunctionMocker;

/**
 * @group geolocation
 */
class TestGeolocation extends \OTGS_TestCase {

	public function tearDown() {
		global $woocommerce_wpml;
		unset( $woocommerce_wpml );
		parent::tearDown();
	}

	/**
	 * @test
	 * @group wcml-3503
	 */
	public function testIsUsedWithoutMulticurrency() {
		\WP_Mock::userFunction( 'wcml_is_multi_currency_on' )->andReturn( false );

		$this->assertFalse( Geolocation::isUsed() );
	}

	/**
	 * @test
	 * @group wcml-3503
	 */
	public function testIsUsedWithMulticurrencyAndNoActualUsage() {
		\WP_Mock::userFunction( 'wcml_is_multi_currency_on' )->andReturn( true );

		$this->mockWcmlSettings( [
			'currency_mode' => Geolocation::MODE_BY_LANGUAGE,
			'default_currencies' => [
				'fr' => 0,
				'en',
				'de' => 'EUR',
			],
		] );

		$this->assertFalse( Geolocation::isUsed() );
	}

	/**
	 * @test
	 * @dataProvider dpWcmlSettingsUsingGeolocation
	 * @group wcml-3503
	 *
	 * @param array $wcmlSettings
	 */
	public function testIsUsedWithMulticurrencyAndIsActuallyUsed( array $wcmlSettings ) {
		\WP_Mock::userFunction( 'wcml_is_multi_currency_on' )->andReturn( true );

		$this->mockWcmlSettings( $wcmlSettings );

		$this->assertFalse( Geolocation::isUsed() );
	}

	public function dpWcmlSettingsUsingGeolocation() {
		return [
			'mode by location' => [
				[
					'currency_mode' => Geolocation::MODE_BY_LOCATION,
					'default_currencies' => [
						'fr' => 0,
						'en',
						'de' => 'EUR',
					],

				],
			],
			'location in default currencies' => [
				[
					'currency_mode' => Geolocation::MODE_BY_LANGUAGE,
					'default_currencies' => [
						'fr' => 0,
						'en' => 'location',
						'de' => 'EUR',
					],

				],
			],
		];
	}

	/**
	 * @test
	 */
	public function itShouldGetCurrencyCodeByUserCountryFromNonPersistentCache() {
		\WP_Mock::passthruFunction( 'wc_clean' );
		\WP_Mock::passthruFunction( 'wp_unslash' );

		$geolocatedCountryCode = 'UA';
		$expected_code         = 'UAH';

		\WP_Mock::userFunction( 'wp_cache_add_non_persistent_groups' )
		        ->times( 1 )
		        ->with( Geolocation::class );

		\WP_Mock::userFunction( 'wp_cache_get' )
		        ->with( 'country', Geolocation::class )
		        ->andReturn( $geolocatedCountryCode );

		\WP_Mock::userFunction( 'wp_cache_add' )
		        ->times( 0 );

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [ 'country' => $geolocatedCountryCode ] );

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
	public function itShouldGetCurrencyCodeByUserCountry() {
		\WP_Mock::passthruFunction( 'wc_clean' );
		\WP_Mock::passthruFunction( 'wp_unslash' );

		$geolocatedCountryCode = 'UA';
		$expected_code         = 'UAH';

		$this->mockGeolocationCache( $geolocatedCountryCode );

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [ 'country' => $geolocatedCountryCode ] );

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

		$expected_code         = 'UAH';
		$user_id               = 1;

		$this->mockGeolocationCache( '' );

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

		$this->mockGeolocationCache( '' );

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

		$expected_code         = 'EUR';
		$geolocatedCountryCode = '';

		$_GET['wc-ajax']  = 'checkout';
		$_POST['billing_country'] = 'ES';
		$_POST['shipping_country'] = 'UA';

		$this->mockGeolocationCache( $geolocatedCountryCode );

		\WP_Mock::onFilter( 'wcml_geolocation_get_user_country' )
		        ->with( 'ES', [ 'billing' => 'ES', 'shipping' => 'UA', 'geolocation' => $geolocatedCountryCode ] )
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

		$this->mockGeolocationCache( '' );

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
		$geolocatedCountryCode = 'UAU';

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [ 'country' => $geolocatedCountryCode ] );

		$this->mockGeolocationCache( $geolocatedCountryCode );

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
		$geolocatedCountryCode             = 'UA';
		$currencySettings['location_mode'] = 'include';
		$currencySettings['countries']     = [ 'UA', 'DE' ];

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [ 'country' => $geolocatedCountryCode ] );

		$this->mockGeolocationCache( $geolocatedCountryCode );

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
		$geolocatedCountryCode             = 'UA';
		$currencySettings['location_mode'] = 'exclude';
		$currencySettings['countries']     = [ 'DE' ];

		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [ 'country' => $geolocatedCountryCode ] );

		$this->mockGeolocationCache( $geolocatedCountryCode );

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

	private function mockWcmlSettings( array $settings ) {
		global $woocommerce_wpml;

		$woocommerce_wpml = $this->getMockBuilder( '\woocommerce_wpml' )
			->setMethods( [ 'get_setting' ] )
			->disableOriginalConstructor()->getMock();

		$woocommerce_wpml->method( 'get_setting' )
			->willReturnCallback( function ( $key, $default = null ) use ( $settings ) {
				return isset( $settings[ $key ] ) ? isset( $settings[ $key ] ) : $default;
			} );
	}

	/**
	 * @param string $rawCountryCode
	 */
	private function mockGeolocationCache( $rawCountryCode ) {
		\WP_Mock::userFunction( 'wp_cache_add_non_persistent_groups' )
		        ->times( 1 )
		        ->with( Geolocation::class );

		\WP_Mock::userFunction( 'wp_cache_get' )
		        ->with( 'country', Geolocation::class )
		        ->andReturn( false );

		\WP_Mock::userFunction( 'wp_cache_add' )
		        ->times( 1 )
		        ->with( 'country', $rawCountryCode, Geolocation::class );
	}
}
