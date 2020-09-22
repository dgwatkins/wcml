<?php

namespace WCML\Rest\Wrapper;

/**
 * @group rest
 * @group rest-product-terms
 */
class TestProductTerms extends \OTGS_TestCase {

	/** @var Sitepress */
	private $sitepress;

	public function setUp(){
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( [
			                        'set_element_language_details'
		                        ] )
		                        ->getMock();
	}


	function get_subject() {
		return new ProductTerms( $this->sitepress );
	}


	/**
	 * @test
	 */
	public function filter_terms_query_lang_en(){

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_params' ] )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( [ 'lang' => 'en' ] );

		\WP_Mock::onFilter( 'wpml_language_is_active' )->with( false, 'en' )->reply( true );

		$args = [];
		\WP_Mock::userFunction( 'remove_filter', [ 'times' => 0 ] );
		$subject->query( $args, $request );

	}

	/**
	 * @test
	 */
	public function filter_terms_query_lang_all(){

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_params' ] )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( [ 'lang' => 'all' ] );

		$args = [];

		\WP_Mock::userFunction( 'remove_filter', [ 'times' => 2 ] );
		$subject->query( $args, $request );

	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Invalid language parameter
	 */
	public function filter_terms_query_lang_exception(){

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_params' ] )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( [ 'lang' => 'EXCEPTION' ] );

		\WP_Mock::onFilter( 'wpml_language_is_active' )->with( false, 'EXCEPTION' )->reply( false );

		\WP_Mock::wpPassthruFunction( '__' );

		$args = [];
		$subject->query( $args, $request );

	}

}
