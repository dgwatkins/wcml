<?php

namespace WCML\User;

use WPML\LIB\WP\OnActionMock;

class TestHooks extends \OTGS_TestCase {

	use OnActionMock;

	public function setUp() {
		parent::setUp();
		$this->setUpOnAction();
	}

	public function tearDown() {
		$this->tearDownOnAction();
		parent::tearDown();
	}

	private function get_subject() {
		return new Hooks();
	}

	/**
	 * @test
	 */
	public function itShouldSetCustomerProfileLanguage() {
		$userId = 123;
		$lang   = 'fr';

		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'wpml_get_current_language', [
			'times'  => 1,
			'args'   => [],
			'return' => $lang,
		] );

		\WP_Mock::userFunction( 'wp_update_user', [
			'times'  => 1,
			'args'   => [
				[
					'ID'     => $userId,
					'locale' => $lang,
				],
			]
		 ] );

		$subject->add_hooks();
		$this->runAction( 'woocommerce_created_customer', $userId );
	}
}
