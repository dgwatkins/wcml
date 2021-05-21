<?php

namespace WCML\Utilities;

use WP_Mock\Functions;

/**
 * @group utilities
 */
class TestPost extends \OTGS_TestCase {

	/**
	 * @test
	 * @group wcml-3648
	 */
	public function itShouldInsert() {
		$args = [ 'foo' => 'bar' ];
		$lang = 'fr';
		$trid = 123;

		\WP_Mock::userFunction( 'wp_insert_post' )->once()->with( $args );

		// We cannot mock `add_filter` as it should be spied with `expectFilterAdded`
		// but we can't provide the anonymous callable.
		\WP_Mock::userFunction( 'remove_filter' )->times( 2 );

		Post::insert( $args, $lang, $trid );
	}
}
