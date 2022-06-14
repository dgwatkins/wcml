<?php

namespace WCML\Utilities\Suspend;

/**
 * @group utilities
 * @group wcml-4110
 */
class TestFilters extends \OTGS_TestCase {

	const PRIORITY_10 = 10;
	const PRIORITY_0  = 0;

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testWithResume() {
		$subject = $this->getSubject();

		// Do something;

		$subject->resume();
	}

	/**
	 * @test
	 *
	 * @return void
	 */
	public function testWithRunAndResume() {
		$this->assertEquals(
			'foo',
			$this->getSubject()->runAndResume( function() {
				return 'foo';
			} )
		);
	}

	/**
	 * @return Filters
	 */
	private function getSubject() {
		$args1 = [ 'the_hook_1', 'some_function_1', self::PRIORITY_10, 2 ];
		$args2 = [ 'the_hook_2', 'some_function_2', 20 ];
		$args3 = [ 'the_hook_3', 'some_function_3', 20 ];
		$args4 = [ 'the_hook_1', 'some_function_4', self::PRIORITY_0, 2 ];

		\WP_Mock::userFunction( 'remove_filter', [
			'times'  => 1,
			'args'   => $args1,
			'return' => true,
		] );

		\WP_Mock::userFunction( 'remove_filter', [
			'times'  => 1,
			'args'   => $args2,
			'return' => true,
		] );

		\WP_Mock::userFunction( 'remove_filter', [
			'times'  => 1,
			'args'   => $args3,
			'return' => false, // This filter was not actually removed.
		] );

		\WP_Mock::userFunction( 'remove_filter', [
			'times'  => 1,
			'args'   => $args4,
			'return' => true,
		] );

		\WP_Mock::userFunction( 'has_filter', [
			'times'  => 1,
			'args'   => $args1,
			'return' => self::PRIORITY_10,
		] );

		\WP_Mock::userFunction( 'has_filter', [
			'times'  => 1,
			'args'   => $args2,
			'return' => false, // We'll consider this filter has been re-added outside out code.
		] );

		\WP_Mock::userFunction( 'has_filter', [
			'times' => 0, // This filter was not actually suspended.
			'args'  => $args3,
		] );

		\WP_Mock::userFunction( 'has_filter', [
			'times'  => 1,
			'args'   => $args4,
			'return' => self::PRIORITY_0,
		] );

		\WP_Mock::expectFilterAdded( ...$args1 );
		\WP_Mock::expectFilterNotAdded( ...$args2 ); // Considering it was already re-added outside out code.
		\WP_Mock::expectFilterNotAdded( ...$args3 ); // As it was not removed, we do not re-add it.
		\WP_Mock::expectFilterAdded( ...$args4 );

		return new Filters( [ $args1, $args2, $args3, $args4 ] );
	}
}
