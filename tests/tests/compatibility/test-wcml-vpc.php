<?php

/**
 * Class Test_WCML_Vpc
 */
class Test_WCML_Vpc extends WCML_UnitTestCase {

	function setUp() {
		parent::setUp();

	}

	private function get_test_subject() {
		return new WCML_Vpc();
	}

	function test_wcml_vpc_cart_exc(){

		$vpc_obj = $this->get_test_subject();
		//create cart object to test
		$cart = new StdClass();
		$cart->cart_contents = array();

		$this->assertEquals( true, $vpc_obj->wcml_vpc_cart_exc( true, $cart ) );

		$cart->cart_contents[0]['visual-product-configuration'] = 1;

		$this->assertEquals( false, $vpc_obj->wcml_vpc_cart_exc( true, $cart ) );



	}
}
