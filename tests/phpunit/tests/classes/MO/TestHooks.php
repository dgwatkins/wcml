<?php

namespace WCML\MO;

/**
 * @group mo
 * @group wcml-3112
 */
class TestHooks extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldLoadOnFrontendAndBackend() {
		$subject = new Hooks();
		$this->assertInstanceOf( \IWPML_Backend_Action::class, $subject );
		$this->assertInstanceOf( \IWPML_Frontend_Action::class, $subject );
	}

	/**
	 * @test
	 */
	public function itShouldAddHooks() {
		$subject = new Hooks();
		\WP_Mock::expectActionNotAdded( 'wpml_language_has_switched', [ $subject, 'forceRemoveUnloadedDomain' ], 0 );
		$subject->add_hooks();
	}

	/**
	 * @test
	 * @dataProvider dpShouldForceRemoveUnloadedDomain
	 *
	 * @param mixed $globals
	 * @param mixed $expectedGlobals
	 */
	public function itShouldForceRemoveUnloadedDomain( $globals, $expectedGlobals ) {
		$original_GLOBALS = $GLOBALS;
		$GLOBALS = $globals;

		$subject = new Hooks();
		$subject->forceRemoveUnloadedDomain();

		$this->assertFalse( isset( $GLOBALS['l10n_unloaded']['woocommerce'] ) );
		$this->assertEquals( $expectedGlobals, $GLOBALS );

		$GLOBALS = $original_GLOBALS;
	}

	public function dpShouldForceRemoveUnloadedDomain() {
		return [
			'globals empty' => [
				[],
				[],
			],
			'l10n_unloaded empty' => [
				['l10n_unloaded' => [] ],
				['l10n_unloaded' => [] ],
			],
			'l10n_unloaded with other domain' => [
				['l10n_unloaded' => [ 'some-domain' => true ] ],
				['l10n_unloaded' => [ 'some-domain' => true ] ],
			],
			'l10n_unloaded with WC domain' => [
				['l10n_unloaded' => [ 'some-domain' => true, 'woocommerce' => true ] ],
				['l10n_unloaded' => [ 'some-domain' => true ] ],
			],
		];
	}
}
