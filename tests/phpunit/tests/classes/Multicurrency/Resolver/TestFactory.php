<?php

namespace WCML\MultiCurrency\Resolver;

use tad\FunctionMocker\FunctionMocker;
use WCML\MultiCurrency\Settings;

/**
 * @group multicurrency
 * @group multicurrency-resolver
 */
class TestFactory extends \OTGS_TestCase {

	/**
	 * @test
	 *
	 * @return void
	 */
	public function itShouldCreate() {
		FunctionMocker::replace( Settings::class . '::isModeByLocation', true );

		$this->assertInstanceOf( Resolver::class, Factory::create() );
	}
}
