<?php

class Test_WCML_Attributes extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();
		global $woocommerce_wpml;

		$this->woocommerce_wpml = &$woocommerce_wpml;
		$this->woocommerce_wpml->attributes = new WCML_Attributes();
		$this->wcml_helper = new WCML_Helper();

	}

	function test_attributes_config(){

		$attr = 'color';
		$this->wcml_helper->register_attribute( $attr );
		$term = $this->wcml_helper->add_attribute_term( 'white', $attr, 'en' );
		$es_term = $this->wcml_helper->add_attribute_term( 'blanco', $attr, 'es', $term['trid'] );

		$_POST[ 'wcml-is-translatable-attr' ] = 1;
		$this->woocommerce_wpml->attributes->set_attribute_readonly_config( 1, array( 'attribute_name' => $attr ) );
		$this->assertEquals( 1, $this->woocommerce_wpml->attributes->is_translatable_attribute( $attr ) );
		$attr_terms = $this->woocommerce_wpml->attributes->get_attribute_terms( 'pa_'.$attr );
		$this->assertEquals( 2, count( $attr_terms ) );


		unset( $_POST[ 'wcml-is-translatable-attr' ] );
		$this->woocommerce_wpml->attributes->set_attribute_readonly_config( 1, array( 'attribute_name' => $attr ) );
		$this->assertEquals( 0, $this->woocommerce_wpml->attributes->is_translatable_attribute( $attr ) );

		$attr_terms = $this->woocommerce_wpml->attributes->get_attribute_terms( 'pa_'.$attr );
		$this->assertEquals( 1, count( $attr_terms ) );
	}

}