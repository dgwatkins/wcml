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

		$check_version = '2.6.0';
		$wc_version = '2.5.0';

		$this->wp_api->expects( $this->once() )
		             ->method( 'constant' )
		             ->with( 'WC_VERSION' )
		             ->willReturn( $wc_version );
		$this->wp_api->expects( $this->once() )
		             ->method( 'version_compare' )
		             ->with( $wc_version, $check_version, '>=' )
		             ->willReturn( false );

		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'wcml_shipping_price_amount', array( $subject, 'shipping_price_filter' ) );
		\WP_Mock::expectFilterAdded( 'wcml_shipping_free_min_amount', array( $subject, 'shipping_free_min_amount' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_evaluate_shipping_cost_args', array( $subject, 'woocommerce_evaluate_shipping_cost_args' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_shipping_packages', array( $subject, 'convert_shipping_taxes' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_package_rates', array( $subject, 'convert_shipping_costs_in_package_rates' ) );

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function hooks_from_wc_2_6()
	{
		$check_version = '2.6.0';
		$wc_version = '2.7.0';
		$this->wp_api->expects( $this->once() )
			->method( 'constant' )
			->with( 'WC_VERSION' )
			->willReturn( $wc_version );
		$this->wp_api->expects( $this->once() )
			->method( 'version_compare' )
			->with( $wc_version, $check_version, '>=' )
			->willReturn( true );

		$method = new stdClass();
		$method->method_id = rand_str();
		$method->instance_id = rand( 1, 100 );
		$rates = array( $method );

		$this->wpdb->method('get_results')->willReturn( $rates );

		$subject = $this->get_subject();

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
		$rate              = new stdClass();
		$rate->cost        = rand( 1, 100 );
		$rates[ $rate_id ] = $rate;

		\WP_Mock::wpFunction( 'wp_cache_get', array(
			'args'   => array( $rate_id, 'converted_shipping_cost' ),
			'return' => false
		) );

		$this->multi_currency->prices = $this->getMockBuilder( 'WCML_Multi_Currency_Prices' )
		                                     ->disableOriginalConstructor()
		                                     ->setMethods( array( 'raw_price_filter' ) )
		                                     ->getMock();

		$converted_cost = rand( 1, 100 );
		$this->multi_currency->prices->expects( $this->once() )->method( 'raw_price_filter' )->with( $rate->cost, $client_currency )->willReturn( $converted_cost );

		\WP_Mock::wpFunction( 'wp_cache_set', array(
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

}
