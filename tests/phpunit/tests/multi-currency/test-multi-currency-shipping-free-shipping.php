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
			['USD', 'Minimal order amount in USD', 'The minimal order amount if customer choose USD as a purchase currency.'],
			['PLN', 'Minimal order amount in PLN', 'The minimal order amount if customer choose PLN as a purchase currency.'],
			['USDii', 'Minimal order amount in USDii', 'The minimal order amount if customer choose USDii as a purchase currency.'],
			[null, 'Minimal order amount in ', 'The minimal order amount if customer choose  as a purchase currency.'],
			[false, 'Minimal order amount in ', 'The minimal order amount if customer choose  as a purchase currency.'],
			[new stdClass(), 'Minimal order amount in ', 'The minimal order amount if customer choose  as a purchase currency.'],
			[[], 'Minimal order amount in ', 'The minimal order amount if customer choose  as a purchase currency.'],
		];
	}
}