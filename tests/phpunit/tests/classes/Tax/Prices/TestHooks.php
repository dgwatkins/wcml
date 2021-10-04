<?php

namespace WCML\Tax\Prices;

use OTGS_TestCase;
use WCML_Multi_Currency;
use WCML_Multi_Currency_Prices;
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
	 * @dataProvider getApplyRoundingRulesScenarios
	 * @param float  $price
	 * @param float  $expectedPrice
	 * @param string $currency
	 * @param array  $currencyOptions
	 */
	public function itApplyRoundingRules( $price, $expectedPrice, $currency, $currencyOptions ) {
		$subject = $this->getSubject( $this->getMockWcml( $currency, $currencyOptions ) );

		$this->assertEquals( $expectedPrice, $subject->applyRoundingRules( $price ) );
	}

	/**
	 * @test
	 * @dataProvider getApplyRoundingRulesScenarios
	 * @param float  $price
	 * @param float  $expectedPrice
	 * @param string $currency
	 * @param array  $currencyOptions
	 */
	public function itApplyShippingRoundingRules( $price, $expectedPrice, $currency, $currencyOptions ) {
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

		$subject = $this->getSubject( $this->getMockWcml( $currency, $currencyOptions ) );

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
	private function getMockWcml( $currency, $currencyOptions ) {
		$multiCurrency = $this->getMockBuilder( WCML_Multi_Currency::class )
			->disableOriginalConstructor()
			->setMethods( [ 'apply_rounding_rules', 'get_client_currency' ] )
			->getMock();
		$multiCurrency->expects( $this->any() )
			->method( 'get_client_currency' )
			->willReturn( $currency );

		/** @var WCML_Multi_Currency $multiCurrency */
		$prices = new WCML_Multi_Currency_Prices( $multiCurrency, $currencyOptions );

		$wcml = $this->getMockBuilder( woocommerce_wpml::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_multi_currency', 'get_setting' ] )
			->getMock();
		$wcml->expects( $this->any() )
			->method( 'get_multi_currency' )
			->willReturn( $multiCurrency );
		$wcml->expects( $this->any() )
			->method( 'get_setting' )
			->with( 'currency_options' )
			->willReturn( $currencyOptions );

		$multiCurrency->prices = $prices;

		return $wcml;
	}

	/** @return array [  $isMultiCurrencyEnabled ] */
	public function getSettings() {
		return [
			[ true ],
			[ false ],
		];
	}

	/** @return array [ $price, $expectedPrice, $currency, $currencyOptions ] */
	public function getApplyRoundingRulesScenarios() {
		return [
			[
				0.99,
				0.99,
				'USD',
				[
					'USD' => [
						'rounding' => 'disabled',
					],
				],
			],
			[
				0.99,
				1,
				'USD',
				[
					'USD' => [
						'rounding'           => 'up',
						'rounding_increment' => 0,
						'auto_subtract'      => 0,
					],
				],
			],
		];
	}
}
