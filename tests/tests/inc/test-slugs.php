<?php

class Test_WCML_Slugs extends WCML_UnitTestCase {


	function setUp(){
		parent::setUp();

		global $WPML_String_Translation;
		$WPML_String_Translation->init_active_languages();

		$this->wc_permalinks = get_option( 'woocommerce_permalinks' );

	}

	function test_translate_product_slug() {

		$iclsettings = $this->sitepress->get_settings();

		$iclsettings['posts_slug_translation']['on'] = 1;
		$iclsettings['posts_slug_translation']['types']['test_type'] = 1;

		$this->sitepress->save_settings( $iclsettings );

		$this->woocommerce_wpml->url_translation->translate_product_base();

		$icl_settings_updated = $this->sitepress->get_settings();

		$this->assertEquals( 1, $icl_settings_updated['posts_slug_translation']['types']['test_type'] );
	}

	function test_get_woocommerce_product_base(){

		$this->woocommerce_wpml->url_translation->wc_permalinks['product_base'] = '/test_slug';

		$this->assertEquals( 'test_slug', $this->woocommerce_wpml->url_translation->get_woocommerce_product_base() );

		$this->woocommerce_wpml->url_translation->wc_permalinks['product_base'] = '';

		update_option( 'woocommerce_product_slug', 'test_slug2' );

		$this->assertEquals( 'test_slug2', $this->woocommerce_wpml->url_translation->get_woocommerce_product_base() );

		$this->woocommerce_wpml->url_translation->wc_permalinks['product_base'] = '';

		update_option( 'woocommerce_product_slug', false );

		$this->assertEquals( 'product', $this->woocommerce_wpml->url_translation->get_woocommerce_product_base() );

	}

	function test_get_translated_tax_slug(){
		$category_base = !empty( $this->wc_permalinks['category_base'] ) ? trim( $this->wc_permalinks['category_base'], '/' ) : 'product-category';
		$name = $this->woocommerce_wpml->url_translation->url_string_name( 'product_cat' );
		do_action( 'wpml_register_single_string', $this->woocommerce_wpml->url_translation->url_strings_context(), $name, $category_base );
		$string_id = icl_get_string_id( $category_base, $this->woocommerce_wpml->url_translation->url_strings_context(), $name );

		icl_add_string_translation( $string_id, 'es', 'categoria-producto', ICL_TM_COMPLETE );
		$this->wcml_helper->icl_clear_and_init_cache( 'es' );
		$translated_tax = $this->woocommerce_wpml->url_translation->get_translated_tax_slug('product_cat','es');

		$this->assertTrue( (bool) has_filter('wpml_translate_single_string') );
		$this->assertEquals( 'categoria-producto', $translated_tax['translated_slug'] );

	}

	function test_translate_taxonomy_base(){
		global $wpml_term_translations, $wp_rewrite;

		$wp_rewrite->use_trailing_slashes = true;

		$taxonomy = 'product_cat';

		$tax_term = wp_insert_term( 'test_cat1', $taxonomy );
		$ttid = $tax_term[ 'term_taxonomy_id' ];

		$this->sitepress->set_element_language_details( $ttid, 'tax_'.$taxonomy , false, $this->sitepress->get_default_language() );
		$trid = $wpml_term_translations->get_element_trid( $ttid );

		$translated_language = 'es';
		$tr_tax_term = wp_insert_term( 'test_cat1_es', $taxonomy );
		$tr_ttid = $tr_tax_term[ 'term_taxonomy_id' ];

		$this->sitepress->set_element_language_details( $tr_ttid, 'tax_'.$taxonomy, $trid, $translated_language );

		$this->assertEquals(
			'http://'.WP_TESTS_DOMAIN.'/?product_cat=test_cat1_es' ,
			$this->woocommerce_wpml->url_translation->translate_taxonomy_base(
				get_term_link( $tr_tax_term['term_id'], $taxonomy ),
				get_term( $tr_tax_term['term_id'], $taxonomy ),
				$taxonomy
			) )
		;

	}

	// note - make sure you have .mo files in wp-content->languages directory in your wordpress test folder
	// the unit tests use a custom WP_LANG_DIR value wordpress-tests-lib/data/languages
	function test_add_default_slug_translations(){

		$category_base = !empty( $this->wc_permalinks['category_base'] ) ? $this->wc_permalinks['category_base'] : $this->woocommerce_wpml->url_translation->default_product_category_base;
		$name = $this->woocommerce_wpml->url_translation->url_string_name( 'product_cat' );

		$this->woocommerce_wpml->url_translation->add_default_slug_translations($category_base, $name);
		$string_id = icl_get_string_id( $category_base, $this->woocommerce_wpml->url_translation->url_strings_context(), $name );
		$this->wcml_helper->icl_clear_and_init_cache( 'fr' );
		$translations = icl_get_string_translations_by_id($string_id);

		$this->assertEquals( 'categorie-produit', $translations['fr']['value']);
	}

}