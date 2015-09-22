<?php

class Test_WCML_Slugs extends WCML_UnitTestCase {


	function __construct(){
		global $woocommerce_wpml;

		require_once WCML_PLUGIN_PATH . '/inc/wc-strings.class.php';
		$woocommerce_wpml->strings           = new WCML_WC_Strings;

		require_once WCML_PLUGIN_PATH . '/inc/url-translation.class.php';
		$woocommerce_wpml->url_translation = new WCML_Url_Translation();

		$this->url_translation =& $woocommerce_wpml->url_translation;
	}


	function test_translate_product_slug() {
		global $woocommerce_wpml, $sitepress;

		$icl_settings = $sitepress->get_settings();

		$iclsettings['posts_slug_translation']['on'] = 1;
		$iclsettings['posts_slug_translation']['types']['test_type'] = 1;

		$sitepress->save_settings( $iclsettings );

		$this->url_translation->translate_product_base();

		$icl_settings_updated = $sitepress->get_settings();

		$this->assertEquals( 1, $icl_settings_updated['posts_slug_translation']['types']['test_type'] );
	}

}