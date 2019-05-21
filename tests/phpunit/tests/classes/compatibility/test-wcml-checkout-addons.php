<?php

class Test_WCML_Checkout_Addons extends OTGS_TestCase {
	public function setUp()	{
		parent::setUp();
	}

	private function get_subject() {
		return new WCML_Checkout_Addons();
	}

	/**
	 * @test
	 */
	public function add_hooks(){
		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'option_wc_checkout_add_ons', array( $subject, 'option_wc_checkout_add_ons' ), 10, 2 );
		$result = $subject->add_hooks();
		$this->assertNull( $result );
	}

	/**
	 * @test
	 */
	public function should_return_same_array_if_empty() {
		$option_value = array();

		$subject = $this->get_subject();
		$returned = $subject->option_wc_checkout_add_ons($option_value, null);

		$this->assertEquals( $option_value, $returned );
	}

	/**
	 * @test
	 */
	public function should_return_same_null_value() {
		$option_value = null;

		$subject = $this->get_subject();
		$returned = $subject->option_wc_checkout_add_ons($option_value, null);

		$this->assertEquals( $option_value, $returned );
	}

	/**
	 * @test
	 */
	public function should_return_same_on_different_array() {
		$option_value = array(
			'foo' => 'bar',
			'baz' => 'no'
		);

		$subject = $this->get_subject();
		$returned = $subject->option_wc_checkout_add_ons($option_value, null);

		$this->assertEquals( $option_value, $returned );
	}

	/**
	 * @test
	 */
	public function should_return_same_on_default_language() {

		$option_value = array(
			'addonid' => array(
				'label' => 'foo',
				'description' => 'bar',
				'adjustment_type' => 'fixed',
				'adjustment' => 10
			)
		);

		\WP_Mock::onFilter( 'wpml_current_language' )->with( null )->reply( 'en' );
		\WP_Mock::onFilter( 'wpml_default_language' )->with( null )->reply( 'en' );

		\WP_Mock::expectAction( 'wpml_register_single_string', 'wc_checkout_addons', 'addonid_label_' . md5( 'foo' ), 'foo' );
		\WP_Mock::expectAction( 'wpml_register_single_string', 'wc_checkout_addons', 'addonid_description_' . md5( 'bar' ), 'bar' );


		$subject = $this->get_subject();
		$returned = $subject->option_wc_checkout_add_ons($option_value, null);

		$this->assertEquals( $option_value, $returned );
	}

	/**
	 * @test
	 */
	public function should_return_translated_with_different_price_on_secondary_language() {
		$option_value = array(
			'addonid' => array(
				'label' => 'foo',
				'description' => 'bar',
				'adjustment_type' => 'fixed',
				'adjustment' => 10
			)
		);

		$option_value_translated = array(
			'addonid' => array(
				'label' => 'foo pl',
				'description' => 'bar pl',
				'adjustment_type' => 'fixed',
				'adjustment' => 20
			)
		);

		\WP_Mock::onFilter( 'wpml_current_language' )->with( null )->reply( 'pl' );
		\WP_Mock::onFilter( 'wpml_default_language' )->with( null )->reply( 'en' );

		\WP_Mock::onFilter( 'wpml_translate_single_string' )->with( 'foo', 'wc_checkout_addons', 'addonid_label_' . md5( 'foo' ) )->reply( 'foo pl' );
		\WP_Mock::onFilter( 'wpml_translate_single_string' )->with( 'bar', 'wc_checkout_addons', 'addonid_description_' . md5( 'bar' ) )->reply( 'bar pl' );
		\WP_Mock::onFilter( 'wcml_raw_price_amount' )->with( 10 )->reply( 20 );

		$subject = $this->get_subject();
		$returned = $subject->option_wc_checkout_add_ons($option_value, null);

		$this->assertEquals( $returned, $option_value_translated );
	}
}