<?php

class Test_WCML_URLS extends WCML_UnitTestCase {

	private $default_language;
	private $second_language;

	function setUp(){
		parent::setUp();

		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';
	}


	function test_url_string_name(){

		$this->assertEquals( 'URL product_cat tax slug', $this->woocommerce_wpml->url_translation->url_string_name( 'product_cat' ) );

		$this->assertEquals( 'URL slug: product', $this->woocommerce_wpml->url_translation->url_string_name( 'product' ) );

	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wcml-1033
	 */

	function test_translate_bases_in_rewrite_rules_with_empty_string() {

		$empty_string = '';
		$filtered_values = $this->woocommerce_wpml->url_translation->translate_bases_in_rewrite_rules( $empty_string );
		$this->assertEquals( $empty_string, $filtered_values );
	}

	function test_check_wc_tax_url_on_redirect(){
		global $wp_query;

		$cat = $this->wcml_helper->add_term( 'Test', 'product_cat', $this->default_language );
		$cat_es = $this->wcml_helper->add_term( 'Test Es', 'product_cat', $this->second_language, false, $cat->trid  );

		$wp_query->is_tax = true;
		$wp_query->queried_object = new stdClass();
		$wp_query->queried_object->term_id = $cat_es->term_id;
		$wp_query->queried_object->taxonomy = 'product_cat';
		$this->sitepress->switch_lang( $this->second_language );
		$expected_url = 'http://example.org/?product_cat=test-es&lang=es';

		$filtered_url = $this->woocommerce_wpml->url_translation->check_wc_tax_url_on_redirect( '', 'http://example.org/?product_cat=test&lang='.$this->second_language );
		$this->assertEquals( $expected_url, $filtered_url );
	}

}