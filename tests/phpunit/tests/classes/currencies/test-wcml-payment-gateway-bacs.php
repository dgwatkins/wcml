<?php

class Test_WCML_Payment_Gateway_Bacs extends OTGS_TestCase {

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

			WP_Mock::userFunction( 'get_option', array(
				'args' => array( WCML_Payment_Gateway::OPTION_KEY.$gateway->id, array() ),
				'times' => 1,
				'return' => array()
			));
		}

		$template_service = $this->getMockBuilder( 'IWPML_Template_Service' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		return new WCML_Payment_Gateway_Bacs( $gateway, $template_service, $this->woocommerce_wpml );
	}

	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'woocommerce_bacs_accounts', array( $subject, 'filter_bacs_accounts' ) );

		$subject->add_hooks();
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

	/**
	 * @test
	 */
	public function it_should_filter_bacs_accounts() {

		$accounts = array( 'Test' => array( 'settings' ), 'Test2' => array( 'settings' ) );
		$client_currency = 'USD';

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                               ->disableOriginalConstructor()
		                                               ->setMethods( array( 'get_client_currency' ) )
		                                               ->getMock();

		$this->woocommerce_wpml->multi_currency->method('get_client_currency')->willReturn( $client_currency );


		$gateway = $this->getMockBuilder('WC_Payment_Gateway')
		                ->disableOriginalConstructor()
		                ->getMock();
		$gateway->id = 'id';
		$gateway->title = 'title';
		$gateway->settings = array( $client_currency => array( 'currency' => 'EUR', 'value' => 'Test' ) );

		WP_Mock::userFunction( 'get_option', array(
			'args' => array( WCML_Payment_Gateway::OPTION_KEY.$gateway->id, array() ),
			'times' => 1,
			'return' => $gateway->settings
		));

		$subject = $this->get_subject( $gateway );

		$filtered_accounts = $subject->filter_bacs_accounts( $accounts );
		$expected_filtered_accounts = array( $accounts[ 'Test' ] );

		$this->assertSame( $expected_filtered_accounts, $filtered_accounts );
	}

}
