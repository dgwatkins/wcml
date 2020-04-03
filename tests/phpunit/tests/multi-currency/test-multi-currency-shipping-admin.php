<?php

class Test_WCML_Multi_Currency_Shipping_Admin extends OTGS_TestCase {

	private function get_wcml_multi_currency_mock() {
		return $this->getMockBuilder( 'WCML_Multi_Currency' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'get_currencies', 'get_currency_codes', 'get_default_currency' ] )
		            ->getMock();
	}

	private function get_woocommerce_wpml_mock() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_subject( $multicurrency_enabled = true, $wcml_multi_currency = null ) {
		if ( ! $wcml_multi_currency ) {
			$wcml_multi_currency = $this->get_wcml_multi_currency_mock();
		}

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		if ( $multicurrency_enabled ) {
			$woocommerce_wpml->settings['enable_multi_currency'] = 2;
		} else {
			$woocommerce_wpml->settings['enable_multi_currency'] = 0;
		}

		return new WCML_Multi_Currency_Shipping_Admin( $wcml_multi_currency, $woocommerce_wpml );
	}

	public function setUp() {
		parent::setUp();

		if ( ! defined('WCML_MULTI_CURRENCIES_INDEPENDENT' ) ) {
			define( 'WCML_MULTI_CURRENCIES_INDEPENDENT', 2 );
		}
	}

	/**
	 * @test
	 */
	public function hooks_added() {
		$wcml_multi_currency = $this->get_wcml_multi_currency_mock();
		$wcml_multi_currency->expects( $this->once() )
		                    ->method( 'get_currency_codes' )
		                    ->willReturn( [ 'PLN', 'EUR' ] );

		$wcml_multi_currency->expects( $this->once() )
		                    ->method( 'get_default_currency' )
		                    ->willReturn( 'USD' );

		$subject = $this->get_subject( true, $wcml_multi_currency );
		\WP_Mock::expectFilterAdded( 'woocommerce_shipping_instance_form_fields_flat_rate', [ $subject, 'add_currency_shipping_fields_to_shipping_method_form' ], 10, 1 );
		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $subject, 'load_js' ] );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function hooks_NOT_added_when_NO_multicurrency() {
		$wcml_multi_currency = $this->get_wcml_multi_currency_mock();
		$wcml_multi_currency->method( 'get_currency_codes' )
		                    ->willReturn( [ 'PLN', 'EUR' ] );

		$wcml_multi_currency->method( 'get_default_currency' )
		                    ->willReturn( 'USD' );

		$subject = $this->get_subject( false, $wcml_multi_currency );
		\WP_Mock::expectFilterNotAdded( 'woocommerce_shipping_instance_form_fields_flat_rate', [ $subject, 'add_currency_shipping_fields_to_shipping_method_form' ], 10, 1 );
		\WP_Mock::expectActionNotAdded( 'admin_enqueue_scripts', [ $subject, 'load_js' ] );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function hooks_NOT_added_when_NO_additional_currencies_defined() {
		$wcml_multi_currency = $this->get_wcml_multi_currency_mock();
		$wcml_multi_currency->method( 'get_currency_codes' )
		                    ->willReturn( [  ] );

		$wcml_multi_currency->method( 'get_default_currency' )
		                    ->willReturn( 'USD' );

		$subject = $this->get_subject( true, $wcml_multi_currency );
		\WP_Mock::expectFilterNotAdded( 'woocommerce_shipping_instance_form_fields_flat_rate', [ $subject, 'add_currency_shipping_fields_to_shipping_method_form' ], 10, 1 );
		\WP_Mock::expectActionNotAdded( 'admin_enqueue_scripts', [ $subject, 'load_js' ] );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function NO_fields_added_when_argument_is_not_an_array() {

		$subject = $this->get_subject( true );

		$this->expectException( TypeError::class );

		$subject->add_currency_shipping_fields_to_shipping_method_form( '' );
	}

	/**
	 * @test
	 */
	public function fields_NOT_added_when_NO_multicurrency() {
		$subject = $this->get_subject( false );

		$fields = [];

		$new_fields = $subject->add_currency_shipping_fields_to_shipping_method_form( $fields );

		$this->assertSame( $fields, $new_fields );
	}

	/**
	 * @test
	 */
	public function fields_NOT_added_when_NO_additional_currencies_defined() {
		$wcml_multi_currency = $this->get_wcml_multi_currency_mock();
		$wcml_multi_currency->method( 'get_currency_codes' )
		                    ->willReturn( [  ] );

		$wcml_multi_currency->method( 'get_default_currency' )
		                    ->willReturn( 'USD' );

		$subject = $this->get_subject( true, $wcml_multi_currency );
		$fields = [];

		$new_fields = $subject->add_currency_shipping_fields_to_shipping_method_form( $fields );

		$this->assertSame( $fields, $new_fields );
	}

	/**
	 * @test
	 */
	public function fields_added_when_multicurrency_enabled() {
		$wcml_multi_currency = $this->get_wcml_multi_currency_mock();
		$wcml_multi_currency->expects( $this->atLeastOnce() )
		                    ->method( 'get_currency_codes' )
		                    ->willReturn( [ 'PLN', 'EUR' ] );

		$wcml_multi_currency->expects( $this->atLeastOnce() )
		                    ->method( 'get_default_currency' )
		                    ->willReturn( 'USD' );

		$subject = $this->get_subject( true, $wcml_multi_currency );

		$fields = [];

		$new_fields = $subject->add_currency_shipping_fields_to_shipping_method_form( $fields );

		$this->assertTrue( isset( $new_fields['wcml_shipping_costs'] ) );
		$this->assertTrue( isset( $new_fields['cost_PLN'] ) );
	}


}