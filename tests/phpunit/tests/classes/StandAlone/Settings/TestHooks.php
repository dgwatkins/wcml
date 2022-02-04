<?php

namespace WCML\StandAlone\Settings;

use tad\FunctionMocker\FunctionMocker;
use WCML\MultiCurrency\Settings;
use WPML\LIB\WP\OnActionMock;

/**
 * @group standalone
 */
class TestHooks extends \OTGS_TestCase {

	use OnActionMock;

	public function setUp() {
		parent::setUp();
		$this->setUpOnAction();
	}

	/**
	 * @test
	 */
	public function itShouldNOTForceMultiCurrencyByLocation() {
		FunctionMocker::replace( Settings::class . '::isModeByLanguage', false );

		$setMode = FunctionMocker::replace( Settings::class . '::setMode' );

		( new Hooks() )->add_hooks();

		$this->runAction( 'admin_init' );

		$setMode->wasNotCalled();
	}

	/**
	 * @test
	 */
	public function itShouldForceMultiCurrencyByLocation() {
		FunctionMocker::replace( Settings::class . '::isModeByLanguage', true );

		$setMode = FunctionMocker::replace( Settings::class . '::setMode' );

		( new Hooks() )->add_hooks();

		$this->runAction( 'admin_init' );

		$setMode->wasCalledWithOnce( [ Settings::MODE_BY_LOCATION ] );
	}
}
