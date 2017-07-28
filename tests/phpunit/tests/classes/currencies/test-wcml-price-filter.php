<?php

class Test_WCML_Price_Filter extends OTGS_TestCase {

	/**
	 * @var woocommerce_wpml;
	 */
	private $woocommerce_wpml;
	/**
	 * @var bool
	 */
	private $is_admin = false;

	/**
	 * @var array
	 */
	private $options = [];

	/**
	 * @var string
	 */
	private $woocommerce_currency = '';

	/**
	 * @var string
	 */
	private $client_currency = '';

	public function setUp() {
		parent::setUp();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_client_currency' ) )
		                               ->getMock();

		$that = $this;
		$this->woocommerce_wpml->multi_currency
			->method( 'get_client_currency' )
			->will( $this->returnCallback(
				function() use( $that ){
					return $that->client_currency;
				}
			) );

		$this->woocommerce_wpml->multi_currency->prices =
			$this->getMockBuilder( 'WCML_Multi_Currency_Prices' )
			     ->disableOriginalConstructor()
				->setMethods( array( 'unconvert_price_amount' ) )
			     ->getMock();

		$this->woocommerce_wpml->multi_currency->prices->method( 'unconvert_price_amount' )
			->will( $this->returnCallback(
				function( $amount ) use ( $that ) {
					return $that->convert_price( $amount );
				}
			) );



		\WP_Mock::wpFunction( 'is_admin', array( 'return' => $this->is_admin ) );

		\WP_Mock::wpFunction( 'get_option', array(
			'return' => function ( $option_name ) {
				return isset( $this->options[ $option_name ] ) ? $this->options[ $option_name ] : null;
			}
		) );

	}

	private function convert_price( $amount ){
		return 2*$amount;
	}

	/**
	 * @test
	 */
	public function add_hooks_admin() {
		$subject = new WCML_Price_Filter( $this->woocommerce_wpml );

		$this->is_admin = true;
		WP_Mock::expectActionAdded( 'wp_footer', array( $subject, 'override_currency_symbol' ), 100 );
		WP_Mock::expectActionNotAdded(
			'woocommerce_product_query_meta_query',
			array(
			$subject,
			'unconvert_price_filter_limits'
			),
			10,
			1
		);
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_hooks_non_admin(){
		$subject = new WCML_Price_Filter( $this->woocommerce_wpml );

		$this->is_admin = false;
		WP_Mock::expectFilterAdded( 'woocommerce_product_query_meta_query', array( $subject, 'unconvert_price_filter_limits') );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function unconvert_price_filter_limits() {

		$subject = new WCML_Price_Filter( $this->woocommerce_wpml );
		$meta_query = array();

		$this->options['woocommerce_currency'] = rand_str();
		$this->client_currency = rand_str();
		$meta_query = $subject->unconvert_price_filter_limits( $meta_query );
		$this->assertEquals( array(), $meta_query );

		$meta_query = $empty_price_filter = [ 'price_filter' => [ 'key' => rand_str() ] ];
		$meta_query = $subject->unconvert_price_filter_limits( $meta_query );
		$this->assertEquals( $empty_price_filter, $meta_query );

		$meta_query = [
			'price_filter' => [
					'key' => '_price',
					'value' => [
						0 => rand(1, 100),
						1 => rand(1, 100)
					]
				]
		];
		$meta_query_filtered = $subject->unconvert_price_filter_limits( $meta_query );

		$this->assertEquals(
			$this->convert_price( $meta_query['price_filter']['value'][0] ),
			$meta_query_filtered['price_filter']['value'][0]
		);
		$this->assertEquals(
			$this->convert_price( $meta_query['price_filter']['value'][1] ),
			$meta_query_filtered['price_filter']['value'][1]
		);

	}
}
