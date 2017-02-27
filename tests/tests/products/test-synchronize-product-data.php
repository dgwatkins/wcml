<?php

class Test_WCML_Synchronize_Product_Data extends WCML_UnitTestCase {

	private $test_data;
	private $default_language;
	private $second_language;

	function setUp() {
		parent::setUp();

		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';
		$this->test_data = new stdClass();
		//add product for tests
		$this->test_data->orig_product = $this->wcml_helper->add_product( $this->default_language, false, 'product 1' );
		$this->test_data->es_product = $this->wcml_helper->add_product( $this->second_language, $this->test_data->orig_product->trid, 'producto 1' );

		$this->woocommerce_wpml->sync_variations_data = new WCML_Synchronize_Variations_Data( $this->woocommerce_wpml, $this->sitepress, $this->wpdb );
	}

	function test_duplicate_product_post_meta() {
		$custom_field = '_custom_field_to_test';

		$this->wcml_helper->set_custom_field_to_translate( $custom_field );
		//add values to original product
		add_post_meta( $this->test_data->orig_product->id, $custom_field, rand_str() );
		add_post_meta( $this->test_data->orig_product->id, $custom_field, rand_str() );

		$translated_value_for_first_field = rand_str();
		$translated_value_for_second_field = rand_str();

		// test new values
		// configure data array
		$data = array(
			md5( $custom_field.':new0' ) => $translated_value_for_first_field,
			md5( $custom_field.':new1' ) => $translated_value_for_second_field
		);
		//configure POST data array of fields
		$_POST['data'] = array(
			'fields' => array(
				$custom_field.':new0' => $translated_value_for_first_field,
				$custom_field.':new1' => $translated_value_for_second_field
			)
		);

		$this->woocommerce_wpml->sync_product_data->duplicate_product_post_meta( $this->test_data->orig_product->id, $this->test_data->es_product->id, $data );
		$translated_meta = get_post_meta( $this->test_data->es_product->id, $custom_field );
		$this->assertCount( count( $data ), $translated_meta );
		$this->assertEquals( $translated_value_for_first_field, $translated_meta[ 0 ] );
		$this->assertEquals( $translated_value_for_second_field, $translated_meta[ 1 ] );

		//test updating values
		$translated_mid_ids = $this->woocommerce_wpml->products->get_mid_ids_by_key( $this->test_data->es_product->id, $custom_field );
		$values_to_update = array();
		$data = array();
		$_POST = array();
		foreach( $translated_mid_ids as $mid_id ){
			$value = rand_str();
			$values_to_update[] = $value;

			// configure data array
			$data[ md5( $custom_field.':'.$mid_id ) ] = $value ;
			//configure POST data array of fields
			$_POST['data'][ 'fields' ][ $custom_field.':'.$mid_id ] = $value ;
		}

		$this->woocommerce_wpml->sync_product_data->duplicate_product_post_meta( $this->test_data->orig_product->id, $this->test_data->es_product->id, $data );
		$translated_meta = get_post_meta( $this->test_data->es_product->id, $custom_field );
		$this->assertCount( count( $data ), $translated_meta );
		$this->assertEquals( $values_to_update[ 0 ], $translated_meta[ 0 ] );
		$this->assertEquals( $values_to_update[ 1 ], $translated_meta[ 1 ] );
	}

	function test_sync_custom_field_value(){

		//test variation custom fields
		$custom_field = '_variable_single_custom_field';
		$this->wcml_helper->set_custom_field_to_translate( $custom_field );

		$variation = $this->wcml_helper->add_product_variation( $this->default_language, false );
		$trid = $this->sitepress->get_element_trid( $variation->id, 'post_product_variation' );
		$es_variation = $this->wcml_helper->add_product_variation( $this->second_language, $trid );

		//add values to original variation
		add_post_meta( $variation->id, $custom_field, rand_str() );

		$translated_value = rand_str();

		// configure data array
		$data = array(
			md5( $custom_field.$variation->id ) => $translated_value
		);
		//configure POST data array of fields
		$_POST['data'] = array(
			'fields' => array(
				$custom_field.$variation->id => $translated_value
			)
		);

		$this->woocommerce_wpml->sync_product_data->sync_custom_field_value( $custom_field, $data, $es_variation->id, null, $variation->id, true );
		$translated_meta = get_post_meta( $es_variation->id, $custom_field );
		$this->assertCount( count( $data ), $translated_meta );
		$this->assertEquals( $translated_value, $translated_meta[ 0 ] );


		//test custom filed with multiple values
		$custom_field = '_variable_multiple_custom_field';
		$this->wcml_helper->set_custom_field_to_translate( $custom_field );
		add_post_meta( $variation->id, $custom_field, rand_str() );

		$translated_value_for_first_field = rand_str();
		$translated_value_for_second_field = rand_str();

		// test new values
		// configure data array
		$data = array(
			md5( $custom_field.$variation->id.':new0' ) => $translated_value_for_first_field,
			md5( $custom_field.$variation->id.':new1' ) => $translated_value_for_second_field
		);
		//configure POST data array of fields
		$_POST['data'] = array(
			'fields' => array(
				$custom_field.$variation->id.':new0' => $translated_value_for_first_field,
				$custom_field.$variation->id.':new1' => $translated_value_for_second_field
			)
		);

		$this->woocommerce_wpml->sync_product_data->sync_custom_field_value( $custom_field, $data, $es_variation->id, null, $variation->id, true );
		$translated_meta = get_post_meta( $es_variation->id, $custom_field );
		$this->assertCount( count( $data ), $translated_meta );
		$this->assertEquals( $translated_value_for_first_field, $translated_meta[ 0 ] );
		$this->assertEquals( $translated_value_for_second_field, $translated_meta[ 1 ] );

	}

