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