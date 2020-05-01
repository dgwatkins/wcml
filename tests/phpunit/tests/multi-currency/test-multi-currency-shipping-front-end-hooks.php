<?php

use \WCML\Multicurrency\Shipping\FrontEndHooks;
use WCML\Multicurrency\Shipping\ShippingMode;
use WCML\Multicurrency\Shipping\ShippingModeProvider;

class Test_WCML_Multi_Currency_Shipping_Front_End_Hooks extends OTGS_TestCase {

	private function get_wcml_multi_currency_mock() {
		return $this->getMockBuilder( 'WCML_Multi_Currency' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'get_currencies', 'get_currency_codes', 'get_default_currency', 'get_client_currency' ] )
		            ->getMock();
	}

	private function get_subject( $wcmlMultiCurrency = null ) {
		$wcmlMultiCurrency = $wcmlMultiCurrency ?: $this->get_wcml_multi_currency_mock();

		return new FrontEndHooks( $wcmlMultiCurrency );
	}

	/**
	 * @test
	 * @group pierre
	 */
	public function hooks_added() {
		\WP_Mock::userFunction( 'WPML\Container\make', [
			'return' => function( $className ) {
				return new $className();
			},
		]);

		$subject = $this->get_subject();

		$this->assertGreaterThan( 0, ShippingModeProvider::getAll()->count(), 'the provider should have at least one item' );

		ShippingModeProvider::getAll()->each( function( ShippingMode $shippingMode ) use ( $subject ) {
			\WP_Mock::expectFilterAdded(
				'woocommerce_shipping_' . $shippingMode->getMethodId() . '_instance_option',
				$subject->getShippingCost( $shippingMode ),
				10,
				3
			);
		});

		$subject->add_hooks();
	}

	/**
	 * @test
	 *
	 * @dataProvider ShippingCostData
	 */
	public function getShippingCost_runs_for_cost_field( $field, $cost, $expected ) {
		$wcml_multi_currency = $this->get_wcml_multi_currency_mock();
		$wcml_multi_currency->method( 'get_client_currency' )
		                    ->willReturn( 'PLN' );

		$subject = $this->get_subject( $wcml_multi_currency );
		$method = $this->getMockBuilder( 'WCML\Multicurrency\Shipping\FlatRateShipping' )
		               ->disableOriginalConstructor()
		               ->setMethods( [ 'isManualPricingEnabled', 'getShippingCostValue', 'getShippingClassCostValue', 'getNoShippingClassCostValue' ] )
		               ->getMock();
		$method->method( 'isManualPricingEnabled' )->willReturn( true );
		$method->method( 'getShippingCostValue' )->willReturn( $expected );
		$method->method( 'getShippingClassCostValue' )->willReturn( $expected );
		$method->method( 'getNoShippingClassCostValue' )->willReturn( $expected );

		$wcmethod = new stdClass();

		$result = $subject->getShippingCost( $method )( $cost, $field, $wcmethod  );

		$this->assertEquals( $result, $expected );
	}

	public function ShippingCostData() {
		return [
			['cost', 100, 200],
			['class_cost_24', 100, 200],
			['no_class_cost', 100, 200]
		];
	}
}