<?php

class Test_WCML_Synchronize_Product_Data extends OTGS_TestCase {

	/** @var wpdb */
	private $wpdb;

	public function setUp()
	{
		parent::setUp();

		\WP_Mock::wpPassthruFunction( 'wp_cache_delete' );
		\WP_Mock::wpPassthruFunction( 'remove_filter' );

		$this->wpdb = $this->stubs->wpdb();
	}


	/**
	 * @return woocommerce_wpml
	 */
	private function get_woocommerce_wpml() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @return SitePress
	 */
	private function get_sitepress( $wp_api = null ) {
		$sitepress = $this->getMockBuilder('SitePress')
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_wp_api' ) )
		                  ->getMock();

		if( null === $wp_api ){
			$wp_api = $this->get_wpml_wp_api_mock();
		}

		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		return $sitepress;
	}

	/**
	 * @return WPML_WP_API
	 */
	private function get_wpml_wp_api_mock() {
		return $this->getMockBuilder( 'WPML_WP_API' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'constant', 'version_compare' ) )
		            ->getMock();
	}

	/**
	 * @return WCML_Synchronize_Product_Data
	 */
	private function get_subject(  $woocommerce_wpml = null, $sitepress = null  ){

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress();
		}


		return new WCML_Synchronize_Product_Data( $woocommerce_wpml, $sitepress, $this->wpdb );
	}

	/**
	 * @test
	 */
	public function it_adds_admin_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => true,
			'times'  => 1
		) );

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'woocommerce_product_set_visibility', array( $subject, 'sync_product_translations_visibility' ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_adds_admin_and_front_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => true,
			'times'  => 1
		) );

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'woocommerce_product_set_stock_status', array( $subject, 'sync_stock_status_for_translations' ), 100, 2 );
		\WP_Mock::expectActionAdded( 'woocommerce_variation_set_stock_status', array( $subject, 'sync_stock_status_for_translations' ), 10, 2 );

		\WP_Mock::expectActionAdded( 'woocommerce_reduce_order_stock', array( $subject, 'sync_product_stocks_reduce' ) );
		\WP_Mock::expectActionAdded( 'woocommerce_restore_order_stock', array( $subject, 'sync_product_stocks_restore' ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function get_translated_custom_field_values(){

		$subject = $this->get_subject( );

		$custom_field = rand_str();
		$custom_field_index = 0;
		$translated_title = rand_str();
		$translated_id = rand_str();
		$translated_content = rand_str();

		$custom_field_value = array(
			'title'   => rand_str(),
			'id'	  => rand_str(),
			'content' => rand_str()
		);

		$custom_fields_values = array();
		$custom_fields_values[ $custom_field_index ] = $custom_field_value;

		$translation_data = array();
		$translation_data[ md5( 'field-'.$custom_field.'-0-title' ) ] = $translated_title;
		$translation_data[ md5( 'field-'.$custom_field.'-0-id' ) ] = $translated_id;
		$translation_data[ md5( 'field-'.$custom_field.'-0-content' ) ] = $translated_content;

		$translated_values = $subject->get_translated_custom_field_values( $custom_fields_values, $translation_data, $custom_field, $custom_field_value, $custom_field_index );

		$this->assertEquals( $translated_title, $translated_values[ $custom_field_index ][ 'title' ] );
		$this->assertEquals( $translated_id, $translated_values[ $custom_field_index ][ 'id' ] );
		$this->assertEquals( $translated_content, $translated_values[ $custom_field_index ][ 'content' ] );

	}

	/**
	 * @test
	 */
	public function sync_grouped_products(){

		$product_id = mt_rand( 1, 10 );
		$translated_product_id = mt_rand( 10, 20 );
		$language = rand_str();

		$original_child = mt_rand( 20, 30 );
		$translated_child = mt_rand( 30, 40 );

		\WP_Mock::wpPassthruFunction('maybe_unserialize');

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_children', true ),
			'return' => array( $original_child )
		));

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $original_child ),
			'return' => 'product'
		));

		\WP_Mock::onFilter( 'translate_object_id' )->with( $original_child, 'product', false, $language )->reply( $translated_child );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_children', array( $translated_child ) ),
			'return' => true,
			'times'  => 1
		));

		$subject = $this->get_subject();
		$subject->sync_grouped_products( $product_id, $translated_product_id, $language );
	}

	/**
	 * @test
	 */
	public function sync_product_translations_visibility(){

		$product_id = mt_rand( 1, 100 );
		$trid = mt_rand( 100, 200 );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		     ->disableOriginalConstructor()
		     ->getMock();
		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'is_original_product' ) )
		                                         ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->with( $product_id )->willReturn( true );


		$sitepress = $this->getMockBuilder( 'SitePress' )
		     ->disableOriginalConstructor()
		     ->setMethods( array( 'get_element_translations', 'get_element_trid' ) )
		     ->getMock();

		$sitepress->expects( $this->once() )->method( 'get_element_trid' )->with( $product_id, 'post_product' )->willReturn( $trid );

		$en_translation = new stdClass();
		$en_translation->original = true;
		$en_translation->element_id = $product_id;
		$translations['en'] = $en_translation;

		$fr_translation = new stdClass();
		$fr_translation->original = false;
		$fr_translation->element_id = mt_rand( 200, 300 );
		$translations['fr'] = $fr_translation;

		$sitepress->expects( $this->once() )->method( 'get_element_translations' )->with( $trid, 'post_product' )->willReturn( $translations );

		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );

		$product_object = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'is_featured' ) )
		                ->getMock();

		$product_object->method( 'is_featured' )->willReturn( true );

		\WP_Mock::wpFunction( 'wc_get_product', array(
			'args' => array( $product_id ),
			'return' => $product_object
		) );

		\WP_Mock::wpFunction( 'wp_set_post_terms', array(
			'args' => array( $fr_translation->element_id, array( 'featured' ), 'product_visibility', false ),
			'return' => true,
			'times' => 1
		) );

		$subject->sync_product_translations_visibility( $product_id );
	}

	/**
	 * @test
	 * @group wcml-2135
	 */
	public function sync_custom_field_value_should_sync_serialzied_cf(){
		$subject = $this->get_subject();

		$custom_field = rand_str(32);
		$translated_product_id = random_int(1, 1000);
		$post_fields = [
			'field-' . $custom_field . '-0' => [
				'data'   => rand_str( 32 ),
				'tid'    => 0,
				'format' => 'base64',
			],
			'field-' . $custom_field . '-1' => [
				'data'   => rand_str( 32 ),
				'tid'    => 0,
				'format' => 'base64',
			],
			'field-' . $custom_field . '-2' => [
				'data'   => rand_str( 32 ),
				'tid'    => 0,
				'format' => 'base64',
			]
		];
		$original_product_id = random_int(1001, 2000);
		$translation_data = [
			md5( 'field-' . $custom_field . '-0' ) => rand_str( 32 ),
			md5( 'field-' . $custom_field . '-1' ) => rand_str( 32 ),
			md5( 'field-' . $custom_field . '-2' ) => rand_str( 32 ),
		];

		$original_custom_fields = [
			'key1' => rand_str(32),
			'key2' => rand_str(32),
			'key3' => rand_str(32)
		];

		\WP_Mock::userFunction( 'get_post_meta', [
			'expected' => 1,
			'args' => [ $original_product_id, $custom_field, true ],
			'return' => $original_custom_fields
		] );

		$expected_translated_custom_field = [
			'key1' => $translation_data[ md5( 'field-' . $custom_field . '-0' ) ],
			'key2' => $translation_data[ md5( 'field-' . $custom_field . '-1' ) ],
			'key3' => $translation_data[ md5( 'field-' . $custom_field . '-2' ) ],
		];

		\WP_Mock::userFunction( 'update_post_meta', [
			'expected' => 1,
			'args' => [ $translated_product_id, $custom_field, $expected_translated_custom_field ],
		] );

		$subject->sync_custom_field_value(
			$custom_field, $translation_data, $translated_product_id, $post_fields, $original_product_id );

	}

	/**
	 * @test
	 */
	public function duplicate_product_post_meta_updating_values() {

		$subject = $this->get_subject();

		$custom_field          = rand_str();
		$original_product_id   = mt_rand( 1, 100 );
		$translated_product_id = mt_rand( 101, 200 );

		$first_mid  = mt_rand( 201, 300 );
		$second_mid = mt_rand( 301, 400 );

		$first_mid_value  = rand_str();
		$second_mid_value = rand_str();

		$translation_data = array(
			md5( $custom_field . ':' . $first_mid )  => $first_mid_value,
			md5( $custom_field . ':' . $second_mid ) => $second_mid_value,
		);

		$post_fields = array(
			$custom_field . ':' . $first_mid  => rand_str(),
			$custom_field . ':' . $second_mid => rand_str()
		);

		\WP_Mock::wpFunction( 'update_meta', array(
			'args'  => array( $first_mid, $custom_field, $first_mid_value ),
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'update_meta', array(
			'args'  => array( $second_mid, $custom_field, $second_mid_value ),
			'times' => 1
		) );

		$subject->sync_custom_field_value( $custom_field, $translation_data, $translated_product_id, $post_fields );

	}

	/**
	 * @test
	 */
	public function sync_variable_custom_field_value() {

		$subject = $this->get_subject();

		$custom_field            = rand_str();
		$original_product_id   = mt_rand( 1, 100 );
		$translated_product_id = mt_rand( 101, 200 );

		$translated_value_for_first_field  = rand_str( 10 );
		$translated_value_for_second_field = rand_str( 11 );

		$translation_data = array(
			md5( $custom_field . ':new0' ) => $translated_value_for_first_field,
			md5( $custom_field . ':new1' ) => $translated_value_for_second_field,
		);

		$post_fields = array(
			$custom_field . ':new0' => rand_str(),
			$custom_field . ':new1' => rand_str()
		);

		\WP_Mock::wpFunction( 'add_post_meta', array(
			'args'  => array( $translated_product_id, $custom_field, $translated_value_for_first_field ),
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'add_post_meta', array(
			'args'  => array( $translated_product_id, $custom_field, $translated_value_for_second_field ),
			'times' => 1
		) );

		$subject->sync_custom_field_value( $custom_field, $translation_data, $translated_product_id, $post_fields );

	}

	/**
	 * @test
	 */
	public function sync_variable_custom_field_multiple_values() {

		$subject = $this->get_subject();

		$custom_field            = rand_str();
		$original_variation_id   = mt_rand( 1, 100 );
		$translated_variation_id = mt_rand( 101, 200 );

		$translated_value_for_first_field  = rand_str( 15 );
		$translated_value_for_second_field = rand_str( 17 );

		$translation_data = array(
			md5( $custom_field . $original_variation_id . ':new0' ) => $translated_value_for_first_field,
			md5( $custom_field . $original_variation_id . ':new1' ) => $translated_value_for_second_field,
		);

		$post_fields = array(
			$custom_field . $original_variation_id . ':new0' => rand_str(),
			$custom_field . $original_variation_id . ':new1' => rand_str()
		);

		\WP_Mock::wpFunction( 'add_post_meta', array(
			'args'  => array( $translated_variation_id, $custom_field, $translated_value_for_first_field ),
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'add_post_meta', array(
			'args'  => array( $translated_variation_id, $custom_field, $translated_value_for_second_field ),
			'times' => 1
		) );

		$subject->sync_custom_field_value( $custom_field, $translation_data, $translated_variation_id, $post_fields, $original_variation_id, true );

	}

	/**
	 * @test
	 */
	public function sync_total_sales_simple_product() {

		//handle a case when managing stock is off for product but total sales should be sync
		$product_id = mt_rand( 1, 100 );
		$quantity = mt_rand( 101, 200 );
		$orig_language_details = new stdClass();
		$orig_language_details->language_code = rand_str();

		$translations = array();
		$translation = new stdClass();
		$translation->language_code = rand_str();
		$translation->element_id = mt_rand( 201, 300 );
		$translations[] = $translation;

		$total_sales = mt_rand( 301, 400 );

		$sitepress = $this->getMockBuilder('SitePress')
		     ->disableOriginalConstructor()
		     ->setMethods( array( 'get_element_trid', 'get_element_translations', 'get_element_language_details', 'get_wp_api'  ) )
		     ->getMock();

		$check_version = '3.0.0';
		$wc_version = '2.7';

		$wp_api = $this->get_wpml_wp_api_mock();

		$wp_api->expects( $this->once() )
		       ->method( 'constant' )
		       ->with( 'WC_VERSION' )
		       ->willReturn( $wc_version );
		$wp_api->expects( $this->once() )
		       ->method( 'version_compare' )
		       ->with( $wc_version, $check_version, '>=' )
		       ->willReturn( false );

		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );
		$sitepress->method( 'get_element_trid' )->willReturn( rand_str() );
		$sitepress->method( 'get_element_translations' )->willReturn( $translations );
		$sitepress->method( 'get_element_language_details' )->willReturn( $orig_language_details );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                   ->disableOriginalConstructor()
		                                   ->setMethods( array( 'is_original_product' ) )
		                                   ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->willReturn( true );

		$subject = $this->get_subject( $woocommerce_wpml , $sitepress );

		$order = $this->getMockBuilder( 'WC_Order' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_items' ) )
		              ->getMock();

		$items = array();
		$order_item = $this->getMockBuilder( 'WC_Order_Item_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_variation_id','get_product_id','get_quantity' ) )
		                ->getMock();

		$items[] = $order_item;

		$order->method( 'get_items' )->willReturn( $items );

		$order_item->method( 'get_variation_id' )->willReturn( false );
		$order_item->method( 'get_product_id' )->willReturn( $product_id );
		$order_item->method( 'get_quantity' )->willReturn( $quantity );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'  => array( $translation->element_id ),
			'return' => rand_str()
		) );

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'exists', 'managing_stock' ) )
		                ->getMock();

		$product->method( 'exists' )->willReturn( true );
		$product->method( 'managing_stock' )->willReturn( false );

		\WP_Mock::wpFunction( 'wc_get_product', array(
			'args'  => array( $translation->element_id ),
			'return' => $product
		) );


		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'  => array( $translation->element_id, 'total_sales', true ),
			'return' => $total_sales
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'  => array( $translation->element_id, 'total_sales', $total_sales + $quantity ),
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'wc_recount_after_stock_change', array(
			'args'  => array( $translation->element_id ),
			'times' => 1
		) );

		$subject->sync_product_stocks( $order, 'reduce' );

	}

	/**
	 * @test
	 */
	public function it_should_sync_product_stocks_on_order_edit_page() {

		$post_buff = $_POST;
		$_POST['action'] = 'editpost';
		$_POST['post_type'] = 'shop_order';

		$product_id = 10;
		$quantity = 1;
		$orig_language_details = new stdClass();
		$orig_language_details->language_code = rand_str();

		$translations = array();
		$translation = new stdClass();
		$translation->language_code = rand_str();
		$translation->element_id = 21;
		$translations[] = $translation;

		$total_sales = 2;

		$sitepress = $this->getMockBuilder('SitePress')
		     ->disableOriginalConstructor()
		     ->setMethods( array( 'get_element_trid', 'get_element_translations', 'get_element_language_details', 'get_wp_api'  ) )
		     ->getMock();

		$sitepress->method( 'get_element_trid' )->willReturn( rand_str() );
		$sitepress->method( 'get_element_translations' )->willReturn( $translations );
		$sitepress->method( 'get_element_language_details' )->willReturn( $orig_language_details );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                   ->disableOriginalConstructor()
		                                   ->setMethods( array( 'is_original_product' ) )
		                                   ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->willReturn( true );

		$subject = $this->get_subject( $woocommerce_wpml , $sitepress );

		$order = $this->getMockBuilder( 'WC_Order' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_items' ) )
		              ->getMock();

		$items = array();
		$order_item = $this->getMockBuilder( 'WC_Order_Item_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_variation_id','get_product_id','get_quantity' ) )
		                ->getMock();

		$items[] = $order_item;

		$order->method( 'get_items' )->willReturn( $items );

		$order_item->method( 'get_variation_id' )->willReturn( false );
		$order_item->method( 'get_product_id' )->willReturn( $product_id );
		$order_item->method( 'get_quantity' )->willReturn( $quantity );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'  => array( $translation->element_id ),
			'return' => rand_str()
		) );

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'exists', 'managing_stock' ) )
		                ->getMock();

		$product->method( 'exists' )->willReturn( true );
		$product->method( 'managing_stock' )->willReturn( false );

		\WP_Mock::wpFunction( 'wc_get_product', array(
			'args'  => array( $translation->element_id ),
			'return' => $product
		) );


		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'  => array( $translation->element_id, 'total_sales', true ),
			'return' => $total_sales
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'  => array( $translation->element_id, 'total_sales', $total_sales + $quantity ),
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'wc_recount_after_stock_change', array(
			'args'  => array( $translation->element_id ),
			'times' => 1
		) );

		$subject->sync_product_stocks( $order, 'reduce' );

		$_POST = $post_buff;
	}
	/**
	 * @test
	 */
	public function sync_total_sales_variable_product() {

		//handle a case when managing stock is off for product but total sales should be sync
		$product_id = mt_rand( 1, 100 );
		$variation_id = mt_rand( 101, 200 );
		$quantity = mt_rand( 201, 300 );
		$orig_language_details = new stdClass();
		$orig_language_details->language_code = rand_str();

		$translations = array();
		$translation = new stdClass();
		$translation->language_code = rand_str();
		$translation->element_id = mt_rand( 201, 300 );
		$translations[] = $translation;

		$total_sales = mt_rand( 301, 400 );

		$translation_product_id = mt_rand( 401, 500 );

		$sitepress = $this->getMockBuilder('SitePress')
		     ->disableOriginalConstructor()
		     ->setMethods( array( 'get_element_trid', 'get_element_translations', 'get_element_language_details', 'get_wp_api' ) )
		     ->getMock();

		$check_version = '3.0.0';
		$wc_version = '2.7';

		$wp_api = $this->get_wpml_wp_api_mock();

		$wp_api->expects( $this->once() )
		       ->method( 'constant' )
		       ->with( 'WC_VERSION' )
		       ->willReturn( $wc_version );
		$wp_api->expects( $this->once() )
		       ->method( 'version_compare' )
		       ->with( $wc_version, $check_version, '>=' )
		       ->willReturn( false );

		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		$sitepress->method( 'get_element_trid' )->willReturn( rand_str() );
		$sitepress->method( 'get_element_translations' )->willReturn( $translations );
		$sitepress->method( 'get_element_language_details' )->willReturn( $orig_language_details );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'is_original_product' ) )
		                         ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->willReturn( true );

		$subject = $this->get_subject( $woocommerce_wpml , $sitepress );

		$order = $this->getMockBuilder( 'WC_Order' )
		              ->disableOriginalConstructor()
		              ->setMethods( array( 'get_items' ) )
		              ->getMock();

		$items = array();
		$order_item = $this->getMockBuilder( 'WC_Order_Item_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_variation_id','get_product_id','get_quantity' ) )
		                ->getMock();

		$items[] = $order_item;

		$order->method( 'get_items' )->willReturn( $items );

		$order_item->method( 'get_variation_id' )->willReturn( $variation_id );
		$order_item->method( 'get_product_id' )->willReturn( $product_id );
		$order_item->method( 'get_quantity' )->willReturn( $quantity );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'  => array( $translation->element_id ),
			'return' => 'product_variation'
		) );

		\WP_Mock::wpFunction( 'wp_get_post_parent_id', array(
			'args'  => array( $translation->element_id ),
			'return' => $translation_product_id
		) );

		\WP_Mock::wpFunction( 'get_post', array(
			'args'  => array( $translation_product_id ),
			'return' => true
		) );

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'exists', 'managing_stock' ) )
		                ->getMock();

		$product->method( 'exists' )->willReturn( true );
		$product->method( 'managing_stock' )->willReturn( false );

		\WP_Mock::wpFunction( 'wc_get_product', array(
			'args'  => array( $translation->element_id ),
			'return' => $product
		) );


		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'  => array( $translation_product_id, 'total_sales', true ),
			'return' => $total_sales
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'  => array( $translation_product_id, 'total_sales', $total_sales + $quantity ),
			'times' => 1
		) );


		\WP_Mock::wpFunction( 'wc_recount_after_stock_change', array(
			'args'  => array( $translation_product_id),
			'times' => 1
		) );

		$subject->sync_product_stocks( $order, 'reduce' );

	}


	/**
	 * @test
	 */
	public function it_sync_stock_status_for_translations() {

		$product_id = mt_rand( 1, 100 );
		$status = rand_str( 5 );
		$post_type = 'product';

		$translation = new stdClass();
		$translation->original = false;
		$translation->language_code = rand_str();
		$translation->element_id = mt_rand( 101, 200 );
		$translations[] = $translation;

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'is_original_product' ) )
		                                         ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->with( $product_id )->willReturn( true );

		$sitepress = $this->getMockBuilder('SitePress')
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_element_trid', 'get_element_translations' ) )
		                  ->getMock();

		$sitepress->method( 'get_element_trid' )->willReturn( mt_rand( 201, 300 ) );
		$sitepress->method( 'get_element_translations' )->willReturn( $translations );


		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'  => array( $product_id ),
			'return' => $post_type
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'  => array( $translation->element_id, '_stock_status', $status ),
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'wc_recount_after_stock_change', array(
			'args'  => array( $translation->element_id),
			'times' => 1
		) );

		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );

		$subject->sync_stock_status_for_translations( $product_id, $status );

	}

	/**
	 * @test
	 */
	public function it_does_not_sync_stock_status_for_purchased_product() {

		$product_id = mt_rand( 1, 100 );
		$status = rand_str( 5 );
		$post_type = 'product';

		$translation = new stdClass();
		$translation->element_id = $product_id;
		$translations[] = $translation;

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                   ->disableOriginalConstructor()
		                                   ->setMethods( array( 'is_original_product' ) )
		                                   ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->with( $product_id )->willReturn( true );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'  => array( $product_id ),
			'return' => $post_type
		) );

		$sitepress = $this->getMockBuilder('SitePress')
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_element_trid', 'get_element_translations' ) )
		                  ->getMock();

		$sitepress->method( 'get_element_trid' )->willReturn( mt_rand( 201, 300 ) );
		$sitepress->method( 'get_element_translations' )->willReturn( $translations );

		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );

		$subject->sync_stock_status_for_translations( $product_id, $status );

	}

	/**
	 * @test
	 */
	public function it_does_not_sync_stock_status_for_not_original() {

		$product_id = mt_rand( 1, 100 );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                   ->disableOriginalConstructor()
		                                   ->setMethods( array( 'is_original_product' ) )
		                                   ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->with( $product_id )->willReturn( false );

		\WP_Mock::userFunction( 'update_post_meta', array( 'times' => 0 ) );

		$subject = $this->get_subject( $woocommerce_wpml );

		$subject->sync_stock_status_for_translations( $product_id, rand_str() );

	}

}
