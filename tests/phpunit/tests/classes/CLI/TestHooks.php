<?php

namespace WCML\CLI;

/**
 * @group cli
 */
class TestHooks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = new Hooks();
		\WP_Mock::expectActionAdded( 'shutdown', [ $subject, 'preventWcWizardRedirection' ] );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldPreventWcWizardRedirection() {
		\WP_Mock::userFunction( 'delete_transient' )->once()->with( '_wc_activation_redirect' );

		$subject = new Hooks();
		$subject->preventWcWizardRedirection();
	}
}
