<?php

namespace WCML\MultiCurrency;

use tad\FunctionMocker\FunctionMocker;

/**
 * @group geolocation
 */
class TestGeolocation extends \OTGS_TestCase {

	public function setUp() {
		parent::setUp();
		\WP_Mock::passthruFunction( 'wc_clean' );
		\WP_Mock::passthruFunction( 'wp_unslash' );
	}

	public function tearDown() {
		global $woocommerce_wpml;
		unset( $woocommerce_wpml );
		$_GET = $_POST = [];
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

		FunctionMocker::replace( Settings::class . '::isModeByLocation', false );

		$this->mockWcmlSettings( [
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

		FunctionMocker::replace( Settings::class . '::isModeByLocation', Settings::MODE_BY_LOCATION === $wcmlSettings['currency_mode'] );

		$this->mockWcmlSettings( $wcmlSettings );

		$this->assertTrue( Geolocation::isUsed() );
	}

	public function dpWcmlSettingsUsingGeolocation() {
		return [
			'mode by location' => [
				[
					'currency_mode' => Settings::MODE_BY_LOCATION,
					'default_currencies' => [
						'fr' => 0,
						'en' => 'USD',
						'de' => 'EUR',
					],

				],
			],
			'location in default currencies' => [
				[
					'currency_mode' => Settings::MODE_BY_LANGUAGE,
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
	public function itShouldGetOfficialCurrencyCodeByCountry() {
		$this->assertEquals( 'UAH', Geolocation::getOfficialCurrencyCodeByCountry( 'UA' ) );
		$this->assertEquals( 'EUR', Geolocation::getOfficialCurrencyCodeByCountry( 'FR' ) );
		$this->assertEquals( 'USD', Geolocation::getOfficialCurrencyCodeByCountry( 'US' ) );
	}

	/**
	 * @test
	 */
	public function itShouldGetUserCountryByUserBillingAddress() {
		$country = 'FR';
		$userId  = 1;

		$this->mockGeolocationCache( 'US' );
		$this->mockWcCustomer( $country, $country );
		$this->mockCurrentUserId( $userId );

		$this->assertEquals( $country, Geolocation::getUserCountry() );
	}

	/**
	 * @test
	 */
	public function itShouldGetUserCountryByGeolocationFromCacheIfUnidentifiedUser() {
		$country = 'FR';

		$this->mockGeolocationCache( $country );
		$this->mockWcCustomer( '', '' );
		$this->mockCurrentUserId( 0 );

		$this->assertEquals( $country, Geolocation::getUserCountry() );
	}

	/**
	 * @test
	 */
	public function itShouldGetUserCountryByGeolocationIfUnidentifiedUser() {
		$country = 'FR';

		$this->mockGeolocationCache( false, $country );
		$this->mockWcGeolocation( $country );
		$this->mockWcCustomer( '', '' );
		$this->mockCurrentUserId( 0 );

		$this->assertEquals( $country, Geolocation::getUserCountry() );
	}

	/**
	 * @test
	 */
	public function itShouldGetUserCountryCodeByUserAddressFromAjax() {
		$country = 'ES';

		$_GET['wc-ajax']  = 'update_order_review';
		$_POST['country'] = $country;

		$this->mockGeolocationCache( 'US' );

		$this->assertEquals( $country, Geolocation::getUserCountry() );
	}

	/**
	 * @test
	 */
	public function itShouldGetCountryCodeByUserAddressFromCheckoutAjax() {
		$billingCountry        = 'ES';
		$shippingCountry       = 'UA';
		$geolocatedCountryCode = 'US';

		$_GET['wc-ajax']           = 'checkout';
		$_POST['billing_country']  = $billingCountry;
		$_POST['shipping_country'] = $shippingCountry;

		$this->mockGeolocationCache( $geolocatedCountryCode );

		\WP_Mock::onFilter( 'wcml_geolocation_get_user_country' )
		        ->with( $billingCountry, [ 'billing' => $billingCountry, 'shipping' => $shippingCountry, 'geolocation' => $geolocatedCountryCode ] )
		        ->reply( $billingCountry );

		$this->assertSame( $billingCountry, Geolocation::getUserCountry() );
	}

	private function mockWcmlSettings( array $settings ) {
		global $woocommerce_wpml;

		$woocommerce_wpml = $this->getMockBuilder( '\woocommerce_wpml' )
			->setMethods( [ 'get_setting' ] )
			->disableOriginalConstructor()->getMock();

		$woocommerce_wpml->method( 'get_setting' )
			->willReturnCallback( function ( $key, $default = null ) use ( $settings ) {
				return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
			} );
	}

	/**
	 * @param string      $cachedCountryCode
	 * @param string|null $resolvedCountryCode
	 */
	private function mockGeolocationCache( $cachedCountryCode, $resolvedCountryCode = null ) {
		\WP_Mock::userFunction( 'wp_cache_add_non_persistent_groups' )
		        ->times( 1 )
		        ->with( Geolocation::class );

		\WP_Mock::userFunction( 'wp_cache_get' )
		        ->with( 'country', Geolocation::class )
		        ->andReturn( $cachedCountryCode );

		\WP_Mock::userFunction( 'wp_cache_add' )
		        ->times( $resolvedCountryCode ? 1 : 0 )
		        ->with( 'country', $resolvedCountryCode, Geolocation::class );
	}

	/**
	 * @param string $country
	 *
	 * @return void
	 */
	private function mockWcGeolocation( $country ) {
		FunctionMocker::replace( 'WC_Geolocation::get_ip_address', '127.0.0.1' );
		FunctionMocker::replace( 'WC_Geolocation::geolocate_ip', [ 'country' => $country ] );
	}

	/**
	 * @param string $billingCountry
	 * @param string $shippingCountry
	 *
	 * @return void
	 */
	private function mockWcCustomer( $billingCountry, $shippingCountry ) {
		$wc_customer = \Mockery::mock( 'overload:WC_Customer' );
		$wc_customer->shouldReceive( 'get_billing_country' )->andReturn( $billingCountry );
		$wc_customer->shouldReceive( 'get_shipping_country' )->andReturn( $shippingCountry );

		$WC          = $this->getMockBuilder( 'WC' )->disableOriginalConstructor()->getMock();
		$WC->session = $this->getMockBuilder( 'WC_Session_Handler' )->disableOriginalConstructor()->getMock();

		\WP_Mock::userFunction( 'WC' )->andReturn( $WC );
	}

	/**
	 * @param int $userId
	 *
	 * @return void
	 */
	private function mockCurrentUserId( $userId ) {
		\WP_Mock::userFunction( 'get_current_user_id' )->andReturn( $userId );
	}
}
