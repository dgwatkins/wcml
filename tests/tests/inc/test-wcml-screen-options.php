<?php

/**
 * Class Test_WCML_Screen_Options
 */
class Test_WCML_Screen_Options extends WCML_UnitTestCase {

	/**
	 * Test \WCML_Screen_Options::init()
	 * @test
	 */
	public function check_setup_hooks() {
		$subject = $this->get_test_subject();
		$subject->init();

		$this->assertEquals( 10, has_filter( 'default_hidden_columns', array( $subject, 'filter_screen_options' ) ) );
	}

	/**
	 * Test \WCML_Screen_Options::filter_screen_options()
	 * @test
	 */
	public function filter_screen_options() {
		$subject = $this->get_test_subject();
		$hidden = array();
		$screen = new stdClass();
		$screen->id = 'edit-product';

		$this->assertTrue( in_array( 'icl_translations', $subject->filter_screen_options( $hidden, $screen ) ) );

		$screen->id = 'edit-cpt';
		$this->assertFalse( in_array( 'icl_translations', $subject->filter_screen_options( $hidden, $screen ) ) );
	}

	private function get_test_subject() {
		return new WCML_Products_Screen_Options();
	}
}
