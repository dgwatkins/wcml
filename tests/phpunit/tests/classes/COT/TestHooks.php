<?php

namespace WCML\COT;

use WPML\LIB\WP\OnActionMock;
use Automattic\WooCommerce\Utilities\FeaturesUtil as FeaturesUtil;
use tad\FunctionMocker\FunctionMocker;

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
	public function itDeclaresCompatiblity() {
		$declare = FunctionMocker::replace( FeaturesUtil::class . '::declare_compatibility' );

		( new Hooks() )->add_hooks();

		$this->runAction( 'before_woocommerce_init' );

		$declare->wasCalledOnce();
	}

}