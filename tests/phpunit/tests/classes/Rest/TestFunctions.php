<?php

namespace WCML\Rest;

/**
 * @group rest
 * @group rest-functions
 */
class TestFunctions extends \OTGS_TestCase {

	/**
	 * @test
	 */
	public function is_rest_api_request(){

		\WP_Mock::userFunction( 'trailingslashit', [
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		] );
		\WP_Mock::userFunction( 'rest_get_url_prefix', [
			'return' => 'wp-json',
		] );

		// Part 1
		if( isset( $_SERVER['REQUEST_URI'] ) ){
			unset($_SERVER['REQUEST_URI']);
		}

		// test
		$this->assertFalse( Functions::isRestApiRequest() );


		// Part 2
		$_SERVER['REQUEST_URI'] = 'wp-json/wc/';
		// test
		$this->assertTrue( Functions::isRestApiRequest() );

	}

	/**
	 * @test
	 */
	function test_get_api_request_version() {

		$version = rand( 1, 1000 );

		\WP_Mock::userFunction( 'trailingslashit', [
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		] );
		\WP_Mock::userFunction( 'rest_get_url_prefix', [ 'return' => 'wp-json' ] );

		$_SERVER['REQUEST_URI'] = sprintf( '/wp-json/wc/v%d/', $version );
		$this->assertEquals( $version, Functions::getApiRequestVersion() );

		$_SERVER['REQUEST_URI'] = sprintf( rand_str( 8 ), $version );
		$this->assertEquals( 0, Functions::getApiRequestVersion() );

	}

}
