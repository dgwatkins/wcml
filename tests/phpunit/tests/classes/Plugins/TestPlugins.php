<?php

namespace WCML;

use tad\FunctionMocker\FunctionMocker;

/**
 * @group plugins
 * @group wcml-2991
 */
class TestPlugins extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function itShouldDoNothingIfIclSitepressVersionIsSet() {
		FunctionMocker::replace( 'defined', function( $name ) {
			return 'ICL_SITEPRESS_VERSION' == $name;
		} );

		\WP_Mock::userFunction( 'get_option', [ 'times' => 0 ] );
		\WP_Mock::expectActionNotAdded( 'plugins_loaded', [ Plugins::class, 'loadCoreFirst' ] );


		Plugins::maybeLoadCoreFirst();
	}

	/**
	 * @test
	 */
	public function itShouldDoNothingIfCoreIsNotActive() {
		FunctionMocker::replace( 'defined', false );

		\WP_Mock::userFunction( 'get_option', [
			'args'   => [ 'active_plugins' ],
			'return' => [ 'foo/bar.php' ],
		] );
		\WP_Mock::expectActionNotAdded( 'plugins_loaded', [ Plugins::class, 'loadCoreFirst' ] );


		Plugins::maybeLoadCoreFirst();
	}

	/**
	 * @test
	 */
	public function itShouldAddActionIfCoreIsActive() {
		FunctionMocker::replace( 'defined', false );

		\WP_Mock::userFunction( 'get_option', [
			'args'   => [ 'active_plugins' ],
			'return' => [
				'foo/bar.php',
				'sitepress-multilingual-cms/sitepress.php',
			],
		] );
		\WP_Mock::expectActionAdded( 'plugins_loaded', [ Plugins::class, 'loadCoreFirst' ] );


		Plugins::maybeLoadCoreFirst();
	}

	/**
	 * @test
	 */
	public function itShouldNotLoadCoreFirstIfClassIsMissing() {
		$classExists = FunctionMocker::replace( 'class_exists', false );
		$wpmlPlugins = FunctionMocker::replace( '\WPML\Plugins::loadCoreFirst', null );

		Plugins::loadCoreFirst();

		$classExists->wasCalledWithOnce( [ '\WPML\Plugins' ] );
		$wpmlPlugins->wasNotCalled();
	}

	/**
	 * @test
	 */
	public function itShouldLoadCoreFirst() {
		$classExists = FunctionMocker::replace( 'class_exists', true );
		$wpmlPlugins = FunctionMocker::replace( '\WPML\Plugins::loadCoreFirst', null );

		Plugins::loadCoreFirst();

		$classExists->wasCalledWithOnce( [ '\WPML\Plugins' ] );
		$wpmlPlugins->wasCalledOnce();
	}
}
