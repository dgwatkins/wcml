<?php

namespace WCML\Compatibility\YithWcQuickView;

class TestMulticurrencyHooks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function ajax_action_needs_multi_currency(){
		$subject = new MulticurrencyHooks();

		\WP_Mock::expectFilterAdded( 'wcml_multi_currency_ajax_actions', [ $subject, 'ajax_action_needs_multi_currency' ] );

		$subject->add_hooks();
	}
}
