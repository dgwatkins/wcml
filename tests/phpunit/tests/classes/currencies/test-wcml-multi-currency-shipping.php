<?php

class Test_WCML_Multi_Currency_Shipping extends OTGS_TestCase {

	/** @var WCML_Multi_Currency */
	private $multi_currency;
	/** @var Sitepress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	public function setUp() {
		parent::setUp();

		$this->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_client_currency' ) )
		    ->getMock();

		$this->sitepress = $this->getMockBuilder( 'Sitepress' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api' ) )
			->getMock();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )
			->disableOriginalConstructor()
			->setMethods( array( 'constant', 'version_compare' ) )
			->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );

		$this->wpdb = $this->stubs->wpdb();
	}

	/**
	 * @return WCML_Multi_Currency_Shipping
	 */
	private function get_subject(){
		$subject = new WCML_Multi_Currency_Shipping( $this->multi_currency, $this->sitepress, $this->wpdb );

		return $subject;
	}

	/**
	* @test
	*/
	public function add_hooks(){


		$method = new stdClass();
		$method->method_id = rand_str();
		$method->instance_id = rand( 1, 100 );
		$rates = array( $method );

		$this->wpdb->method('get_results')->willReturn( $rates );
		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'wcml_shipping_price_amount', array( $subject, 'shipping_price_filter' ) );
		\WP_Mock::expectFilterAdded( 'wcml_shipping_free_min_amount', array( $subject, 'shipping_free_min_amount' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_evaluate_shipping_cost_args', array( $subject, 'woocommerce_evaluate_shipping_cost_args' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_shipping_packages', array( $subject, 'convert_shipping_taxes' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_package_rates', array( $subject, 'convert_shipping_costs_in_package_rates' ) );

		\WP_Mock::expectFilterAdded( 'option_woocommerce_'.$method->method_id.'_'.$method->instance_id.'_settings', array( $subject, 'convert_shipping_method_cost_settings' ) );

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function convert_shipping_costs_in_package_rates() {

		$client_currency = rand_str();
		$this->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );

		$rates             = array();
		$rate_id           = rand_str();
		$rate              = $this->getMockBuilder( 'WC_Shipping_Rate' )
			->disableOriginalConstructor()
			->getMock();
		$rate->cost        = rand( 1, 100 );
		$rates[ $rate_id ] = $rate;

		\WP_Mock::userFunction( 'wp_cache_get', array(
			'args'   => array( $rate_id, 'converted_shipping_cost' ),
			'return' => false
		) );

		$this->multi_currency->prices = $this->getMockBuilder( 'WCML_Multi_Currency_Prices' )
		                                     ->disableOriginalConstructor()
		                                     ->setMethods( array( 'raw_price_filter' ) )
		                                     ->getMock();

		$converted_cost = rand( 1, 100 );
		$this->multi_currency->prices->expects( $this->once() )->method( 'raw_price_filter' )->with( $rate->cost, $client_currency )->willReturn( $converted_cost );

		\WP_Mock::userFunction( 'wp_cache_set', array(
			'args'   => array( $rate_id, $converted_cost, 'converted_shipping_cost' ),
			'return' => true
		) );

		$subject = $this->get_subject();
		$rates   = $subject->convert_shipping_costs_in_package_rates( $rates );

		$this->assertEquals( $converted_cost, $rates[ $rate_id ]->cost );
	}

	/**
	 * @test
	 */
	public function convert_shipping_costs_in_package_rates_when_manual_shipping_cost_is_set() {
		\WP_Mock::userFunction( 'WPML\Container\make', [
			'return' => function( $className ) {
				return new $className();
			},
		]);


		$client_currency = rand_str();
		$this->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );

		$rates             = [];
		$rate_id           = rand_str();
		$rate              = $this->getMockBuilder( 'WC_Shipping_Rate' )
		                          ->disableOriginalConstructor()
		                          ->getMock();
		$rate->cost        = 15;
		$rate->method_id = 'flat_rate';
		$rate->instance_id = 2;
		$rates[ $rate_id ] = $rate;

		$currency_cost = 20;
		$currency_key = 'cost_' . $client_currency;

		\WP_Mock::userFunction( 'wp_cache_get', array(
			'args'   => array( $rate_id, 'converted_shipping_cost' ),
			'return' => false
		) );

		\WP_Mock::userFunction( 'get_option', [
			'return' => [
				$currency_key => $currency_cost
			],
			'args' => [ 'woocommerce_' . $rate->method_id . '_' . $rate->instance_id . '_settings' ]
		]);


		$this->multi_currency->prices = $this->getMockBuilder( 'WCML_Multi_Currency_Prices' )
		                                     ->disableOriginalConstructor()
		                                     ->setMethods( array( 'raw_price_filter' ) )
		                                     ->getMock();

		$converted_cost = $currency_cost;

		\WP_Mock::userFunction( 'wp_cache_set', array(
			'args'   => array( $rate_id, $converted_cost, 'converted_shipping_cost' ),
			'return' => true
		) );

		$subject = $this->get_subject();
		$rates   = $subject->convert_shipping_costs_in_package_rates( $rates );

		$this->assertEquals( $converted_cost, $rates[ $rate_id ]->cost );
	}

	/**
	 * @test
	 */
	public function convert_shipping_costs_in_package_rates_test_cost_from_cache(){

		$client_currency = rand_str();
		$this->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );

		$rates = array();
		$rate_id = rand_str();
		$rate              = new stdClass();
		$rate->cost        = rand( 1, 100 );
		$rates[ $rate_id ] = $rate;

		$converted_cost_from_cache = rand( 1, 100 );

		\WP_Mock::wpFunction( 'wp_cache_get', array(
			'args' => array( $rate_id, 'converted_shipping_cost' ),
			'return' => $converted_cost_from_cache
		) );

		$subject = $this->get_subject();
		$rates = $subject->convert_shipping_costs_in_package_rates( $rates );

		$this->assertEquals( $converted_cost_from_cache, $rates[ $rate_id ]->cost );

	}

