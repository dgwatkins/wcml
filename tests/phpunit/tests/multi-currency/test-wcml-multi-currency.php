<?php

use tad\FunctionMocker\FunctionMocker;
use WCML\MultiCurrency\Geolocation;

/**
 * Class Test_WCML_Multi_Currency
 *
 * @group multi-currency
 */
class Test_WCML_Multi_Currency extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function it_gets_currency_codes() {
		$subject                 = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$currency_codes          = [ 'USD', 'EUR' ];
		$subject->currency_codes = $currency_codes;

		$this->assertSame( $currency_codes, $subject->get_currency_codes() );
	}

	/**
	 * @test
	 */
	public function it_gets_currency_code() {
		$currency_codes   = [ 'USD', 'EUR' ];
		$default_currency = 'EUR';
		$currency_option  = 'USD';

		$multi_currency = \Mockery::mock( 'WCML_Multi_Currency' );
		$multi_currency->shouldReceive( 'get_default_currency' )->never();

		$woocommerce_wpml                 = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->multi_currency = $multi_currency;

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes   = $currency_codes;
		$subject->woocommerce_wpml = $woocommerce_wpml;

		\WP_Mock::userFunction(
			'wcml_get_woocommerce_currency_option',
			[
				'args'   => [],
				'times'  => 1,
				'return' => $currency_option,
			]
		);

		$this->assertSame( $currency_option, $subject->get_currency_code() );
	}

	/**
	 * @test
	 */
	public function it_gets_currency_code_using_default_currency() {
		$currency_codes   = [ 'USD', 'EUR' ];
		$default_currency = 'EUR';

		$multi_currency = \Mockery::mock( 'WCML_Multi_Currency' );
		$multi_currency->shouldReceive( 'get_default_currency' )->once()->andReturn( 'EUR' );

		$woocommerce_wpml                 = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->multi_currency = $multi_currency;

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes   = $currency_codes;
		$subject->woocommerce_wpml = $woocommerce_wpml;

		\WP_Mock::userFunction(
			'wcml_get_woocommerce_currency_option',
			[
				'args'   => [],
				'times'  => 1,
				'return' => 'RUB',
			]
		);

		$this->assertSame( $default_currency, $subject->get_currency_code() );
	}

	/**
	 * @test
	 */
	public function is_currency_active() {
		$currency_codes = [ 'USD', 'UAH' ];

		$subject                 = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes = $currency_codes;

		$this->assertTrue( $subject->is_currency_active( 'UAH' ) );

		$this->assertFalse( $subject->is_currency_active( 'EUR' ) );
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_should_get_currency_by_geolocation() {
		\WP_Mock::passthruFunction( 'wp_cache_add_non_persistent_groups' );

		$client_currency  = null;
		$country_currency = 'UAH';
		$currency_codes   = [ 'USD', $country_currency ];

		$woocommerce = \Mockery::mock( 'woocommerce' );

		$woocommerce->session = $this->getMockBuilder( 'WC_Session' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( [ 'get' ] )
		                            ->getMock();

		$woocommerce->session->method( 'get' )->with( 'client_currency' )->willReturn( false );

		\WP_Mock::userFunction(
			'is_ajax',
			[
				'return' => false,
			]
		);

		$wpml_cache = \Mockery::mock( 'overload:WPML_WP_Cache' );
		$wpml_cache->shouldReceive( 'get' )->with( 'location_currency', false )->andReturn( false );
		$wpml_cache->shouldReceive( 'set' )->with( 'location_currency', $country_currency )->andReturn( true );

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->settings['currency_options'][ $country_currency ] = [];
		$woocommerce_wpml->shouldReceive( 'get_setting' )->with( 'currency_mode' )->andReturn( Geolocation::MODE_BY_LOCATION );

		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::getCurrencyCodeByUserCountry', $country_currency );
		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::isCurrencyAvailableForCountry', true );

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes   = $currency_codes;
		$subject->woocommerce_wpml = $woocommerce_wpml;
		$subject->woocommerce      = $woocommerce;

		$this->assertEquals( $country_currency, $subject->maybe_get_currency_by_geolocation( $client_currency ) );

	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_should_get_currency_by_geolocation_for_checkout_ajax() {
		\WP_Mock::passthruFunction( 'wp_cache_add_non_persistent_groups' );

		$client_currency  = 'EUR';
		$country_currency = 'UAH';
		$currency_codes   = [ 'USD', $country_currency ];

		\WP_Mock::userFunction(
			'is_ajax',
			[
				'return' => true,
			]
		);

		$_GET['wc-ajax'] = 'update_order_review';

		$wpml_cache = \Mockery::mock( 'overload:WPML_WP_Cache' );
		$wpml_cache->shouldReceive( 'get' )->with( 'location_currency', false )->andReturn( false );
		$wpml_cache->shouldReceive( 'set' )->with( 'location_currency', $country_currency )->andReturn( true );

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->settings['currency_options'][ $country_currency ] = [];
		$woocommerce_wpml->shouldReceive( 'get_setting' )->with( 'currency_mode' )->andReturn( Geolocation::MODE_BY_LOCATION );

		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::getCurrencyCodeByUserCountry', $country_currency );
		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::isCurrencyAvailableForCountry', true );

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes   = $currency_codes;
		$subject->woocommerce_wpml = $woocommerce_wpml;

		$this->assertEquals( $country_currency, $subject->maybe_get_currency_by_geolocation( $client_currency, false ) );
		unset( $_GET['wc-ajax'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_get_currency_by_geolocation_if_currency_already_set_in_session() {

		\WP_Mock::wpFunction( 'wp_cache_add_non_persistent_groups', [] );

		$client_currency  = null;
		$country_currency = 'UAH';
		$currency_codes   = [ 'USD', $country_currency ];

		$woocommerce = \Mockery::mock( 'woocommerce' );

		$woocommerce->session = $this->getMockBuilder( 'WC_Session' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( [ 'get' ] )
		                            ->getMock();

		$woocommerce->session->method( 'get' )->with( 'client_currency' )->willReturn( 'USD' );

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->shouldReceive( 'get_setting' )->with( 'currency_mode' )->andReturn( Geolocation::MODE_BY_LOCATION );

		$subject                       = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes       = $currency_codes;
		$subject->woocommerce_wpml     = $woocommerce_wpml;
		$subject->woocommerce          = $woocommerce;

		\WP_Mock::userFunction(
			'is_ajax',
			[
				'return' => false,
			]
		);

		$this->assertEquals( $client_currency, $subject->maybe_get_currency_by_geolocation( $client_currency ) );

	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_should_not_get_currency_by_geolocation_if_country_currency_not_active() {
		\WP_Mock::passthruFunction( 'wp_cache_add_non_persistent_groups' );

		$client_currency  = 'EUR';
		$country_currency = 'UAH';
		$currency_codes   = [ 'USD', 'EUR' ];

		$woocommerce = \Mockery::mock( 'woocommerce' );

		$woocommerce->session = $this->getMockBuilder( 'WC_Session' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( [ 'get' ] )
		                            ->getMock();

		$woocommerce->session->method( 'get' )->with( 'client_currency' )->willReturn( false );

		$wpml_cache = \Mockery::mock( 'overload:WPML_WP_Cache' );
		$wpml_cache->shouldReceive( 'get' )->with( 'location_currency', false )->andReturn( false );
		$wpml_cache->shouldReceive( 'set' )->with( 'location_currency', $client_currency )->andReturn( true );

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->shouldReceive( 'get_setting' )->with( 'currency_mode' )->andReturn( Geolocation::MODE_BY_LOCATION );

		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::getCurrencyCodeByUserCountry', $country_currency );

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes   = $currency_codes;
		$subject->woocommerce_wpml = $woocommerce_wpml;
		$subject->woocommerce      = $woocommerce;

		\WP_Mock::userFunction(
			'is_ajax',
			[
				'return' => false,
			]
		);

		$this->assertEquals( $client_currency, $subject->maybe_get_currency_by_geolocation( $client_currency ) );

	}

	/**
	 * @test
	 */
	public function it_should_not_get_currency_by_geolocation_if_geolocation_not_enabled() {

		$client_currency  = 'EUR';

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->shouldReceive( 'get_setting' )->with( 'currency_mode' )->andReturn( Geolocation::MODE_BY_LANGUAGE );

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->woocommerce_wpml = $woocommerce_wpml;

		$this->assertEquals( $client_currency, $subject->maybe_get_currency_by_geolocation( $client_currency ) );

	}

	/**
	 *
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_should_get_language_default_currency_by_location() {
		\WP_Mock::passthruFunction( 'wp_cache_add_non_persistent_groups' );

		$client_language  = 'ua';
		$country_currency = 'UAH';
		$currency_codes   = [ 'USD', $country_currency ];

		$woocommerce_wpml                                                     = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->settings['default_currencies'][ $client_language ] = 'location';
		$woocommerce_wpml->settings['currency_options'][ $country_currency ] = [];

		$wpml_cache = \Mockery::mock( 'overload:WPML_WP_Cache' );
		$wpml_cache->shouldReceive( 'get' )->with( 'location_currency', false )->andReturn( false );
		$wpml_cache->shouldReceive( 'set' )->with( 'location_currency', $country_currency )->andReturn( true );

		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::getCurrencyCodeByUserCountry', $country_currency );
		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::isCurrencyAvailableForCountry', true );

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes   = $currency_codes;
		$subject->woocommerce_wpml = $woocommerce_wpml;

		$this->assertEquals( $country_currency, $subject->get_language_default_currency( $client_language ) );
	}

	/**
	 *
	 * @test
	 */
	public function it_should_get_language_default_currency_by_setting() {

		$client_language = 'ua';
		$client_currency = 'UAH';

		$woocommerce_wpml                                                     = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->settings['default_currencies'][ $client_language ] = $client_currency;

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->woocommerce_wpml = $woocommerce_wpml;

		$this->assertEquals( $client_currency, $subject->get_language_default_currency( $client_language ) );
	}

	/**
	 *
	 * @test
	 */
	public function it_should_not_get_language_default_currency() {

		$client_language = 'ua';

		$woocommerce_wpml                                       = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->settings['default_currencies']['de'] = 'EUR';

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->woocommerce_wpml = $woocommerce_wpml;

		$this->assertFalse( $subject->get_language_default_currency( $client_language ) );
	}

	/**
	 *
	 * @test
	 */
	public function it_should_set_currency_to_session() {

		$client_currency = 'UAH';

		$woocommerce = \Mockery::mock( 'woocommerce' );

		$woocommerce->session = $this->getMockBuilder( 'WC_Session' )
		                             ->disableOriginalConstructor()
		                             ->setMethods( [ 'set' ] )
		                             ->getMock();

		$woocommerce->session->expects( $this->once() )->method( 'set' )->with( 'client_currency', $client_currency )->willReturn( true );

		$subject              = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->woocommerce = $woocommerce;

		$subject->set_currency_in_storage( $client_currency );
	}

	/**
	 *
	 * @test
	 */
	public function it_should_get_currency_from_session() {

		$client_currency = 'UAH';

		$woocommerce = \Mockery::mock( 'woocommerce' );

		$woocommerce->session = $this->getMockBuilder( 'WC_Session' )
		                             ->disableOriginalConstructor()
		                             ->setMethods( [ 'get' ] )
		                             ->getMock();

		$woocommerce->session->expects( $this->once() )->method( 'get' )->with( 'client_currency' )->willReturn( $client_currency );

		$subject              = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->woocommerce = $woocommerce;

		$this->assertEquals( $client_currency, $subject->get_currency_from_storage() );
	}

	/**
	 *
	 * @test
	 */
	public function it_should_set_currency_to_cookie() {

		\WP_Mock::onFilter( 'wcml_store_currency_in_session' )
		        ->with( true )
		        ->reply( false );

		$client_currency = 'EUR';

		$cookie_handler = $this->getMockBuilder( 'WPML_Cookie' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( [ 'headers_sent', 'set_cookie' ] )
		                       ->getMock();

		$session_expiration = time() + (int) 60 * 60 * 48;

		$cookie_handler->expects( $this->once() )->method( 'headers_sent' )->willReturn( false );
		$cookie_handler->expects( $this->once() )->method( 'set_cookie' )->with( 'wcml_client_currency', $client_currency, $session_expiration, COOKIEPATH, COOKIE_DOMAIN )->willReturn( true );

		$subject                 = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->cookie_handler = $cookie_handler;

		$subject->set_currency_in_storage( $client_currency );
	}

	/**
	 *
	 * @test
	 */
	public function it_should_get_currency_from_cookie() {

		\WP_Mock::onFilter( 'wcml_store_currency_in_session' )
		        ->with( true )
		        ->reply( false );

		$client_currency = 'EUR';

		$cookie_handler = $this->getMockBuilder( 'WPML_Cookie' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( [ 'get_cookie' ] )
		                       ->getMock();

		$cookie_handler->expects( $this->once() )->method( 'get_cookie' )->willReturn( $client_currency );

		$subject                 = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->cookie_handler = $cookie_handler;

		$this->assertEquals( $client_currency, $subject->get_currency_from_storage() );
	}

}
