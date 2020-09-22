<?php

namespace WCML\Rest\Wrapper\Products;

use stdClass;

/**
 * @group rest
 * @group rest-products
 */
class TestImages extends \OTGS_TestCase {

	/** @var WCML_Products */
	private $wcmlProduct;

	/** @var $WCML_Media */
	private $wcmlMedia;

	public function setUp(){
		parent::setUp();
	}

	function get_subject() {
		return new Images( $this->wcmlProduct, $this->wcmlMedia );
	}

	/**
	 * @test
	 * @dataProvider api_method_type
	 */
	function it_should_set_product_images_for_translation( $api_method_type ) {

		$post = $this->getMockBuilder( 'WC_Simple_Product' )
		             ->disableOriginalConstructor()
		             ->setMethods( [
			             'get_id'
		             ] )
		             ->getMock();

		$post->ID = mt_rand( 1, 10 );
		$post->method( 'get_id' )->willReturn( $post->ID );

		$translation_of = mt_rand( 11, 20 );
		$language = 'ro';
		$translated_variation_id = mt_rand( 21, 30 );
		$original_variation_id = mt_rand( 31, 40 );
		$variations = [
			$translated_variation_id
		];

		$request1 = $this->getMockBuilder( 'WP_REST_Request' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( [ 'get_params', 'get_method' ] )
		                 ->getMock();
		$request1->method( 'get_params' )->willReturn( [
			'lang'           => $language,
			'translation_of' => $translation_of,
			'variations' => $variations,
		] );
		$request1->method( 'get_method' )->willReturn( $api_method_type );


		$this->wcmlProduct = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( [ 'get_original_product_id' ] )
		                                         ->getMock();
		$this->wcmlProduct->method( 'get_original_product_id' )->with( $translated_variation_id )->willReturn( $original_variation_id );

		$this->wcmlMedia = $this->getMockBuilder( 'WCML_Media' )
		                                      ->disableOriginalConstructor()
		                                      ->setMethods( [ 'sync_thumbnail_id', 'sync_product_gallery', 'sync_variation_thumbnail_id' ] )
		                                      ->getMock();

		$this->wcmlMedia->expects( $this->once() )->method( 'sync_thumbnail_id' )->with( $translation_of, $post->ID, $language )->willReturn( true );
		$this->wcmlMedia->expects( $this->once() )->method( 'sync_product_gallery' )->with( $translation_of )->willReturn( true );
		$this->wcmlMedia->expects( $this->once() )->method( 'sync_variation_thumbnail_id' )->with( $original_variation_id, $translated_variation_id, $language )->willReturn( true );

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
