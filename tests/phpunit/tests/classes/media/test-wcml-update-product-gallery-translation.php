<?php

/**
 * Class Test_WCML_Update_Product_Gallery_Translation
 * @group product-media
 */
class Test_WCML_Update_Product_Gallery_Translation extends OTGS_TestCase {

	public function get_subject( $translation_element_factory = null, $media_usage_factory = null ) {
		if ( null === $translation_element_factory ) {
			$translation_element_factory = $this->get_translation_element_factory();
		}
		if ( null === $media_usage_factory ) {
			$media_usage_factory = $this->get_media_usage_factory();
		}

		return new WCML_Update_Product_Gallery_Translation( $translation_element_factory, $media_usage_factory );
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
		                ->setMethods( [ 'get_translation', 'get_id', 'get_language_code' ] )
		                ->getMock();
		$element->method( 'get_id' )->willReturn( $id );

		return $element;
	}

	public function get_media_usage_factory() {
		return $this->getMockBuilder( 'WPML_Media_Usage_Factory' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'create' ] )
		            ->getMock();
	}

	public function get_media_usage() {
		return $this->getMockBuilder( 'WPML_Media_Usage' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'get_posts' ] )
		            ->getMock();
	}

	/**
	 * @test
	 */
	public function it_should_add_hooks() {
		$subject = $this->get_subject();

		$this->expectActionAdded( 'wpml_added_media_file_translation', array(
			$subject,
			'update_meta'
		), PHP_INT_MAX, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_update_meta() {

		$translation_element_factory = $this->get_translation_element_factory();
		$media_usage_factory         = $this->get_media_usage_factory();

		$original_attachment_id  = 9;

		$source_post_id = 19;
		$posts_using = [ $source_post_id ];
		$media_usage = $this->get_media_usage();
		$media_usage->expects( $this->once() )->method( 'get_posts' )->with()->willReturn( $posts_using );

		$media_usage_factory->expects( $this->once() )->method( 'create' )->with()->willReturn( $media_usage );

		$subject = $this->get_subject( $translation_element_factory, $media_usage_factory );

		$source_gallery_ids     = '41,42,43';
		$translated_gallery_ids = '51,52';

		$expected_gallery = '51,52,43';

		$language = 'ro';

		$source_post = $this->get_wpml_post_element( $source_post_id );

		$original_attachment_element = $this->get_wpml_post_element( $original_attachment_id );

		$updated_attachment_element = $this->get_wpml_post_element( $original_attachment_id );
		$updated_attachment_element->method( 'get_language_code' )->willReturn( $language );

		$original_attachment_element->method('get_translation')
		                            ->with($language)
		                            ->willReturn( $updated_attachment_element );

		\WP_Mock::userFunction( 'get_post_meta', [
			'times'  => 1,
			'args'   => [ $source_post_id, '_product_image_gallery', true ],
			'return' => $source_gallery_ids
		] );


		$source_gallery_ids_array = explode( ',', $source_gallery_ids );
		foreach ( $source_gallery_ids_array as $index => $value ) {
			$attachment_element[ $index ] = $this->get_wpml_post_element( $value );
		}

		$translation_element_factory->method( 'create' )->will( $this->returnValueMap( [
			[ $source_post_id, 'post', $source_post ],
			[ $original_attachment_id, 'post', $original_attachment_element ],
			[ $source_gallery_ids_array[0], 'post', $attachment_element[0] ],
			[ $source_gallery_ids_array[1], 'post', $attachment_element[1] ],
			[ $source_gallery_ids_array[2], 'post', $attachment_element[2] ]
		] ) );

		$translated_gallery_ids_array = explode( ',', $translated_gallery_ids );
		$translated_attachment[0]     = $this->get_wpml_post_element( $translated_gallery_ids_array[0] );
		$translated_attachment[1]     = $this->get_wpml_post_element( $translated_gallery_ids_array[1] );

		$attachment_element[0]->method( 'get_translation' )->willReturn( $translated_attachment[0] );
		$attachment_element[1]->method( 'get_translation' )->willReturn( $translated_attachment[1] );
		$attachment_element[2]->method( 'get_translation' )->willReturn( null );

		$translated_post_id = 199;
		$translated_post    = $this->get_wpml_post_element( $translated_post_id );
		$source_post->method( 'get_translation' )->with( $language )->willReturn( $translated_post );

		\WP_Mock::userFunction( 'update_post_meta', [
			'times' => 1,
			'args'  => [ $translated_post_id, '_product_image_gallery', $expected_gallery ]
		] );

		$subject->update_meta( $original_attachment_id, rand_str(), $language );
	}
}