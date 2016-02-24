<?php

class Test_WCML_Products extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();
		global $woocommerce_wpml, $wpml_post_translations, $wpml_term_translations;

		$this->woocommerce_wpml = &$woocommerce_wpml;
		require_once WCML_PLUGIN_PATH . '/inc/products.class.php';
		$this->woocommerce_wpml->products           = new WCML_Products;
		$this->wcml_helper = new WCML_Helper();

		//add product for tests
		$orig_product = $this->wcml_helper->add_product( 'en', false, 'product 1' );
		$this->orig_product_id = $orig_product->id;

		$es_product = $this->wcml_helper->add_product( 'es', $orig_product->trid, 'producto 1' );
		$this->es_product_id = $es_product->id;

		//add global attribute for tests
		$attr = 'color';
		$this->wcml_helper->register_attribute( $attr );
		$term = $this->wcml_helper->add_attribute_term( 'white', $attr, 'en' );
		$es_term = $this->wcml_helper->add_attribute_term( 'blanco', $attr, 'es', $term['trid'] );

	}

	function test_get_cart_attribute_translation(){
		global $wpml_term_translations;

		//test global attribute
		$trnsl_attr = $this->woocommerce_wpml->products->get_cart_attribute_translation( 'attribute_pa_color', 'white', false, 'es', false, false );

		$this->assertEquals( 'blanco', $trnsl_attr );

		//test local attribute
		$variation = $this->wcml_helper->add_product_variation( 'es', false );
		$variation_id = $variation->id;

		add_post_meta( $variation_id, 'attribute_pa_size', 'medio' );
		$trnsl_attr = $this->woocommerce_wpml->products->get_cart_attribute_translation( 'attribute_pa_size', 'medio', $variation_id, 'es', false, false );

		$this->assertEquals( 'medio', $trnsl_attr );

		//test local attribute with variation set to any

		$this->wcml_helper->add_local_attribute( $this->orig_product_id, 'Size', 'small | medium' );

		$this->wcml_helper->add_local_attribute( $this->es_product_id, 'Size', 'pequena | medio' );

		$variation = $this->wcml_helper->add_product_variation( 'es', false );
		$variation_id = $variation->id;
		add_post_meta( $variation_id, 'attribute_size', '' );

		$trnsl_attr = $this->woocommerce_wpml->products->get_cart_attribute_translation( 'attribute_size', 'small', $variation_id, 'es', $this->orig_product_id , $this->es_product_id );

		$this->assertEquals( 'pequena', $trnsl_attr );

	}

	function test_sync_parent_products_transients(){
		global $pagenow;

		$pagenow = 'post-new.php';

		$parent_product = $this->wcml_helper->add_product( 'en', false, 'Parent Product EN' );
		$child_product = $this->wcml_helper->add_product( 'en', false, 'Child Product EN', $parent_product->id );


		$parent_product_es = $this->wcml_helper->add_product( 'es', $parent_product->trid, 'Parent Product ES' );
		$child_product_es = $this->wcml_helper->add_product( 'es', $child_product->trid, 'Child Product ES', $parent_product_es->id );


		$grouped_es = new WC_Product_Grouped($parent_product_es->id);
		$this->assertEquals(array( $child_product_es->id ), $grouped_es->get_children());


		// Setting the child status to private should reset the children list transient for translated parent
		$child = array(
			'ID'			=> $child_product->id,
			'post_title' 	=> 'Child Product EN MADE PRIVATE',
			'post_status'	=> 'private',
		);
		$this->wcml_helper->update_product( $child );

		// FORCE status on translated child - should be synced autoamtically
		$child_es = array(
			'ID'			=> $child_product_es->id,
			'post_title' 	=> 'Child Product ES MADE PRIVATE',
			'post_status'	=> 'private',
		);
		$this->wcml_helper->update_product( $child_es );

		$this->woocommerce_wpml->products->sync_linked_products( $child_product->id, $child_product_es->id, 'es' );

		$grouped_es = new WC_Product_Grouped( $parent_product_es->id ); //need to reinstantiate

		$this->assertEquals( array(), $grouped_es->get_children() );

	}

}