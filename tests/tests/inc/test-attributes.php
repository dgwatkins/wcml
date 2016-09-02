<?php

class Test_WCML_Attributes extends WCML_UnitTestCase {

	private $attr;

	function setUp(){
		parent::setUp();

		$this->attr = 'color';
		$this->wcml_helper->register_attribute( $this->attr );

		$term = $this->wcml_helper->add_attribute_term( 'white', $this->attr, 'en' );

		foreach( $this->sitepress->get_active_languages() as $language ){
			if( $language['code'] != 'en' )	$this->wcml_helper->add_attribute_term( rand_str(), $this->attr, $language['code'], $term['trid'] );
		}

	}

	function test_attributes_config(){

		$_POST[ 'wcml-is-translatable-attr' ] = 1;
		$attr_formated = wc_attribute_taxonomy_name( $this->attr );
		$this->woocommerce_wpml->attributes->set_attribute_readonly_config( 1, array( 'attribute_name' => $this->attr ) );
		$this->assertEquals( 1, $this->woocommerce_wpml->attributes->is_translatable_attribute( $attr_formated ) );
		$attr_terms = $this->woocommerce_wpml->attributes->get_attribute_terms($attr_formated );
		$this->assertEquals( count( $this->sitepress->get_active_languages() ), count( $attr_terms ) );


		unset( $_POST[ 'wcml-is-translatable-attr' ] );
		$this->woocommerce_wpml->attributes->set_attribute_readonly_config( 1, array( 'attribute_name' => $this->attr ) );
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

	function test_is_attributes_fully_translated(){

		$_POST[ 'wcml-is-translatable-attr' ] = 1;
		$this->woocommerce_wpml->attributes->set_attribute_readonly_config( 1, array( 'attribute_name' => $this->attr ) );

		$is_fully_translated = $this->woocommerce_wpml->attributes->is_attributes_fully_translated();

		$this->assertTrue( $is_fully_translated );

		$term = $this->wcml_helper->add_attribute_term( 'black', $this->attr, 'en' );
		$this->woocommerce_wpml->terms->update_terms_translated_status( wc_attribute_taxonomy_name( $this->attr ) );
		$is_fully_translated = $this->woocommerce_wpml->attributes->is_attributes_fully_translated();

		$this->assertFalse( $is_fully_translated );

	}

}