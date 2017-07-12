<?php

class Test_WCML_Cart_Switch_Lang_Functions extends OTGS_TestCase {

	private function get_subject(){
		return new WCML_Cart_Switch_Lang_Functions();
	}

	/**
	 * @test
	 */
	public function add_actions(){

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'wp_footer', array( $subject, 'wcml_language_switch_dialog' ) );
		\WP_Mock::expectActionAdded( 'wp_loaded', array( $subject, 'wcml_language_force_switch' ) );
		\WP_Mock::expectActionAdded( 'wcml_user_switch_language', array( $subject, 'language_has_switched' ), 10, 2 );

		$subject->add_actions();

	}

}