<?php

/**
 * Class Test_WCML_Payment_Gateway_PayPal
 *
 * @group fix-tests-on-windows
 */
class Test_WCML_Payment_Gateway_PayPal extends OTGS_TestCase {

	/** @var  woocommerce_wpml */
	private $woocommerce_wpml;

	function setUp() {
		parent::setUp();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();
	}

	private function get_subject( $gateway = null ) {
		if( null === $gateway ){
			$gateway = $this->getMockBuilder('WC_Payment_Gateway')
			                ->disableOriginalConstructor()
			                ->getMock();

			$gateway->id = 'id';
			$gateway->title = 'title';
			$gateway->settings['email'] = 'test_email';

			WP_Mock::userFunction( 'get_option', array(
				'args' => array( WCML_Payment_Gateway::OPTION_KEY.$gateway->id, array() ),
				'times' => 1,
				'return' => array()
			));
		}

		$template_service = $this->getMockBuilder( 'IWPML_Template_Service' )
		                         ->disableOriginalConstructor()
		                         ->getMock();


		return new WCML_Payment_Gateway_PayPal( $gateway, $template_service, $this->woocommerce_wpml );
	}


	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'woocommerce_paypal_args', array( $subject, 'filter_paypal_args' ), 10, 2 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function is_valid_for_use() {

		\WP_Mock::passthruFunction( 'remove_filter' );

		$subject = $this->get_subject();

		$this->assertTrue( $subject->is_valid_for_use( 'USD' ) );
	}

	/**
	 * @test
	 */
	public function is_not_valid_for_use() {
		$subject = $this->get_subject();

		$this->assertFalse( $subject->is_valid_for_use( 'UAH' ) );
	}

