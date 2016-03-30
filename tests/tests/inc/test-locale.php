<?php

class Test_WCML_Locale extends WCML_UnitTestCase {


	function setUp(){
		parent::setUp();
	}

	function test_switch_locale(){
		$this->assertTrue( $this->woocommerce_wpml->locale->switch_locale( 'de' ) );
		$this->woocommerce_wpml->locale->switch_locale( );
	}

}