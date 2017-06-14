<?php

/**
 * Class Test_WCML_Currency_Switcher_Properties
 * @group currency-switcher
 */
class Test_WCML_Currency_Switcher_Properties extends OTGS_TestCase {

	function setUp() {
		parent::setUp();
	}

	public function get_subject(){

		return new WCML_Currency_Switcher_Properties( );
	}

	/**
	 * @test
	 */
	public function is_product_currency_switcher_active() {

		$wcml_settings = array(
			'currency_switcher_product_visibility' => 1
		);

		$subject = $this->get_subject();

		$this->assertTrue( $subject->is_currency_switcher_active( 'product', $wcml_settings ) );
	}

	/**
	 * @test
	 */
	public function not_active_sidebar_currency_switcher() {
		$wcml_settings = array(
			'currency_switcher_product_visibility' => 1
		);

		$sidebar = rand_str();

		\WP_Mock::wpFunction( 'is_active_sidebar', array(
			'args' => $sidebar,
			'return'=> false
		) );

		$subject = $this->get_subject();

		$this->assertFalse( $subject->is_currency_switcher_active( $sidebar, $wcml_settings ) );
	}

	/**
	 * @test
	 */
	public function is_sidebar_currency_switcher_active() {
		$wcml_settings = array(
			'currency_switcher_product_visibility' => 1
		);

		$sidebar = rand_str();

		\WP_Mock::wpFunction( 'is_active_sidebar', array(
			'args' => $sidebar,
			'return'=> true
		) );

		$subject = $this->get_subject();

		$this->assertTrue( $subject->is_currency_switcher_active( $sidebar, $wcml_settings ) );
	}
}
