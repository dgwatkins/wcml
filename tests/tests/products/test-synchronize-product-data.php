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

	function test_duplicate_variation_data(){
		$custom_field = '_custom_field_to_test';
		$this->wcml_helper->set_custom_field_to_copy( $custom_field );

		$orig_product = $this->wcml_helper->add_product( $this->default_language, false, rand_str() );
		$es_product = $this->wcml_helper->add_product( $this->second_language, $orig_product->trid, rand_str() );

		//add values to original product
		$serialized_value = serialize( array( 'test' ) );
		add_post_meta( $orig_product->id, $custom_field, $serialized_value );

		$this->woocommerce_wpml->sync_variations_data->duplicate_variation_data( $orig_product->id, $es_product->id, array(), 'es', true );

		$this->assertEquals( $serialized_value, get_post_meta( $es_product->id, $custom_field, true ) );
	}

	function test_duplicate_attribute_empty_value(){
		$custom_field = 'attribute_pa_test';
		$this->wcml_helper->set_custom_field_to_copy( $custom_field );

		$orig_product = $this->wcml_helper->add_product( $this->default_language, false, rand_str() );
		$es_product = $this->wcml_helper->add_product( $this->second_language, $orig_product->trid, rand_str() );

		//add values to original product
		add_post_meta( $orig_product->id, $custom_field, null );

		$this->woocommerce_wpml->sync_variations_data->duplicate_variation_data( $orig_product->id, $es_product->id, array(), 'es', true );

		$this->assertEquals( '', get_post_meta( $es_product->id, $custom_field, true ) );
	}

	function test_sync_variable_custom_field_value() {

		//test variation custom fields
		$custom_field = '_variable_single_custom_field';
		$this->wcml_helper->set_custom_field_to_translate( $custom_field );

		$variation    = $this->wcml_helper->add_product_variation( $this->default_language, false );
		$trid         = $this->sitepress->get_element_trid( $variation->id, 'post_product_variation' );
		$es_variation = $this->wcml_helper->add_product_variation( $this->second_language, $trid );

		//add values to original variation
		add_post_meta( $variation->id, $custom_field, rand_str() );

		$translated_value = rand_str();

		// configure data array
		$data = array(
			md5( $custom_field . $variation->id ) => $translated_value
		);
		//configure POST data array of fields
		$_POST['data'] = http_build_query( array(
				'fields' => array(
					$custom_field . $variation->id => $translated_value
				)
			)
		);

		$this->woocommerce_wpml->sync_product_data->sync_custom_field_value( $custom_field, $data, $es_variation->id, null, $variation->id, true );
		$translated_meta = get_post_meta( $es_variation->id, $custom_field );
		$this->assertCount( count( $data ), $translated_meta );
		$this->assertEquals( $translated_value, $translated_meta[0] );
		unset( $_POST['data'] );
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

	public function test_sync_product_taxonomies_exceptions(){

		$this->sitepress->set_setting( 'sync_post_taxonomies', false );

		$default_product = $this->wcml_helper->add_product( $this->default_language, false, rand_str() );
		$translated_product = $this->wcml_helper->add_product( $this->second_language, $default_product->trid, rand_str() );

		$default_cat = $this->wcml_helper->add_term( rand_str(), 'product_type', $this->default_language, $default_product->id );
		$translated_cat = $this->wcml_helper->add_term( rand_str(), 'product_type', $this->second_language, false, $default_cat->trid  );

		$this->wpdb->insert( $this->wpdb->term_relationships, array( 'object_id' => $translated_product->id, 'term_taxonomy_id' => $translated_cat->term_taxonomy_id ) );

		$this->woocommerce_wpml->sync_product_data->sync_product_taxonomies( $default_product->id, $translated_product->id, $this->second_language );

		$this->assertEquals( get_term_meta( $default_cat->term_id, 'product_count_product_cat', true), get_term_meta( $translated_cat->term_id, 'product_count_product_cat', true) );

	}

	public function test_sync_product_taxonomies(){

		$this->sitepress->set_setting( 'sync_post_taxonomies', true );

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

}
