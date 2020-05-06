<?php

use \WCML\Multicurrency\Shipping\FreeShipping;

class Test_WCML_Multi_Currency_Shipping_FreeShipping extends OTGS_TestCase {
	private function get_subject() {
		return new FreeShipping();
	}

	/**
	 * @test
	 */
	public function it_returns_correct_method_id() {
		$expected = 'free_shipping';
		$subject = $this->get_subject();
		$result = $subject->getMethodId();
		$this->assertSame( $result, $expected );
	}

	/**
	 * @test
	 */
	public function it_does_NOT_support_shipping_classes() {
		$subject = $this->get_subject();
		$this->assertFalse( $subject->supportsShippingClasses() );
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
	public function it_updates_minimal_order_amount( $currency, $expectedTitle, $expectedDescription ) {
		$subject = $this->get_subject();

		$amount = 10;
		$expectedAmount = 20;
		$currencyKey = is_string( $currency ) ? $currency : '';
		$shipping = [
			'min_amount_' . $currencyKey => $expectedAmount,
			'wcml_shipping_costs' => 'manual'
		];

		$newAmount = $subject->getMinimalOrderAmountValue( $amount, $shipping, $currency );

		$this->assertSame( $newAmount, $expectedAmount );
	}

	/**
	 * @dataProvider dataProvider
	 *
	 * @test
	 */
	public function it_returns_shipping_cost_unchanged( $currency, $expectedTitle, $expectedDescription ) {
		$subject = $this->get_subject();

		$expectedCost = $cost = 10;

		$rate = $this->getMockBuilder( 'WC_Shipping_Rate' )->disableOriginalConstructor()->getMock();
		$rate->cost = $cost;

		$newCost = $subject->getShippingCostValue( $rate, $currency );

		$this->assertSame( $newCost, $expectedCost );
	}

	/**
	 * @test
	 */
	public function it_returns_zero_for_getShippingClassCostValue() {
		$subject = $this->get_subject();
		$this->expectException( 'Exception' );
		$this->assertEquals( 0, $subject->getShippingClassCostValue( [], 'PLN', 'class_cost_24' ) );
	}

	/**
	 * @test
	 */
	public function it_returns_zero_for_getNoShippingClassCostValue() {
		$subject = $this->get_subject();
		$this->expectException( 'Exception' );
		$this->assertEquals( 0, $subject->getNoShippingClassCostValue( [], 'PLN', 'class_cost_24' ) );
	}

	/**
	 * @return array
	 */
	public function dataProvider() {
		return [
			['USD', 'Minimal order amount in USD', 'The minimal order amount if customer choose USD as a purchase currency.'],
			['PLN', 'Minimal order amount in PLN', 'The minimal order amount if customer choose PLN as a purchase currency.'],
			['USDii', 'Minimal order amount in USDii', 'The minimal order amount if customer choose USDii as a purchase currency.']
		];
	}
}