<?php

namespace WCML\MultiCurrency;

/**
 * @group multicurrency
 * @group geolocation
 * @group wcml-3503
 */
class TestGeolocationBackendHooks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itLoadsOnBackend() {
		$subject = new GeolocationBackendHooks();
		$this->assertInstanceOf( '\IWPML_Backend_Action', $subject );
	}

	/**
	 * @test
	 */
	public function itAddsHooks() {
		$subject = new GeolocationBackendHooks();

		\WP_Mock::expectFilterAdded( 'wcml_geolocation_is_used', [ Geolocation::class, 'isUsed' ] );

		$subject->add_hooks();
	}
}
