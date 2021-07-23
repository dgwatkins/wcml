<?php

namespace WCML\Reviews\Translations;

/**
 * @group reviews
 */
class TestFactory extends \WCML_UnitTestCase {

	/**
	 * @test
	 */
	public function itShouldCreate() {
		$hooks = ( new Factory() )->create();

		$this->assertCount( 1, $hooks );
		$this->assertInstanceOf( FrontEndHooks::class, $hooks[0] );
	}
}
