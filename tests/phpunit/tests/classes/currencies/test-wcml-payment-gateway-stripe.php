<?php

class Test_WCML_Payment_Gateway_Stripe extends OTGS_TestCase {

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

		return new WCML_Payment_Gateway_Stripe( $gateway, $template_service );
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

}
