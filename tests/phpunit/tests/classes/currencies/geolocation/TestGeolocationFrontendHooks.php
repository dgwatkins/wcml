<?php

namespace WCML\MultiCurrency;

use tad\FunctionMocker\FunctionMocker;

/**
 * @group multicurrency
 * @group geolocation
 * @group wcml-3503
 */
class TestGeolocationFrontendHooks  extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itLoadsOnFrontend() {
		$subject = new GeolocationFrontendHooks();
		$this->assertInstanceOf( '\IWPML_Frontend_Action', $subject );
	}

	/**
	 * @test
	 * @group wcml-3503
	 */
	public function itDoesNOTAddHooks() {
		FunctionMocker::replace( Geolocation::class . '::isUsed', false );

		$subject = new GeolocationFrontendHooks();

		\WP_Mock::expectActionNotAdded( 'after_setup_theme', [ GeolocationFrontendHooks::class, 'storeUserCountry' ] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @group wcml-3503
	 */
	public function itAddsHooks() {
		FunctionMocker::replace( Geolocation::class . '::isUsed', true );

		$subject = new GeolocationFrontendHooks();

		\WP_Mock::expectActionAdded( 'after_setup_theme', [ GeolocationFrontendHooks::class, 'storeUserCountry' ] );

		$subject->add_hooks();
	}
}
