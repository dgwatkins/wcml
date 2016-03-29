<?php

class Test_WCML_Dependencies extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();
	}

	function test_check_versions(){
		global $woocommerce_wpml;

		$check = $woocommerce_wpml->dependencies->check();

		$this->assertTrue( (bool)$check );


	}
}