<?php

class Test_WCML_Wpb_Vc extends OTGS_TestCase {

	/**
	 * @test
	 */
	public function is_ajax_action_needs_multi_currency() {

		$subject = new WCML_Wpb_Vc();

		\WP_Mock::expectFilterAdded( 'wcml_is_localize_woocommerce_on_ajax', array(
			$subject,
			'is_localize_woocommerce_on_ajax'
		), 10, 2 );

		$subject->add_hooks();

	}
}
