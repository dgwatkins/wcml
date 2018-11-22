<?php

class Test_WCML_WpFastest_Cache extends OTGS_TestCase {

	private function get_subject() {
		return new WCML_WpFastest_Cache();
	}

	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'wcml_is_cache_enabled_for_switching_currency', array(
			$subject,
			'is_cache_enabled_for_switching_currency'
		) );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_set_is_cache_enabled_for_switching_currency() {

		$cache_options                       = new stdClass();
		$cache_options->wpFastestCacheStatus = 'on';
		$cache_options                       = json_encode( $cache_options );

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'WpFastestCache' ),
			'return' => $cache_options,
			'times'  => 1
		) );

		$subject = $this->get_subject();
		$this->assertTrue( $subject->is_cache_enabled_for_switching_currency( false ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_set_is_cache_enabled_for_switching_currency() {

		$cache_options                       = new stdClass();
		$cache_options                       = json_encode( $cache_options );

		\WP_Mock::userFunction( 'get_option', array(
			'args'   => array( 'WpFastestCache' ),
			'return' => $cache_options,
			'times'  => 1
		) );

		$subject = $this->get_subject();
		$this->assertFalse( $subject->is_cache_enabled_for_switching_currency( false ) );
	}

}
