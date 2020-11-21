<?php

namespace WCML\Rest\Wrapper;

use WCML\Rest\Wrapper\Reports\TopSeller;

/**
 * @group rest
 * @group rest-reports
 */
class TestTopSeller extends \OTGS_TestCase {

	/** @var Sitepress */
	private $sitepress;

	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( [
			                        'get_language_for_element',
			                        'is_active_language'
		                        ] )
		                        ->getMock();
	}


	function get_subject() {
		return new TopSeller( $this->sitepress );
	}

	/**
	 * @test
	 */
	public function filter_top_sellers_shod_remove_if_language_not_matched() {

		$object             = new \stdClass();
		$object->product_id = 12;

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => [ $object->product_id ],
			'return' => 'product'
		] );

		$this->sitepress->method( 'is_active_language' )->with( 'es' )->willReturn( true );
		$this->sitepress->method( 'get_language_for_element' )->with( $object->product_id, 'post_product' )->willReturn( 'en' );

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_params' ] )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( [ 'lang' => 'es' ] );

		$response = $this->getMockBuilder( 'WP_REST_Response' )
		                 ->disableOriginalConstructor()
		                 ->getMock();

		\WP_Mock::userFunction( 'remove_filter', [ 'times' => 0 ] );
		$this->assertFalse( $subject->prepare( $response, $object, $request ) );
	}

	/**
	 * @test
	 */
	public function filter_top_sellers_shod_not_remove_if_language_the_same() {

		$object             = new \stdClass();
		$object->product_id = 12;

		\WP_Mock::userFunction( 'get_post_type', [
			'args'   => [ $object->product_id ],
			'return' => 'product'
		] );

		$this->sitepress->method( 'is_active_language' )->with( 'es' )->willReturn( true );
		$this->sitepress->method( 'get_language_for_element' )->with( $object->product_id, 'post_product' )->willReturn( 'es' );

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_params' ] )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( [ 'lang' => 'es' ] );

		$response = $this->getMockBuilder( 'WP_REST_Response' )
		                 ->disableOriginalConstructor()
		                 ->getMock();

		$this->assertEquals( $response, $subject->prepare( $response, $object, $request ) );
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Invalid language parameter
	 */
	public function filter_top_sellers_lang_exception() {

		$this->sitepress->method( 'is_active_language' )->with( 'EXCEPTION' )->willReturn( false );

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_params' ] )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( [ 'lang' => 'EXCEPTION' ] );

		$response = $this->getMockBuilder( 'WP_REST_Response' )
		                 ->disableOriginalConstructor()
		                 ->getMock();

		$subject->prepare( $response, new \stdClass(), $request );
	}
}
