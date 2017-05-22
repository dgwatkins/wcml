<?php

/**
 * @author OnTheGo Systems
 * @group  wcml-1964
 */
class Test_WCML_Widgets extends OTGS_TestCase {
	/**
	 * @test
	 */
	function it_add_hooks() {
		$settings = array();
		$subject  = new WCML_Widgets( $settings );

		WP_Mock::expectActionAdded( 'widgets_init', array( $subject, 'register_widgets' ) );

		$subject->init_hooks();
	}

	/**
	 * @test
	 */
	function it_register_no_widgets() {
		$settings = array();
		$subject  = new WCML_Widgets( $settings );

		WP_Mock::wpFunction( 'register_widget', array( 'times' => 0 ) );

		$subject->register_widgets();
	}

	/**
	 * @test
	 */
	function it_register_all_widgets() {
		$settings = array(
			'enable_multi_currency' => WCML_MULTI_CURRENCIES_INDEPENDENT,
			'cart_sync'             => array(
				'currency_switch' => WCML_CART_CLEAR,
			),
		);
		$subject  = new WCML_Widgets( $settings );

		WP_Mock::wpFunction( 'register_widget', array( 'times' => 1, 'args' => array( 'WCML_Currency_Switcher_Widget' ) ) );
		WP_Mock::wpFunction( 'register_widget', array( 'times' => 1, 'args' => array( 'WCML_Cart_Removed_Items_Widget' ) ) );

		$subject->register_widgets();
	}

	/**
	 * @test
	 */
	function it_register_the_currency_switcher_widget() {
		$settings = array(
			'enable_multi_currency' => WCML_MULTI_CURRENCIES_INDEPENDENT,
		);
		$subject  = new WCML_Widgets( $settings );

		WP_Mock::wpFunction( 'register_widget', array( 'times' => 1, 'args' => array( 'WCML_Currency_Switcher_Widget' ) ) );

		$subject->register_widgets();
	}

	/**
	 * @test
	 */
	function it_register_the_cart_removed_items_widget_from_currency_switch() {
		$settings = array(
			'cart_sync' => array(
				'currency_switch' => WCML_CART_CLEAR,
			),
		);
		$subject  = new WCML_Widgets( $settings );

		WP_Mock::wpFunction( 'register_widget', array( 'times' => 1, 'args' => array( 'WCML_Cart_Removed_Items_Widget' ) ) );

		$subject->register_widgets();
	}

	/**
	 * @test
	 */
	function it_register_the_cart_removed_items_widget_from_lang_switch() {
		$settings = array(
			'cart_sync' => array(
				'lang_switch' => WCML_CART_CLEAR,
			),
		);
		$subject  = new WCML_Widgets( $settings );

		WP_Mock::wpFunction( 'register_widget', array( 'times' => 1, 'args' => array( 'WCML_Cart_Removed_Items_Widget' ) ) );

		$subject->register_widgets();
	}
}