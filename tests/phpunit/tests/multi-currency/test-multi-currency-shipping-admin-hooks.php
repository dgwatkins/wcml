<?php

use \WCML\Multicurrency\Shipping\AdminHooks;

class Test_WCML_Multi_Currency_Shipping_Admin_Hooks extends OTGS_TestCase {

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

	private function get_subject( $wcmlMultiCurrency = null ) {
		$wcmlMultiCurrency = $wcmlMultiCurrency ?: $this->get_wcml_multi_currency_mock();

		return new AdminHooks( $wcmlMultiCurrency );
	}

	/**
	 * @test
	 */
	public function hooks_added() {
		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_shipping_instance_form_fields_flat_rate', [ $subject, 'addCurrencyShippingFieldsToFlatRate' ], 10, 1 );
		\WP_Mock::expectFilterAdded( 'woocommerce_shipping_instance_form_fields_free_shipping', [ $subject, 'addCurrencyShippingFieldsToFreeShipping' ], 10, 1 );
		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $subject, 'loadJs' ] );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function NO_fields_added_to_flat_rate_when_argument_is_not_an_array() {

		$subject = $this->get_subject();

		$this->expectException( TypeError::class );

		$subject->addCurrencyShippingFieldsToFlatRate( '' );
	}

	/**
	 * @test
	 */
	public function NO_fields_added_to_free_shipping_when_argument_is_not_an_array() {

		$subject = $this->get_subject();

		$this->expectException( TypeError::class );

		$subject->addCurrencyShippingFieldsToFreeShipping( '' );
	}

	/**
	 * @test
	 */
	public function fields_added_to_flat_rate() {
		$wcml_multi_currency = $this->get_wcml_multi_currency_mock();
		$wcml_multi_currency->expects( $this->atLeastOnce() )
		                    ->method( 'get_currency_codes' )
		                    ->willReturn( [ 'PLN', 'EUR' ] );

		$wcml_multi_currency->expects( $this->atLeastOnce() )
		                    ->method( 'get_default_currency' )
		                    ->willReturn( 'USD' );

		$subject = $this->get_subject( $wcml_multi_currency );

		$fields = [];

		$new_fields = $subject->addCurrencyShippingFieldsToFlatRate( $fields );

		$this->assertTrue( isset( $new_fields['wcml_shipping_costs'] ) );
		$this->assertTrue( isset( $new_fields['cost_PLN'] ) );
	}

	/**
	 * @test
	 */
	public function fields_added_to_free_shipping() {
		$wcml_multi_currency = $this->get_wcml_multi_currency_mock();
		$wcml_multi_currency->expects( $this->atLeastOnce() )
		                    ->method( 'get_currency_codes' )
		                    ->willReturn( [ 'PLN', 'EUR' ] );

		$wcml_multi_currency->expects( $this->atLeastOnce() )
		                    ->method( 'get_default_currency' )
		                    ->willReturn( 'USD' );

		$subject = $this->get_subject( $wcml_multi_currency );

		$fields = [];

		$new_fields = $subject->addCurrencyShippingFieldsToFreeShipping( $fields );

		$this->assertTrue( isset( $new_fields['wcml_shipping_costs'] ) );
		$this->assertTrue( isset( $new_fields['min_amount_PLN'] ) );
	}

	/**
	 * @test
	 */
	public function itShouldLoadJs() {
		\WP_Mock::userFunction( 'wp_enqueue_script', [
			'args' => [
				'wcml-admin-shipping-currency-selector',
				constant( 'WCML_PLUGIN_URL' ) . '/dist/js/multicurrencyShippingAdmin/app.js',
				[],
				constant( 'WCML_VERSION' ),
				true,
			],
			'times' => 1,
		] );

		$this->get_subject()->loadJs();
	}
}