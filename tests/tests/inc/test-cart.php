<?php

class Test_WCML_Cart extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();

		//add product for tests
		$orig_product = $this->wcml_helper->add_product( 'en', false, 'product 1' );
		$this->orig_product_id = $orig_product->id;

		$es_product = $this->wcml_helper->add_product( 'es', $orig_product->trid, 'producto 1' );
		$this->es_product_id = $es_product->id;

		//add global attribute for tests
		$attr = 'size';
		$this->wcml_helper->register_attribute( $attr );
		$term = $this->wcml_helper->add_attribute_term( 'medium', $attr, 'en' );
		$es_term = $this->wcml_helper->add_attribute_term( 'medio', $attr, 'es', $term['trid'] );
	}

	function test_get_cart_attribute_translation(){

		//test global attribute
		$trnsl_attr = $this->woocommerce_wpml->cart->get_cart_attribute_translation( 'attribute_pa_size', 'medium', false, 'es', false, false );

		$this->assertEquals( 'medio', $trnsl_attr );

		//test variation global attribute
		$variation = $this->wcml_helper->add_product_variation( 'es', false );
		$variation_id = $variation->id;

		add_post_meta( $variation_id, 'attribute_pa_size', 'medio' );
		$trnsl_attr = $this->woocommerce_wpml->cart->get_cart_attribute_translation( 'attribute_pa_size', 'medio', $variation_id, 'es', false, false );

		$this->assertEquals( 'medio', $trnsl_attr );

		//test local attribute with variation set to any

		$this->wcml_helper->add_local_attribute( $this->orig_product_id, 'Size', 'small | medium' );

		$this->wcml_helper->add_local_attribute( $this->es_product_id, 'Size', 'pequena | medio' );

		$variation = $this->wcml_helper->add_product_variation( 'es', false );
		$variation_id = $variation->id;
		add_post_meta( $variation_id, 'attribute_size', '' );

		$trnsl_attr = $this->woocommerce_wpml->cart->get_cart_attribute_translation( 'attribute_size', 'small', $variation_id, 'es', $this->orig_product_id , $this->es_product_id );

		$this->assertEquals( 'pequena', $trnsl_attr );

	}

}