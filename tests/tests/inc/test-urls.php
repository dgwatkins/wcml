<?php

class Test_WCML_URLS extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();
	}

	function test_get_language_pack_uri(){
		global $woocommerce;

		//use stable version to test
		$pack_uri = $this->woocommerce_wpml->languages_upgrader->get_language_pack_uri( 'uk_UA', $this->woocommerce_wpml->get_stable_wc_version() );

		$response = wp_safe_remote_get( $pack_uri, array( 'timeout' => 60 ) );
		$response_result = false;

		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$response_result = true;
		}

		$this->assertTrue( $response_result );
	}

	function test_url_string_name(){

		$this->assertEquals( 'URL product_cat tax slug', WCML_Url_Translation::url_string_name( 'product_cat' ) );

		$this->assertEquals( 'URL slug: product', WCML_Url_Translation::url_string_name( 'product' ) );

	}

	/**
	 * @link https://onthegosystems.myjetbrains.com/youtrack/issue/wcml-1033
	 */

	function test_translate_bases_in_rewrite_rules_with_empty_string() {
		$woocommerce_wpml_mock          = $this->get_wcml_mock();
		$woocommerce_wpml_mock->strings = new WCML_WC_Strings;

		$sitepress_mock                 = $this->get_sitepress_mock();
		$sitepress_mock->method( 'get_current_language' )->willReturn( 'de' );
		$sitepress_mock->method( 'get_active_languages' )->willReturn( array() );

		$url_translation = new WCML_Url_Translation( $woocommerce_wpml_mock, $sitepress_mock );

		$empty_string = '';
		$filtered_values = $url_translation->translate_bases_in_rewrite_rules( $empty_string );
		$this->assertEquals( $empty_string, $filtered_values );
	}

}