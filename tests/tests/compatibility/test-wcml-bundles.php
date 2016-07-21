<?php

/**
 * Class Test_WCML_Bundles
 */
class Test_WCML_Product_Bundles extends WCML_UnitTestCase {

	private $bundles;


	function setUp() {

		parent::setUp();

		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';
		$this->tp = new WPML_Element_Translation_Package;

		//add bundle product
		$this->bundle_product = $this->wcml_helper->add_product( $this->default_language, false, random_string() );
		wp_set_object_terms( $this->bundle_product->id, 'bundle', 'product_type', true );
		$this->translated_bundle_product = $this->wcml_helper->add_product( $this->second_language, $this->bundle_product->trid, random_string() );
		wp_set_object_terms( $this->translated_bundle_product->id, 'bundle', 'product_type', true );
	}

	/**
	 * @return WCML_Product_Bundles
	 */
	private function get_test_subject() {
		return new WCML_Product_Bundles( $this->sitepress, $this->woocommerce_wpml );
	}

	public function test_make__bundle_data_not_translatable_by_default() {
		$product_bundles = $this->get_test_subject();
		$wpml_config_array = new stdClass();
		$wpml_config_array->plugins = array( 'Test plugin' );
		$this->assertEquals( (array) $wpml_config_array, (array) $product_bundles->make__bundle_data_not_translatable_by_default( $wpml_config_array ) );
		$wpml_config_array->plugins[ 'WooCommerce Product Bundles' ] = '<custom-field action="translate">_bundle_data</custom-field>';
		$output = $product_bundles->make__bundle_data_not_translatable_by_default( $wpml_config_array );
		$this->assertFalse( strpos( $wpml_config_array->plugins[ 'WooCommerce Product Bundles' ], 'action="translate"' ) );
		$this->assertTrue( false !== strpos( $wpml_config_array->plugins[ 'WooCommerce Product Bundles' ], 'action="nothing"' ) );
	}

	public function test_sync_bundled_ids(){

		$product_bundles = $this->get_test_subject();

		//test sync without override title and desc
		$this->setup_product_in_bundle( $this->bundle_product->id, false, false );
		$tr_bundle_data = $product_bundles->sync_bundled_ids( $this->bundle_product->id, $this->translated_bundle_product->id );
		$this->assertTrue( !empty( $tr_bundle_data ) );
		$this->assertTrue( isset( $tr_bundle_data[ $this->translated_product_in_bundle->id ] ) );
		$this->assertEquals( $this->translated_product_in_bundle->id, $tr_bundle_data[ $this->translated_product_in_bundle->id ][ 'product_id' ] );
		$this->assertEquals( 'no', $tr_bundle_data[ $this->translated_product_in_bundle->id ][ 'override_title' ] );

		//test sync with override title and desc
		$this->setup_product_in_bundle( $this->bundle_product->id, true, true );
		$tr_bundle_data = $product_bundles->sync_bundled_ids( $this->bundle_product->id, $this->translated_bundle_product->id );
		$this->assertTrue( !empty( $tr_bundle_data ) );
		$this->assertTrue( isset( $tr_bundle_data[ $this->translated_product_in_bundle->id ] ) );
		$this->assertEquals( $this->translated_product_in_bundle->id, $tr_bundle_data[ $this->translated_product_in_bundle->id ][ 'product_id' ] );
		$this->assertTrue( empty( $tr_bundle_data[ $this->translated_product_in_bundle->id ][ 'product_title' ] ) );
		$this->assertTrue( empty( $tr_bundle_data[ $this->translated_product_in_bundle->id ][ 'product_description' ] ) );
	}

