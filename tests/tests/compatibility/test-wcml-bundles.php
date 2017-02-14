<?php

/**
 * Class Test_WCML_Bundles
 */
class Test_WCML_Product_Bundles extends WCML_UnitTestCase {

	private $bundles;
	private $default_language;
	private $second_language;
	private $tp;
	private $test_data;

	private $WCML_WC_Product_Bundles_Items_Mock;


	function setUp() {

		parent::setUp();

		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';
		$this->tp = new WPML_Element_Translation_Package;

		$this->create_tables();

		$this->test_data = new stdClass();
		//add bundle product
		$this->test_data->bundle_product = $this->wcml_helper->add_product( $this->default_language, false, random_string() );
		wp_set_object_terms( $this->test_data->bundle_product->id, 'bundle', 'product_type', true );
		$this->test_data->translated_bundle_product = $this->wcml_helper->add_product( $this->second_language, $this->test_data->bundle_product->trid, random_string() );
		wp_set_object_terms( $this->test_data->translated_bundle_product->id, 'bundle', 'product_type', true );

		$this->WCML_WC_Product_Bundles_Items_Mock = $this->getMockBuilder('WCML_WC_Product_Bundles_Items')
		                                           ->setMethods( array(
			                                           'get_items',
			                                           'get_item_data',
			                                           'copy_item_data',
			                                           'get_item_data_object',
			                                           'update_item_meta',
			                                           'save_item_meta'
		                                           ) )->getMock();

		$this->WCML_WC_Product_Bundles_Items_Mock->method( 'get_items' )
		                                   ->will( $this->returnCallback(  array($this, 'get_items' ) ) );

		$this->WCML_WC_Product_Bundles_Items_Mock->method( 'get_item_data' )
		                                   ->will( $this->returnCallback( array($this, 'get_item_data')) );

		$this->WCML_WC_Product_Bundles_Items_Mock->method( 'copy_item_data' )
		                                   ->will( $this->returnCallback( array($this, 'copy_item_data')) );

		$this->WCML_WC_Product_Bundles_Items_Mock->method( 'get_item_data_object' )
		                                   ->will( $this->returnCallback( array($this, 'get_item_data_object')) );

		$this->WCML_WC_Product_Bundles_Items_Mock->method( 'update_item_meta' )
		                                   ->will( $this->returnCallback( array($this, 'update_item_meta')) );

		$this->WCML_WC_Product_Bundles_Items_Mock->method( 'save_item_meta' )
		                                   ->will( $this->returnCallback( array($this, 'save_item_meta')) );


	}

