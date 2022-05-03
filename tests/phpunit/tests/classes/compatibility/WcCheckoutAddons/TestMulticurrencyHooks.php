<?php

namespace WCML\Compatibility\WcCheckoutAddons;

use WP_Mock;

/**
 * @group compatibility
 * @group wc-checkout-addons
 */
class TestMulticurrencyHooks extends \OTGS_TestCase {

	private function getSubject() {
		return new MulticurrencyHooks();
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldAddHooks() {
		$subject = $this->getSubject();
		WP_Mock::expectFilterAdded( 'option_wc_checkout_add_ons', [ $subject, 'optionWcCheckoutAddOnsFilter' ] );
		$subject->add_hooks();
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldConvertPrices() {
		$originalPrice  = 10.90;
		$convertedPrice = 25.90;

		$getOption = function( $price ) {
			return [
				'addonid' => [
					'label'           => 'foo',
					'description'     => 'bar',
					'adjustment_type' => 'fixed',
					'adjustment'      => $price,
					'options'         => [
						[
							'adjustment_type' => 'fixed',
							'adjustment'      => $price,
						],
						[
							'foo' => 'bar', // non-matching data
						],
					],
				],
			];
		};

		WP_Mock::onFilter( 'wcml_raw_price_amount' )->with( $originalPrice )->reply( $convertedPrice );

		$this->assertEquals(
			$getOption( $convertedPrice ),
			$this->getSubject()->optionWcCheckoutAddOnsFilter( $getOption( $originalPrice ) )
		);
	}
}
