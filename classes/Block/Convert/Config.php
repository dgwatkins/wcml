<?php

namespace WCML\Block\Convert;

use WCML\Block\Convert\Type\NonConvertible;
use WCML\Block\Convert\Type\ProductsByAttributes;
use WCML\Block\Convert\Type\WithIdsAttributes;

class Config {

	/**
	 * @param string $blockName
	 *
	 * @return Type\Base
	 */
	public static function get( $blockName ) {
		/** @var \SitePress $sitepress */
		global $sitepress;

		return wpml_collect( [
			'woocommerce/product-category' => new WithIdsAttributes( $sitepress, [
				[ 'name' => 'categories', 'type' => 'product_cat' ],
			] ),
			'woocommerce/featured-category' => new WithIdsAttributes( $sitepress, [
				[ 'name' => 'categoryId', 'type' => 'product_cat' ],
			] ),
			'woocommerce/featured-product' => new WithIdsAttributes( $sitepress, [
				[ 'name' => 'productId', 'type' => 'product' ],
			] ),
			'woocommerce/handpicked-products' => new WithIdsAttributes( $sitepress, [
				[ 'name' => 'products', 'type' => 'product' ],
			] ),
			'woocommerce/product-tag' => new WithIdsAttributes( $sitepress, [
				[ 'name' => 'tags', 'type' => 'product_tag' ],
			] ),
			'woocommerce/reviews-by-product' => new WithIdsAttributes( $sitepress, [
				[ 'name' => 'productId', 'type' => 'product' ],
			] ),
			'woocommerce/reviews-by-category' => new WithIdsAttributes( $sitepress, [
				[ 'name' => 'categoryIds', 'type' => 'product_cat' ],
			] ),
			'woocommerce/products-by-attribute' => new ProductsByAttributes( $sitepress ),
		] )->get( $blockName, new NonConvertible() );
	}
}
