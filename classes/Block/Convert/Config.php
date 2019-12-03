<?php

namespace WCML\Block\Convert;

use WCML\Block\Convert\Type\ProductsByAttributes;
use WPML\PB\Gutenberg\ConvertIdsInBlock\Composite;
use WPML\PB\Gutenberg\ConvertIdsInBlock\NullConvert;
use WPML\PB\Gutenberg\ConvertIdsInBlock\BlockAttributes;
use WPML\PB\Gutenberg\ConvertIdsInBlock\TagAttributes;

class Config {

	/**
	 * @param string $blockName
	 *
	 * @return \WPML\PB\Gutenberg\ConvertIdsInBlock\Base
	 */
	public static function get( $blockName ) {
		switch ( $blockName ) {
			case 'woocommerce/product-category':
				$converter = new BlockAttributes( [ [ 'name' => 'categories', 'type' => 'product_cat' ] ] );
				break;

			case 'woocommerce/featured-category':
				$converter = new BlockAttributes( [ [ 'name' => 'categoryId', 'type' => 'product_cat' ] ] );
				break;

			case 'woocommerce/featured-product':
				$converter = new BlockAttributes( [ [ 'name' => 'productId', 'type' => 'product' ] ] );
				break;

			case 'woocommerce/handpicked-products':
				$converter = new BlockAttributes( [ [ 'name' => 'products', 'type' => 'product' ] ] );
				break;

			case 'woocommerce/product-tag':
				$converter = new BlockAttributes( [ [ 'name' => 'tags', 'type' => 'product_tag' ] ] );
				break;

			case 'woocommerce/reviews-by-product':
				$converter = new Composite( [
					                            new BlockAttributes( [ [ 'name' => 'productId', 'type' => 'product' ] ] ),
					                            new TagAttributes( [
                         [
                             'xpath' => '//*[contains(@class, "wp-block-woocommerce-reviews-by-product")]/@data-product-id',
                             'type' => 'product'
                         ]
                    ] )
                ] );
				break;

			case 'woocommerce/reviews-by-category':
				$converter = new Composite( [
					                            new BlockAttributes( [ [ 'name' => 'categoryIds', 'type' => 'product_cat' ] ] ),
					                            new TagAttributes( [
					 	[
					 		'xpath' => '//*[contains(@class, "wp-block-woocommerce-reviews-by-category")]/@data-category-ids',
						    'type' => 'product_cat'
					    ]
					 ] )
				] );
				break;

			case 'woocommerce/products-by-attribute':
				$converter = new ProductsByAttributes();
				break;

			default:
				$converter = new NullConvert();
		}

		return $converter;
	}
}
