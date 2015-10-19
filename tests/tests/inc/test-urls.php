<?php

class Test_WCML_URLS extends WCML_UnitTestCase {

	function test_get_language_pack_uri(){
		global $woocommerce_wpml;

		$pack_uri = $woocommerce_wpml->get_language_pack_uri( 'uk_UA' );

		$response = wp_safe_remote_get( $pack_uri, array( 'timeout' => 60 ) );
		$response_result = false;

		if ( ! is_wp_error( $response ) && $response['response']['code'] >= 200 && $response['response']['code'] < 300 ) {
			$response_result = true;
		}

		$this->assertTrue( $response_result );
	}

	function test_url_string_name(){
		global $woocommerce_wpml;

		$this->assertEquals( 'URL product_cat tax slug', $woocommerce_wpml->url_translation->url_string_name( 'product_cat' ) );

		$this->assertEquals( 'URL slug: product', $woocommerce_wpml->url_translation->url_string_name( 'product' ) );

	}

}