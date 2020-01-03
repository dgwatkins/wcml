<?php
/**
 * Class Test_WCML_REST_API_Query_Filters_Products
 * @group wcml-1979
 */
class Test_WCML_REST_API_Query_Filters_Products extends OTGS_TestCase {

	/** @var WPML_Query_Filter */
	private $wpml_query_filter;

	public function setUp(){
		parent::setUp();
		$this->wpml_query_filter = $this->getMockBuilder( 'WPML_Query_Filter' )
		                                 ->disableOriginalConstructor()
		                                 ->getMock();
	}

	public function tearDown() {
		unset( $this->wpml_query_filter );
		parent::tearDown();
	}

	/**
	 * @test
	 */
	function test_add_hooks(){

		$subject = new WCML_REST_API_Query_Filters_Products( $this->wpml_query_filter );

		\WP_Mock::expectFilterAdded( 'woocommerce_rest_product_query', array( $subject, 'filter_products_query'), 10, 2 );
		\WP_Mock::expectFilterAdded( 'woocommerce_rest_product_object_query', array( $subject, 'filter_products_query'), 10, 2 );

		$subject->add_hooks();

	}

	/**
	 * @test
	 *
	 */
	public function filter_products_query_lang_en(){

		$subject = new WCML_REST_API_Query_Filters_Products( $this->wpml_query_filter );

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params' ) )
		                 ->getMock();
		$request->method( 'get_params' )->willReturn( array( 'lang' => 'en' ) );

		$args = [];

		\WP_Mock::wpFunction( 'remove_filter', array(
			'times' => 0
		) );

		$subject->filter_products_query( $args, $request );

	}

	/**
	 * @test
	 *
	 */
	public function filter_products_query_lang_all(){
		$subject = new WCML_REST_API_Query_Filters_Products( $this->wpml_query_filter );

		$args = [];

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'get_params' ) )
		                 ->getMock();
		$request->method( 'get_params' )->willReturn( array( 'lang' => 'all' ) );

		\WP_Mock::wpFunction( 'remove_filter', array(
			'times' => 2
		) );

		$subject->filter_products_query( $args, $request );

	}

}
