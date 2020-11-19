<?php

namespace WCML\Rest\Wrapper\Products;

/**
 * @group rest
 * @group rest-products
 */
class TestLanguages extends \OTGS_TestCase {


	/** @var WPML_Query_Filter */
	private $wpml_query_filter;
	/** @var Sitepress */
	private $sitepress;
	/** @var WPML_Post_Translation */
	private $wpml_post_translations;

	public function setUp(){
		parent::setUp();
		$this->wpml_query_filter = $this->getMockBuilder( 'WPML_Query_Filter' )
		                                ->disableOriginalConstructor()
		                                ->getMock();

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( [
			                        'set_element_language_details', 'copy_custom_fields'
		                        ] )
		                        ->getMock();

		$this->wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                                     ->disableOriginalConstructor()
		                                     ->setMethods( [ 'get_element_trid', 'get_element_translations', 'get_element_lang_code' ] )
		                                     ->getMock();
	}


	function get_subject() {
		return new Languages( $this->sitepress, $this->wpml_post_translations, $this->wpml_query_filter );
	}


	/**
	 * @test
	 *
	 */
	public function filter_products_query_lang_en(){

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_params' ] )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( [ 'lang' => 'en' ] );

		$args = [];

		\WP_Mock::userFunction( 'remove_filter', [
			'times' => 0
		] );

		$subject->query( $args, $request );

	}

	/**
	 * @test
	 *
	 */
	public function filter_products_query_lang_all(){
		$subject = $subject = $this->get_subject();

		$args = [];

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_params' ] )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( [ 'lang' => 'all' ] );

		\WP_Mock::userFunction( 'remove_filter', [
			'times' => 2
		] );

		$subject->query( $args, $request );

	}

	/**
	 * @test
	 */
	public function append_product_language_and_translations() {

		$this->default_language   = 'en';
		$this->secondary_language = 'fr';

		$trid                                 = 11;
		$this->product_id_in_default_language = 12;

		// for original
		$product_data       = $this->getMockBuilder( 'WP_REST_Response' )
		                           ->disableOriginalConstructor()
		                           ->getMock();
		$product_data->data = [
			'id' => $this->product_id_in_default_language
		];

		$this->wpml_post_translations->method( 'get_element_trid' )->with( $this->product_id_in_default_language )->willReturn( $trid );

		$fr_translation_id = 14;

		$this->wpml_post_translations->method( 'get_element_translations' )->with( $this->product_id_in_default_language, $trid )->willReturn( [ $fr_translation_id ] );

		$that = $this;
		$this->wpml_post_translations->method( 'get_element_lang_code' )->willReturnCallback( function ( $product_id ) use ( $that ) {
			if ( $that->product_id_in_default_language === $product_id ) {
				return $that->default_language;
			}

			return $that->secondary_language;

		} );

		$subject      = $this->get_subject();
		$product_data = $subject->prepare( $product_data,
			$this->getMockBuilder( 'WC_Data' )
			     ->disableOriginalConstructor()
			     ->getMock(),
			$this->getMockBuilder( 'WP_REST_Request' )
			     ->disableOriginalConstructor()
			     ->getMock() );

		$this->assertEquals( $this->default_language, $product_data->data['lang'] );
		$this->assertEquals(
			[ $this->secondary_language => $fr_translation_id ],
			$product_data->data['translations']
		);
	}

	/**
	* @test
	* @dataProvider api_method_type
	* @expectedException Exception
	* @expectedExceptionCode 422
	* @expectedExceptionMessage Invalid language parameter
	*/
	function set_product_language_wrong_lang( $api_method_type ) {

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params', 'get_method' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [ 'lang' => 'ru' ] );
		$request1->method( 'get_method' )->willReturn( $api_method_type );

		$this->sitepress->method( 'set_element_language_details' )->willReturn( true );

		$post     = $this->getMockBuilder( 'WP_Post' )
		                 ->disableOriginalConstructor()
		                 ->getMock();
		$post->ID = 1;

		$subject = $this->get_subject();
		$subject->insert( $post, $request1, true );

	}

