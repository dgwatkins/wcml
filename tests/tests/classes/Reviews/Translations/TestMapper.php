<?php

namespace WCML\Reviews\Translations;

use function WPML\Container\make;

/**
 * @group reviews
 */
class TestMapper extends \WCML_UnitTestCase {

	/**
	 * @test
	 */
	public function itInstantiatesMapperAndCallsMethods() {
		$mapper = make( Mapper::class );

		$this->assertInternalType( 'int', $mapper->countMissingReviewStrings() );

		$mapper->registerMissingReviewStrings();
	}
}
