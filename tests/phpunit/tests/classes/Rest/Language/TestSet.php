<?php

namespace WCML\Rest\Language;

/**
 * @group rest
 * @group wcml-3591
 */
class TestSet extends \OTGS_TestCase {

	public function tearDown() {
		unset( $_GET['lang'] );
		return parent::tearDown();
	}

	/**
	 * @test
	 */
	public function itShouldNotSetFromUrlQueryVarIfLangNotSet() {
		$restServer = $this->getRestServer();

		unset( $_GET['lang'] );

		\WP_Mock::userFunction( 'wpml_switch_language_action' )->times( 0 );

		Set::fromUrlQueryVar( $restServer );
	}


	/**
	 * @test
	 */
	public function itShouldSetFromUrlQueryVar() {
		$lang         = 'ro';
		$_GET['lang'] = $lang;
		$restServer   = $this->getRestServer();

		\WP_Mock::userFunction( 'wpml_switch_language_action' )
			->times( 1 )
			->with( $lang );

		Set::fromUrlQueryVar( $restServer );
	}

	/**
	 * @test
	 */
	public function itShouldSetBeforeCallbacksFromRequestParams() {
		$lang = 'fr';

		$response = 'This is a response object';
		$handler  = [ 'This is a handler' ];
		$request  = $this->getRestRequest( [ 'lang' => $lang ] );

		\WP_Mock::userFunction( 'wpml_switch_language_action' )
		        ->times( 1 )
		        ->with( $lang );

		$this->assertSame(
			$response,
			Set::beforeCallbacks( $response, $handler, $request )
		);
	}

	/**
	 * @test
	 */
	public function itShouldSetBeforeCallbacksFromProduct() {
		$lang      = 'fr';
		$productId = 123;

		$response = 'This is a response object';
		$handler  = [
			'callback' => [
				$this->getMockBuilder( '\WC_REST_Products_Controller' )->getMock(),
				'get_item',
			],
		];
		$request  = $this->getRestRequest( [ 'id' => $productId ] );

		$languageDetails = [
			'foo'           => 'bar',
			'language_code' => $lang,
		];

		\WP_Mock::onFilter( 'wpml_post_language_details' )
			->with( [], $productId )
			->reply( $languageDetails );

		\WP_Mock::userFunction( 'wpml_switch_language_action' )
		        ->times( 1 )
		        ->with( $lang );

		$this->assertSame(
			$response,
			Set::beforeCallbacks( $response, $handler, $request )
		);
	}

	/**
	 * @test
	 * @dataProvider dpInvalidCallbacks
	 *
	 * @param mixed $callback
	 */
	public function itShouldNotSetBeforeCallbacks( $callback ) {
		$response = 'This is a response object';
		$handler  = [
			'callback' => $callback,
		];
		$request  = $this->getRestRequest();

		\WP_Mock::userFunction( 'wpml_switch_language_action' )
		        ->times( 0 );

		$this->assertSame(
			$response,
			Set::beforeCallbacks( $response, $handler, $request )
		);
	}

	public function dpInvalidCallbacks() {
		return [
			'Not a \WC_REST_Products_Controller' => [
				[
					$this->getMockBuilder( '\SomeRestController' )->getMock(),
					'doSomething',
				],
			],
			'Just a simple function - wcml-3716' => [
				[
					'a_dummy_simple_function'
				],
			],
		];
	}

	private function getRestServer() {
		return $this->getMockBuilder( '\WP_REST_Server' )
		            ->disableOriginalConstructor()->getMock();
	}

	private function getRestRequest( array $params = [] ) {
		$request = $this->getMockBuilder( '\WP_REST_Request' )
			->setMethods( [ 'get_param' ] )
			->disableOriginalConstructor()->getMock();
		$request->method( 'get_param' )
			->willReturnCallback( function( $key ) use ( $params ) {
				return isset( $params[ $key ] ) ? $params[ $key ] : null;
			} );

		return $request;
	}
}
