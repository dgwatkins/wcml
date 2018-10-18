<?php

class Test_WCML_Payment_Gateway_PayPal extends OTGS_TestCase {

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

		return new WCML_Payment_Gateway_PayPal( $gateway, $template_service );
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
		$expected_currencies_details = array(
			'USD' => array( 'value' => 'test_email', 'is_valid' => true ),
			'UAH' => array( 'value' => '', 'is_valid' => false )
		);

		$this->assertSame( $expected_currencies_details, $subject->get_currencies_details( $active_currencies ) );
	}

}
