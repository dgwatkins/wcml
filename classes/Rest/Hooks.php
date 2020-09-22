<?php

namespace WCML\Rest;

use WCML\Rest\Wrapper\Factory;

class Hooks {

	public static function addHooks() {

		Generic::preventDefaultLangUrlRedirect();

		add_action( 'rest_api_init', [ Generic::class, 'setLanguageForRequest' ] );
		add_action( 'parse_query', [ Generic::class, 'autoAdjustIncludedIds' ] );

		foreach ( [ 'product', 'shop_order', 'product_cat', 'product_tag' ] as $type ) {

			$restObject = Factory::create( $type );

			add_filter( "woocommerce_rest_{$type}_query", [ $restObject, 'query' ], 10, 2 );
			add_filter( "woocommerce_rest_{$type}_object_query", [ $restObject, 'query' ], 10, 2 );
			add_action( "woocommerce_rest_prepare_{$type}_object", [ $restObject, 'prepare' ], 10, 3 );
			add_action( "woocommerce_rest_insert_{$type}_object", [ $restObject, 'insert' ], 10, 3 );
		}


		self::addHooksSpecificForV1();
	}

	private static function addHooksSpecificForV1() {

		if ( 1 === Functions::getApiRequestVersion() ) {
			add_action( 'woocommerce_rest_prepare_product', [ Factory::create( 'product' ), 'prepare' ], 10, 3 );
			add_action( 'woocommerce_rest_insert_product', [ Factory::create( 'product' ), 'insert' ], 10, 3 );
			add_action( 'woocommerce_rest_update_product', [ Factory::create( 'product' ), 'insert' ], 10, 3 );

			add_action( 'woocommerce_rest_insert_shop_order', [ Factory::create( 'shop_order' ), 'insert' ], 10, 3 );
			add_action( 'woocommerce_rest_prepare_shop_order', [ Factory::create( 'shop_order' ), 'prepare' ], 10, 3 );
		}
	}

}