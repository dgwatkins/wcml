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

	function test_attributes_config_while_saving_existing(){

		$_POST[ 'wcml-is-translatable-attr' ] = 1;
		$_POST[ 'save_attribute' ] = 1;
		$_POST[ 'attribute_name' ] = $this->attr;
		$id = 10;
		$data = array(
			'attribute_name' => $_POST[ 'attribute_name' ]
		);
		$attr_formated = wc_attribute_taxonomy_name( $this->attr );
		$this->woocommerce_wpml->attributes->set_attribute_readonly_config( $id, $data, $this->attr );
		$this->assertEquals( 1, $this->woocommerce_wpml->attributes->is_translatable_attribute( $attr_formated ) );
		$attr_terms = $this->woocommerce_wpml->attributes->get_attribute_terms($attr_formated );
		$this->assertEquals( count( $this->sitepress->get_active_languages() ), count( $attr_terms ) );

		unset( $_POST[ 'wcml-is-translatable-attr' ] );
		unset( $_POST[ 'save_attribute' ] );
		unset( $_POST[ 'attribute_name' ] );
	}

	function test_attributes_config_while_adding_new(){

		$_POST[ 'wcml-is-translatable-attr' ] = 1;
		$_POST[ 'add_new_attribute' ] = 1;
		$_POST[ 'attribute_name' ] = '';
		$_POST[ 'attribute_label' ] = $this->attr;
		$id = 10;
		$data = array(
			'attribute_name' => $_POST[ 'attribute_name' ]
		);
		$attr_formated = wc_attribute_taxonomy_name( $this->attr );
		$this->woocommerce_wpml->attributes->set_attribute_readonly_config( $id, $data );
		$this->assertEquals( 1, $this->woocommerce_wpml->attributes->is_translatable_attribute( $attr_formated ) );
		$attr_terms = $this->woocommerce_wpml->attributes->get_attribute_terms($attr_formated );
		$this->assertEquals( count( $this->sitepress->get_active_languages() ), count( $attr_terms ) );


		unset( $_POST[ 'wcml-is-translatable-attr' ] );
		unset( $_POST[ 'add_new_attribute' ] );
		unset( $_POST[ 'attribute_name' ] );
		unset( $_POST[ 'attribute_label' ] );
	}

	function test_attributes_config_when_attribute_not_translatable(){

		$_POST[ 'save_attribute' ] = 1;
		$_POST[ 'attribute_name' ] = $this->attr;
		$id = 10;
		$data = array(
			'attribute_name' => $_POST[ 'attribute_name' ]
		);
		$attr_formated = wc_attribute_taxonomy_name( $this->attr );
		$this->woocommerce_wpml->attributes->set_attribute_readonly_config( $id, $data );
		$this->assertEquals( 0, $this->woocommerce_wpml->attributes->is_translatable_attribute( $attr_formated ) );

		$attr_terms = $this->woocommerce_wpml->attributes->get_attribute_terms( $attr_formated );
		$this->assertEquals( 1, count( $attr_terms ) );
		unset( $_POST[ 'save_attribute' ] );
		unset( $_POST[ 'attribute_name' ] );
	}

	function test_set_attribute_config_in_wpml_settings(){

		$sync_settings = $this->sitepress->get_setting( 'taxonomies_sync_option', array() );
		$this->woocommerce_wpml->attributes->set_attribute_config_in_wpml_settings( 'pa_size', 1 );
		$updated_sync_settings = $this->sitepress->get_setting( 'taxonomies_sync_option', array() );

		$this->assertEquals( 1, $updated_sync_settings['pa_size'] );
		$this->assertEquals( count( $sync_settings ) + 1, count( $updated_sync_settings ) );


	}

	/**
	 * @group wcml-3272
	 */
	function test_is_attributes_fully_translated(){

		$_POST[ 'wcml-is-translatable-attr' ] = 1;
		$_POST[ 'save_attribute' ] = 1;
		$_POST[ 'attribute_name' ] = $this->attr;
		$id = 10;
		$data = array(
			'attribute_name' => $_POST[ 'attribute_name' ]
		);
		$this->woocommerce_wpml->attributes->set_attribute_readonly_config( $id, $data );

		self::clear_attribute_taxonomies_cache();
		$is_fully_translated = $this->woocommerce_wpml->attributes->is_attributes_fully_translated();

		$this->assertTrue( $is_fully_translated );

		$term = $this->wcml_helper->add_attribute_term( 'black', $this->attr, 'en' );
		$this->woocommerce_wpml->terms->update_terms_translated_status( wc_attribute_taxonomy_name( $this->attr ) );

		self::clear_attribute_taxonomies_cache();
		$is_fully_translated = $this->woocommerce_wpml->attributes->is_attributes_fully_translated();

		$this->assertFalse( $is_fully_translated );

		unset( $_POST[ 'wcml-is-translatable-attr' ] );
		unset( $_POST[ 'attribute_name' ] );
		unset( $_POST[ 'save_attribute' ] );
	}

	/**
	 * @see wc_get_attribute_taxonomies
	 */
	public static function clear_attribute_taxonomies_cache() {
		$prefix    = WC_Cache_Helper::get_cache_prefix( 'woocommerce-attributes' );
		$cache_key = $prefix . 'attributes';
		wp_cache_delete( $cache_key, 'woocommerce-attributes' );
	}
}