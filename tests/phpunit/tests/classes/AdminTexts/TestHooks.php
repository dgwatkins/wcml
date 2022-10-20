<?php

namespace WCML\AdminTexts;

use WPML\FP\Lst;
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

	/**
	 * @test
	 */
	public function itAddsHooks() {
		$array    = [ 'existing' ];
		$expected = Lst::append( 'woocommerce_permalinks', $array );

		( new Hooks() )->add_hooks();

		$this->assertSame(
			$expected,
			$this->runFilter( 'wpml_st_blacklisted_options', $array )
		);
	}

}
