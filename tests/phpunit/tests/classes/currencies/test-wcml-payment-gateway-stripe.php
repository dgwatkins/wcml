<?php

class Test_WCML_Payment_Gateway_Stripe extends OTGS_TestCase {

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
			$gateway->settings['publishable_key'] = 'publishable_key';
			$gateway->settings['secret_key'] = 'secret_key';

			WP_Mock::userFunction( 'get_option', array(
				'args' => array( WCML_Payment_Gateway::OPTION_KEY.$gateway->id, array() ),
				'times' => 1,
				'return' => array()
			));
		}

		$template_service = $this->getMockBuilder( 'IWPML_Template_Service' )
		                         ->disableOriginalConstructor()
		                         ->getMock();


		return new WCML_Payment_Gateway_Stripe( $gateway, $template_service, $this->woocommerce_wpml );
	}

	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'woocommerce_stripe_request_body', array( $subject, 'filter_request_body' ) );

		$subject->add_hooks();
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
		$expected_currencies_details = array(
			'USD' => array( 'publishable_key' => 'publishable_key', 'secret_key' => 'secret_key' ),
			'UAH' => array( 'publishable_key' => '', 'secret_key' => '' )
		);

		$this->assertSame( $expected_currencies_details, $subject->get_currencies_details( $active_currencies ) );
	}

	/**
	 * @test
	 */
	public function filter_request_body() {

		$client_currency = 'USD';
		$request         = array( 'currency' => strtolower( $client_currency ), 'amount' => 10 );
		$convert_amount  = 100;

		$gateway           = $this->getMockBuilder( 'WC_Payment_Gateway' )
		                          ->disableOriginalConstructor()
		                          ->getMock();
		$gateway->id       = 'id';
		$gateway->title    = 'title';
		$gateway->settings = array( $client_currency => array( 'currency' => 'EUR' ) );

		WP_Mock::userFunction( 'get_option', array(
			'args'   => array( WCML_Payment_Gateway::OPTION_KEY . $gateway->id, array() ),
			'times'  => 1,
			'return' => $gateway->settings
		) );

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );

		$this->woocommerce_wpml->cart = $this->getMockBuilder( 'WCML_Cart' )
		                                     ->disableOriginalConstructor()
		                                     ->setMethods( array( 'get_cart_total_in_currency' ) )
		                                     ->getMock();

		$this->woocommerce_wpml->cart->method( 'get_cart_total_in_currency' )->with( $gateway->settings[ $client_currency ]['currency'] )->willReturn( $convert_amount );

		$stripe_helper = \Mockery::mock( 'overload:WC_Stripe_Helper' );
		$stripe_helper->shouldReceive( 'get_stripe_amount' )
		              ->andReturn( $convert_amount );

		$subject = $this->get_subject( $gateway );

		$expected_request = array(
			'currency' => strtolower( $gateway->settings[ $client_currency ]['currency'] ),
			'amount'   => $convert_amount
		);

		$filtered_request = $subject->filter_request_body( $request );

		$this->assertSame( $expected_request, $filtered_request );
	}

	/**
	 * @test
	 */
	public function it_should_filter_stripe_settings() {

		$client_currency = 'USD';
		$settings        = array(
			'testmode'             => 'yes',
			'test_publishable_key' => rand_str(),
			'test_secret_key'      => rand_str()
		);

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method( 'get_client_currency' )->willReturn( $client_currency );

		global $woocommerce_wpml;
		$woocommerce_wpml = $this->woocommerce_wpml;

		$gateway_settings = array(
			$client_currency => array(
				'currency'        => 'EUR',
				'publishable_key' => rand_str(),
				'secret_key'      => rand_str()
			)
		);

		WP_Mock::userFunction( 'get_option', array(
			'args'   => array( WCML_Payment_Gateway_Stripe::OPTION_KEY . WCML_Payment_Gateway_Stripe::ID, array() ),
			'times'  => 2,
			'return' => $gateway_settings
		) );

		$expected_settings = array(
			'testmode'             => 'yes',
			'test_publishable_key' => $gateway_settings[ $client_currency ]['publishable_key'],
			'test_secret_key'      => $gateway_settings[ $client_currency ]['secret_key']
		);

		$filtered_settings = WCML_Payment_Gateway_Stripe::filter_stripe_settings( $settings );

		$this->assertSame( $expected_settings, $filtered_settings );

		$settings          = array( 'testmode' => 'no', 'publishable_key' => rand_str(), 'secret_key' => rand_str() );
		$expected_settings = array(
			'testmode'        => 'no',
			'publishable_key' => $gateway_settings[ $client_currency ]['publishable_key'],
			'secret_key'      => $gateway_settings[ $client_currency ]['secret_key']
		);

		$filtered_settings = WCML_Payment_Gateway_Stripe::filter_stripe_settings( $settings );

		$this->assertSame( $expected_settings, $filtered_settings );
	}

}
