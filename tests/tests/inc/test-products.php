<?php

class Test_WCML_Products extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();
		global $woocommerce_wpml, $wpml_post_translations, $wpml_term_translations;

		require_once WCML_PLUGIN_PATH . '/inc/products.class.php';
		$woocommerce_wpml->products           = new WCML_Products;

		//add products for tests
		wpml_test_reg_custom_post_type( 'product' );
		$settings_helper = wpml_load_settings_helper();
		$settings_helper->set_post_type_translatable( 'product'  );
		$this->orig_product = wpml_test_insert_post( 'en', 'product', false, 'product 1' );

		$trid = $wpml_post_translations->get_element_trid( $this->orig_product  );
		$this->es_product = wpml_test_insert_post( 'es', 'product', $trid, 'producto 1' );

		//add global attribute for tests
		$taxonomy   = 'pa_color';
		wpml_test_reg_custom_taxonomy( $taxonomy );
		$settings_helper = wpml_load_settings_helper();
		$settings_helper->set_taxonomy_translatable( $taxonomy );
		$this->orig_term = wpml_test_insert_term( 'en', $taxonomy, false, 'white' );

		$ttid_org = $this->orig_term[ 'term_taxonomy_id' ];
		$trid     = $wpml_term_translations->get_element_trid( $ttid_org );
		$this->es_term = wpml_test_insert_term( 'es', $taxonomy, $trid, 'blanco' );


	}

	function test_get_cart_attribute_translation(){
		global $woocommerce_wpml, $wpml_term_translations;

		//test global attribute
		$trnsl_attr = $woocommerce_wpml->products->get_cart_attribute_translation( 'attribute_pa_color', 'white', false, 'es', false, false );

		$this->assertEquals( 'blanco', $trnsl_attr );

		//test local attribute
		$variation_id = wpml_test_insert_post( 'es', 'product_variation', false, 'variation 1' );

		add_post_meta( $variation_id, 'attribute_pa_size', 'medio' );
		$trnsl_attr = $woocommerce_wpml->products->get_cart_attribute_translation( 'attribute_pa_size', 'medio', $variation_id, 'es', false, false );

		$this->assertEquals( 'medio', $trnsl_attr );

		//test local attribute with variation set to any

		$orig_attrs = array(
			'size' =>
				array(
					'name' => 'Size' ,
					'value' => 'small | medium',
					'is_taxonomy' => 0
				));
		add_post_meta( $this->orig_product , '_product_attributes', $orig_attrs );

		$trnsl_attrs = array(
			'size' =>
				array(
					'name' => 'Size' ,
					'value' => 'pequena | medio',
					'is_taxonomy' => 0
			));
		add_post_meta( $this->es_product , '_product_attributes', $trnsl_attrs );

		$variation_id = wpml_test_insert_post( 'es', 'product_variation', false, 'variation 1' );
		add_post_meta( $variation_id, 'attribute_size', '' );

		$trnsl_attr = $woocommerce_wpml->products->get_cart_attribute_translation( 'attribute_size', 'small', $variation_id, 'es', $this->orig_product , $this->es_product );

		$this->assertEquals( 'pequena', $trnsl_attr );

	}

	function test_sync_parent_products_transients(){
		global $woocommerce_wpml, $pagenow;

		$pagenow = 'post-new.php';

		add_action( 'save_post', array( $woocommerce_wpml->products, 'sync_post_action' ), 110, 2 );

		$parent_product = WCML_Helper::add_product('Parent Product EN' , 'en');
		$child = array(
			'post_title' 	=> 'Child Product EN',
			'post_status'	=> 'publish',
			'post_parent'	=> $parent_product->product_id
		);
		$child_product = WCML_Helper::add_product( $child , 'en');


		$parent_product_es = WCML_Helper::add_product('Parent Product ES' , 'es', $parent_product->trid);
		$child_es = array(
			'post_title' 	=> 'Child Product ES',
			'post_status'	=> 'publish',
			'post_parent'	=> $parent_product_es->product_id
		);
		$child_product_es = WCML_Helper::add_product( $child_es , 'es', $child_product->trid);


		$grouped_es = new WC_Product_Grouped($parent_product_es->product_id);
		$this->assertEquals(array( $child_product_es->product_id ), $grouped_es->get_children());


		// Setting the child status to private should reset the children list transient for translated parent
		$child = array(
			'ID'			=> $child_product->product_id,
			'post_title' 	=> 'Child Product EN MADE PRIVATE',
			'post_status'	=> 'private',
		);
		WCML_Helper::update_product( $child );

		// FORCE status on translated child - should be synced autoamtically
		$child_es = array(
			'ID'			=> $child_product_es->product_id,
			'post_title' 	=> 'Child Product ES MADE PRIVATE',
			'post_status'	=> 'private',
		);
		WCML_Helper::update_product( $child_es );

		$grouped_es = new WC_Product_Grouped($parent_product_es->product_id); //need to reinstantiate

		$this->assertEquals(array(), $grouped_es->get_children());

	}

}