	/**
	 * @test
	 * @dataProvider api_method_type
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Product not found:
	 */
	function set_product_language_no_source_product( $api_method_type ) { // with translation_of

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params', 'get_method' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [
			'lang'           => 'ro',
			'translation_of' => 11
		] );
		$request1->method( 'get_method' )->willReturn( $api_method_type );

		\WP_Mock::onFilter( 'wpml_language_is_active' )->with( false, 'ro' )->reply( true );

		$this->sitepress->method( 'set_element_language_details' )->willReturn( true );

		$post     = $this->getMockBuilder( 'WP_Post' )
		                 ->disableOriginalConstructor()
		                 ->getMock();
		$post->ID = 12;

		$subject = $this->get_subject();
		$subject->insert( $post, $request1, true );

	}

	/**
	 * @test
	 * @dataProvider api_method_type
	 */
	function set_product_language_with_trid( $api_method_type ) {

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params', 'get_method' ] )
		                 ->getMock();
		$translation_of = 11;

		$request1->method( 'get_params' )->willReturn( [
			'lang'           => 'ro',
			'translation_of' => $translation_of
		] );
		$request1->method( 'get_method' )->willReturn( $api_method_type );

		$this->expected_trid = null;
		$this->actual_trid   = 12;

		$post = $this->getMockBuilder( 'WC_Simple_Product' )
		             ->disableOriginalConstructor()
		             ->setMethods( [
			             'get_id'
		             ] )
		             ->getMock();
		$post->ID = rand( 1, 100 );

		$post->method( 'get_id' )->willReturn( $post->ID );

		$this->wpml_post_translations->method( 'get_element_trid' )->with( $translation_of )->willReturn( $this->actual_trid );

		\WP_Mock::onFilter( 'wpml_language_is_active' )->with( false, 'ro' )->reply( true );

		$this->sitepress->method( 'set_element_language_details' )->will( $this->returnCallback(
			function ( $post_id, $element_type, $trid, $lang ) {
				$this->expected_trid = $trid;

				return true;
			}
		) );
		$this->sitepress->method( 'copy_custom_fields' )->with( $translation_of, $post->ID )->willReturn( true );

		if ( ! defined( 'ICL_TM_COMPLETE' ) ) {
			define( 'ICL_TM_COMPLETE', true );
		}

		$this->test_data['posts'][ $post->ID ] = $post;

		\WP_Mock::userFunction( 'get_post', [
			'args'  => [ $post->ID ],
			'return' => $post
		] );

		\WP_Mock::userFunction( 'wpml_tm_save_post', [
			'times' => 1,
			'args'  => [ $post->ID, $post, ICL_TM_COMPLETE ]
		] );

		$subject = $this->get_subject();
		$subject->insert( $post, $request1, true );
		$this->assertEquals( $this->expected_trid, $this->actual_trid );

	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Using "translation_of" requires providing a "lang" parameter too
	 */
	function set_product_language_missing_lang() {

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [ 'translation_of' => rand( 1, 100 ) ] );

		$post     = $this->getMockBuilder( 'WP_Post' )
		                 ->disableOriginalConstructor()
		                 ->getMock();
		$post->ID = rand( 1, 100 );

		$subject = $this->get_subject();
		$subject->insert( $post, $request1, true );

	}

	/**
	 * @test
	 * @dataProvider api_method_type
	 */
	function set_product_language_new_product( $api_method_type ) { // no translation_of

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params', 'get_method' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [
			'lang' => 'ro'
		] );
		$request1->method( 'get_method' )->willReturn( $api_method_type );

		$this->expected_trid = null;
		$this->actual_trid   = null;

		$this->wpml_post_translations->method( 'get_element_trid' )->willReturn( $this->actual_trid );
		$this->sitepress->method( 'set_element_language_details' )->will( $this->returnCallback(
			function ( $post_id, $element_type, $trid, $lang ) {
				$this->expected_trid = null;

				return true;
			}
		) );

		$post = $this->getMockBuilder( 'WC_Simple_Product' )
		             ->disableOriginalConstructor()
		             ->setMethods( [
			             'get_id'
		             ] )
		             ->getMock();

		$post->ID = rand( 1, 100 );
		$post->method( 'get_id' )->willReturn( $post->ID );

		\WP_Mock::onFilter( 'wpml_language_is_active' )->with( false, 'ro' )->reply( true );

		$subject = $this->get_subject();
		$subject->insert( $post, $request1, true );
		$this->assertEquals( $this->expected_trid, $this->actual_trid );

	}

	/**
	 * @test
	 */
	function do_no_set_poduct_language_if_method_not_post_or_put(){


		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params', 'get_method' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [
			'lang' => 'en'
		] );
		$request1->method( 'get_method' )->willReturn( 'GET' );


		$post = $this->getMockBuilder( 'WP_Post' )
		             ->disableOriginalConstructor()
		             ->getMock();
		$post->ID = rand(1,100);

		$subject = $this->get_subject();
		$subject->insert( $post, $request1, true );
	}

	function api_method_type() {
		return [
			'Use POST' => [ 'POST' ],
			'User PUT' => [ 'PUT' ],
		];
	}

}
