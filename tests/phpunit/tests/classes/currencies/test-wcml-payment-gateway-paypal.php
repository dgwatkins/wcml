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

		WP_Mock::userFunction( 'get_option', array(
			'args' => array( 'woocommerce_currency' ),
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
	 */
	public function it_should_filter_paypal_args() {

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

		$product_id               = 1;
		$cart_contents            = array( array( 'product_id' => $product_id ) );
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

		$wc->cart->method( 'get_cart_contents' )->willReturn( $cart_contents );
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

		$this->woocommerce_wpml->multi_currency->prices->method( 'get_product_price_in_currency' )->with( $product_id, $gateway->settings[ $client_currency ]['currency'] )->willReturn( $converted_product_price );
		$this->woocommerce_wpml->multi_currency->prices->method( 'convert_price_amount_by_currencies' )->with( $shipping_total, $client_currency, $gateway->settings[ $client_currency ]['currency'] )->willReturn( $converted_shipping_total );


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

}
