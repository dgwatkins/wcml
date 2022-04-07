<?php

namespace WCML\Compatibility\WcProductBundles;

use WP_Mock;

/**
 * @group compatibility
 * @group wc-product-bundles
 */
class TestMulticurrencyHooks extends \OTGS_TestCase {

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldAddHooks() {
		$subject = $this->getSubject();

		WP_Mock::expectFilterAdded( 'wcml_price_custom_fields_filtered', [ $subject, 'getPriceCustomFields' ], 10, 2 );
		WP_Mock::expectFilterAdded( 'wcml_update_custom_prices_values', [ $subject, 'updateBundlesCustomPricesValues' ], 10, 2 );
		WP_Mock::expectActionAdded( 'wcml_after_save_custom_prices', [ $subject, 'updateBundlesBasePrice' ], 10, 4 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldGetPriceCustomFields() {
		$original = [
			'foo',
			'bar',
		];

		$expected = [
			'foo',
			'bar',
			'_wc_pb_base_regular_price',
			'_wc_pb_base_sale_price',
			'_wc_pb_base_price',
			'_wc_sw_max_price',
			'_wc_sw_max_regular_price',
		];

		$this->assertEquals(
			$expected,
			$this->getSubject()->getPriceCustomFields( $original )
		);
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldUpdateBundlesCustomPricesValues() {
		$prices             = [];
		$code               = 'GBP';
		$base_regular_price = 15;
		$base_sale_price    = 12;

		$_POST['_custom_regular_price'][ $code ] = $base_regular_price;
		$_POST['_custom_sale_price'][ $code ]    = $base_sale_price;

		$expected_prices_codes = [
			'_wc_pb_base_regular_price' => $base_regular_price,
			'_wc_pb_base_sale_price' => $base_sale_price
		];

		WP_Mock::userFunction( 'wc_format_decimal', [
				'args' => [ $base_regular_price ],
				'return' => $base_regular_price
			]
		);

		WP_Mock::userFunction( 'wc_format_decimal', [
				'args' => [ $base_sale_price ],
				'return' => $base_sale_price
			]
		);

		$this->assertEquals(
			$expected_prices_codes,
			$this->getSubject()->updateBundlesCustomPricesValues( $prices, $code )
		);

		$_POST = [];
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldUpdateBundlesBasePrice() {
		$post_id       = 123;
		$product_price = 456;
		$custom_prices = [ '_wc_pb_base_regular_price' => 789 ];
		$code          = 'GBP';

		WP_Mock::userFunction( 'update_post_meta', [
				'args'  => [ $post_id, '_wc_pb_base_price_' . $code, $product_price ],
				'times' => 1
			]
		);

		$this->getSubject()->updateBundlesBasePrice( $post_id, $product_price, $custom_prices, $code );
	}

	private function getSubject() {
		return new MulticurrencyHooks();
	}
}