	private function setup_product_in_bundle( $bundle_product_id, $override_title = false, $override_description = false ){

		$bundle_data = array();

		//insert simple product to bundle
		$product_title = random_string();
		$this->product_in_bundle = $this->wcml_helper->add_product( $this->default_language, false, $product_title );
		$translated_product_title = random_string();
		$this->translated_product_in_bundle = $this->wcml_helper->add_product( $this->second_language, $this->product_in_bundle->trid, $translated_product_title );

		$bundle_data[ $this->product_in_bundle->id ] = array(
			'product_id' => $this->product_in_bundle->id,
			'override_title' => 'no',
			'override_description' => 'no',
			'hide_thumbnail' => 'no',
			'optional' => 'no',
			'bundle_quantity' => 1,
			'bundle_quantity_max' => 1,
			'bundle_discount' => '',
			'visibility' => array(
				'product' => 'visible',
				'cart' => 'visible',
				'order' => 'visible'
			)
		);

		if( $override_title ){
			$bundle_data[ $this->product_in_bundle->id ][ 'override_title' ] = 'yes';
			$bundle_data[ $this->product_in_bundle->id ][ 'product_title' ] = random_string();
		}
		if( $override_description ){
			$bundle_data[ $this->product_in_bundle->id ][ 'override_description' ] = 'yes';
			$bundle_data[ $this->product_in_bundle->id ][ 'product_description' ] = random_string();
		}

		update_post_meta( $bundle_product_id, '_bundle_data', $bundle_data );

		return $bundle_data;
	}

	public function test_bundle_update(){

		$product_bundles = $this->get_test_subject();

		$this->setup_product_in_bundle( $this->bundle_product->id, true, true );
		$product_bundles->sync_bundled_ids( $this->bundle_product->id, $this->translated_bundle_product->id );

		$trnsl_title = random_string();
		$trnsl_desc = random_string();

		$data = array(
			md5( 'bundle_'.$this->product_in_bundle->id.'_title' ) => $trnsl_title,
			md5( 'bundle_'.$this->product_in_bundle->id.'_desc' ) => $trnsl_desc
		);

		$tr_bundle_data = $product_bundles->bundle_update( $this->bundle_product->id, $this->translated_bundle_product->id, $data, $this->second_language );

		$this->assertEquals( $trnsl_title, $tr_bundle_data[ $this->translated_product_in_bundle->id ][ 'product_title' ] );
		$this->assertEquals( $trnsl_desc, $tr_bundle_data[ $this->translated_product_in_bundle->id ][ 'product_description' ] );

	}

	public function test_custom_box_html_data() {

		$product_bundles = $this->get_test_subject();

		//test custom box without override title and desc
		$bundle_data = $this->setup_product_in_bundle( $this->bundle_product->id, false, false );
		$product_bundles->sync_bundled_ids( $this->bundle_product->id, $this->translated_bundle_product->id );

		$this->assertEquals( array(), $product_bundles->custom_box_html_data( array(), $this->bundle_product->id, get_post( $this->translated_bundle_product->id ), $this->second_language ) );

		//test custom box with override title and desc
		$bundle_data = $this->setup_product_in_bundle( $this->bundle_product->id, true, true );
		$product_bundles->sync_bundled_ids( $this->bundle_product->id, $this->translated_bundle_product->id );

		$expected = array(
			'bundle_'.$this->product_in_bundle->id.'_title' => array(
				'original'    => $bundle_data[ $this->product_in_bundle->id ][ 'product_title' ],
				'translation' => '',
			),
			'bundle_'.$this->product_in_bundle->id.'_desc' => array(
				'original'    => $bundle_data[ $this->product_in_bundle->id ][ 'product_description' ],
				'translation' => '',
			)
		);

		$this->assertEquals( $expected, $product_bundles->custom_box_html_data( array(), $this->bundle_product->id, get_post( $this->translated_bundle_product->id ), $this->second_language ) );
	}

