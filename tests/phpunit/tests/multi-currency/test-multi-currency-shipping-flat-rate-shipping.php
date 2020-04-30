<?php

use \WCML\Multicurrency\Shipping\FlatRateShipping;

class Test_WCML_Multi_Currency_Shipping_FlatRateShipping extends OTGS_TestCase {
	private function get_subject() {
		return new FlatRateShipping();
	}

	/**
	 * @test
	 */
	public function it_returns_correct_method_id() {
		$expected = 'flat_rate';
		$subject = $this->get_subject();
		$result = $subject->getMethodId();
		$this->assertSame( $result, $expected );
	}

	/**
	 * @dataProvider dataProvider
	 *
	 * @test
	 */
	public function it_returns_correct_field_title( $currency_code, $expectedTitle, $expectedDescription ) {
		$subject = $this->get_subject();
		$result = $subject->getFieldTitle( $currency_code );
		$this->assertSame( $result, $expectedTitle );
	}

	/**
	 * @dataProvider dataProvider
	 *
	 * @test
	 */
	public function it_returns_correct_field_description( $currency_code, $expectedTitle, $expectedDescription ) {
		$subject = $this->get_subject();
		$result = $subject->getFieldDescription( $currency_code );
		$this->assertSame( $result, $expectedDescription );
	}

	/**
	 * @dataProvider dataProvider
	 *
	 * @test
	 */
	public function it_returns_minimal_order_amount_unchanged( $currency, $expectedTitle, $expectedDescription ) {
		$subject = $this->get_subject();

		$expectedAmount = $amount = 10;
		$currencyKey = is_string( $currency ) ? $currency : '';
		$shipping = [
			'min_amount_' . $currencyKey => $expectedAmount
		];

		$newAmount = $subject->getMinimalOrderAmountValue( $amount, $shipping, $currency );

		$this->assertSame( $newAmount, $expectedAmount );
	}

	/**
	 * @dataProvider dataProvider
	 *
	 * @test
	 */
	public function it_returns_shipping_cost_updated( $currency, $expectedTitle, $expectedDescription ) {
		$subject = $this->get_subject();

		$cost = 10;
		$expectedCost = 20;

		$rate = $this->getMockBuilder( 'WC_Shipping_Rate' )->disableOriginalConstructor()->getMock();
		$rate->cost = $cost;
		$rate->method_id = 'flat_rate';
		$rate->instance_id = 7;

		$currencyKey = is_string( $currency ) ? $currency : '';

		\WP_Mock::userFunction( 'get_option', [
			'return' => [
				'cost_' . $currencyKey => $expectedCost
			]
		] );

		$newCost = $subject->getShippingCostValue( $rate, $currency );

		$this->assertSame( $newCost, $expectedCost );
	}

	/**
	 * @return array
	 */

	public function dataProvider() {
		return [
			['USD', 'Cost in USD', 'The shipping cost if customer choose USD as a purchase currency.'],
			['PLN', 'Cost in PLN', 'The shipping cost if customer choose PLN as a purchase currency.'],
			['USDii', 'Cost in USDii', 'The shipping cost if customer choose USDii as a purchase currency.'],
			[null, 'Cost in ', 'The shipping cost if customer choose  as a purchase currency.'],
			[false, 'Cost in ', 'The shipping cost if customer choose  as a purchase currency.'],
			[new stdClass(), 'Cost in ', 'The shipping cost if customer choose  as a purchase currency.'],
			[[], 'Cost in ', 'The shipping cost if customer choose  as a purchase currency.'],
		];
	}
}