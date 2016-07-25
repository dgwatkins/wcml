<?php

class Test_WCML_Synchronize_Product_Data extends WCML_UnitTestCase {

	function setUp() {
		parent::setUp();

		//add product for tests
		$orig_product = $this->wcml_helper->add_product( 'en', false, 'product 1' );
		$this->orig_product_id = $orig_product->id;

		$es_product = $this->wcml_helper->add_product( 'es', $orig_product->trid, 'producto 1' );
		$this->es_product_id = $es_product->id;
	}

	function test_duplicate_product_post_meta() {
		$custom_field = '_custom_field_to_test';

		$this->wcml_helper->set_custom_field_to_translate( $custom_field );
		//add values to original product
		add_post_meta( $this->orig_product_id, $custom_field, rand_str() );
		add_post_meta( $this->orig_product_id, $custom_field, rand_str() );

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

		$this->woocommerce_wpml->sync_product_data->duplicate_product_post_meta( $this->orig_product_id, $this->es_product_id, $data );
		$translated_meta = get_post_meta( $this->es_product_id, $custom_field );
		$this->assertEquals( count( $data ), count( $translated_meta ) );
		$this->assertEquals( $translated_value_for_first_field, $translated_meta[ 0 ] );
		$this->assertEquals( $translated_value_for_second_field, $translated_meta[ 1 ] );

		//test updating values
		$translated_mid_ids = $this->woocommerce_wpml->products->get_mid_ids_by_key( $this->es_product_id, $custom_field );
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

		$this->woocommerce_wpml->sync_product_data->duplicate_product_post_meta( $this->orig_product_id, $this->es_product_id, $data );
		$translated_meta = get_post_meta( $this->es_product_id, $custom_field );
		$this->assertEquals( count( $data ), count( $translated_meta ) );
		$this->assertEquals( $values_to_update[ 0 ], $translated_meta[ 0 ] );
		$this->assertEquals( $values_to_update[ 1 ], $translated_meta[ 1 ] );

	}

}