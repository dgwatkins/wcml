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
	 */
	public function it_should_get_currency_by_geolocation() {

		$client_currency  = null;
		$country_currency = 'UAH';
		$currency_codes   = [ 'USD', $country_currency ];

		$woocommerce_session = $this->getMockBuilder( 'WC_Session' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( [ 'get' ] )
		                            ->getMock();

		$woocommerce_session->method( 'get' )->with( 'client_currency' )->willReturn( false );

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->settings['currency_options'][ $country_currency ] = [];
		$woocommerce_wpml->shouldReceive( 'get_setting' )->with( 'currency_mode' )->andReturn( Geolocation::MODE_BY_LOCATION );

		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::getCurrencyCodeByUserCountry', $country_currency );
		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::isCurrencyAvailableForCountry', true );

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes   = $currency_codes;
		$subject->woocommerce_wpml = $woocommerce_wpml;

		$this->assertEquals( $country_currency, $subject->maybe_get_currency_by_geolocation( $client_currency, $woocommerce_session ) );

	}

	/**
	 * @test
	 */
	public function it_should_not_get_currency_by_geolocation_if_currency_already_set_in_session() {

		$client_currency  = null;
		$country_currency = 'UAH';
		$currency_codes   = [ 'USD', $country_currency ];

		$woocommerce_session = $this->getMockBuilder( 'WC_Session' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( [ 'get' ] )
		                            ->getMock();

		$woocommerce_session->method( 'get' )->with( 'client_currency' )->willReturn( 'USD' );

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->shouldReceive( 'get_setting' )->with( 'currency_mode' )->andReturn( Geolocation::MODE_BY_LOCATION );

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes   = $currency_codes;
		$subject->woocommerce_wpml = $woocommerce_wpml;

		$this->assertEquals( $client_currency, $subject->maybe_get_currency_by_geolocation( $client_currency, $woocommerce_session ) );

	}

	/**
	 * @test
	 */
	public function it_should_not_get_currency_by_geolocation_if_country_currency_not_active() {

		$client_currency  = null;
		$country_currency = 'UAH';
		$currency_codes   = [ 'USD', 'EUR' ];

		$woocommerce_session = $this->getMockBuilder( 'WC_Session' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( [ 'get' ] )
		                            ->getMock();

		$woocommerce_session->method( 'get' )->with( 'client_currency' )->willReturn( false );

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->shouldReceive( 'get_setting' )->with( 'currency_mode' )->andReturn( Geolocation::MODE_BY_LOCATION );

		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::getCurrencyCodeByUserCountry', $country_currency );

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes   = $currency_codes;
		$subject->woocommerce_wpml = $woocommerce_wpml;

		$this->assertEquals( $client_currency, $subject->maybe_get_currency_by_geolocation( $client_currency, $woocommerce_session ) );

	}

	/**
	 *
	 * @test
	 */
	public function it_should_not_get_currency_by_geolocation_if_geolocation_not_enabled() {

		$client_currency  = null;
		$country_currency = 'UAH';
		$currency_codes   = [ 'USD', $country_currency ];

		$woocommerce_session = $this->getMockBuilder( 'WC_Session' )
		                            ->disableOriginalConstructor()
		                            ->setMethods( [ 'get' ] )
		                            ->getMock();

		$woocommerce_session->method( 'get' )->with( 'client_currency' )->willReturn( false );

		$woocommerce_wpml = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->shouldReceive( 'get_setting' )->with( 'currency_mode' )->andReturn( Geolocation::MODE_BY_LANGUAGE );

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes   = $currency_codes;
		$subject->woocommerce_wpml = $woocommerce_wpml;

		$this->assertEquals( $client_currency, $subject->maybe_get_currency_by_geolocation( $client_currency, $woocommerce_session ) );

	}

	/**
	 * @test
	 * @group wcml-3232
	 */
	public function it_should_get_language_default_currency_by_location() {

		$client_language  = 'ua';
		$country_currency = 'UAH';
		$currency_codes   = [ 'USD', $country_currency ];

		$woocommerce_wpml                                                     = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->settings['default_currencies'][ $client_language ] = 'location';

		FunctionMocker::replace( 'WCML\MultiCurrency\Geolocation::getCurrencyCodeByUserCountry', $country_currency );

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->currency_codes   = $currency_codes;
		$subject->woocommerce_wpml = $woocommerce_wpml;

		$this->assertEquals( $country_currency, $subject->get_language_default_currency( $client_language ) );
	}

	/**
	 * @test
	 * @group wcml-3232
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
	 * @test
	 * @group wcml-3232
	 */
	public function it_should_not_get_language_default_currency() {

		$client_language = 'ua';

		$woocommerce_wpml                                       = \Mockery::mock( 'woocommerce_wpml' );
		$woocommerce_wpml->settings['default_currencies']['de'] = 'EUR';

		$subject                   = \Mockery::mock( 'WCML_Multi_Currency' )->makePartial();
		$subject->woocommerce_wpml = $woocommerce_wpml;

		$this->assertFalse( $subject->get_language_default_currency( $client_language ) );
	}
}