	function tearDown(){
		global $wpdb;
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_bundled_items");
		$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}woocommerce_bundled_itemmeta");
		parent::tearDown();
	}

	/**
	 * @return WCML_Product_Bundles
	 */
	private function get_test_subject() {

		return new WCML_Product_Bundles( $this->sitepress, $this->woocommerce_wpml, $this->WCML_WC_Product_Bundles_Items_Mock );

	}

	public function get_items( $bundle_id ){

		$bundle_items = array();
		$items = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT * FROM {$this->wpdb->prefix}woocommerce_bundled_items WHERE bundle_id=%d", $bundle_id) );

		foreach( $items as $item ){
			$bundled_item = new stdClass;
			$bundled_item->product_id = $this->get_product_id_from_item_id($item->bundled_item_id);
			$bundled_item->item_id = $item->bundled_item_id;
			$bundled_item->bundle = $item->bundle_id;

			$bundled_item->item_data = $this->get_item_data( $bundled_item );

			$bundle_items[ $item->bundled_item_id ] = $bundled_item;
		}

		return $bundle_items;
	}

	public function get_item_data( $item ){

		$item_meta = array();
		$metas = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT meta_key, meta_value FROM {$this->wpdb->prefix}woocommerce_bundled_itemmeta WHERE bundled_item_id=%d", $item->item_id) );

		foreach( $metas as $meta ){
			$item_meta[ $meta->meta_key ] = $meta->meta_value;
		}

		$item_meta['product_id'] = $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT product_id FROM {$this->wpdb->prefix}woocommerce_bundled_items WHERE bundled_item_id=%d", $item->item_id ) );

		return $item_meta;
	}

	public function copy_item_data( $item_id_1, $item_id_2 ){

		$metas = $this->wpdb->get_results( $this->wpdb->prepare(
			"SELECT meta_key, meta_value FROM {$this->wpdb->prefix}woocommerce_bundled_itemmeta WHERE bundled_item_id=%d", $item_id_1 ) );

		foreach( $metas as $meta ){
			$this->update_item_meta( $item_id_2, $meta->meta_key, $meta->meta_value );
		}

	}

	public function get_item_data_object( $item_id ){
		return $item_id;
	}

	public function update_item_meta( $item_id, $meta_key, $meta_value ){
		$meta_id = $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT meta_id FROM {$this->wpdb->prefix}woocommerce_bundled_itemmeta WHERE bundled_item_id=%d AND meta_key=%s ",
			$item_id, $meta_key ) );
		if( $meta_id ){
			$this->wpdb->update( $this->wpdb->prefix . 'woocommerce_bundled_itemmeta',
				array( 'meta_value' => $meta_value ),
				array( 'meta_id' => $meta_id, 'meta_key' => $meta_key )
			);
		}else{
			$this->wpdb->insert( $this->wpdb->prefix . 'woocommerce_bundled_itemmeta',
				array( 'bundled_item_id' => $item_id, 'meta_id' => $meta_id, 'meta_key' => $meta_key, 'meta_value' => $meta_value )
			);
			$meta_id = $this->wpdb->insert_id;
		}
		return $meta_id;
	}

	public function save_item_meta(){
		return true;
	}

	private function create_tables() {
		global $wpdb;

		$collate = '';

		if ( $wpdb->has_cap( 'collation' ) ) {
			$collate = $wpdb->get_charset_collate();
		}

		$max_index_length = 191;

		$tables = "
			CREATE TABLE {$wpdb->prefix}woocommerce_bundled_items (
			  bundled_item_id bigint(20) NOT NULL auto_increment,
			  product_id bigint(20) NOT NULL,
			  bundle_id bigint(20) NOT NULL,
			  menu_order bigint(20) NOT NULL,
			  PRIMARY KEY  (bundled_item_id),
			  KEY product_id (product_id),
			  KEY bundle_id (bundle_id)
			) $collate;
			CREATE TABLE {$wpdb->prefix}woocommerce_bundled_itemmeta (
			  meta_id bigint(20) NOT NULL auto_increment,
			  bundled_item_id bigint(20) NOT NULL,
			  meta_key varchar(255) default NULL,
			  meta_value longtext NULL,
			  PRIMARY KEY  (meta_id),
			  KEY bundled_item_id (bundled_item_id),
			  KEY meta_key (meta_key($max_index_length))
			) $collate;
		";

		dbDelta( $tables );

		return $tables;
	}

	public function test_append_bundle_data_translation_package(){

		$this->switch_to_admin();
		$product_bundles = $this->get_test_subject();

		$this->setup_product_in_bundle( $this->test_data->bundle_product->id, true, true );

		$package = array( 'contents' => array() );
		$post = get_post( $this->test_data->bundle_product->id );

		$package = $product_bundles->append_bundle_data_translation_package( $package, $post );

		$this->assertArrayHasKey( 'product_bundles:' . $this->test_data->product_in_bundle->id . ':title' , $package['contents'] );
		$this->assertArrayHasKey( 'product_bundles:' . $this->test_data->product_in_bundle->id . ':description' , $package['contents'] );

		$this->assertEquals( 1, $package['contents']['product_bundles:' . $this->test_data->product_in_bundle->id . ':title']['translate'] );
		$this->assertEquals( 1, $package['contents']['product_bundles:' . $this->test_data->product_in_bundle->id . ':description']['translate'] );

		$package = array( 'contents' => array() );
		$this->setup_product_in_bundle( $this->test_data->product_in_bundle->id, true, false );
		$this->assertArrayNotHasKey( 'product_bundles:' . $this->test_data->product_in_bundle->id . ':description' , $package['contents'] );

		$package = array( 'contents' => array() );
		$this->setup_product_in_bundle( $this->test_data->product_in_bundle->id, false, true );
		$this->assertArrayNotHasKey( 'product_bundles:' . $this->test_data->product_in_bundle->id . ':title' , $package['contents'] );

		$package = array( 'contents' => array() );
		$this->setup_product_in_bundle( $this->test_data->product_in_bundle->id, false, false );
		$this->assertArrayNotHasKey( 'product_bundles:' . $this->test_data->product_in_bundle->id . ':description' , $package['contents'] );
		$this->assertArrayNotHasKey( 'product_bundles:' . $this->test_data->product_in_bundle->id . ':title' , $package['contents'] );

	}

	public function test_bundle_update(){

		$product_bundles = $this->get_test_subject();

		$this->setup_product_in_bundle( $this->test_data->bundle_product->id, true, true );

		$trnsl_title = random_string();
		$trnsl_desc = random_string();

		$data = array(
			md5( 'bundle_'.$this->test_data->product_in_bundle->id.'_title' ) => $trnsl_title,
			md5( 'bundle_'.$this->test_data->product_in_bundle->id.'_desc' ) => $trnsl_desc
		);

		$tr_bundle_data = $product_bundles->bundle_update(
			$this->test_data->bundle_product->id,
			$this->test_data->translated_bundle_product->id,
			$data,
			$this->second_language
		);

		$translated_bundle_id = apply_filters(
			'translate_object_id',
			$this->test_data->bundle_product->id,
			get_post_type( $this->test_data->bundle_product->id ),
			false,
			$this->second_language
		);

		$item_id = $this->get_item_id_from_product_id( $this->test_data->translated_product_in_bundle->id, $translated_bundle_id );

		$this->assertEquals( $trnsl_title, $tr_bundle_data[ $item_id ][ 'title' ] );
		$this->assertEquals( $trnsl_desc, $tr_bundle_data[ $item_id ][ 'description' ] );
	}

	public function test_custom_box_html() {

		$product_bundles = $this->get_test_subject();

		$bundle_product = $this->wcml_helper->add_product( $this->default_language, false, random_string() );

		//test custom box with override title and desc
		$bundle_data = $this->setup_product_in_bundle( $bundle_product->id, true, true );

		$job_details = array(
			'job_type'             => 'product',
			'job_id'               => $bundle_product->id,
			'target'               => $this->second_language,
			'translation_complete' => true,
		);

		$obj = new WCML_Editor_UI_Product_Job( $job_details, $this->woocommerce_wpml, $this->sitepress, $this->wpdb );

		$data = $product_bundles->custom_box_html_data( array(), $bundle_product->id, false, $this->second_language );
		$product_bundles->custom_box_html( $obj, $bundle_product->id, $data );

		$product_obj = get_post( $bundle_product->id );
		$expected = array(
			array(
				'title'                 => 'Title',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'title',
				'field_data'            => $product_obj->post_title,
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => 'Slug',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'slug',
				'field_data'            => $product_obj->post_name,
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => 'Content / Description',
				'tid'                   => '0',
				'field_style'           => '2',
				'field_type'            => 'product_content',
				'field_data'            => $product_obj->post_content,
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => '',
				'tid'                   => '0',
				'field_style'           => '2',
				'field_type'            => 'product_excerpt',
				'field_data'            => $product_obj->post_excerpt,
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => '',
				'tid'                   => '0',
				'field_style'           => '1',
				'field_type'            => '_purchase_note',
				'field_data'            => '',
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => 'Name',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'bundle_'.$this->test_data->product_in_bundle->id.'_title',
				'field_data'            => $bundle_data[ $this->test_data->product_in_bundle->id ][ 'title' ],
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => 'Description',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'bundle_'.$this->test_data->product_in_bundle->id.'_desc',
				'field_data'            => $bundle_data[ $this->test_data->product_in_bundle->id ][ 'description' ],
				'field_data_translated' => '',
				'field_finished'        => '0',
			)
		);

		$this->assertEquals( $expected, $obj->get_all_fields() );
	}

	public function test_custom_box_html_data(){

		$product_bundles = $this->get_test_subject();


		//test custom box without override title and desc
		$bundle_data = $this->setup_product_in_bundle( $this->test_data->bundle_product->id, false, false );
		$product_bundles->sync_bundled_ids( $this->test_data->bundle_product->id, $this->test_data->translated_bundle_product->id );

		$this->assertEquals( array(),
			$product_bundles->custom_box_html_data(
				array(),
				$this->test_data->bundle_product->id,
				get_post( $this->test_data->translated_bundle_product->id ),
				$this->second_language
			)
		);

		//test custom box with override title and desc
		$bundle_data = $this->setup_product_in_bundle( $this->test_data->bundle_product->id, true, true );

		$product_bundles->sync_bundled_ids( $this->test_data->bundle_product->id, $this->test_data->translated_bundle_product->id );

		$expected = array(
			'bundle_'.$this->test_data->product_in_bundle->id.'_title' => array(
				'original'    => $bundle_data[ $this->test_data->product_in_bundle->id ][ 'title' ],
				'translation' => '',
			),
			'bundle_'.$this->test_data->product_in_bundle->id.'_desc' => array(
				'original'    => $bundle_data[ $this->test_data->product_in_bundle->id ][ 'description' ],
				'translation' => '',
			)
		);

		$data = $product_bundles->custom_box_html_data(
			array(),
			$this->test_data->bundle_product->id,
			get_post( $this->test_data->translated_bundle_product->id ),
			$this->second_language
		);

		//don't test for translation
		foreach ( $data as $key => $val ){
			$data[ 'bundle_'.$this->test_data->product_in_bundle->id.'_title' ]['translation'] = '';
			$data[ 'bundle_'.$this->test_data->product_in_bundle->id.'_desc' ]['translation'] = '';
		}

		$this->assertEquals( $expected, $data );

	}

	public function test_resync_bundle(){

		$cart_item_data = new stdClass();
		$cart_item_data->product_type = 'bundle';
		$cart_item_data->bundle_data = array();

		$cart_item = array(
			'bundled_items' => array(),
			'data' => $cart_item_data,
			'product_id' => $this->test_data->bundle_product->id,
		);

		$product_bundles = $this->get_test_subject();

		$this->sitepress->switch_lang( $this->second_language );

		$new_cart_item = $product_bundles->resync_bundle( $cart_item, false, false );

		$this->assertEquals( $this->test_data->translated_bundle_product->id, $new_cart_item[ 'data' ]->id );

		$this->sitepress->switch_lang( $this->default_language );
	}

	public function test_resync_bundle_clean(){
		//TBA
	}

	public function test_save_bundle_data_translation() {

		$product_bundles = $this->get_test_subject();
		$product = get_post( $this->test_data->bundle_product->id );

		$bundle_data = $this->setup_product_in_bundle( $this->test_data->bundle_product->id, true, true );
		$data = array(
			'title' => array(
				'field_type' => 'product_bundles:'.$this->test_data->product_in_bundle->id.':title',
				'data'	=> random_string()
			),
			'description' => array(
				'field_type' => 'product_bundles:'.$this->test_data->product_in_bundle->id.':description',
				'data'	=> random_string()
			)
		);

		$job = new stdClass();
		$job->original_doc_id = $this->test_data->bundle_product->id;
		$job->language_code = $this->second_language;

		$product_bundles->save_bundle_data_translation( $this->test_data->translated_bundle_product->id, $data, $job );
		$this->wcml_helper->icl_clear_and_init_cache( $this->second_language );

		$expected = array(
			$this->test_data->translated_product_in_bundle->id => array(
				'product_id' => $this->test_data->translated_product_in_bundle->id,
				'hide_thumbnail' => 'no',
				'override_title' => 'yes',
				'title' => $data[ 'title' ][ 'data' ],
				'override_description' => 'yes',
				'description' => $data[ 'description' ][ 'data' ],
				'optional' => 'no',
				'bundle_quantity' => 1,
				'bundle_quantity_max' => 1,
				'bundle_discount' => '',
				'stock_status' => 'in_stock',
				'max_stock' => '',
				'quantity_min' => 1,
				'quantity_max' => 1,
				'shipped_individually' => 'no',
				'priced_individually' => 'no',
				'single_product_visibility' => 'visible',
				'cart_visibility' => 'visible',
				'order_visibility' => 'visible',
				'single_product_price_visibility' => 'visible',
				'cart_price_visibility' => 'visible',
				'order_price_visibility' => 'visible'
			)
		);

		$translated_bundle_items = $this->get_items( $this->test_data->translated_bundle_product->id );

		foreach( $translated_bundle_items as $key => $translated_bundle_item ){
			$item_data = $this->get_item_data( $translated_bundle_item );

			$this->assertEquals( $this->test_data->translated_product_in_bundle->id, $item_data['product_id'] );
			foreach( $expected as $item_id => $data ){
				foreach( $data as $key=> $value){
					$this->assertEquals( $value, $item_data[ $key ] );
				}
			}

		}

	}

	public function test_sync_bundled_ids(){

		$product_bundles = $this->get_test_subject();

		//test sync without override title and desc
		$this->setup_product_in_bundle( $this->test_data->bundle_product->id, false, false );
		$tr_bundle_data = $product_bundles->sync_bundled_ids( $this->test_data->bundle_product->id, $this->test_data->translated_bundle_product->id );

		$this->assertTrue( !empty( $tr_bundle_data ) );

		$translated_item_id = $this->get_item_id_from_product_id( $this->test_data->translated_product_in_bundle->id, $this->test_data->translated_bundle_product->id );

		$this->assertTrue( isset( $tr_bundle_data[ $translated_item_id ] ) );
		$this->assertEquals( $this->test_data->translated_product_in_bundle->id, $tr_bundle_data[ $translated_item_id ][ 'product_id' ] );
		$this->assertEquals( 'no', $tr_bundle_data[ $translated_item_id ][ 'override_title' ] );
		$this->assertTrue( empty( $tr_bundle_data[ $translated_item_id ][ 'title' ] ) );
		$this->assertTrue( empty( $tr_bundle_data[ $translated_item_id ][ 'description' ] ) );


		//test sync with override title and desc
		$this->setup_product_in_bundle( $this->test_data->bundle_product->id, true, true );
		$tr_bundle_data = $product_bundles->sync_bundled_ids( $this->test_data->bundle_product->id, $this->test_data->translated_bundle_product->id );
		$this->assertTrue( !empty( $tr_bundle_data ) );

		$translated_item_id = $this->get_item_id_from_product_id( $this->test_data->translated_product_in_bundle->id, $this->test_data->translated_bundle_product->id );
		$this->assertTrue( isset( $tr_bundle_data[ $translated_item_id ] ) );
		$this->assertEquals( $this->test_data->translated_product_in_bundle->id, $tr_bundle_data[ $translated_item_id ][ 'product_id' ] );
		$this->assertTrue( !empty( $tr_bundle_data[ $translated_item_id ][ 'title' ] ) );
		$this->assertTrue( !empty( $tr_bundle_data[ $translated_item_id ][ 'description' ] ) );


		//test sync variable products in bundle
		$this->setup_variable_product_in_bundle( $this->test_data->bundle_product->id, true, true );
		$tr_bundle_data = $product_bundles->sync_bundled_ids( $this->test_data->bundle_product->id, $this->test_data->translated_bundle_product->id );
		$allowed_variations = array();
		foreach( wc_get_product( $this->test_data->translated_variable_product_in_bundle->id )->get_available_variations() as $variation_data ){
			$allowed_variations[] = $variation_data[ 'variation_id' ];
		}

		$translated_item_id = $this->get_item_id_from_product_id( $this->test_data->translated_variable_product_in_bundle->id, $this->test_data->translated_bundle_product->id );

		$this->assertTrue( !empty( $tr_bundle_data ) );
		$this->assertTrue( isset( $tr_bundle_data[ $translated_item_id ] ) );
		$this->assertEquals( $this->test_data->translated_variable_product_in_bundle->id, $tr_bundle_data[ $translated_item_id ][ 'product_id' ] );
		$this->assertFalse( empty( $tr_bundle_data[ $translated_item_id ][ 'title' ] ) );
		$this->assertFalse( empty( $tr_bundle_data[ $translated_item_id ][ 'description' ] ) );
		/*
		$this->assertEquals(
			array( $this->test_data->attr_name => $this->test_data->attr_values[ 'medium' ][ 'translated' ] ),
			unserialize( $tr_bundle_data[ $translated_item_id ][ 'bundle_defaults' ] )
		);
		$this->assertEquals( $allowed_variations, unserialize( $tr_bundle_data[ $translated_item_id ][ 'allowed_variations' ] ) );
		*/

	}

	private function setup_product_in_bundle( $bundle_id, $override_title = false, $override_description = false ){

		$bundle_data = array();

		//insert simple product to bundle
		$product_title = random_string();
		$this->test_data->product_in_bundle = $product_in_bundle = $this->wcml_helper->add_product( $this->default_language, false, $product_title );
		$translated_product_title = random_string();
		$this->test_data->translated_product_in_bundle = $this->wcml_helper->add_product( $this->second_language, $product_in_bundle->trid, $translated_product_title );

		$bundle_data[ $product_in_bundle->id ] = array(
			'product_id' => $product_in_bundle->id,
			'override_title' => 'no',
			'override_description' => 'no',
			'hide_thumbnail' => 'no',
			'optional' => 'no',
			'bundle_quantity' => 1,
			'bundle_quantity_max' => 1,
			'bundle_discount' => '',
			'stock_status' => 'in_stock',
			'max_stock' => '',
			'quantity_min' => 1,
			'quantity_max' => 1,
			'shipped_individually' => 'no',
			'priced_individually' => 'no',
			'single_product_visibility' => 'visible',
			'cart_visibility' => 'visible',
			'order_visibility' => 'visible',
			'single_product_price_visibility' => 'visible',
			'cart_price_visibility' => 'visible',
			'order_price_visibility' => 'visible'
		);

		if( $override_title ){
			$bundle_data[ $product_in_bundle->id ][ 'override_title' ] = 'yes';
			$bundle_data[ $product_in_bundle->id ][ 'title' ] = random_string();
		}
		if( $override_description ){
			$bundle_data[ $product_in_bundle->id ][ 'override_description' ] = 'yes';
			$bundle_data[ $product_in_bundle->id ][ 'description' ] = random_string();
		}

		$menu_order = 0;
		foreach( $bundle_data as $product_id => $data ) {
			$this->wpdb->insert(
				$this->wpdb->prefix . 'woocommerce_bundled_items',
				array(
					'product_id' => $product_id,
					'bundle_id'  => $bundle_id,
					'menu_order' => $menu_order
				)
			);

			$bundled_item_id = $this->wpdb->insert_id;

			foreach( $data as $key => $value ){
				$this->wpdb->insert(
					$this->wpdb->prefix . 'woocommerce_bundled_itemmeta',
					array(
						'bundled_item_id' => $bundled_item_id,
						'meta_key'  => $key,
						'meta_value' => $value
					)
				);
			}

			$menu_order++;
		}

		return $bundle_data;
	}

	private function setup_variable_product_in_bundle( $bundle_id, $override_title = false, $override_description = false ){
		$bundle_data = array();

		$attr = 'size_test';
		$this->test_data->attr_name = $attr_name = 'pa_size_test';
		$attr_values = array();

		$attr_values[ 'medium' ] = array(
			'original' => 'medium',
			'translated' => 'medio'
		);

		$this->test_data->attr_default = 'medium';

		$this->test_data->attr_values = $attr_values;

		$this->wcml_helper->register_attribute( $attr );
		$attr_variations_data = array();

		foreach( $attr_values as $attr_value ){
			$term = $this->wcml_helper->add_attribute_term( $attr_value['original'], $attr, $this->default_language );
			$this->wcml_helper->add_attribute_term( $attr_value['translated'], $attr, $this->second_language, $term['trid'] );
			$attr_variations_data[ $attr_value['original'] ] = array(
				'price'     => 10,
				'regular'   => 10
			);
		}

		//insert variable product to bundle
		$variation_data = array(
			'product_title' => rand_str(),
			'attribute' => array(
				'name' => $attr_name
			),
			'variations' => $attr_variations_data
		);
		$this->test_data->variable_product_in_bundle = $variable_product_in_bundle = $this->wcml_helper->add_variable_product( $variation_data, false);

		$attr_variations_data = array();

		foreach( $variable_product_in_bundle->variations as $product_variations ){
			$attr_variations_data[ $attr_values[ $product_variations[ 'attr_value' ] ][ 'translated' ] ] = array(
				'original_variation_id' => $product_variations[ 'variation_id' ],
				'price'     => 10,
				'regular'   => 10
			);
		}

		$variation_data = array(
			'product_title' => rand_str(),
			'attribute' => array(
				'name' => $attr_name
			),
			'variations' => $attr_variations_data
		);
		$this->test_data->translated_variable_product_in_bundle = $this->wcml_helper->add_variable_product( $variation_data, $variable_product_in_bundle->trid, $this->second_language );

		$allowed_variations = array();
		foreach( wc_get_product( $variable_product_in_bundle->id )->get_available_variations() as $variation_data ){
			$allowed_variations[] = $variation_data[ 'variation_id' ];
		}

		$bundle_data[ $variable_product_in_bundle->id ] = array(
			'product_id' => $variable_product_in_bundle->id,
			'override_title' => 'no',
			'override_description' => 'no',
			'hide_thumbnail' => 'no',
			'optional' => 'no',
			'bundle_quantity' => 1,
			'bundle_quantity_max' => 1,
			'bundle_discount' => '',
			'single_product_visibility' => 'visible',
			'cart_visibility' => 'visible',
			'order_visibility' => 'visible',
			'filter_variations' => 'yes',
			'allowed_variations' => $allowed_variations,
			'hide_filtered_variations' => 'no',
			'bundle_defaults' => array(
				$attr_name => $this->test_data->attr_default
			),
			'override_defaults' => 'yes',
			'stock_status' => 'in_stock',
			'max_stock' => '',
			'quantity_min' => 1,
			'quantity_max' => 1
		);

		if( $override_title ){
			$bundle_data[ $variable_product_in_bundle->id ][ 'override_title' ] = 'yes';
			$bundle_data[ $variable_product_in_bundle->id ][ 'title' ] = random_string();
		}
		if( $override_description ){
			$bundle_data[ $variable_product_in_bundle->id ][ 'override_description' ] = 'yes';
			$bundle_data[ $variable_product_in_bundle->id ][ 'description' ] = random_string();
		}

		$menu_order = 0;
		foreach( $bundle_data as $product_id => $data ) {
			$this->wpdb->insert(
				$this->wpdb->prefix . 'woocommerce_bundled_items',
				array(
					'product_id' => $product_id,
					'bundle_id'  => $bundle_id,
					'menu_order' => $menu_order
				)
			);
			$bundled_item_id = $this->wpdb->insert_id;

			foreach( $data as $key => $value ){
				$this->wpdb->insert(
					$this->wpdb->prefix . 'woocommerce_bundled_itemmeta',
					array(
						'bundled_item_id' => $bundled_item_id,
						'meta_key'  => $key,
						'meta_value' => maybe_serialize( $value )
					)
				);
			}

			$menu_order++;
		}

		return $bundle_data;
	}

	private function get_item_id_from_product_id( $product_id, $bundle_id ){

		$item_id = $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT bundled_item_id FROM {$this->wpdb->prefix}woocommerce_bundled_items WHERE product_id=%d AND bundle_id=%d",
			$product_id, $bundle_id
		) );

		return $item_id;
	}

	private function get_product_id_from_item_id( $item_id ){

		return $this->wpdb->get_var( $this->wpdb->prepare(
			"SELECT product_id FROM {$this->wpdb->prefix}woocommerce_bundled_items WHERE bundled_item_id=%d", $item_id) );
	}


}
