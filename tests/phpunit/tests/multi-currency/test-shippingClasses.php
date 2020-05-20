<?php

use \WCML\Multicurrency\Shipping\ShippingClasses;

class Test_ShippingClasses extends OTGS_TestCase {
	private function get_subject() {
		return new ShippingClasses();
	}

	private function get_wcml_multi_currency_mock() {
		return $this->getMockBuilder( 'WCML_Multi_Currency' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'get_currencies', 'get_currency_codes', 'get_default_currency', 'get_client_currency' ] )
		            ->getMock();
	}

	/**
	 * @test
	 */
	public function it_adds_shipping_classes_fields() {
		$subject = $this->get_subject();

		$WCML_Multi_Currency = $this->get_wcml_multi_currency_mock();
		$WCML_Multi_Currency->method( 'get_currency_codes' )->willReturn( [ 'EUR', 'PLN' ] );
		$WCML_Multi_Currency->method( 'get_default_currency' )->willReturn( 'EUR' );

		$wcShippingClass = new stdClass();
		$wcShippingClass->term_id  = 'foo';
		$wcShippingClass->name     = 'bar';
		$wcShippingClass->taxonomy = 'product_shipping_class';
		$wcShippingClasses = [ $wcShippingClass ];

		$WC = $this->getMockBuilder( 'WC' )->disableOriginalConstructor()->setMethods( [ 'shipping', 'get_shipping_classes' ] )->getMock();
		$WC->method( 'shipping' )->willReturnSelf();
		$WC->method( 'get_shipping_classes' )->willReturn( $wcShippingClasses );
		WP_Mock::userFunction( 'WC', [
			'return' => $WC
		] );

		$languageDetails = new stdClass();
		$languageDetails->source_language_code = null;
		WP_Mock::onFilter( 'wpml_element_language_details' )->with( null )->reply( $languageDetails );


		$result = $subject->addFields( [], $WCML_Multi_Currency );

		$this->assertTrue( isset( $result['class_cost_foo_PLN'] ) );
		$this->assertTrue( isset( $result['no_class_cost_PLN'] ) );
	}
}
