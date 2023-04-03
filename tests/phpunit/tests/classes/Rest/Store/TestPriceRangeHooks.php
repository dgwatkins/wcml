<?php

namespace WCML\Rest\Store;

/**
 * @group rest/store
 */
class TestPriceRangeHooks extends \OTGS_TestCase {

	/**
	 * @test
	 *
	 * @return void 
	 */
	public function itAddsHooks() {
		$subject = $this->getSubject();

		\WP_Mock::expectFilterAdded( 'rest_request_after_callbacks', [ $subject, 'convertPriceRange' ], 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function itConvertsPriceRange() {
		$minPriceEur = 1000;
		$maxPriceEur = 2000;

		$convertEurToUsd = function( $value, $currency = 'USD' ) {
			$this->assertSame( 'USD', $currency );
			return $value * 1.5;
		};

		$data = [
			'price_range' => (object) [
				'min_price' => $minPriceEur,
				'max_price' => $maxPriceEur,
			],
		];

		$expected = [
			'price_range' => (object) [
				'min_price' => $convertEurToUsd( $minPriceEur ),
				'max_price' => $convertEurToUsd( $maxPriceEur ),
			],
		];

		\WP_Mock::userFunction( 'trailingslashit', [
			'return' => function( $url ) {
				return rtrim( $url, '/' ) . '/';
			}
		] );

		$request = $this->getMockBuilder( \WP_REST_Request::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_method', 'get_route', 'get_param' ] )
			->getMock();

		$request->method( 'get_method' )
			->willReturn( 'GET' );

		$request->method( 'get_route' )
			->willReturn( '/wc/store/v1/products/collection-data' );

		$request->method( 'get_param' )
			->with( 'calculate_price_range' )
			->willReturn( 'true' );

		$response = $this->getMockBuilder( \WP_Rest_Response::class )
			->disableOriginalConstructor()
			->setMethods( [ 'set_data', 'get_data' ] )
			->getMock();
		$response->data = $data;

		$response->method( 'get_data' )
			->willReturnCallback( function() use ( &$response ) {
				return $response->data;
			} );

		$response->method( 'set_data' )
			->willReturnCallback( function( $data ) use ( &$response ) {
				$response->data = $data;
			} );

		$prices = $this->getMockBuilder( \WCML_Multi_Currency_Prices::class )
			->disableOriginalConstructor()
			->setMethods( [ 'convert_price_amount' ] )
			->getMock();

		$prices->method( 'convert_price_amount' )->willReturnCallback( $convertEurToUsd );

		$mc = $this->getMockBuilder( \WCML_Multi_Currency::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_default_currency', 'get_client_currency' ] )
			->getMock();

		$mc->prices = $prices;

		$mc->method( 'get_default_currency' )
			->willReturn( 'EUR' );

		$mc->method( 'get_client_currency' )
			->willReturn( 'USD' );

		$subject = $this->getSubject( $mc );

		$subject->convertPriceRange( $response, [], $request );

		$this->assertEquals( $expected, $response->get_data() );
	}

	/**
	 * @test
	 */
	public function itDoesNOTConvertPriceRangeWhenNoGET() {
		$request = $this->getMockBuilder( \WP_REST_Request::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_method' ] )
			->getMock();

		$request->method( 'get_method' )
			->willReturn( 'POST' );

		$response = $this->getMockBuilder( \WP_Rest_Response::class )
			->disableOriginalConstructor()
			->getMock();

		$subject = $this->getSubject();

		$this->assertEquals(
			$response,
			$subject->convertPriceRange( $response, [], $request )
		);
	}

	/**
	 * @test
	 */
	public function itDoesNOTConvertPriceRangeForOtherRoutes() {
		$request = $this->getMockBuilder( \WP_REST_Request::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_method', 'get_route' ] )
			->getMock();

		$request->method( 'get_method' )
			->willReturn( 'GET' );

		$request->method( 'get_route' )
			->willReturn( '/wc/store/v1/products/other-route' );

		$response = $this->getMockBuilder( \WP_Rest_Response::class )
			->disableOriginalConstructor()
			->getMock();

		$subject = $this->getSubject();

		$this->assertEquals(
			$response,
			$subject->convertPriceRange( $response, [], $request )
		);
	}

	/**
	 * @test
	 */
	public function itDoesNOTConvertPriceRangeWhenParamMissing() {
		$request = $this->getMockBuilder( \WP_REST_Request::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_method', 'get_route', 'get_param' ] )
			->getMock();

		$request->method( 'get_method' )
			->willReturn( 'GET' );

		$request->method( 'get_route' )
			->willReturn( '/wc/store/v1/products/collection-data' );

		$request->method( 'get_param' )
			->with( 'calculate_price_range' )
			->willReturn( null );

		$response = $this->getMockBuilder( \WP_Rest_Response::class )
			->disableOriginalConstructor()
			->getMock();

		$subject = $this->getSubject();

		$this->assertEquals(
			$response,
			$subject->convertPriceRange( $response, [], $request )
		);
	}

	/**
	 * @return PriceRangeHooks
	 */
	private function getSubject( $mc = null ) {
		$woocommerce_wpml = $this->getMockBuilder( '\woocommerce_wpml' )
			->disableOriginalConstructor()
			->getMock();

		if ( null === $mc ) {
			$mc = $this->getMockBuilder( \WCML_Multi_Currency::class )
				->disableOriginalConstructor()
				->getMock();
		}

		$woocommerce_wpml->multi_currency = $mc;

		return new PriceRangeHooks( $woocommerce_wpml );
	}

}
