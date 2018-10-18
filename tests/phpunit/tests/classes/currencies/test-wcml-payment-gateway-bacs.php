<?php

class Test_WCML_Payment_Gateway_Bacs extends OTGS_TestCase {

	private function get_subject( $gateway = null ) {
		if( null === $gateway ){
			$gateway = $this->getMockBuilder('WC_Payment_Gateway')
			                ->disableOriginalConstructor()
			                ->getMock();

			$gateway->id = 'id';
			$gateway->title = 'title';

			WP_Mock::userFunction( 'get_option', array(
				'args' => array( WCML_Payment_Gateway::OPTION_KEY.$gateway->id, array() ),
				'times' => 1,
				'return' => array()
			));
		}

		return new WCML_Payment_Gateway_Bacs( $gateway );
	}

	/**
	 * @test
	 */
	public function should_return_id() {
		$subject = $this->get_subject();

		$this->assertSame( 'id', $subject->get_id() );
	}

	/**
	 * @test
	 */
	public function should_return_title() {
		$subject = $this->get_subject();

		$this->assertSame( 'title', $subject->get_title() );
	}

	/**
	 * @test
	 */
	public function should_return_settings() {

		$gateway = $this->getMockBuilder('WC_Payment_Gateway')
		                ->disableOriginalConstructor()
		                ->getMock();
		$gateway->id = 'id';
		$settings = array( 'USD' => array( 'currency' => 'USD', 'value' => 'all' ) );

		WP_Mock::userFunction( 'get_option', array(
			'args' => array( WCML_Payment_Gateway::OPTION_KEY.$gateway->id, array() ),
			'times' => 1,
			'return' => $settings
		));

		$subject = $this->get_subject( $gateway );

		$this->assertSame( $settings, $subject->get_settings() );
	}

	/**
	 * @test
	 */
	public function should_return_setting() {

		$gateway = $this->getMockBuilder('WC_Payment_Gateway')
		                ->disableOriginalConstructor()
		                ->getMock();
		$gateway->id = 'id';
		$settings = array( 'USD' => array( 'currency' => 'USD', 'value' => 'all' ) );

		WP_Mock::userFunction( 'get_option', array(
			'args' => array( WCML_Payment_Gateway::OPTION_KEY.$gateway->id, array() ),
			'times' => 1,
			'return' => $settings
		));

		$subject = $this->get_subject( $gateway );

		$this->assertSame( $settings['USD'], $subject->get_setting( 'USD' ) );
	}

	/**
	 * @test
	 */
	public function should_save_setting() {

		$gateway = $this->getMockBuilder('WC_Payment_Gateway')
		                ->disableOriginalConstructor()
		                ->getMock();
		$gateway->id = 'id';


		WP_Mock::userFunction( 'get_option', array(
			'args' => array( WCML_Payment_Gateway::OPTION_KEY.$gateway->id, array() ),
			'times' => 1,
			'return' => array()
		));

		$settings = array( 'currency' => 'USD', 'value' => 'all' );
		$expected_settings = array( 'USD' => $settings );

		WP_Mock::userFunction( 'update_option', array(
			'args' => array( WCML_Payment_Gateway::OPTION_KEY.$gateway->id, $expected_settings ),
			'times' => 1
		));

		$subject = $this->get_subject( $gateway );

		$subject->save_setting( 'USD', $settings );
	}
}
