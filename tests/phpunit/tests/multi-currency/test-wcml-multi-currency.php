<?php

/**
 * Class Test_WCML_Multi_Currency
 *
 * @group wcml-2957
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
}
