<?php

/**
 * Class Test_WCML_Update_Product_Gallery_Translation
 * @group product-media
 */
class Test_WCML_Update_Product_Gallery_Translation extends OTGS_TestCase {

	public function get_subject( $translation_element_factory = null, $sitepress = null ) {
		if ( null === $translation_element_factory ) {
			$translation_element_factory = $this->get_translation_element_factory();
		}
		if ( null === $sitepress ) {
			$sitepress = $this->get_sitepress();
		}

		return new WCML_Update_Product_Gallery_Translation( $translation_element_factory, $sitepress );
	}

	public function get_translation_element_factory() {
		return $this->getMockBuilder( 'WPML_Translation_Element_Factory' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'create' ] )
		            ->getMock();
	}

	public function get_sitepress() {
		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'get_wp_api' ] )
		            ->getMock();
	}

	public function get_wpml_wp_api() {
		return $this->getMockBuilder( 'WPML_WP_API' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'constant' ] )
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

	/**
	 * @test
	 */
	public function it_should_add_hooks() {
		$subject = $this->get_subject();

		$this->expectActionAdded( 'wpml_added_media_file_translation', array(
			$subject,
			'update_meta'
		), PHP_INT_MAX, 1 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_update_meta() {

		$translation_element_factory = $this->get_translation_element_factory();
		$sitepress                   = $this->get_sitepress();

		$post_source_meta_key = rand_str();

		$wpml_wp_api = $this->get_wpml_wp_api();
		$wpml_wp_api->expects( $this->once() )
		            ->method( 'constant' )
		            ->with( 'WPML_Media_Translation_Status::POST_SOURCE_META_KEY' )
		            ->willReturn( $post_source_meta_key );

		$sitepress->expects( $this->once() )->method( 'get_wp_api' )->willReturn( $wpml_wp_api );

		$subject = $this->get_subject( $translation_element_factory, $sitepress );

		$attachment_id  = 9;
		$source_post_id = 19;

		$source_gallery_ids     = '41,42,43';
		$translated_gallery_ids = '51,52';

		$expected_gallery = '51,52,43';

		$language = 'ro';

		\WP_Mock::userFunction( 'get_post_meta', [
			'times'  => 1,
			'args'   => [ $attachment_id, $post_source_meta_key, true ],
			'return' => $source_post_id
		] );

		$source_post = $this->get_wpml_post_element( $source_post_id );

		$updated_attachment_element = $this->get_wpml_post_element( $attachment_id );
		$updated_attachment_element->method( 'get_language_code' )->willReturn( $language );

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
			[ $attachment_id, 'post', $updated_attachment_element ],
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

		\WP_Mock::userFunction( 'delete_post_meta', [
			'times' => 1,
			'args'  => [ $attachment_id, $post_source_meta_key ]
		] );

		$subject->update_meta( $attachment_id );
	}
}