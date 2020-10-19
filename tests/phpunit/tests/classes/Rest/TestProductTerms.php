<?php

namespace WCML\Rest\Wrapper;

/**
 * @group rest
 * @group rest-product-terms
 */
class TestProductTerms extends \OTGS_TestCase {

	/** @var Sitepress */
	private $sitepress;
	/** @var WPML_Term_Translation */
	private $wpmlTermTranslations;

	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( [
			                        'set_element_language_details',
			                        'is_active_language'
		                        ] )
		                        ->getMock();

		$this->wpmlTermTranslations = $this->getMockBuilder( 'WPML_Term_Translation' )
		                                   ->disableOriginalConstructor()
		                                   ->setMethods( [
			                                   'get_element_trid',
			                                   'get_element_translations',
			                                   'get_element_lang_code',
		                                   ] )
		                                   ->getMock();
	}


	function get_subject() {
		return new ProductTerms( $this->sitepress, $this->wpmlTermTranslations );
	}


	/**
	 * @test
	 */
	public function filter_terms_query_lang_en() {

		$this->sitepress->method( 'is_active_language' )->with( 'en' )->willReturn( true );

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_params' ] )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( [ 'lang' => 'en' ] );

		$args = [];
		\WP_Mock::userFunction( 'remove_filter', [ 'times' => 0 ] );
		$subject->query( $args, $request );

	}

	/**
	 * @test
	 */
	public function filter_terms_query_lang_all() {

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
	public function filter_terms_query_lang_exception() {

		$this->sitepress->method( 'is_active_language' )->with( 'EXCEPTION' )->willReturn( false );

		$subject = $this->get_subject();

		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_params' ] )
		                ->getMock();
		$request->method( 'get_params' )->willReturn( [ 'lang' => 'EXCEPTION' ] );

		\WP_Mock::wpPassthruFunction( '__' );

		$args = [];
		$subject->query( $args, $request );

	}

	/**
	 * @test
	 */
	public function append_product_language_and_translations() {

		$this->default_language   = 'en';
		$this->secondary_language = 'fr';

		$trid                                 = 11;
		$this->term_id_in_default_language = 12;

		// for original
		$product_data       = $this->getMockBuilder( 'WP_REST_Response' )
		                           ->disableOriginalConstructor()
		                           ->getMock();
		$product_data->data = [
			'id' => $this->term_id_in_default_language
		];

		$this->wpmlTermTranslations->method( 'get_element_trid' )->with( $this->term_id_in_default_language )->willReturn( $trid );

		$fr_translation_id = 14;

		$this->wpmlTermTranslations->method( 'get_element_translations' )->with( $this->term_id_in_default_language, $trid )->willReturn( [ $this->secondary_language => $fr_translation_id ] );
		$this->wpmlTermTranslations->method( 'get_element_lang_code' )->with( $this->term_id_in_default_language )->willReturn( $this->default_language );

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
	function set_term_language_wrong_lang( $api_method_type ) {

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params', 'get_method' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [ 'lang' => 'ru' ] );
		$request1->method( 'get_method' )->willReturn( $api_method_type );

		$this->sitepress->expects( $this->never() )->method( 'set_element_language_details' );

		$term          = $this->getMockBuilder( 'WP_Term' )
		                      ->disableOriginalConstructor()
		                      ->getMock();
		$term->term_id = 1;

		$subject = $this->get_subject();
		$subject->insert( $term, $request1, true );

	}

	/**
	 * @test
	 * @dataProvider api_method_type
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Term not found:
	 */
	function set_term_language_no_source_product( $api_method_type ) { // with translation_of

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params', 'get_method' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [
			'lang'           => 'ro',
			'translation_of' => 11
		] );
		$request1->method( 'get_method' )->willReturn( $api_method_type );

		$this->sitepress->method( 'is_active_language' )->with( 'ro' )->willReturn( true );

		$this->sitepress->method( 'set_element_language_details' )->willReturn( true );

		$term          = $this->getMockBuilder( 'WP_Term' )
		                      ->disableOriginalConstructor()
		                      ->getMock();
		$term->term_id = 1;

		$subject = $this->get_subject();
		$subject->insert( $term, $request1, true );

	}

	/**
	 * @test
	 * @dataProvider api_method_type
	 */
	function set_term_language_with_trid( $api_method_type ) {

		$request1       = $this->getMockBuilder( 'WP_REST_Request' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( [ 'get_params', 'get_method' ] )
		                       ->getMock();
		$translation_of = 11;
		$lang           = 'es';

		$request1->method( 'get_params' )->willReturn( [
			'lang'           => $lang,
			'translation_of' => $translation_of
		] );
		$request1->method( 'get_method' )->willReturn( $api_method_type );

		$term           = $this->getMockBuilder( 'WP_Term' )
		                       ->disableOriginalConstructor()
		                       ->getMock();
		$term->term_id  = 1;
		$term->taxonomy = 'product_cat';

		$trid = 12;

		$this->wpmlTermTranslations->method( 'get_element_trid' )->with( $translation_of )->willReturn( $trid );

		$this->sitepress->method( 'is_active_language' )->with( $lang )->willReturn( true );

		$this->sitepress->method( 'set_element_language_details' )->with( $term->term_id, 'tax_' . $term->taxonomy, $trid, $lang )->willReturn( true );

		$subject = $this->get_subject();
		$subject->insert( $term, $request1, true );
	}

	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Using "translation_of" requires providing a "lang" parameter too
	 */
	function set_term_language_missing_lang() {

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [ 'translation_of' => rand( 1, 100 ) ] );

		$term          = $this->getMockBuilder( 'WP_Term' )
		                      ->disableOriginalConstructor()
		                      ->getMock();
		$term->term_id = 1;

		$subject = $this->get_subject();
		$subject->insert( $term, $request1, true );

	}

	/**
	 * @test
	 * @dataProvider api_method_type
	 */
	function set_term_language_new_term( $api_method_type ) { // no translation_of

		$lang = 'es';

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params', 'get_method' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [
			'lang' => $lang
		] );
		$request1->method( 'get_method' )->willReturn( $api_method_type );

		$term           = $this->getMockBuilder( 'WP_Term' )
		                       ->disableOriginalConstructor()
		                       ->getMock();
		$term->term_id  = 1;
		$term->taxonomy = 'product_cat';

		$this->sitepress->method( 'is_active_language' )->with( $lang )->willReturn( true );

		$this->wpmlTermTranslations->method( 'get_element_trid' )->willReturn( null );
		$this->sitepress->method( 'set_element_language_details' )->with( $term->term_id, 'tax_' . $term->taxonomy, null, $lang )->willReturn( true );

		$subject = $this->get_subject();
		$subject->insert( $term, $request1, true );
	}

	/**
	 * @test
	 */
	function do_no_set_term_language_if_method_not_post_or_put() {


		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params', 'get_method' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [
			'lang' => 'en'
		] );
		$request1->method( 'get_method' )->willReturn( 'GET' );


		$term          = $this->getMockBuilder( 'WP_Term' )
		                      ->disableOriginalConstructor()
		                      ->getMock();
		$term->term_id = 1;

		$subject = $this->get_subject();
		$subject->insert( $term, $request1, true );
	}

	function api_method_type() {
		return [
			'Use POST' => [ 'POST' ],
			'User PUT' => [ 'PUT' ],
		];
	}

}
