<?php

namespace WCML\Rest\Wrapper;

/**
 * @group rest
 * @group rest-product-terms
 */
class TestProductTerms extends \OTGS_TestCase {

	/** @var \SitePress|\PHPUnit_Framework_MockObject_MockObject */
	private $sitepress;
	/** @var \WPML_Term_Translation|\PHPUnit_Framework_MockObject_MockObject */
	private $wpmlTermTranslations;
	/** @var \WCML_Terms|\PHPUnit_Framework_MockObject_MockObject */
	private $wcmlTerms;

	public function setUp() {
		parent::setUp();

		$this->sitepress = $this->getMockBuilder( \SitePress::class )
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

		$this->wcmlTerms = $this->getMockBuilder( 'WCML_Terms' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( [
			                        'update_terms_translated_status'
		                        ] )
		                        ->getMock();
	}


	function get_subject() {
		return new ProductTerms( $this->sitepress, $this->wpmlTermTranslations, $this->wcmlTerms );
	}


	/**
	 * @test
	 */
	public function filter_terms_query_lang_en() {

		$this->sitepress->method( 'is_active_language' )->with( 'en' )->willReturn( true );

		$subject = $this->get_subject();

		$request = $this->getRestRequest( [ 'lang' => 'en' ] );

		$args = [];
		\WP_Mock::userFunction( 'remove_filter', [ 'times' => 0 ] );
		$subject->query( $args, $request );

	}

	/**
	 * @test
	 */
	public function filter_terms_query_lang_all() {

		$subject = $this->get_subject();

		$request = $this->getRestRequest( [ 'lang' => 'all' ] );

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

		$request = $this->getRestRequest( [ 'lang' => 'EXCEPTION' ] );

		\WP_Mock::passthruFunction( '__' );

		$args = [];
		$subject->query( $args, $request );

	}

	/**
	 * @test
	 */
	public function append_product_language_and_translations() {
		$originalTerm   = $this->getTerm( 1, 2 );
		$translatedTerm = $this->getTerm( 11, 12 );

		$originalLang  = 'en';
		$secondaryLang = 'fr';

		$trid = 11;

		\WP_Mock::userFunction( 'remove_filter', [ 'return' => true ] );

		\WP_Mock::expectFilterAdded( 'get_term', [ $this->sitepress, 'get_term_adjust_id' ], 1 );

		\WP_Mock::userFunction( 'get_term_by', [
			'return' => function( $by, $TermTaxonomyId ) use ( $originalTerm, $translatedTerm ) {
				if ( 'term_taxonomy_id' === $by ) {
					switch( $TermTaxonomyId ) {
						case $originalTerm->term_taxonomy_id:
							return $originalTerm;

						case $translatedTerm->term_taxonomy_id:
							return $translatedTerm;
					}
				}

				return false;
			},
		] );

		// for original
		$response = $this->getMockBuilder( '\WP_REST_Response' )->getMock();
		$response->data = [
			'id' => $originalTerm->term_id,
		];

		$this->wpmlTermTranslations->method( 'get_element_trid' )
		                           ->with( $originalTerm->term_taxonomy_id )
		                           ->willReturn( $trid );

		$this->wpmlTermTranslations->method( 'get_element_translations' )
		                           ->with( $originalTerm->term_taxonomy_id, $trid )
		                           ->willReturn( [
		                           	    $originalLang  => $originalTerm->term_taxonomy_id,
		                           	    $secondaryLang => $translatedTerm->term_taxonomy_id
		                           ] );

		$this->wpmlTermTranslations->method( 'get_element_lang_code' )
		                           ->with( $originalTerm->term_taxonomy_id )
		                           ->willReturn( $originalLang );

		$filteredResponse = $this->get_subject()->prepare(
			$response,
			$originalTerm,
			$this->getRestRequest()
		);

		$this->assertEquals( $originalLang, $filteredResponse->data['lang'] );
		$this->assertEquals(
			[
				$originalLang  => $originalTerm->term_id,
				$secondaryLang => $translatedTerm->term_id,
			],
			$filteredResponse->data['translations']
		);
	}


	/**
	 * @test
	 * @expectedException Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Invalid language parameter
	 */
	function set_term_language_wrong_lang() {

		$request1 = $this->getRestRequest( [ 'lang' => 'ru' ] );

		$this->sitepress->expects( $this->never() )->method( 'set_element_language_details' );
		$this->wcmlTerms->expects( $this->never() )->method( 'update_terms_translated_status' );

		$term = $this->getTerm( 1, 2 );

		$subject = $this->get_subject();
		$subject->insert( $term, $request1, true );

	}

	/**
	 * @test
	 * @expectedException \Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Term not found: 11
	 */
	function set_term_language_no_source_product() { // with translation_of
		$term = $this->getTerm( 1, 2 );

		$request1 = $this->getRestRequest( [
			'lang'           => 'ro',
			'translation_of' => 11
		] );

		\WP_Mock::userFunction( 'get_term', [
			'return' => function( $termId, $taxonomy ) use( $term ) {
				if (
					$termId === $term->term_id
					&& $taxonomy === $term->taxonomy
				) {
					return $term;
				}

				return false;
			}
		] );

		$this->sitepress->method( 'is_active_language' )->with( 'ro' )->willReturn( true );

		$this->sitepress->method( 'set_element_language_details' )->willReturn( true );

		$this->wcmlTerms->method( 'update_terms_translated_status' )->with( $term->taxonomy )->willReturn( true );

		$subject = $this->get_subject();
		$subject->insert( $term, $request1, true );

	}

	/**
	 * @test
	 */
	function set_term_language_with_trid() {
		$translation_of = 1;
		$lang           = 'es';

		$request1 = $this->getRestRequest( [
			'lang'           => $lang,
			'translation_of' => $translation_of
		] );

		$originalTerm   = $this->getTerm( 1, 2 );
		$translatedTerm = $this->getTerm( 11, 12 );

		$trid = 12;

		\WP_Mock::userFunction( 'get_term', [
			'return' => function( $termId, $taxonomy ) use( $originalTerm ) {
				if (
					$termId === $originalTerm->term_id
					&& $taxonomy === $originalTerm->taxonomy
				) {
					return $originalTerm;
				}

				return false;
			}
		] );

		$this->wpmlTermTranslations->method( 'get_element_trid' )->with( $originalTerm->term_taxonomy_id )->willReturn( $trid );

		$this->sitepress->method( 'is_active_language' )->with( $lang )->willReturn( true );

		$this->sitepress->method( 'set_element_language_details' )->with( $translatedTerm->term_taxonomy_id, 'tax_' . $translatedTerm->taxonomy, $trid, $lang )->willReturn( true );

		$this->wcmlTerms->method( 'update_terms_translated_status' )->with( $translatedTerm->taxonomy )->willReturn( true );

		$subject = $this->get_subject();
		$subject->insert( $translatedTerm, $request1, true );
	}

	/**
	 * @test
	 * @expectedException \Exception
	 * @expectedExceptionCode 422
	 * @expectedExceptionMessage Using "translation_of" requires providing a "lang" parameter too
	 */
	function set_term_language_missing_lang() {
		$request1 = $this->getRestRequest( [ 'translation_of' => rand( 1, 100 ) ] );
		$term     = $this->getTerm( 1, 2 );

		$subject = $this->get_subject();
		$subject->insert( $term, $request1, true );

	}

	/**
	 * @test
	 */
	function set_term_language_new_term() { // no translation_of
		$lang = 'es';

		$request1 = $this->getRestRequest( [
			'lang' => $lang
		] );

		$term = $this->getTerm( 1, 2 );

		$this->sitepress->method( 'is_active_language' )->with( $lang )->willReturn( true );

		$this->wpmlTermTranslations->method( 'get_element_trid' )->willReturn( null );
		$this->sitepress->method( 'set_element_language_details' )->with( $term->term_taxonomy_id, 'tax_' . $term->taxonomy, null, $lang )->willReturn( true );
		$this->wcmlTerms->method( 'update_terms_translated_status' )->with( $term->taxonomy )->willReturn( true );

		$subject = $this->get_subject();
		$subject->insert( $term, $request1, true );
	}

	/**
	 * @test
	 */
	function update_term_description() {
		$lang = 'es';

		$request = $this->getRestRequest( [
			'lang'        => $lang,
			'description' => 'Wingardium Leviosa',
		] );

		$term = $this->getTerm( 1, 11 );
		$trid = 111;

		$this->sitepress->method( 'is_active_language' )->with( $lang )->willReturn( true );

		$this->wpmlTermTranslations
			->method( 'get_element_trid' )
			->with( $term->term_taxonomy_id )
			->willReturn( $trid );

		$this->sitepress
			->expects( $this->once() )
			->method( 'set_element_language_details' )
			->with( $term->term_taxonomy_id, 'tax_' . $term->taxonomy, $trid, $lang );
		$this->wcmlTerms
			->expects( $this->once() )
			->method( 'update_terms_translated_status' )
			->with( $term->taxonomy );

		$subject = $this->get_subject();
		$subject->insert( $term, $request, false );
	}

	/**
	 * @param int $termId
	 * @param int $termTaxonomyId
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|\WP_Term
	 */
	private function getTerm( $termId, $termTaxonomyId ) {
		$term = $this->getMockBuilder( 'WP_Term' )
		     ->disableOriginalConstructor()
		     ->getMock();
		$term->term_id = $termId;
		$term->term_taxonomy_id = $termTaxonomyId;
		$term->taxonomy = 'product_cat';

		return $term;
	}

	/**
	 * @param array $params
	 *
	 * @return \PHPUnit_Framework_MockObject_MockObject|\WP_REST_Request
	 */
	private function getRestRequest( array $params = [] ) {
		$request = $this->getMockBuilder( 'WP_REST_Request' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( [ 'get_params' ] )
		                       ->getMock();

		$request->method( 'get_params' )
		        ->willReturn( $params );

		return $request;
	}
}
