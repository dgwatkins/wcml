<?php
/**
 * Class Test_WCML_REST_API_Query_Filters_Terms
 * @group wcml-1979
 */
class Test_WCML_REST_API_Query_Filters_Terms extends OTGS_TestCase {

	/** @var  @var SitePress */
	private $sitepress;

	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array(
			                  'get_active_languages',
		                  ) )
		                  ->getMock();

		$this->sitepress->method('get_active_languages')->willReturn( [ 'en' => 1 ] );

	}

	private function get_subject(){
		return new WCML_REST_API_Query_Filters_Terms( $this->sitepress );
	}

	/**
	 * @test
	 */
	function test_add_hooks(){

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'woocommerce_rest_product_cat_query', array( $subject, 'filter_terms_query'), 10, 2 );
		\WP_Mock::expectActionAdded( 'woocommerce_rest_product_tag_query', array( $subject, 'filter_terms_query'), 10, 2 );

		$subject->add_hooks();

	}

	/**
	 * @test
	 */
	public function filter_terms_query_lang_en(){

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params' ) )
		                 ->getMock();
		$request->method( 'get_params' )->willReturn( array( 'lang' => 'en' ) );

		$args = [];
		\WP_Mock::wpFunction( 'remove_filter', array( 'times' => 0 ) );
		$subject->filter_terms_query( $args, $request );

	}

	/**
	 * @test
	 */
	public function filter_terms_query_lang_all(){

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_params' ) )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( array( 'lang' => 'all' ) );

		$args = [];

		\WP_Mock::wpFunction( 'remove_filter', array( 'times' => 2 ) );
		$subject->filter_terms_query( $args, $request );

	}

	/**
	 * @test
	 * @expectedException WCML_REST_Invalid_Language_Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Invalid language parameter
	 */
	public function filter_terms_query_lang_exception(){

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'get_params' ) )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( array( 'lang' => 'EXCEPTION' ) );

		\WP_Mock::wpPassthruFunction( '__' );

		$args = [];
		$subject->filter_terms_query( $args, $request );

	}

}
