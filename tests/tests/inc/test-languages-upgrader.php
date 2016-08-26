<?php

class Test_WCML_Languages_Upgrader extends WCML_UnitTestCase {

	private $default_language;
	private $second_language;
	private $current_screen;

	function setUp(){
		parent::setUp();

		$this->default_language = $this->sitepress->get_default_language();
		$this->second_language = 'es';
		$this->current_screen = get_current_screen();

		set_current_screen( 'admin' );

		$this->woocommerce_wpml->languages_upgrader = new WCML_Languages_Upgrader;
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

	function test_download_woocommerce_translations_for_active_languages() {
		//use stable version to test
		$wc_version = $this->woocommerce_wpml->get_stable_wc_version();
		delete_option('woocommerce_language_pack_version_fr_FR');
		$this->woocommerce_wpml->languages_upgrader->download_woocommerce_translations_for_active_languages( $wc_version );

		$downloaded_translation_info = get_option('woocommerce_language_pack_version_fr_FR');

		$this->assertEquals( $downloaded_translation_info[0], $wc_version );
		$this->assertEquals( $downloaded_translation_info[1], 'fr_FR' );
	}

	public function tearDown() {
		set_current_screen( $this->current_screen );
	}

}