	/**
	 * @test
	 */
	public function it_does_not_convert_shipping_taxes_when_wc_taxes_calculation_is_off(){

		$subject = $this->get_subject();
		$packages = [ rand_str() => rand_str() ];

		\WP_Mock::wpFunction( 'get_option', [
			'times'=> 1,
			'args' => 'woocommerce_calc_taxes',
			'return' => 'no'
		] );

		$this->assertSame( $packages, $subject->convert_shipping_taxes( $packages ) );
	}

	/**
	 * @test
	 */
	public function it_does_not_convert_shipping_taxes_when_no_shipping_tax(){

		$subject = $this->get_subject();

		$rate1 = $this->getMockBuilder( 'WC_Shipping_Rate' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_shipping_tax' ) )
		              ->getMock();

		$rate1->method( 'get_shipping_tax' )->willReturn( 0 );

		$packages = [
			0 => [ 'rates' => [ $rate1 ] ]

		];

		\WP_Mock::wpFunction( 'get_option', [
			'times'=> 1,
			'args' => 'woocommerce_calc_taxes',
			'return' => 'yes'
		] );

		$this->assertSame( $packages, $subject->convert_shipping_taxes( $packages ) );
	}

	/**
	 * @test
	 */
	public function it_does_convert_shipping_taxes_when_wc_taxes_calculation_is_on(){

		$subject = $this->get_subject();

		$rate1 = $this->getMockBuilder( 'WC_Shipping_Rate' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_shipping_tax' ) )
			->getMock();

		$rate1->method( 'get_shipping_tax' )->willReturn( mt_rand( 1, 10 ) );

		$rate1->taxes = [
			0 => round ( random_int(1, 100) / 100 ),
			1 => round ( random_int(1, 100) / 100 )
		];
		$rate1->cost = round ( random_int(1, 100) / 100 );

		$rate2 = $this->getMockBuilder( 'WC_Shipping_Rate' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_shipping_tax' ) )
		              ->getMock();

		$rate2->method( 'get_shipping_tax' )->willReturn( mt_rand( 1, 10 ) );

		$rate2->cost = round ( random_int(1, 100) / 100 );

		$rate2->taxes = [
			0 => round ( random_int(1, 100) / 100, 2 ),
			1 => round ( random_int(1, 100) / 100, 2 )
		];

		$packages = [
			0 => [ 'rates' => [ $rate1, $rate2 ] ]

		];

		\WP_Mock::wpFunction( 'get_option', [
			'times'=> 1,
			'args' => 'woocommerce_calc_taxes',
			'return' => 'yes'
		] );

		$wc_tax_mock = \Mockery::mock( 'overload:WC_Tax' );


		$converted_taxes_1 = [
			0 => round ( random_int(1, 100) / 100, 2 ),
			1 => round ( random_int(1, 100) / 100, 2 )
		];
		$converted_taxes_2 = [
			0 => round ( random_int(1, 100) / 100, 2 ),
			1 => round ( random_int(1, 100) / 100, 2 )
		];


		$wc_tax_mock->shouldReceive( 'calc_shipping_tax' )
		            ->andReturn( $converted_taxes_1, $converted_taxes_2 );

		$wc_tax_mock->shouldReceive( 'get_shipping_tax_rates' )->twice();

		$packages_converted = $subject->convert_shipping_taxes( $packages );

		$this->assertSame( $converted_taxes_1, $packages_converted[0]['rates'][0]->taxes );
		$this->assertSame( $converted_taxes_2, $packages_converted[0]['rates'][1]->taxes );

	}

}
