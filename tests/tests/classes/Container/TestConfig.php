<?php

namespace WCML\Container;

use function WPML\Container\make;

/**
 * @group container
 */
class TestConfig extends \WCML_UnitTestCase {

	/**
	 * @test
	 */
	public function itShouldShareInstances() {
		foreach ( Config::getSharedInstances() as $instance ) {
			$class = get_class( $instance );
			$this->assertSame( $instance, make( $class ) );
		}
	}
}
