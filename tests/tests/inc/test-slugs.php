<?php

class Test_WCML_Slugs extends WCML_UnitTestCase {


	function setUp(){
		parent::setUp();
		global $woocommerce_wpml, $WPML_String_Translation, $sitepress;
		$WPML_String_Translation->init_active_languages();

		require_once WCML_PLUGIN_PATH . '/inc/wc-strings.class.php';
		$woocommerce_wpml->strings           = new WCML_WC_Strings;

		require_once WCML_PLUGIN_PATH . '/inc/url-translation.class.php';
		$woocommerce_wpml->url_translation = new WCML_Url_Translation( $woocommerce_wpml, $sitepress );

		require_once WCML_PLUGIN_PATH . '/inc/class-wcml-languages-upgrader.php';
		$woocommerce_wpml->languages_upgrader = new WCML_Languages_Upgrader;

		$this->url_translation =& $woocommerce_wpml->url_translation;

		$this->wc_permalinks = get_option( 'woocommerce_permalinks' );
	}

	function test_download_woocommerce_translations_for_active_languages() {
		global $woocommerce_wpml;

		//use stable version to test
		$wc_version = $woocommerce_wpml->get_stable_wc_version();
		delete_option( 'woocommerce_language_pack_version_fr_FR' );
		$woocommerce_wpml->languages_upgrader->download_woocommerce_translations_for_active_languages( $wc_version );

		$downloaded_translation_info = get_option('woocommerce_language_pack_version_fr_FR');

		$this->assertEquals( $downloaded_translation_info[0], $wc_version );
		$this->assertEquals( $downloaded_translation_info[1], 'fr_FR' );
	}

	function test_translate_product_slug() {
		global $sitepress;

		$iclsettings = $sitepress->get_settings();

		$iclsettings['posts_slug_translation']['on'] = 1;
		$iclsettings['posts_slug_translation']['types']['test_type'] = 1;

		$sitepress->save_settings( $iclsettings );

		$this->url_translation->translate_product_base();

		$icl_settings_updated = $sitepress->get_settings();

		$this->assertEquals( 1, $icl_settings_updated['posts_slug_translation']['types']['test_type'] );
	}

	function test_get_woocommerce_product_base(){

		$this->url_translation->wc_permalinks['product_base'] = '/test_slug';

		$this->assertEquals( 'test_slug', $this->url_translation->get_woocommerce_product_base() );

		$this->url_translation->wc_permalinks['product_base'] = '';

		update_option( 'woocommerce_product_slug', 'test_slug2' );

		$this->assertEquals( 'test_slug2', $this->url_translation->get_woocommerce_product_base() );

		$this->url_translation->wc_permalinks['product_base'] = '';

		update_option( 'woocommerce_product_slug', false );

		$this->assertEquals( 'product', $this->url_translation->get_woocommerce_product_base() );

	}

	function test_get_translated_tax_slug(){
		$category_base = !empty( $this->wc_permalinks['category_base'] ) ? trim( $this->wc_permalinks['category_base'], '/' ) : 'product-category';
		$name = $this->url_translation->url_string_name( 'product_cat' );
		do_action( 'wpml_register_single_string', $this->url_translation->url_strings_context(), $name, $category_base );
		$string_id = icl_get_string_id( $category_base, $this->url_translation->url_strings_context(), $name );

		icl_add_string_translation( $string_id, 'es', 'categoria-producto', ICL_TM_COMPLETE );
		$translated_tax = $this->url_translation->get_translated_tax_slug('product_cat','es');

		$this->assertTrue( (bool) has_filter('wpml_translate_single_string') );
		$this->assertEquals( 'categoria-producto', $translated_tax['translated_slug'] );

	}

	function test_translate_taxonomy_base(){
		global $sitepress, $wpml_term_translations;

		$taxonomy = 'product_cat';

		$tax_term = wp_insert_term( 'test_cat1', $taxonomy );
		$ttid = $tax_term[ 'term_taxonomy_id' ];

		$sitepress->set_element_language_details( $ttid, 'tax_'.$taxonomy , false, $sitepress->get_default_language() );
		$trid = $wpml_term_translations->get_element_trid( $ttid );

		$translated_language = 'es';
		$tr_tax_term = wp_insert_term( 'test_cat1_es', $taxonomy );
		$tr_ttid = $tr_tax_term[ 'term_taxonomy_id' ];

		$sitepress->set_element_language_details( $tr_ttid, 'tax_'.$taxonomy, $trid, $translated_language );

		$this->assertEquals( 'http://'.WP_TESTS_DOMAIN.'/?product_cat=test_cat1_es&lang=es' , $this->url_translation->translate_taxonomy_base(  get_term_link( $tr_tax_term['term_id'], $taxonomy ), get_term( $tr_tax_term['term_id'], $taxonomy ), $taxonomy ) );

	}

	// note - make sure you have .mo files in wp-content->languages directory in your wordpress test folder
	function test_add_default_slug_translations(){

		$category_base = !empty( $this->wc_permalinks['category_base'] ) ? $this->wc_permalinks['category_base'] : $this->url_translation->default_product_category_base;
		$name = $this->url_translation->url_string_name( 'product_cat' );

		$this->url_translation->add_default_slug_translations($category_base, $name);
		$string_id = icl_get_string_id( $category_base, $this->url_translation->url_strings_context(), $name );
		$translations = icl_get_string_translations_by_id($string_id);

		$this->assertEquals( 'categorie-produit', $translations['fr']['value']);
	}

}