	/**
	 * @test
	 */
	public function is_should_get_currencies_details() {
		$subject = $this->get_subject();

		WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => 'USD'
		));

		$active_currencies = array( 'USD' => array(), 'UAH' => array() );
		WP_Mock::userFunction( 'get_woocommerce_currencies', array(
			'return' => $active_currencies
		));
		$expected_currencies_details = array(
			'USD' => array( 'value' => 'test_email', 'currency' => 'USD', 'is_valid' => true ),
			'UAH' => array( 'value' => '', 'currency' => 'UAH', 'is_valid' => false )
		);

		$this->assertSame( $expected_currencies_details, $subject->get_currencies_details() );
	}

	/**
	 * @test
	 * @dataProvider get_cart_contents
	 *
	 * @param array $cart_contents
	 */
	public function it_should_filter_paypal_args( $cart_contents ) {

		$args            = array( 'currency_code' => 'USD', 'business' => 'business@email.com' );
		$client_currency = 'USD';

		$gateway           = $this->getMockBuilder( 'WC_Payment_Gateway' )
		                          ->disableOriginalConstructor()
		                          ->getMock();
		$gateway->id       = 'id';
		$gateway->title    = 'title';
		$gateway->settings = array( $client_currency => array( 'currency' => 'EUR', 'value' => 'test@email.com' ) );

		WP_Mock::userFunction( 'get_option', array(
			'args'   => array( WCML_Payment_Gateway::OPTION_KEY . $gateway->id, array() ),
			'times'  => 1,
			'return' => $gateway->settings
		) );

		$order_data = array( 'currency' => $client_currency );

		$order = $this->getMockBuilder( 'WC_Order' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_data' ) )
		              ->getMock();

		$order->method( 'get_data' )->willReturn( $order_data );

		$converted_product_price  = 11;
		$shipping_total           = 10;
		$converted_shipping_total = 11;

		$wc = $this->getMockBuilder( 'WC' )
		           ->disableOriginalConstructor()
		           ->getMock();

		$wc->cart = $this->getMockBuilder( 'WC_Cart' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_cart_contents', 'get_shipping_total' ) )
		                 ->getMock();

		$wc->cart->method( 'get_cart_contents' )->willReturn( [ $cart_contents ] );
		$wc->cart->method( 'get_shipping_total' )->willReturn( $shipping_total );

		WP_Mock::userFunction( 'WC', array(
			'return' => $wc
		) );


		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->prices = $this->getMockBuilder( 'WCML_Multi_Currency_Prices' )
		                                                       ->disableOriginalConstructor()
		                                                       ->setMethods( array(
			                                                       'get_product_price_in_currency',
			                                                       'convert_price_amount_by_currencies'
		                                                       ) )
		                                                       ->getMock();

		$item_product_id = $cart_contents[ 'variation_id' ] ? $cart_contents[ 'variation_id' ] : $cart_contents[ 'product_id' ];
		$this->woocommerce_wpml->multi_currency->prices->method( 'get_product_price_in_currency' )->with( $item_product_id, $gateway->settings[ $client_currency ]['currency'] )->willReturn( $converted_product_price );

		$this->woocommerce_wpml->cart = $this->getMockBuilder( 'WCML_Cart' )
		                                                       ->disableOriginalConstructor()
		                                                       ->setMethods( array(
			                                                       'get_cart_shipping_in_currency'
		                                                       ) )
		                                                       ->getMock();

		$this->woocommerce_wpml->cart->method( 'get_cart_shipping_in_currency' )->with( $gateway->settings[ $client_currency ]['currency'] )->willReturn( $converted_shipping_total );

		$subject = $this->get_subject( $gateway );

		$filtered_args          = $subject->filter_paypal_args( $args, $order );
		$expected_filtered_args = array(
			'currency_code' => $gateway->settings[ $client_currency ]['currency'],
			'business'      => $gateway->settings[ $client_currency ]['value'],
			'amount_1'      => $converted_product_price,
			'shipping_1'    => $converted_shipping_total
		);

		$this->assertSame( $expected_filtered_args, $filtered_args );
	}

	public function get_cart_contents(){
		return [
			[
				[
					'product_id'   => 11,
					'variation_id' => 0,
					'quantity'     => 1
				]
			],
			[
				[
					'product_id'   => 12,
					'variation_id' => 14,
					'quantity'     => 2
				]
			]
		];
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function it_should_add_not_supported_currency_to_supported_currencies_array() {

		$client_currency = 'UAH';

		$this->mock_woocommerce_wpml( $client_currency );

		$gateway_settings = array(
			$client_currency => array(
				'currency'   => 'USD',
				'email'      => rand_str()
			)
		);

		WP_Mock::userFunction( 'get_option', array(
			'args'   => array( WCML_Payment_Gateway_PayPal::OPTION_KEY . WCML_Payment_Gateway_PayPal::ID, array() ),
			'times'  => 1,
			'return' => $gateway_settings
		) );

		$expected_currencies = array( $client_currency );

		$filtered_currencies = WCML_Payment_Gateway_PayPal::filter_supported_currencies( array() );

		$this->assertSame( $expected_currencies, $filtered_currencies );
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 */
	public function it_should_not_add_not_supported_currency_to_supported_currencies_array() {

		$client_currency = 'UAH';

		$this->mock_woocommerce_wpml( $client_currency );

		$gateway_settings = array(
			$client_currency => array(
				'currency'   => $client_currency,
				'email'      => rand_str()
			)
		);

		WP_Mock::userFunction( 'get_option', array(
			'args'   => array( WCML_Payment_Gateway_PayPal::OPTION_KEY . WCML_Payment_Gateway_PayPal::ID, array() ),
			'times'  => 1,
			'return' => $gateway_settings
		) );

		$filtered_currencies = WCML_Payment_Gateway_PayPal::filter_supported_currencies( array() );

		$this->assertSame( array(), $filtered_currencies );
	}

	private function mock_woocommerce_wpml( $client_currency ) {

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );

		global $woocommerce_wpml;
		$woocommerce_wpml = $this->woocommerce_wpml;
	}

}
