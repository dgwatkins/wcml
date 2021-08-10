<?php

namespace WCML\Tax\Prices;

use OTGS_TestCase;
use WCML_Multi_Currency_Shipping;
use woocommerce_wpml;
use WP_Mock;

/**
 * @group tax
 * @group tax-prices
 */
class TestHooks extends OTGS_TestCase {
	/**
	 * @test
	 * @dataProvider getSettings
	 * @param bool $isMultiCurrencyEnabled
	 */
	public function itShouldAddHooks( $isMultiCurrencyEnabled ) {
		WP_Mock::userFunction(
			'wcml_is_multi_currency_on',
			[
				'return' => $isMultiCurrencyEnabled,
			]
		);

		$subject = $this->getSubject();

		if ( $isMultiCurrencyEnabled ) {
			WP_Mock::expectFilterAdded( 'woocommerce_get_price_excluding_tax', [ $subject, 'applyRoundingRules' ] );
			WP_Mock::expectFilterAdded( 'woocommerce_get_price_including_tax', [ $subject, 'applyRoundingRules' ] );
			WP_Mock::expectFilterAdded( 'woocommerce_shipping_packages', [ $subject, 'applyShippingRoundingRules' ], WCML_Multi_Currency_Shipping::PRIORITY_SHIPPING + 1 );
		} else {
			WP_Mock::expectFilterNotAdded( 'woocommerce_get_price_excluding_tax', [ $subject, 'applyRoundingRules' ] );
			WP_Mock::expectFilterNotAdded( 'woocommerce_get_price_including_tax', [ $subject, 'applyRoundingRules' ] );
			WP_Mock::expectFilterNotAdded( 'woocommerce_shipping_packages', [ $subject, 'applyShippingRoundingRules' ], WCML_Multi_Currency_Shipping::PRIORITY_SHIPPING + 1 );
		}

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itApplyRoundingRules() {
		$expectedPrice = 2;
		$price         = 1.7;

		$subject = $this->getSubject( $this->getMockWcml( $expectedPrice, $price ) );

		$this->assertEquals( $expectedPrice, $subject->applyRoundingRules( $price ) );
	}

	/**
	 * @test
	 */
	public function itApplyShippingRoundingRules() {
		$expectedPrice    = 2;
		$price            = 1.7;
		$expectedPackages = [
			[
				'rates' => [
					'table_rate:7:4' => (object) [
						'cost'  => $expectedPrice,
						'taxes' => [ 1 => $expectedPrice ],
					],
				],
			],
		];
		$packages         = [
			[
				'rates' => [
					'table_rate:7:4' => (object) [
						'cost'  => $price,
						'taxes' => [ 1 => $price ],
					],
				],
			],
		];

		$subject = $this->getSubject( $this->getMockWcml( $expectedPrice, $price ) );

		$this->assertEquals( $expectedPackages, $subject->applyShippingRoundingRules( $packages ) );
	}

	private function getSubject( woocommerce_wpml $wcml = null ) {
		if ( null === $wcml ) {
			$wcml = $this->getMockBuilder( woocommerce_wpml::class )
				->disableOriginalConstructor()->getMock();
		}
		return new Hooks( $wcml );
	}

	/**
	 * @param float $expectedPrice
	 * @param float $price
	 * @return woocommerce_wpml
	 */
	private function getMockWcml( $expectedPrice, $price ) {
		$prices = $this->getMockBuilder( WCML_Multi_Currency_Prices::class )
			->disableOriginalConstructor()
			->setMethods( [ 'apply_rounding_rules' ] )
			->getMock();
		$prices->expects( $this->any() )
			->method( 'apply_rounding_rules' )
			->with( $price )->willReturn( $expectedPrice );

		$wcml = $this->getMockBuilder( woocommerce_wpml::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_multi_currency' ] )
			->getMock();
		$wcml->expects( $this->any() )
			->method( 'get_multi_currency' )
			->willReturn( (object) [ 'prices' => $prices ] );

		return $wcml;
	}

	/** @return array [  $isMultiCurrencyEnabled ] */
	public function getSettings() {
		return [
			[ true ],
			[ false ],
		];
	}
}
