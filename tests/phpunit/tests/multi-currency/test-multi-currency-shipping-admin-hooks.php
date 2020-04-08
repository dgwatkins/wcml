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

	private function get_subject( $wcml_multi_currency = null ) {
		if ( ! $wcml_multi_currency ) {
			$wcml_multi_currency = $this->get_wcml_multi_currency_mock();
		}

		return new AdminHooks( $wcml_multi_currency );
	}

	/**
	 * @test
	 */
	public function hooks_added() {
		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_shipping_instance_form_fields_flat_rate', [ $subject, 'addCurrencyShippingFieldsToShippingMethodForm' ], 10, 1 );
		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $subject, 'loadJs' ] );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function NO_fields_added_when_argument_is_not_an_array() {

		$subject = $this->get_subject();

		$this->expectException( TypeError::class );

		$subject->addCurrencyShippingFieldsToShippingMethodForm( '' );
	}

	/**
	 * @test
	 */
	public function fields_added() {
		$wcml_multi_currency = $this->get_wcml_multi_currency_mock();
		$wcml_multi_currency->expects( $this->atLeastOnce() )
		                    ->method( 'get_currency_codes' )
		                    ->willReturn( [ 'PLN', 'EUR' ] );

		$wcml_multi_currency->expects( $this->atLeastOnce() )
		                    ->method( 'get_default_currency' )
		                    ->willReturn( 'USD' );

		$subject = $this->get_subject( $wcml_multi_currency );

		$fields = [];

		$new_fields = $subject->addCurrencyShippingFieldsToShippingMethodForm( $fields );

		$this->assertTrue( isset( $new_fields['wcml_shipping_costs'] ) );
		$this->assertTrue( isset( $new_fields['cost_PLN'] ) );
	}
}