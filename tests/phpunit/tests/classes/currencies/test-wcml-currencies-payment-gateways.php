<?php

class Test_WCML_Currencies_Payment_Gateways extends OTGS_TestCase {

	private function get_subject() {
		$wp_api           = $this->getMockBuilder( 'WPML_WP_API' )->disableOriginalConstructor()->setMethods( array( 'constant' ) )->getMock();
		$wcml_plugin_path = '../..';
		$wp_api->method( 'constant' )->with( 'WCML_PLUGIN_PATH' )->willReturn( $wcml_plugin_path );

		return new WCML_Currencies_Payment_Gateways( $wp_api );
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

		$subject = $this->get_subject();

		$gateway     = $this->getMockBuilder( 'WC_Payment_Gateway' )
		                    ->disableOriginalConstructor()
		                    ->getMock();
		$gateway->id = 'test';

		$template_service = $this->getMockBuilder( 'IWPML_Template_Service' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$available_payment_gateways[ $gateway->id ] = $gateway;

		$not_supported_payment_gateway = new WCML_Not_Supported_Payment_Gateway( $gateway, $template_service );

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

		$expected_payment_gateways[ $gateway->id ] = $not_supported_payment_gateway;

		$payment_gateways = $subject->get_gateways();

		$this->assertEquals( $expected_payment_gateways, $payment_gateways );
	}

}
