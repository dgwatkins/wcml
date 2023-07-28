<?php

namespace WCML\OrderNotes;

use WPML\LIB\WP\OnActionMock;

class TestBlockHooks extends \OTGS_TestCase {

	use OnActionMock;

	public function setUp() {
		parent::setUp();
		$this->setUpOnAction();
	}

	public function tearDown() {
		$this->tearDownOnAction();
		parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itSwitchesToAdminLanguage() {
		$lang = 'en';

		\WP_Mock::onFilter( 'wpml_default_language' )
			->with( null )
			->reply( $lang );

		\WP_Mock::expectAction( 'wpml_switch_language', $lang );

		$subject = new Hooks();
		$subject->add_hooks();

		$this->runAction( 'woocommerce_after_order_object_save' );
	}

}