	public function test_icl_connect_translations_action(){

		$custom_field = 'test_custom_1';

		$en_product = $this->wcml_helper->add_product( $this->default_language, false, rand_str() );
		update_post_meta( $en_product->id, '_regular_price', 100 );
		update_post_meta( $en_product->id, $custom_field, 'custom_value' );
		$this->wcml_helper->set_custom_field_to_copy( $custom_field );

		$es_product = $this->wcml_helper->add_product( $this->second_language, false, rand_str() );

		$_POST['icl_ajx_action'] = 'connect_translations';
		$_POST['new_trid'] = $es_product->trid;
		$_POST['post_type'] = 'product';
		$_POST['post_id'] = $en_product->id;
		$_POST['set_as_source'] = true;

		$this->woocommerce_wpml->sync_product_data->icl_connect_translations_action();
		wp_cache_init();

		$this->assertEquals( 'custom_value', get_post_meta( $es_product->id, $custom_field, true ) );

		update_post_meta( $es_product->id, $custom_field, 'custom_value_es' );

		$this->sitepress->switch_lang( $this->second_language );
		$_POST['icl_ajx_action'] = 'connect_translations';
		$_POST['new_trid'] = $es_product->trid;
		$_POST['post_type'] = 'product';
		$_POST['post_id'] = $en_product->id;
		$_POST['set_as_source'] = false;

		$this->woocommerce_wpml->sync_product_data->icl_connect_translations_action();
		wp_cache_init();

		$this->assertEquals( 'custom_value_es', get_post_meta( $en_product->id, $custom_field, true ) );

	}

	function test_check_if_product_fields_sync_needed(){

		$sync_needed = $this->woocommerce_wpml->sync_product_data->check_if_product_fields_sync_needed( $this->test_data->orig_product->id, $this->test_data->es_product->id, 'postmeta_fields' );
		$this->assertTrue( $sync_needed );
		wp_cache_init();

		$sync_needed = $this->woocommerce_wpml->sync_product_data->check_if_product_fields_sync_needed( $this->test_data->orig_product->id, $this->test_data->es_product->id, 'postmeta_fields' );
		$this->assertFalse( $sync_needed );
		wp_cache_init();

		update_post_meta( $this->test_data->orig_product->id, '_regular_price', 100 );
		$sync_needed = $this->woocommerce_wpml->sync_product_data->check_if_product_fields_sync_needed( $this->test_data->orig_product->id, $this->test_data->es_product->id, 'postmeta_fields' );
		$this->assertTrue( $sync_needed );

	}

	public function test_sync_product_taxonomies(){

		$default_product = $this->wcml_helper->add_product( $this->default_language, false, rand_str() );
		$translated_product = $this->wcml_helper->add_product( $this->second_language, $default_product->trid, rand_str() );

		$default_cat = $this->wcml_helper->add_term( rand_str(), 'product_cat', $this->default_language, $default_product->id );
		$translated_cat = $this->wcml_helper->add_term( rand_str(), 'product_cat', $this->second_language, false, $default_cat->trid  );

		$this->wpdb->insert( $this->wpdb->term_relationships, array( 'object_id' => $translated_product->id, 'term_taxonomy_id' => $translated_cat->term_taxonomy_id ) );

		$this->woocommerce_wpml->sync_product_data->sync_product_taxonomies( $default_product->id, $translated_product->id, $this->second_language );

		$this->assertEquals( get_term_meta( $default_cat->term_id, 'product_count_product_cat', true), get_term_meta( $translated_cat->term_id, 'product_count_product_cat', true) );

		$this->wpdb->query( $this->wpdb->prepare( "DELETE FROM {$this->wpdb->term_relationships} WHERE object_id = %d AND term_taxonomy_id = %d", $default_product->id, $default_cat->term_taxonomy_id ) );

		$this->woocommerce_wpml->sync_product_data->delete_term_relationships_update_term_count( $default_product->id, array( $default_cat->term_taxonomy_id ) );

		$this->assertEquals( get_term_meta( $default_cat->term_id, 'product_count_product_cat', true), get_term_meta( $translated_cat->term_id, 'product_count_product_cat', true) );
	}

	public function test_sync_product_stocks(){

		$orig_product_id = $this->test_data->orig_product->id;
		$es_product_id = $this->test_data->es_product->id;

		update_post_meta( $orig_product_id, '_manage_stock', 'yes' );
		$orig_product = wc_get_product( $orig_product_id );
		WooCommerce_Functions_Wrapper::set_stock( $orig_product_id, 99 );
		$orig_product->set_stock_status( 'instock' );

		update_post_meta( $es_product_id, '_manage_stock', 'yes' );
		$es_product = wc_get_product( $es_product_id );
		WooCommerce_Functions_Wrapper::set_stock( $es_product_id, 99 );
		$es_product->set_stock_status( 'instock' );

		$order = WCML_Helper_Orders::create_order( array( 'product_id' => $orig_product_id ) );
		$this->woocommerce_wpml->sync_product_data->sync_product_stocks( $order, 'reduce' );

		$orig_product = wc_get_product( $orig_product_id );
		$this->assertEquals( 98, $orig_product->get_stock_quantity() );

		$es_product = wc_get_product( $es_product_id );
		$this->assertEquals( 98, $es_product->get_stock_quantity() );
	}

}
