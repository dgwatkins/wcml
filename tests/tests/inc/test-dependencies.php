<?php

class Test_WCML_Dependencies extends WCML_UnitTestCase {

	function setUp(){
		parent::setUp();
	}

	function test_check_versions(){

		$check = $this->woocommerce_wpml->dependencies->check();

		$this->assertTrue( $check );


	}
}