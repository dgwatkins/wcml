<?php

namespace WCML\Rest\Wrapper\Products;

use WCML\Rest\ProductSaveActions;

/**
 * @group rest
 * @group rest-products
 */
class TestProduct extends \OTGS_TestCase {


	/** @var \WPML_Query_Filter */
	private $wpml_query_filter;
	/** @var \SitePress|\PHPUnit_Framework_MockObject_MockObject */
	private $sitepress;
	/** @var \WPML_Post_Translation|\PHPUnit_Framework_MockObject_MockObject */
	private $wpml_post_translations;
	/** @var \WCML_Synchronize_Variations_Data */
	private $sync_variations_data;
	/** @var \WCML_Attributes */
	private $attributes;
	/** @var ProductSaveActions|\PHPUnit_Framework_MockObject_MockObject */
	private $product_save_actions;
	/** @var \WCML_WC_Strings */
	private $strings;

	public function setUp() {
		parent::setUp();
		$this->wpml_query_filter = $this->getMockBuilder( 'WPML_Query_Filter' )
										->disableOriginalConstructor()
										->getMock();

		$this->sitepress = $this->getMockBuilder( \SitePress::class )
								->disableOriginalConstructor()
								->setMethods( [
									'set_element_language_details',
									'copy_custom_fields',
									'is_active_language',
								] )
								->getMock();

		$this->wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
											 ->disableOriginalConstructor()
											 ->setMethods( [
												 'get_element_trid',
												 'get_element_translations',
												 'get_element_lang_code'
											 ] )
											 ->getMock();

		$this->product_save_actions = $this->getMockBuilder( ProductSaveActions::class )
										   ->disableOriginalConstructor()
										   ->setMethods( [ 'run' ] )
										   ->getMock();

		$this->strings = $this->getMockBuilder( 'WCML_WC_Strings' )
							  ->disableOriginalConstructor()
							  ->setMethods( array( 'get_translated_string_by_name_and_context' ) )
							  ->getMock();
	}


	function get_subject() {
		return new Products( $this->sitepress, $this->wpml_post_translations, $this->wpml_query_filter, $this->product_save_actions, $this->strings );
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
			     ->setMethods( [ 'get_params' ] )
			     ->getMock() );

		$this->assertEquals( $this->default_language, $product_data->data['lang'] );
		$this->assertEquals(
			[ $this->secondary_language => $fr_translation_id ],
			$product_data->data['translations']
		);
	}

	/**
	 * @test
	 */
	function set_product_language_with_trid() {

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params' ] )
		                 ->getMock();
		$translation_of = 11;
		$lang = 'ro';

		$request1->method( 'get_params' )->willReturn( [
			'lang'           => $lang,
			'translation_of' => $translation_of
		] );

		$this->expected_trid = null;
		$this->actual_trid   = 12;

		$post = $this->getMockBuilder( 'WC_Variable_Product' )
		             ->disableOriginalConstructor()
		             ->setMethods( [
			             'get_id', 'get_type'
		             ] )
		             ->getMock();
		$post->ID = rand( 1, 100 );

		$post->method( 'get_id' )->willReturn( $post->ID );
		$post->method( 'get_type' )->willReturn( 'variable' );

		$this->wpml_post_translations->method( 'get_element_trid' )
			->with( $translation_of )
			->willReturn( $this->actual_trid );

		$this->sitepress->method( 'is_active_language' )
			->with( 'ro' )
			->willReturn( true );

		$this->product_save_actions->expects( $this->once() )
			->method( 'run' )
			->with( $post, $this->actual_trid, $lang, $translation_of );

		$subject = $this->get_subject();
		$subject->insert( $post, $request1, true );

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
	 */
	function set_product_language_new_product() { // no translation_of
		$lang = 'ro';

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params', 'get_method' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [
			'lang' => $lang,
		] );

		$this->expected_trid = null;
		$this->actual_trid   = null;

		$this->wpml_post_translations->method( 'get_element_trid' )->willReturn( $this->actual_trid );

		$post = $this->getMockBuilder( 'WC_Simple_Product' )
		             ->disableOriginalConstructor()
		             ->setMethods( [
			             'get_id'
		             ] )
		             ->getMock();

		$post->ID = rand( 1, 100 );
		$post->method( 'get_id' )->willReturn( $post->ID );

		\WP_Mock::userFunction( 'get_post_type', [
			'args'  => [ $post->ID ],
			'return' => 'product'
		] );

		$this->sitepress->method( 'is_active_language' )
			->with( $lang )
			->willReturn( true );

		$this->product_save_actions->expects( $this->once() )
			->method( 'run' )
			->with( $post, $this->actual_trid, $lang, null );

		$subject = $this->get_subject();
		$subject->insert( $post, $request1, true );

	}

	/**
	 * @test
	 */
	public function translate_product_attributes_for_product() {
		$lang = 'de';
		$attribute1_english = 'Size';
		$attribute2_english = 'Weight';
		$attribute1_deutsch = 'Große';
		$attribute2_deutsch = 'Gewicht';

		$product_data = $this->getMockBuilder( 'WP_REST_Response' )
			->disableOriginalConstructor()
			->getMock();
		$product_data->data = [
			'id' => 13,
			'attributes' => [
				[ 'name' => $attribute1_english ],
				[ 'name' => $attribute2_english ],
			],
		];

		$translated_attributes =  [
			[ 'name' => $attribute1_deutsch ],
			[ 'name' => $attribute2_deutsch ],
		];

		$request = $this->getMockBuilder( 'WP_REST_Request' )
			->disableOriginalConstructor()
			->setMethods( [ 'get_params' ] )
			->getMock();
		$request->method( 'get_params' )->willReturn( [
			'lang' => $lang,
		] );

		$this->strings->method( 'get_translated_string_by_name_and_context' )
			->withConsecutive(
				[ 'WordPress', 'taxonomy singular name: ' . $attribute1_english, $lang, $attribute1_english ],
				[ 'WordPress', 'taxonomy singular name: ' . $attribute2_english, $lang, $attribute2_english ] )
			->willReturnOnConsecutiveCalls( $attribute1_deutsch, $attribute2_deutsch );

		$subject      = $this->get_subject();
		$product_data = $subject->prepare( $product_data,
			$this->getMockBuilder( 'WC_Data' )
				->disableOriginalConstructor()
				->getMock(), $request );

		$this->assertEquals( $translated_attributes, $product_data->data['attributes'] );
	}
}
