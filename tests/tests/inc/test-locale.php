<?php

class Test_WCML_Locale extends WCML_UnitTestCase {


	function setUp(){
		parent::setUp();
	}


	function test_switch_locale(){
		global $woocommerce_wpml;

		$this->assertTrue( $woocommerce_wpml->switch_locale( 'de' ) );
		$woocommerce_wpml->switch_locale( );
	}

}