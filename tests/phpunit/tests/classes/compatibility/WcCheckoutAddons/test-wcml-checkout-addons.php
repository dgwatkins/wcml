<?php

/**
 * @group compatibility
 * @group wc-checkout-addons
 */
class Test_WCML_Checkout_Addons extends OTGS_TestCase {

	private function get_subject() {
		return new WCML_Checkout_Addons();
	}

	/**
	 * @test
	 */
	public function add_hooks(){
		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'option_wc_checkout_add_ons', array( $subject, 'option_wc_checkout_add_ons' ) );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function should_return_same_array_if_empty() {
		$option_value = [];

		$subject = $this->get_subject();
		$returned = $subject->option_wc_checkout_add_ons( $option_value ) ;

		$this->assertEquals( $option_value, $returned );
	}

	/**
	 * @test
	 */
	public function should_return_same_null_value() {
		$option_value = null;

		$subject = $this->get_subject();
		$returned = $subject->option_wc_checkout_add_ons( $option_value );

		$this->assertEquals( $option_value, $returned );
	}

	/**
	 * @test
	 */
	public function should_return_same_on_different_array() {
		$option_value = [
			'foo' => 'bar',
			'baz' => 'no'
		];

		$subject = $this->get_subject();
		$returned = $subject->option_wc_checkout_add_ons($option_value, null);

		$this->assertEquals( $option_value, $returned );
	}

	/**
	 * @test
	 */
	public function it_should_register_the_strings_and_return_same_on_default_language() {
		$option = [
			'addonid' => [
				'label'           => 'foo',
				'description'     => 'bar',
				'adjustment_type' => 'fixed',
				'adjustment'      => 10.90,
			],
		];

		\WP_Mock::onFilter( 'wpml_current_language' )->with( null )->reply( 'en' );
		\WP_Mock::onFilter( 'wpml_default_language' )->with( null )->reply( 'en' );

		\WP_Mock::expectAction( 'wpml_register_single_string', 'wc_checkout_addons', 'addonid_label_' . md5( 'foo' ), 'foo' );
		\WP_Mock::expectAction( 'wpml_register_single_string', 'wc_checkout_addons', 'addonid_description_' . md5( 'bar' ), 'bar' );

		$this->assertEquals( $option, $this->get_subject()->option_wc_checkout_add_ons( $option ) );
	}

	/**
	 * @test
	 */
	public function should_return_translated_on_secondary_language() {
		$option_value = [
			'addonid' => [
				'label' => 'foo',
				'description' => 'bar',
				'adjustment_type' => 'fixed',
				'adjustment' => 10
			]
		];

		$option_value_translated = [
			'addonid' => [
				'label' => 'foo pl',
				'description' => 'bar pl',
				'adjustment_type' => 'fixed',
				'adjustment' => 10
			]
		];

		\WP_Mock::onFilter( 'wpml_current_language' )->with( null )->reply( 'pl' );
		\WP_Mock::onFilter( 'wpml_default_language' )->with( null )->reply( 'en' );

		\WP_Mock::onFilter( 'wpml_translate_single_string' )->with( 'foo', 'wc_checkout_addons', 'addonid_label_' . md5( 'foo' ) )->reply( 'foo pl' );
		\WP_Mock::onFilter( 'wpml_translate_single_string' )->with( 'bar', 'wc_checkout_addons', 'addonid_description_' . md5( 'bar' ) )->reply( 'bar pl' );

		$this->assertEquals(
			$option_value_translated,
			$this->get_subject()->option_wc_checkout_add_ons( $option_value )
		);
	}

	/**
	 * @test
	 */
	public function should_return_translated_with_options() {
		$option_value = [
			'addonid' => [
				'label' => 'foo',
				'description' => 'bar',
				'adjustment_type' => 'fixed',
				'adjustment' => 10,
				'options' => [
					'optionid' => [
						'label' => 'optionfoo',
						'description' => 'optionbar',
						'adjustment_type' => 'fixed',
						'adjustment' => 30
					]
				]
			]
		];

		$option_value_translated = [
			'addonid' => [
				'label' => 'foo pl',
				'description' => 'bar pl',
				'adjustment_type' => 'fixed',
				'adjustment' => 10,
				'options' => [
					'optionid' => [
						'label' => 'optionfoo pl',
						'description' => 'optionbar pl',
						'adjustment_type' => 'fixed',
						'adjustment' => 30
					]
				]
			]
		];

		\WP_Mock::onFilter( 'wpml_current_language' )->with( null )->reply( 'pl' );
		\WP_Mock::onFilter( 'wpml_default_language' )->with( null )->reply( 'en' );

		\WP_Mock::onFilter( 'wpml_translate_single_string' )->with( 'foo', 'wc_checkout_addons', 'addonid_label_' . md5( 'foo' ) )->reply( 'foo pl' );
		\WP_Mock::onFilter( 'wpml_translate_single_string' )->with( 'bar', 'wc_checkout_addons', 'addonid_description_' . md5( 'bar' ) )->reply( 'bar pl' );

		\WP_Mock::onFilter( 'wpml_translate_single_string' )->with( 'optionfoo', 'wc_checkout_addons', 'optionid_label_' . md5( 'optionfoo' ) )->reply( 'optionfoo pl' );
		\WP_Mock::onFilter( 'wpml_translate_single_string' )->with( 'optionbar', 'wc_checkout_addons', 'optionid_description_' . md5( 'optionbar' ) )->reply( 'optionbar pl' );

		$this->assertEquals(
			$option_value_translated,
			$this->get_subject()->option_wc_checkout_add_ons( $option_value  )
		);
	}
}