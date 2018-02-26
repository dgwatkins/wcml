<?php

/**
 * Class Test_WCML_Product_Gallery_Filter
 * @group product-media
 */
class Test_WCML_Product_Gallery_Filter extends OTGS_TestCase {

	public function get_subject( $translation_element_factory = null ) {
		if ( null === $translation_element_factory ) {
			$translation_element_factory = $this->get_translation_element_factory();
		}

		return new WCML_Product_Gallery_Filter( $translation_element_factory );
	}

	public function get_translation_element_factory() {
		return $this->getMockBuilder( 'WPML_Translation_Element_Factory' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'create' ] )
		            ->getMock();
	}

	private function get_wpml_post_element( $id ) {
		$element = $this->getMockBuilder( 'WPML_Post_Element' )
		                ->disableOriginalConstructor()
		                ->setMethods( [ 'get_source_element', 'get_id', 'get_language_code', 'get_translation' ] )
		                ->getMock();
		$element->method( 'get_id' )->willReturn( $id );

		return $element;
	}

	/**
	 * @test
	 */
	public function it_should_add_hooks() {
		$subject = $this->get_subject();

		$this->expectFilterAdded( 'get_post_metadata', array( $subject, 'localize_image_ids' ), 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_only_filter_product_image_gallery_meta() {
		$subject = $this->get_subject();

		$meta_key  = 'NOT__product_image_gallery';
		$object_id = 123;
		$value     = 88;

		\WP_Mock::userFunction( 'get_post_type', [ 'times' => 0 ] );
		\WP_Mock::userFunction( 'get_post_meta', [ 'times' => 0 ] );

		$this->assertSame( $value, $subject->localize_image_ids( $value, $object_id, $meta_key ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_non_products() {
		$subject = $this->get_subject();

		$meta_key  = '_product_image_gallery';
		$object_id = 123;
		$value     = 88;

		\WP_Mock::userFunction( 'get_post_type', [
			'times'  => 1,
			'args'   => [ $object_id ],
			'return' => 'NOT-product-type'
		] );
		\WP_Mock::userFunction( 'get_post_meta', [ 'times' => 0 ] );

		$this->assertSame( $value, $subject->localize_image_ids( $value, $object_id, $meta_key ) );
	}

	/**
	 * @test
	 */
	public function it_should_use_images_from_original_when_translations_dont_exist() {
		$translation_element_factory = $this->get_translation_element_factory();

		$subject = $this->get_subject( $translation_element_factory );

		$meta_key = '_product_image_gallery';

		$source_object_id = 13;
		$object_id        = 123;

		$source_attachments = [ 0 => '21', 1 => '22', 2 => '23' ];
		$target_attachments = [ $source_attachments[2] => '35' ];

		$expected_gallery = implode( ',', [
			$source_attachments[0],
			$source_attachments[1],
			$target_attachments[ $source_attachments[2] ]
		] );

		$language = 'ro';

		\WP_Mock::userFunction( 'get_post_type', [
			'times'  => 1,
			'args'   => [ $object_id ],
			'return' => 'product'
		] );

		\WP_Mock::userFunction( 'remove_filter', [
			'times' => 1,
			'args'  => [ 'get_post_metadata', array( $subject, 'localize_image_ids' ), 10, 3 ]
		] );

		$source_element = $this->get_wpml_post_element( $source_object_id );
		$post_element   = $this->get_wpml_post_element( $object_id );
		$post_element->method( 'get_language_code' )->willReturn( $language );
		$post_element->expects( $this->once() )->method( 'get_source_element' )->willReturn( $source_element );

		$attachment_elements = [];
		foreach ( $source_attachments as $attachment_id ) {
			$attachment_elements[ $attachment_id ] = $this->get_wpml_post_element( $source_object_id );;
		}

		$attachment_elements[ $source_attachments[0] ]->method( 'get_translation' )
		                                              ->with( $language )
		                                              ->willReturn( null );
		$attachment_elements[ $source_attachments[1] ]->method( 'get_translation' )
		                                              ->with( $language )
		                                              ->willReturn( null );

		$translated_attachment_element = $this->get_wpml_post_element( $target_attachments[ $source_attachments[2] ] );
		$attachment_elements[ $source_attachments[2] ]->method( 'get_translation' )
		                                              ->with( $language )
		                                              ->willReturn( $translated_attachment_element );

		$create_return_map = [
			[ $object_id, 'post', $post_element ],
			[ $source_attachments[0], 'post', $attachment_elements[ $source_attachments[0] ] ],
			[ $source_attachments[1], 'post', $attachment_elements[ $source_attachments[1] ] ],
			[ $source_attachments[2], 'post', $attachment_elements[ $source_attachments[2] ] ]
		];

		$translation_element_factory->method( 'create' )->will( $this->returnValueMap( $create_return_map ) );


		\WP_Mock::userFunction( 'get_post_meta', [
			'times'  => 1,
			'args'   => [ $source_object_id, '_product_image_gallery', true ],
			'return' => implode( ',', $source_attachments )
		] );


		$this->expectFilterAdded( 'get_post_metadata', array( $subject, 'localize_image_ids' ), 10, 3 );

		$this->assertSame(
			$expected_gallery,
			$subject->localize_image_ids( implode( ',', $target_attachments ), $object_id, $meta_key )
		);
	}

}