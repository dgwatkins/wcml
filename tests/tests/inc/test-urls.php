<?php

class Test_WCML_URLS extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();
		global $woocommerce_wpml;

		require_once WCML_PLUGIN_PATH . '/inc/class-wcml-languages-upgrader.php';
		$woocommerce_wpml->languages_upgrader = new WCML_Languages_Upgrader;
	}

	function test_get_language_pack_uri(){
		global $woocommerce_wpml, $woocommerce;

		//use stable version to test
		$pack_uri = $woocommerce_wpml->languages_upgrader->get_language_pack_uri( 'uk_UA', $woocommerce_wpml->get_stable_wc_version() );

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

	function test_filter_paypal_args(){
		global $woocommerce_wpml, $sitepress, $sitepress_settings, $wpml_post_translations;

		$sitepress->switch_lang( 'de' );

		$default_lang_code	= $sitepress->get_default_language();
		$wpml_wp_api        = new WPML_WP_API();
		$hidden_langs 		= array();
		$wpml_url_converter = new WPML_Lang_Parameter_Converter( $default_lang_code, $hidden_langs, $wpml_wp_api );

		$wpml_url_filters = new WPML_URL_Filters( $wpml_post_translations, $wpml_url_converter, $sitepress );

		$_SERVER['SERVER_NAME'] = $sitepress->convert_url( get_home_url() );

		$args['notify_url'] = WC()->api_request_url( 'WC_Gateway_Paypal' );

		$filtered_args = $woocommerce_wpml->filter_paypal_args( $args ) ;

		$this->assertEquals( $sitepress->convert_url( get_home_url() ).'&wc-api=WC_Gateway_Paypal', $filtered_args['notify_url'] );
	}

}