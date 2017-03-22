<?php

class Test_WCML_Klarna_Gateways extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function ajax_action_needs_multi_currency(){

		$subject = new WCML_Klarna_Gateway();

		\WP_Mock::expectFilterAdded( 'wcml_multi_currency_ajax_actions', array( $subject, 'ajax_action_needs_multi_currency' ) );

		$subject->add_hooks();

	}
}
