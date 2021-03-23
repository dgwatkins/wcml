<?php

namespace WCML\Rest;

use WCML\Rest\Wrapper\Factory;

/**
 * @group rest
 * @group rest-hooks
 */
class TestHooks extends \OTGS_TestCase {

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function add_hooks(){

		\Mockery::mock( 'overload:WCML\Rest\Generic' )->shouldReceive( 'preventDefaultLangUrlRedirect' )->andReturn( true );

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => true
		]);

		$attributes = [ 'attr_color' ];

		\WP_Mock::userFunction( 'wc_get_attribute_taxonomy_names', [
			'return' => $attributes
		]);

		$composite_mock = $this->getMockBuilder( 'Composite' )->disableOriginalConstructor()->getMock();

		$factory = \Mockery::mock( 'overload:WCML\Rest\Wrapper\Factory' );

		$factory->shouldReceive( 'create' )
		                   ->with( 'product' )
		                   ->andReturn( $composite_mock );

		$factory->shouldReceive( 'create' )
		                   ->with( 'product_variation' )
		                   ->andReturn( $composite_mock );

		$factory->shouldReceive( 'create' )
		                   ->with( 'shop_order' )
		                   ->andReturn( $composite_mock );

		$terms_mock = $this->getMockBuilder( 'ProductTerms' )->disableOriginalConstructor()->getMock();

		$factory->shouldReceive( 'create' )
		                   ->with( 'term' )
		                   ->andReturn( $terms_mock );

		$reports_top_seller = $this->getMockBuilder( 'TopSeller' )->disableOriginalConstructor()->getMock();

		$factory->shouldReceive( 'create' )
		        ->with( 'reports_top_seller' )
		        ->andReturn( $reports_top_seller );

		$reports_products_sales = $this->getMockBuilder( 'ProductsSales' )->disableOriginalConstructor()->getMock();

		$factory->shouldReceive( 'create' )
		        ->with( 'reports_products_sales' )
		        ->andReturn( $reports_products_sales );

		$reports_products_count = $this->getMockBuilder( 'ProductsCount' )->disableOriginalConstructor()->getMock();

		$factory->shouldReceive( 'create' )
		        ->with( 'reports_products_count' )
		        ->andReturn( $reports_products_count );

		\WP_Mock::expectActionAdded( 'rest_api_init', [ Generic::class, 'setLanguageForRequest' ] );
		\WP_Mock::expectActionAdded( 'rest_api_init', [ Generic::class, 'disableGetTermAdjustIds' ] );
		\WP_Mock::expectActionAdded( 'parse_query', [ Generic::class, 'autoAdjustIncludedIds' ] );

		\WP_Mock::expectFilterAdded( 'woocommerce_rest_product_query', [ Factory::create( 'product' ), 'query' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'woocommerce_rest_product_object_query', [ Factory::create( 'product' ), 'query' ], 10, 2 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_product_object', [ Factory::create( 'product' ), 'prepare' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_insert_product_object', [ Factory::create( 'product' ), 'insert' ], 10, 3 );

		\WP_Mock::expectFilterAdded( 'woocommerce_rest_product_variation_query', [ Factory::create( 'product' ), 'query' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'woocommerce_rest_product_variation_object_query', [ Factory::create( 'product' ), 'query' ], 10, 2 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_product_variation_object', [ Factory::create( 'product' ), 'prepare' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_insert_product_variation_object', [ Factory::create( 'product' ), 'insert' ], 10, 3 );

		\WP_Mock::expectFilterAdded( 'woocommerce_rest_shop_order_object_query', [ Factory::create( 'shop_order' ), 'query' ], 10, 2 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_shop_order_object', [ Factory::create( 'shop_order' ), 'prepare' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_insert_shop_order_object', [ Factory::create( 'shop_order' ), 'insert' ], 10, 3 );

		\WP_Mock::expectFilterAdded( 'woocommerce_rest_product_cat_query', [ Factory::create( 'term' ), 'query' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'woocommerce_rest_product_tag_query', [ Factory::create( 'term' ), 'query' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'woocommerce_rest_product_shipping_class_query', [ Factory::create( 'term' ), 'query' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'woocommerce_rest_'.$attributes[0].'_query', [ Factory::create( 'term' ), 'query' ], 10, 2 );

		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_product_cat', [ Factory::create( 'term' ), 'prepare' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_product_tag', [ Factory::create( 'term' ), 'prepare' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_product_shipping_class', [ Factory::create( 'term' ), 'prepare' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_'.$attributes[0], [ Factory::create( 'term' ), 'prepare' ], 10, 3 );

		\WP_Mock::expectActionAdded( 'woocommerce_rest_insert_product_cat', [ Factory::create( 'term' ), 'insert' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_insert_product_tag', [ Factory::create( 'term' ), 'insert' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_insert_product_shipping_class', [ Factory::create( 'term' ), 'insert' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_insert_'.$attributes[0], [ Factory::create( 'term' ), 'insert' ], 10, 3 );

		\WP_Mock::expectFilterAdded( 'woocommerce_rest_prepare_report_top_sellers', [ Factory::create( 'reports_top_seller' ), 'prepare' ], 10, 3 );
		\WP_Mock::expectFilterAdded( 'woocommerce_rest_prepare_report_sales', [ Factory::create( 'reports_products_sales' ), 'prepare' ], 10, 3 );
		\WP_Mock::expectFilterAdded( 'woocommerce_rest_prepare_report_products_count', [ Factory::create( 'reports_products_count' ), 'prepare' ], 10, 3 );

		\Mockery::mock( 'overload:WCML\Rest\Functions' )->shouldReceive( 'getApiRequestVersion' )->andReturn( 1 );

		$this->add_hooks_specific_for_v1();

		Hooks::addHooks();
	}

	private function add_hooks_specific_for_v1(){
		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_product', [ Factory::create( 'product' ), 'prepare' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_insert_product', [ Factory::create( 'product' ), 'insert' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_update_product', [ Factory::create( 'product' ), 'insert' ], 10, 3 );

		\WP_Mock::expectActionAdded( 'woocommerce_rest_prepare_shop_order', [ Factory::create( 'shop_order' ), 'prepare' ], 10, 3 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_insert_shop_order', [ Factory::create( 'shop_order' ), 'insert' ], 10, 3 );
	}

}
