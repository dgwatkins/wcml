<?php

class Test_WCML_Attributes extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();
	}

	function test_attributes_config(){

		$attr = 'color';
		$this->wcml_helper->register_attribute( $attr );
		$term = $this->wcml_helper->add_attribute_term( 'white', $attr, 'en' );
		$es_term = $this->wcml_helper->add_attribute_term( 'blanco', $attr, 'es', $term['trid'] );

		$_POST[ 'wcml-is-translatable-attr' ] = 1;
		$attr_formated = wc_attribute_taxonomy_name( $attr );
		$this->woocommerce_wpml->attributes->set_attribute_readonly_config( 1, array( 'attribute_name' => $attr ) );
		$this->assertEquals( 1, $this->woocommerce_wpml->attributes->is_translatable_attribute( $attr_formated ) );
		$attr_terms = $this->woocommerce_wpml->attributes->get_attribute_terms($attr_formated );
		$this->assertEquals( 2, count( $attr_terms ) );


		unset( $_POST[ 'wcml-is-translatable-attr' ] );
		$this->woocommerce_wpml->attributes->set_attribute_readonly_config( 1, array( 'attribute_name' => $attr ) );
		$this->assertEquals( 0, $this->woocommerce_wpml->attributes->is_translatable_attribute( $attr_formated ) );

		$attr_terms = $this->woocommerce_wpml->attributes->get_attribute_terms( $attr_formated );
		$this->assertEquals( 1, count( $attr_terms ) );
	}

	function test_set_attribute_config_in_wpml_settings(){

		$sync_settings = $this->sitepress->get_setting( 'taxonomies_sync_option', array() );
		$this->woocommerce_wpml->attributes->set_attribute_config_in_wpml_settings( 'pa_size', 1 );
		$updated_sync_settings = $this->sitepress->get_setting( 'taxonomies_sync_option', array() );

		$this->assertEquals( 1, $updated_sync_settings['pa_size'] );
		$this->assertEquals( count( $sync_settings ) + 1, count( $updated_sync_settings ) );


	}

}