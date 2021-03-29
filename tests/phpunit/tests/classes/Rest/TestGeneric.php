<?php

namespace WCML\Rest;

/**
 * @group rest
 * @group rest-generic
 */
class TestGeneric extends \OTGS_TestCase {

	/**
	 * @test
	 */
	function auto_adjust_included_ids() {

		$wp_query = $this->getMockBuilder( 'WP_Query' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get', 'set' ] )
		                 ->getMock();

		$wp_query->method( 'get' )->will( $this->returnCallback(
			function ( $var ) use ( $wp_query ) {
				return $wp_query->query_vars[ $var ];
			}
		) );
		$wp_query->method( 'set' )->will( $this->returnCallback(
			function ( $var, $value ) use ( $wp_query ) {
				return $wp_query->query_vars[ $var ] = $value;
			}
		) );

		$posts = [
			'original'    => [ rand( 1, 100 ), rand( 101, 200 ) ],
			'translation' => [ rand( 201, 300 ), rand( 301, 400 ) ]
		];

		// no adjusting
		$wp_query->set( 'lang', '' );
		$wp_query->set( 'post__in', false );
		Generic::autoAdjustIncludedIds( $wp_query );
		$this->assertFalse( $wp_query->get( 'post__in' ) );

		// no adjusting
		$wp_query->set( 'lang', 'en' );
		$wp_query->set( 'post__in', false );
		Generic::autoAdjustIncludedIds( $wp_query );
		$this->assertFalse( $wp_query->get( 'post__in' ) );

		// no adjusting
		$wp_query->set( 'lang', 'en' );
		$wp_query->set( 'post__in', $posts['original'] );
		Generic::autoAdjustIncludedIds( $wp_query );
		$this->assertEquals( $posts['original'], $wp_query->get( 'post__in' ) );

		// adjusting
		\WP_Mock::userFunction( 'get_post_type', [
			'times'  => count( $posts['original'] ),
			'return' => function ( $id ) {
				return 'product';
			}
		] );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'  => [ $posts['original'][0], 'product', true ],
			'return' => $posts['translation'][0]
		] );

		\WP_Mock::userFunction( 'wpml_object_id_filter', [
			'args'  => [ $posts['original'][1], 'product', true ],
			'return' => $posts['translation'][1]
		] );

		$wp_query->set( 'lang', '' );
		$wp_query->set( 'post__in', $posts['original'] );
		Generic::autoAdjustIncludedIds( $wp_query );
		$this->assertEquals( $posts['translation'], $wp_query->get( 'post__in' ) );

	}

	/**
	 * @test
	 */
	function test_initialize_with_default_lang_parameters_in_get() {

		$lang = 'en';
		$_SERVER['REQUEST_URI'] = '?lang=' . $lang;

		\WP_Mock::userFunction( 'wpml_get_default_language', [
			'return' => $lang
		] );

		$expected_request_uri = str_replace( 'lang=' . $lang, '', $_SERVER['REQUEST_URI'] );

		Generic::preventDefaultLangUrlRedirect();

		$this->assertEquals( $expected_request_uri, $_SERVER['REQUEST_URI'] );

		unset( $_SERVER['REQUEST_URI'] );
	}

	/**
	 * @test
	 */
	function test_initialize_with_not_default_lang_parameters_in_get() {

		$lang = 'en';
		$_SERVER['REQUEST_URI'] = '?lang=' . $lang;

		\WP_Mock::userFunction( 'wpml_get_default_language', [
			'return' => 'non-' . $lang
		] );

		$expected_request_uri = $_SERVER['REQUEST_URI'];

		Generic::preventDefaultLangUrlRedirect();

		$this->assertEquals( $expected_request_uri, $_SERVER['REQUEST_URI'] );

		unset( $_SERVER['REQUEST_URI'] );

	}
}
