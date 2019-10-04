<?php

use function WPML\Container\make;

/**
 * @group dic
 */
class TestClassesWithDic extends \WCML_UnitTestCase {

	/**
	 * @test
	 */
	public function itShouldBeInstantiatedWithDic() {
		$classesToTest = wpml_collect( [
			\WCML\RewriteRules\Hooks::class,
		] );

		$classesToTest->each( function( $className ) {
			$this->assertInstanceOf( $className, make( $className ) );
		} );
	}
}
