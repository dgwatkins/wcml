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
	 * @return WPML_Post_Translation
	 */
	private function get_wpml_post_translations() {
		return $this->getMockBuilder( 'WPML_Post_Translation' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @return WCML_Synchronize_Product_Data
	 */
	private function get_subject(  $woocommerce_wpml = null, $sitepress = null, $wpml_post_translations = null ){

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress();
		}

		if( null === $wpml_post_translations ) {
			$wpml_post_translations = $this->get_wpml_post_translations();
		}


		return new WCML_Synchronize_Product_Data( $woocommerce_wpml, $sitepress, $wpml_post_translations, $this->wpdb );
	}

	/**
	 * @test
	 */
	public function it_adds_admin_hooks(){
		\WP_Mock::userFunction( 'is_admin', array(
			'return' => true,
			'times'  => 2
		) );

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'save_post', array( $subject, 'synchronize_products' ), PHP_INT_MAX, 2 );
		\WP_Mock::expectActionAdded( 'icl_pro_translation_completed', array( $subject, 'icl_pro_translation_completed' ) );
		\WP_Mock::expectActionAdded( 'woocommerce_product_quick_edit_save', array( $subject, 'woocommerce_product_quick_edit_save' ) );
		\WP_Mock::expectActionAdded( 'woocommerce_product_bulk_edit_save', array( $subject, 'woocommerce_product_quick_edit_save' ) );
		\WP_Mock::expectActionAdded( 'wpml_translation_update', array( $subject, 'icl_connect_translations_action' ) );
		\WP_Mock::expectActionAdded( 'deleted_term_relationships', array( $subject, 'delete_term_relationships_update_term_count' ), 10, 2 );
		\WP_Mock::expectActionAdded( 'deleted_post_meta', array( $subject, 'delete_empty_post_meta_for_translations' ), 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_adds_admin_and_front_hooks(){
		\WP_Mock::userFunction( 'is_admin', array(
			'return' => true,
			'times'  => 2
		) );

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'woocommerce_product_set_stock_status', array( $subject, 'sync_stock_status_for_translations' ), 100, 2 );
		\WP_Mock::expectActionAdded( 'woocommerce_variation_set_stock_status', array( $subject, 'sync_stock_status_for_translations' ), 10, 2 );

		\WP_Mock::expectActionAdded( 'woocommerce_product_set_stock', array( $subject, 'sync_product_stock_hook' ) );
		\WP_Mock::expectActionAdded( 'woocommerce_variation_set_stock', array( $subject, 'sync_product_stock_hook' ) );
		\WP_Mock::expectActionAdded( 'woocommerce_recorded_sales', array( $subject, 'sync_product_total_sales' ) );

		\WP_Mock::expectActionAdded( 'woocommerce_product_set_visibility', array( $subject, 'sync_product_translations_visibility' ) );

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

		\WP_Mock::userFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_children', true ),
			'return' => array( $original_child )
		));

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'   => array( $original_child ),
			'return' => 'product'
		));

		\WP_Mock::onFilter( 'translate_object_id' )->with( $original_child, 'product', false, $language )->reply( $translated_child );

		\WP_Mock::userFunction( 'update_post_meta', array(
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

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		     ->disableOriginalConstructor()
		     ->getMock();
		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'is_original_product' ) )
		                                         ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->with( $product_id )->willReturn( true );

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		     ->disableOriginalConstructor()
		     ->setMethods( array( 'get_element_translations' ) )
		     ->getMock();

		$translations['en'] = $product_id;
		$translations['fr'] = mt_rand( 200, 300 );;

		$wpml_post_translations->expects( $this->once() )->method( 'get_element_translations' )->with( $product_id )->willReturn( $translations );

		$subject = $this->get_subject( $woocommerce_wpml, null, $wpml_post_translations );

		$product_object = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'is_featured', 'get_stock_status' ) )
		                ->getMock();

		$product_object->method( 'is_featured' )->willReturn( true );
		$product_object->method( 'get_stock_status' )->willReturn( 'outofstock' );

		\WP_Mock::userFunction( 'wc_get_product', array(
			'args' => array( $product_id ),
			'return' => $product_object
		) );

		\WP_Mock::userFunction( 'wp_set_post_terms', array(
			'args' => array( $translations['fr'], array( 'featured', 'outofstock' ), 'product_visibility', false ),
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

		$post_fields = array( 'fields' => array(
			$custom_field . ':' . $first_mid  => rand_str(),
			$custom_field . ':' . $second_mid => rand_str()
		) );

		$_POST['data'] = http_build_query( $post_fields );

		\WP_Mock::userFunction( 'update_meta', array(
			'args'  => array( $first_mid, $custom_field, $first_mid_value ),
			'times' => 1
		) );

		\WP_Mock::userFunction( 'update_meta', array(
			'args'  => array( $second_mid, $custom_field, $second_mid_value ),
			'times' => 1
		) );

		$subject->sync_custom_field_value( $custom_field, $translation_data, $translated_product_id, null );

		unset( $_POST['data'] );
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

		\WP_Mock::userFunction( 'add_post_meta', array(
			'args'  => array( $translated_product_id, $custom_field, $translated_value_for_first_field ),
			'times' => 1
		) );

		\WP_Mock::userFunction( 'add_post_meta', array(
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

		\WP_Mock::userFunction( 'add_post_meta', array(
			'args'  => array( $translated_variation_id, $custom_field, $translated_value_for_first_field ),
			'times' => 1
		) );

		\WP_Mock::userFunction( 'add_post_meta', array(
			'args'  => array( $translated_variation_id, $custom_field, $translated_value_for_second_field ),
			'times' => 1
		) );

		$subject->sync_custom_field_value( $custom_field, $translation_data, $translated_variation_id, $post_fields, $original_variation_id, true );

	}


	/**
	 * @test
	 */
	public function it_sync_stock_status_for_translations() {

		$product_id = mt_rand( 1, 100 );
		$status = rand_str( 5 );
		$post_type = 'product';

		$translations['en'] = mt_rand( 101, 200 );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( [ 'is_original_product', 'update_stock_status' ] )
		                                         ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->with( $product_id )->willReturn( true );
		$woocommerce_wpml->products->method( 'update_stock_status' )->with( $translations['en'], $status )->willReturn( true );

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_translations' ) )
		                               ->getMock();

		$wpml_post_translations->method( 'get_element_translations' )->willReturn( $translations );


		\WP_Mock::userFunction( 'get_post_type', array(
			'args'  => array( $product_id ),
			'return' => $post_type
		) );

		\WP_Mock::userFunction( 'wc_recount_after_stock_change', array(
			'args'  => array( $translations['en'] ),
			'times' => 1
		) );

		$subject = $this->get_subject( $woocommerce_wpml, null, $wpml_post_translations );

		$subject->sync_stock_status_for_translations( $product_id, $status );

	}

	/**
	 * @test
	 */
	public function it_does_not_sync_stock_status_for_purchased_product() {

		$product_id = mt_rand( 1, 100 );
		$status = rand_str( 5 );
		$post_type = 'product';

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                   ->disableOriginalConstructor()
		                                   ->setMethods( ['is_original_product'] )
		                                   ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->with( $product_id )->willReturn( true );

		\WP_Mock::userFunction( 'get_post_type', array(
			'args'  => array( $product_id ),
			'return' => $post_type
		) );

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_translations' ) )
		                               ->getMock();

		$wpml_post_translations->method( 'get_element_translations' )->willReturn( [] );

		$subject = $this->get_subject( $woocommerce_wpml, null, $wpml_post_translations );

		$subject->sync_stock_status_for_translations( $product_id, $status );
	}

	/**
	 * @test
	 */
	public function it_should_sync_product_stock_for_translations(){

		WP_Mock::passthruFunction( 'remove_action' );
		WP_Mock::passthruFunction( 'delete_transient' );

		$product_id = 1;
		$qty = 5;
		$stock_status = 'instock';
		$product = $this->getMockBuilder( 'WC_Product' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( array( 'get_id', 'get_stock_quantity', 'get_stock_managed_by_id', 'get_stock_status' ) )
		                       ->getMock();
		$product->method( 'get_id' )->willReturn( $product_id );
		$product->method( 'get_stock_managed_by_id' )->willReturn( $product_id );
		$product->method( 'get_stock_quantity' )->willReturn( $qty );
		$product->method( 'get_stock_status' )->willReturn( $stock_status );

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_translations' ) )
		                               ->getMock();

		$translations['en'] = $product_id;
		$translations['fr'] = 2;

		$wpml_post_translations->expects( $this->once() )->method( 'get_element_translations' )->with( $product_id )->willReturn( $translations );

		$product_object = $this->getMockBuilder( 'WC_Product' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( array( 'get_id', 'get_stock_managed_by_id', 'is_type' ) )
		                       ->getMock();
		$product_object->method( 'get_id' )->willReturn( $translations['fr'] );
		$product_object->method( 'get_stock_managed_by_id' )->willReturn( $translations['fr'] );
		$product_object->method( 'is_type' )->with( 'variation' )->willReturn( false );

		$wc_product_data_store = \Mockery::mock( 'overload:WC_Product_Data_Store_CPT' );
		$wc_product_data_store->shouldReceive( 'update_product_stock' )->with( $translations['fr'], $qty, 'set' )->andReturn( true );

		$wc_data_store = \Mockery::mock( 'overload:WC_Data_Store' );
		$wc_data_store->shouldReceive( 'load' )->andReturn( $wc_product_data_store );

		\WP_Mock::userFunction( 'wc_get_product', array(
			'args' => array( $translations['fr'] ),
			'times' => 1,
			'return' => $product_object
		) );

		$woocommerce_wpml = $this->get_woocommerce_wpml();
		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                   ->disableOriginalConstructor()
		                                   ->setMethods( [ 'update_stock_status' ] )
		                                   ->getMock();
		$woocommerce_wpml->products->method( 'update_stock_status' )->with( $translations['fr'], $stock_status )->willReturn( true );

		$subject = $this->get_subject( $woocommerce_wpml, null, $wpml_post_translations );

		$subject->sync_product_stock( $product );
	}

	/**
	 * @test
	 */
	public function it_should_not_sync_product_stock_if_stock_is_null(){

		$product_id = 101;
		$qty = null;
		$product = $this->getMockBuilder( 'WC_Product' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( array( 'get_stock_quantity' ) )
		                       ->getMock();
		$product->method( 'get_stock_quantity' )->willReturn( $qty );

		$subject = $this->get_subject();

		\WP_Mock::expectActionNotAdded( 'woocommerce_product_set_stock', array( $subject, 'sync_product_stock_hook' ) );
		\WP_Mock::expectActionNotAdded( 'woocommerce_variation_set_stock', array( $subject, 'sync_product_stock_hook' ) );

		$subject->sync_product_stock( $product );
	}

	/**
	 * @test
	 */
	public function it_should_sync_product_stock_for_translation(){

		WP_Mock::passthruFunction( 'remove_action' );

		$product_id = 1;
		$translated_product_id = 10;
		$stock_status = 'instock';
		$qty = 5;
		$product = $this->getMockBuilder( 'WC_Product' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( array( 'get_id', 'get_stock_quantity', 'get_stock_managed_by_id', 'get_stock_status' ) )
		                       ->getMock();
		$product->method( 'get_id' )->willReturn( $product_id );
		$product->method( 'get_stock_managed_by_id' )->willReturn( $product_id );
		$product->method( 'get_stock_quantity' )->willReturn( $qty );
		$product->method( 'get_stock_status' )->willReturn( $stock_status );

		$translated_product = $this->getMockBuilder( 'WC_Product' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( array( 'get_id', 'get_stock_managed_by_id', 'is_type' ) )
		                       ->getMock();
		$translated_product->method( 'get_id' )->willReturn( $translated_product_id );
		$translated_product->method( 'get_stock_managed_by_id' )->willReturn( $translated_product_id );
		$translated_product->method( 'is_type' )->with( 'variation' )->willReturn( false );

		$wc_product_data_store = \Mockery::mock( 'overload:WC_Product_Data_Store_CPT' );
		$wc_product_data_store->shouldReceive( 'update_product_stock' )->with( $translated_product_id, $qty, 'set' )->andReturn( true );

		$wc_data_store = \Mockery::mock( 'overload:WC_Data_Store' );
		$wc_data_store->shouldReceive( 'load' )->andReturn( $wc_product_data_store );

		$woocommerce_wpml = $this->get_woocommerce_wpml();
		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                   ->disableOriginalConstructor()
		                                   ->setMethods( [ 'update_stock_status' ] )
		                                   ->getMock();
		$woocommerce_wpml->products->method( 'update_stock_status' )->with( $translated_product_id, $stock_status )->willReturn( true );
		$subject = $this->get_subject( $woocommerce_wpml );

		$subject->sync_product_stock( $product, $translated_product );
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

	/**
	 * @test
	 */
	public function it_should_delete_empty_post_meta_for_translations() {

		$meta_ids = array();
		$object_id = 10;
		$meta_key = '_sale_price';

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                   ->disableOriginalConstructor()
		                                   ->setMethods( array( 'is_original_product' ) )
		                                   ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->with( $object_id )->willReturn( true );

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_translations' ) )
		                               ->getMock();

		$translations['fr'] = 12;

		$wpml_post_translations->expects( $this->once() )->method( 'get_element_translations' )->with( $object_id, false, true )->willReturn( $translations );

		\WP_Mock::userFunction( 'get_post_type', array(
			'args' => array( $object_id ),
			'times' => 1,
			'return' => 'product',
		) );

		\WP_Mock::userFunction( 'delete_post_meta', array(
			'args' => array( $translations['fr'], $meta_key ),
			'times' => 1,
			'return' => true,
		) );

		$subject = $this->get_subject( $woocommerce_wpml, null, $wpml_post_translations );

		$subject->delete_empty_post_meta_for_translations( $meta_ids, $object_id, $meta_key );

	}

	/**
	 * @test
	 */
	public function it_should_not_delete_empty_post_meta_for_not_original() {

		$meta_ids = array();
		$object_id = 10;
		$meta_key = '_sale_price';

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                   ->disableOriginalConstructor()
		                                   ->setMethods( array( 'is_original_product' ) )
		                                   ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->with( $object_id )->willReturn( false );

		\WP_Mock::userFunction( 'get_post_type', array(
			'args' => array( $object_id ),
			'times' => 1,
			'return' => 'product',
		) );

		\WP_Mock::userFunction( 'delete_post_meta', array(
			'times' => 0
		) );

		$subject = $this->get_subject( $woocommerce_wpml );

		$subject->delete_empty_post_meta_for_translations( $meta_ids, $object_id, $meta_key );

	}

	/**
	 * @test
	 */
	public function it_should_not_delete_empty_post_meta_for_not_products() {

		$meta_ids = array();
		$object_id = 10;
		$meta_key = '_sale_price';

		\WP_Mock::userFunction( 'get_post_type', array(
			'args' => array( $object_id ),
			'times' => 1,
			'return' => rand_str(),
		) );

		\WP_Mock::userFunction( 'delete_post_meta', array(
			'times' => 0
		) );

		$subject = $this->get_subject();

		$subject->delete_empty_post_meta_for_translations( $meta_ids, $object_id, $meta_key );

	}

	/**
	 * @test
	 */
	public function it_should_sync_product_total_sales() {

		\WP_Mock::passthruFunction( 'absint' );

		$order_id   = 11;
		$product_id = 10;
		$qty        = 1;
		$items      = [];

		$order_item = $this->getMockBuilder( 'WC_Order_Item_Product' )
		                   ->disableOriginalConstructor()
		                   ->setMethods( [ 'get_product_id', 'get_quantity' ] )
		                   ->getMock();
		$order_item->method( 'get_product_id' )->willReturn( $product_id );
		$order_item->method( 'get_quantity' )->willReturn( $qty );

		$items[] = $order_item;

		$order_object = $this->getMockBuilder( 'WC_Order' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( [ 'get_items' ] )
		                     ->getMock();

		$order_object->method( 'get_items' )->willReturn( $items );

		\WP_Mock::onFilter( 'wcml_order_item_quantity' )->with( $qty, $order_object, $order_item )->reply( $qty );


		\WP_Mock::userFunction( 'wc_get_order', array(
			'args'   => [ $order_id ],
			'times'  => 1,
			'return' => $order_object,
		) );

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_translations' ) )
		                               ->getMock();

		$translations['en'] = $product_id;
		$translations['fr'] = 21;

		$wpml_post_translations->expects( $this->once() )->method( 'get_element_translations' )->with( $product_id )->willReturn( $translations );

		$wc_product_data_store = \Mockery::mock( 'overload:WC_Product_Data_Store_CPT' );
		$wc_product_data_store->shouldReceive( 'update_product_sales' )->with( $translations['fr'], $qty, 'increase' )->andReturn( true );

		$wc_data_store = \Mockery::mock( 'overload:WC_Data_Store' );
		$wc_data_store->shouldReceive( 'load' )->andReturn( $wc_product_data_store );

		$subject = $this->get_subject( null, null, $wpml_post_translations );

		$subject->sync_product_total_sales( $order_id );

	}

}
