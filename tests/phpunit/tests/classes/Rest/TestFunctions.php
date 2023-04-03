<?php

namespace WCML\Rest;

/**
 * @group rest
 * @group rest-functions
 */
class TestFunctions extends \OTGS_TestCase {

	public function tearDown() {
		unset( $_SERVER['REQUEST_URI'] );
		parent::tearDown();
	}

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
		$_SERVER['REQUEST_URI'] = 'wp-json/wc/v3/';
		// test
		$this->assertTrue( Functions::isRestApiRequest() );
	}

	/**
	 * @test
	 * @dataProvider dp_api_requests
	 */
	public function test_get_api_request_version( $uri, $version ) {

		\WP_Mock::userFunction( 'trailingslashit', [
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		] );
		\WP_Mock::userFunction( 'rest_get_url_prefix', [ 'return' => 'wp-json' ] );

		$_SERVER['REQUEST_URI'] = $uri;
		$this->assertEquals( $version, Functions::getApiRequestVersion() );

		$_SERVER['REQUEST_URI'] = '/random-string/';
		$this->assertEquals( 0, Functions::getApiRequestVersion() );
	}

	public function dp_api_requests() {
		return [
			[ '/wp-json/wc/v3/', 3 ],
			[ '/wp-json/wc/V2/', 2 ],
		];
	}

	/**
	 * @test
	 * @dataProvider dpTestGetStoreStrippedEndpoint
	 */
	public function TestGetStoreStrippedEndpoint( $route, $expected ) {
		\WP_Mock::userFunction( 'trailingslashit', [
			'return' => function( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		] );

		$request = $this->getMockBuilder( '\WP_REST_Request' )
			->disableOriginalConstructor()
			->setMethods( [ 'get_route' ] )
			->getMock();

		$request->method( 'get_route' )
			->willReturn( $route );

		$this->assertEquals( $expected, Functions::getStoreStrippedEndpoint( $request ) );
	}

	public function dpTestGetStoreStrippedEndpoint() {
		return [
			'other rest url' => [ '/any-url/',             null ],
			'store rest url' => [ '/wc/store/v1/test/it/', 'test/it' ],
		];
	}

}
