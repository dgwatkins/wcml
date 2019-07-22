<?php

class Test_WCML_Currencies_Payment_Gateways extends OTGS_TestCase {

	/** @var  woocommerce_wpml */
	private $woocommerce_wpml;

	function setUp() {
		parent::setUp();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();
	}

	private function get_subject() {
		$wp_api           = $this->getMockBuilder( 'WPML_WP_API' )->disableOriginalConstructor()->setMethods( array( 'constant' ) )->getMock();
		$wcml_plugin_path = '../..';
		$wp_api->method( 'constant' )->with( 'WCML_PLUGIN_PATH' )->willReturn( $wcml_plugin_path );

		return new WCML_Currencies_Payment_Gateways( $this->woocommerce_wpml, $wp_api );
	}

	/**
	 * @test
	 */
	public function add_front_hooks(){

		WP_Mock::userFunction( 'is_admin', array(
			'times' => 1,
			'return' => false
		));

		$subject = $this->get_subject();
		\WP_Mock::expectActionAdded( 'init', array( $subject, 'init_gateways' ) );

		\WP_Mock::expectFilterAdded( 'woocommerce_gateway_description', array( $subject, 'filter_gateway_description' ), 10, 2 );
		\WP_Mock::expectFilterAdded( 'option_woocommerce_stripe_settings', array( 'WCML_Payment_Gateway_Stripe', 'filter_stripe_settings' ) );

		\WP_Mock::expectFilterAdded( 'woocommerce_paypal_supported_currencies', array( 'WCML_Payment_Gateway_PayPal', 'filter_supported_currencies' ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function check_is_enabled_for_currency() {
		$subject = $this->get_subject();
		$currency = 'UAH';

		WP_Mock::userFunction( 'get_option', array(
			'args' => array( $subject::OPTION_KEY, array() ),
			'times' => 1,
			'return' => array()
		));

		$this->assertFalse( $subject->is_enabled( $currency ) );
	}

	/**
	 * @test
	 */
	public function should_set_enabled() {
		$subject = $this->get_subject();
		$currency = 'UAH';
		$value = true;

		$enabled_settings[ $currency ] = $value;

		WP_Mock::userFunction( 'get_option', array(
			'args' => array( $subject::OPTION_KEY, array() ),
			'return' => array()
		));

		WP_Mock::userFunction( 'update_option', array(
			'args' => array( $subject::OPTION_KEY, $enabled_settings ),
			'times' => 1,
			'return' => true
		));

		$subject->set_enabled( $currency, $value );
	}

	/**
	 * @test
	 */
	public function should_get_gateways() {

		$not_supported_gateway     = $this->getMockBuilder( 'WC_Payment_Gateway' )
		                    ->disableOriginalConstructor()
		                    ->getMock();
		$not_supported_gateway->id = 'test';

		$paypal_gateway     = $this->getMockBuilder( 'WC_Payment_Gateway' )
		                    ->disableOriginalConstructor()
		                    ->getMock();
		$paypal_gateway->id = 'paypal';

		$client_currency = 'USD';

		$template_service = $this->getMockBuilder( 'IWPML_Template_Service' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method('get_client_currency')->willReturn( $client_currency );

		$available_payment_gateways[ $not_supported_gateway->id ] = $not_supported_gateway;
		$available_payment_gateways[ $paypal_gateway->id ] = $paypal_gateway;

		$wcml_not_supported_payment_gateway = new WCML_Not_Supported_Payment_Gateway( $not_supported_gateway, $template_service, $this->woocommerce_wpml );
		$wcml_paypal_payment_gateway = new WCML_Payment_Gateway_PayPal( $paypal_gateway, $template_service, $this->woocommerce_wpml );

		$twig = \Mockery::mock( 'overload:WPML_Twig_Template_Loader' );
		$twig->shouldReceive( 'get_template' )
		     ->andReturn( $template_service );

		$wc = \Mockery::mock( 'overload:WC' );
		$payment_gateways = \Mockery::mock( 'overload:WC_Payment_Gateways' );

		$payment_gateways->shouldReceive( 'get_available_payment_gateways' )
		                 ->andReturn( $available_payment_gateways );

		$wc->shouldReceive( 'payment_gateways' )
		   ->andReturn( $payment_gateways );

		WP_Mock::userFunction( 'WC', array(
			'return' => $wc
		) );

		$expected_payment_gateways[ $not_supported_gateway->id ] = $wcml_not_supported_payment_gateway;
		$expected_payment_gateways[ $paypal_gateway->id ] = $wcml_paypal_payment_gateway;

		$subject = $this->get_subject();
		$subject->init_gateways();
		$payment_gateways = $subject->get_gateways();

		$this->assertEquals( $expected_payment_gateways, $payment_gateways );
	}


	/**
	 * @test
	 */
	public function should_filter_gateway_description() {

		$client_currency = 'USD';
		$description = rand_str();
		$cart_total = '44 €';

		$gateway = $this->getMockBuilder( 'WC_Payment_Gateway' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'get_setting' ) )
		               ->getMock();

		$gateway->id = 'paypal';
		$gateway->settings = array( $client_currency => array( 'currency' => 'EUR', 'value' => 'test' ) );
		$currency_codes = array( 'USD', 'EUR' );

		$expected_user_notice_text = '<p>Please note that the payment will be made in '.$gateway->settings[$client_currency]['currency'].'. '.$cart_total.' will be debited from your account.</p>';

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency', 'get_currency_codes' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method('get_client_currency')->willReturn( $client_currency );
		$this->woocommerce_wpml->multi_currency->method('get_currency_codes')->willReturn( $currency_codes );

		$this->woocommerce_wpml->cart = $this->getMockBuilder( 'WCML_Cart' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_formatted_cart_total_in_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->cart->method( 'get_formatted_cart_total_in_currency' )->willReturn( $cart_total );


		\WP_Mock::userFunction( 'get_option', array(
			'args' => array( WCML_Payment_Gateway::OPTION_KEY.$gateway->id, array() ),
			'return' => $gateway->settings
		));

		\WP_Mock::userFunction( 'get_option', array(
			'args' => array( WCML_Currencies_Payment_Gateways::OPTION_KEY, array() ),
			'return' => $gateway->settings
		));

		$template_service = $this->getMockBuilder( 'IWPML_Template_Service' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$available_payment_gateways[ $gateway->id ] = $gateway;

		$twig = \Mockery::mock( 'overload:WPML_Twig_Template_Loader' );
		$twig->shouldReceive( 'get_template' )
		     ->andReturn( $template_service );

		$wc = \Mockery::mock( 'overload:WC' );
		$payment_gateways = \Mockery::mock( 'overload:WC_Payment_Gateways' );

		$payment_gateways->shouldReceive( 'get_available_payment_gateways' )
		                 ->andReturn( $available_payment_gateways );

		$wc->shouldReceive( 'payment_gateways' )
		   ->andReturn( $payment_gateways );

		WP_Mock::userFunction( 'WC', array(
			'return' => $wc
		) );

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => rand_str( 3 )
		));

		$subject = $this->get_subject();
		$subject->init_gateways();
		$filtered_description = $subject->filter_gateway_description( $description, $gateway->id );

		$this->assertSame( $description.$expected_user_notice_text, $filtered_description);
	}

	/**
	 * @test
	 */
	public function id_should_not_filter_gateway_description_for_default_currency() {

		$client_currency = 'USD';
		$description = rand_str();

		\WP_Mock::userFunction( 'wcml_get_woocommerce_currency_option', array(
			'return' => $client_currency
		));

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency', 'get_currency_codes' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method('get_client_currency')->willReturn( $client_currency );

		$subject = $this->get_subject();
		$filtered_description = $subject->filter_gateway_description( $description, 'gateway_id' );

		$this->assertSame( $description, $description);
	}

}
