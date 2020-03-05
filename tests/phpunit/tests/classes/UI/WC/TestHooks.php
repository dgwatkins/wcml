<?php

namespace WCML\UI\WC;

use Gitlab\Model\Hook;

/**
 * @group ui
 * @group ui-wc
 * @group wcml-3121
 */
class TestHooks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldLoadOnBackend() {
		$subject = new Hooks();
		$this->assertInstanceOf( \IWPML_Backend_Action::class, $subject );
	}

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = new Hooks();

		\WP_Mock::expectActionAdded( 'admin_enqueue_scripts', [ $subject, 'enqueueScripts' ], 9 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itShouldEnqueueScripts() {
		\WP_Mock::userFunction( 'wp_enqueue_script', [
			'times' => 1,
			'args'  => [ 'wcmlUiWc', WCML_PLUGIN_URL . '/dist/js/adminUiWc/app.js', [ 'wp-hooks' ], WCML_VERSION ],
		] );

		$subject = new Hooks();
		$subject->enqueueScripts();
	}
}