	public function test_custom_box_html() {

		$bundle_product = $this->wcml_helper->add_product( $this->default_language, false, random_string() );

		$job_details = array(
			'job_type'             => 'product',
			'job_id'               => $bundle_product->id,
			'target'               => $this->second_language,
			'translation_complete' => true,
		);

		$obj = new WCML_Editor_UI_Product_Job( $job_details, $this->woocommerce_wpml, $this->sitepress, $this->wpdb );

		$product_bundles = $this->get_test_subject();

		//test custom box with override title and desc
		$bundle_data = $this->setup_product_in_bundle( $bundle_product->id, true, true );
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
				'field_type'            => 'bundle_'.$this->product_in_bundle->id.'_title',
				'field_data'            => $bundle_data[ $this->product_in_bundle->id ][ 'product_title' ],
				'field_data_translated' => '',
				'field_finished'        => '0',
			),
			array(
				'title'                 => 'Description',
				'tid'                   => '0',
				'field_style'           => '0',
				'field_type'            => 'bundle_'.$this->product_in_bundle->id.'_desc',
				'field_data'            => $bundle_data[ $this->product_in_bundle->id ][ 'product_description' ],
				'field_data_translated' => '',
				'field_finished'        => '0',
			)
		);

		$this->assertEquals( $expected, $obj->get_all_fields() );
	}


	public function test_append_bundle_data_translation_package() {

		set_current_screen( 'admin' );
		$product_bundles = $this->get_test_subject();
		$product = get_post( $this->bundle_product->id );

		$bundle_data = $this->setup_product_in_bundle( $this->bundle_product->id, true, true );

		$expected = array(
			'contents' => array(
				'product_bundles:'.$this->product_in_bundle->id.':title' => array(
					'translate' => 1,
					'data'    	=> $this->tp->encode_field_data( $bundle_data[ $this->product_in_bundle->id ][ 'product_title' ], 'base64' ),
					'format'	=> 'base64',
				),
				'product_bundles:'.$this->product_in_bundle->id.':description' => array(
					'translate' => 1,
					'data'    	=> $this->tp->encode_field_data( $bundle_data[ $this->product_in_bundle->id ][ 'product_description' ], 'base64' ),
					'format' 	=> 'base64',
				)
			)
		);

		$this->assertEquals( $expected, $product_bundles->append_bundle_data_translation_package( array(), $product ) );
		set_current_screen( 'front' );
	}

	public function test_save_bundle_data_translation() {

		$product_bundles = $this->get_test_subject();
		$product = get_post( $this->bundle_product->id );

		$bundle_data = $this->setup_product_in_bundle( $this->bundle_product->id, true, true );
		$data = array(
			'title' => array(
				'field_type' => 'product_bundles:'.$this->product_in_bundle->id.':title',
				'data'	=> random_string()
			),
			'description' => array(
				'field_type' => 'product_bundles:'.$this->product_in_bundle->id.':description',
				'data'	=> random_string()
			)
		);

		$job = new stdClass();
		$job->original_doc_id = $this->bundle_product->id;
		$job->language_code = $this->second_language;

		$product_bundles->save_bundle_data_translation( $this->translated_bundle_product->id, $data, $job );
		$this->wcml_helper->icl_clear_and_init_cache();

		$expected = array(
			$this->translated_product_in_bundle->id => array(
				'product_id' => $this->translated_product_in_bundle->id,
				'override_title' => 'yes',
				'product_title' => $data[ 'title' ][ 'data' ],
				'override_description' => 'yes',
				'product_description' => $data[ 'description' ][ 'data' ],
				'hide_thumbnail' => 'no',
				'optional' => 'no',
				'bundle_quantity' => 1,
				'bundle_quantity_max' => 1,
				'bundle_discount' => '',
				'visibility' => array(
					'product' => 'visible',
					'cart' => 'visible',
					'order' => 'visible'
					)
				)
			);

		$output = get_post_meta( $this->translated_bundle_product->id, '_bundle_data', true );
		$this->assertEquals( $expected, $output );
	}

	public function test_resync_bundle(){

		$cart_item_data = new stdClass();
		$cart_item_data->product_type = 'bundle';
		$cart_item_data->bundle_data = array();

		$cart_item = array(
			'bundled_items' => array(),
			'data' => $cart_item_data,
			'product_id' => $this->bundle_product->id,
		);

		$product_bundles = $this->get_test_subject();

		$this->sitepress->switch_lang( $this->second_language );

		$new_cart_item = $product_bundles->resync_bundle( $cart_item, false, false );

		$this->assertEquals( $this->translated_bundle_product->id, $new_cart_item[ 'data' ]->id );

		$this->sitepress->switch_lang( $this->default_language );
	}

}
