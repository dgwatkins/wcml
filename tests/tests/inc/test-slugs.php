<?php

class Test_WCML_Slugs extends WCML_UnitTestCase {

	function test_translate_product_slug() {
		global $woocommerce_wpml, $sitepress;

		$icl_settings = $sitepress->get_settings();

		$iclsettings['posts_slug_translation']['on'] = 1;
		$iclsettings['posts_slug_translation']['types']['test_type'] = 1;

		$sitepress->save_settings( $iclsettings );

		$woocommerce_wpml->translate_product_slug();

		$icl_settings_updated = $sitepress->get_settings();

		$this->assertEquals( 1, $icl_settings_updated['posts_slug_translation']['types']['test_type'] );
	}

}