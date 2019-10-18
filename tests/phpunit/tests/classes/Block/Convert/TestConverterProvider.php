<?php

namespace WCML\Block\Convert;

/**
 * @group block
 * @group block-convert
 */
class TestConverterProvider extends \OTGS_TestCase {

	/**
	 * @test
	 * @dataProvider dpShouldRetrieveBlockConfig
	 */
	public function itShouldRetrieveBlockConfig( $blockName ) {
		$result = ConverterProvider::get( $blockName );
		$this->assertInstanceOf( \WPML\PB\Gutenberg\ConvertIdsInBlock\Base::class, $result );
	}

	public function dpShouldRetrieveBlockConfig() {
		return [
			[ 'woocommerce/product-category' ],
			[ 'woocommerce/featured-category' ],
			[ 'woocommerce/featured-product' ],
			[ 'woocommerce/handpicked-products' ],
			[ 'woocommerce/product-tag' ],
			[ 'woocommerce/reviews-by-product' ],
			[ 'woocommerce/reviews-by-category' ],
			[ 'woocommerce/products-by-attribute' ],
		];
	}

	/**
	 * @test
	 */
	public function itShouldAnNullConvertIfBlockNameDoesNotHaveOne() {
		$result = ConverterProvider::get( 'foo/bar' );
		$this->assertInstanceOf( \WPML\PB\Gutenberg\ConvertIdsInBlock\Base::class, $result );
	}
}
