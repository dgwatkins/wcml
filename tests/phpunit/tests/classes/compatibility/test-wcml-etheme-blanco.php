<?php

class Test_WCML_Etheme_Blanco extends OTGS_TestCase {

	private function get_subject() {
		return new WCML_Etheme_Blanco();
	}

	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'wcml_calculate_totals_exception', array(
			$subject,
			'calculate_totals_on_et_refreshed_fragments'
		), 9 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function et_refreshed_fragments_return() {
		$subject = $this->get_subject();

		$this->assertTrue( $subject->calculate_totals_on_et_refreshed_fragments( true ) );
	}

	/**
	 * @test
	 */
	public function et_refreshed_fragments() {
		$subject = $this->get_subject();

		$_POST['action'] = 'et_refreshed_fragments';

		$this->assertFalse( $subject->calculate_totals_on_et_refreshed_fragments( true ) );
	}

}
