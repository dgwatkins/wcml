<?php
/**
 * @author OnTheGo Systems
 */

namespace WCML\Block\Convert;

class Config {

	/**
	 * @param string $blockName
	 *
	 * @return array
	 */
	public static function get( $blockName ) {
		return wpml_collect( [
			'woocommerce/product-category' => [
				[ 'name' => 'categories', 'type' => 'product_cat' ],
			],
			'woocommerce/featured-category' => [
				[ 'name' => 'categoryId', 'type' => 'product_cat' ],
			],
			'woocommerce/featured-product' => [
				[ 'name' => 'productId', 'type' => 'product' ],
			],
			'woocommerce/handpicked-products' => [
				[ 'name' => 'products', 'type' => 'product' ],
			],
		] )->get( $blockName, [] );
	}
}
