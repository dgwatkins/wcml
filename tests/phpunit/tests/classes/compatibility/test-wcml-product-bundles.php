<?php

class Test_WCML_Product_Bundles extends OTGS_TestCase {

	/** @var  woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var  SitePress */
	private $sitepress;
	/** @var  WCML_WC_Product_Bundles_Items */
	private $product_bundles_items;
	/** @var  wpdb */
	private $wpdb;

	function setUp(){
		parent::setUp();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )->disableOriginalConstructor()->getMock();
		$this->sitepress = $this->getMockBuilder( 'Sitepress' )->setMethods( array( 'get_language_for_element', 'get_current_language' ) )->disableOriginalConstructor()->getMock();
		$this->product_bundles_items = $this->getMockBuilder( 'WCML_WC_Product_Bundles_Items' )->setMethods( array(
			'get_items',
			'get_item_data',
			'get_item_data_object',
			'update_item_meta',
			'save_item_meta'
		) )->disableOriginalConstructor()->getMock();
		$this->wpdb = $this->stubs->wpdb();

		\WP_Mock::wpFunction( 'is_admin', array( 'return' => false ) );
		\WP_Mock::wpFunction( 'wcml_is_multi_currency_on', array( 'return' => false ) );

	}

	private function get_subject() {
		return new WCML_Product_Bundles( $this->sitepress, $this->woocommerce_wpml, $this->product_bundles_items, $this->wpdb );
	}

	/**
	 * @test
	 * @group wcml-1934
	 */
	public function test_translate_allowed_variations() {

		$variations_with_translation = [
			0 => [ 'original' => rand(200, 300), 'translation' => rand( 300, 400) ],
			1 => [ 'original' => rand(400, 500), 'translation' => rand( 600, 700) ],
		];

		$lang = 'fr';

		$subject = $this->get_subject();

		$allowed_variations = [
			$variations_with_translation[0]['original'],
			$variations_with_translation[1]['original'],
		];

		$allowed_variations_expected = [
			$variations_with_translation[0]['translation'],
			$variations_with_translation[1]['translation'],
		];

		for( $i = 0; $i < 2; $i++ ){
			\WP_Mock::onFilter( 'translate_object_id' )
			        ->with( $variations_with_translation[$i]['original'], 'product_variation', true, $lang )
			        ->reply( $variations_with_translation[$i]['translation'] );
		}

		$allowed_variations_translated = $subject->translate_allowed_variations( $allowed_variations, $lang );
		$this->assertEquals( $allowed_variations_expected, $allowed_variations_translated );

	}

	/**
	* @test
	*/
	public function update_bundles_custom_prices_values() {

		$prices = array( );
		$code = rand_str();
		$base_regular_price = rand( 1, 100 );
		$base_sale_price = rand( 1, 100 );

		$_POST[ '_custom_regular_price' ][ $code ] = $base_regular_price;
		$_POST[ '_custom_sale_price' ][ $code ] = $base_sale_price;

		$expected_prices_codes = array(
			'_wc_pb_base_regular_price' => $base_regular_price,
			'_wc_pb_base_sale_price' => $base_sale_price
		);

		\WP_Mock::wpFunction( 'wc_format_decimal', array(
				'args' => array( $base_regular_price ),
				'return' => $base_regular_price
			)
		);

		\WP_Mock::wpFunction( 'wc_format_decimal', array(
				'args' => array( $base_sale_price ),
				'return' => $base_sale_price
			)
		);

		$subject = $this->get_subject();
		$filtered_prices = $subject->update_bundles_custom_prices_values( $prices, $code );

		$this->assertEquals( $expected_prices_codes, $filtered_prices );

	}

	/**
	* @test
	*/
	public function update_bundles_base_price() {


		$post_id = rand( 1, 100 );
		$product_price = rand( 1, 100 );
		$custom_prices = array( '_wc_pb_base_regular_price' => rand( 1, 100 ) );
		$code = rand_str();

		\WP_Mock::wpFunction( 'update_post_meta', array(
				'args' => array( $post_id, '_wc_pb_base_price_' . $code, $product_price ),
				'times' => 1
			)
		);

		$subject = $this->get_subject();
		$filtered_prices = $subject->update_bundles_base_price( $post_id, $product_price, $custom_prices, $code );

	}

	/**
	 * @test
	 */
	public function is_bundle_product() {

		$bundle_product_id = mt_rand( 1, 100 );

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_type' ) )
		                ->getMock();

		$product->method( 'get_type' )->willReturn( 'bundle' );

		\WP_Mock::wpFunction( 'wc_get_product', array(
			'args' => array( $bundle_product_id ),
			'return' => $product
		) );

		$subject = $this->get_subject();
		$this->assertTrue( $subject->is_bundle_product( $bundle_product_id ) );

	}

	/**
	 * @test
	 * @group wcml-2204
	 */
	public function sync_product_bundle_meta() {

		$this->bundle_id = 1;
		$this->translated_bundle_id = 2;
		$language = 'es';

		$bundle_item = 10;
		$this->bundles_items = [ $bundle_item ];

		$this->translated_bundle_items = [];

		$this->product_bundles_items->method( 'get_items' )->will( $this->returnCallback(
			function ( $id ) {

				if( $this->bundle_id == $id ){
					return $this->bundles_items;
				}elseif( $this->translated_bundle_id == $id ){
					return $this->translated_bundle_items;
				}

				return [];
			}
		));

		$this->sitepress->method( 'get_language_for_element' )->willReturn( $language );

		$variation_id = 30;
		$translated_variation_id = 31;

		$this->fields_to_sync = [
			'optional',
			'stock_status',
			'max_stock',
			'quantity_min',
			'quantity_max',
			'shipped_individually',
			'priced_individually',
			'single_product_visibility',
			'cart_visibility',
			'order_visibility',
			'single_product_price_visibility',
			'cart_price_visibility',
			'order_price_visibility',
			'discount',
			'override_variations',
			'override_default_variation_attributes',
			'hide_filtered_variations'
		];

		$attribute_taxonomy = 'pa_color';
		$attribute_term_slug = 'red';
		$custom_attribute = 'size';
		$custom_attribute_value = 'small';

		$this->item_meta = [
			'product_id' => 20,
			'allowed_variations' => [ $variation_id ],
			'default_variation_attributes' => [ $attribute_taxonomy => $attribute_term_slug , $custom_attribute => $custom_attribute_value ]
		];

		foreach( $this->fields_to_sync as $field_key ){
			$this->item_meta[ $field_key ] = rand_str();
		}

		$translated_product_id = 21;

		$this->product_bundles_items->method( 'get_item_data' )->with( $bundle_item )->willReturn( $this->item_meta );

		\WP_Mock::userFunction( 'get_post_type', [
				'args' => [ $this->item_meta[ 'product_id' ] ],
				'return' => 'product'
			]
		);

		\WP_Mock::onFilter( 'translate_object_id' )
		        ->with( $this->item_meta['product_id'], 'product', false, $language )
		        ->reply( $translated_product_id );

		\WP_Mock::onFilter( 'translate_object_id' )
		        ->with( $variation_id, 'product_variation', true, $language )
		        ->reply( $translated_variation_id );

		$this->translated_item = 40;

		$this->product_bundles_items->method( 'get_item_data_object' )->willReturn( $this->translated_item );

		$this->item_meta['allowed_variations'] = [ $translated_variation_id ];

		$translated_custom_attribute_value = 'small es';
		$attribute_term_id = 50;
		$translated_attribute_term_id = 60;
		$translated_term = new stdClass();
		$translated_term->slug = rand_str();

		$this->woocommerce_wpml->terms = $this->getMockBuilder( 'WCML_Terms' )
		                                      ->setMethods( array( 'wcml_get_term_id_by_slug', 'wcml_get_term_by_id' ) )
		                                      ->disableOriginalConstructor()
		                                      ->getMock();

		$this->woocommerce_wpml->terms->method( 'wcml_get_term_id_by_slug' )->with( $attribute_taxonomy, $attribute_term_slug )->willReturn( $attribute_term_id );

		\WP_Mock::onFilter( 'translate_object_id' )
		        ->with( $attribute_term_id, $attribute_taxonomy, true, $language )
		        ->reply( $translated_attribute_term_id );

		$this->woocommerce_wpml->terms->method( 'wcml_get_term_by_id' )->with( $translated_attribute_term_id, $attribute_taxonomy )->willReturn( $translated_term );

		$this->item_meta[ 'default_variation_attributes'] = [ $attribute_taxonomy => $translated_term->slug, $custom_attribute => $translated_custom_attribute_value ];

		$this->woocommerce_wpml->attributes = $this->getMockBuilder( 'WCML_Terms' )
		                                           ->setMethods( [ 'get_custom_attr_translation' ] )
		                                           ->disableOriginalConstructor()
		                                           ->getMock();

		$this->woocommerce_wpml->attributes->method( 'get_custom_attr_translation' )->with( $this->item_meta['product_id'], $translated_product_id, $custom_attribute, $custom_attribute_value )->willReturn( $translated_custom_attribute_value );

		$this->product_bundles_items->expects( $this->exactly( count( $this->fields_to_sync ) + 2 ) )->method( 'update_item_meta' )->will( $this->returnCallback(
			function ( $item, $key, $value ) {

				if( $this->translated_item === $item && $this->item_meta[$key] === $value ){
					return true;
				}

				return new WP_Error();
			}
		));

		$this->product_bundles_items->method( 'save_item_meta' )->with( $this->translated_item )->willReturn( true );

		$subject = $this->get_subject();
		$subject->sync_product_bundle_meta( $this->bundle_id, $this->translated_bundle_id );
	}

	/**
	 * @test
	 */
	public function it_should_filter_woocommerce_json_search_products_in_current_language() {

		$product_id = mt_rand( 1, 100 );
		$found_products = [ $product_id => rand_str() ];

		$this->sitepress->method( 'get_language_for_element' )->willReturn( 'es' );
		$this->sitepress->method( 'get_current_language' )->willReturn( 'en' );

		\WP_Mock::userFunction( 'get_post_type', [
				'args'   => [ $product_id ],
				'return' => 'product'
			]
		);

		$subject = $this->get_subject();
		$filtered_products = $subject->woocommerce_json_search_filter_found_products( $found_products );

		$this->assertEmpty( $filtered_products );

	}
}
