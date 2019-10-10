<?php

/**
 * Class Test_WCML_Product_Image_Filter
 * @group product-media
 */
class Test_WCML_Product_Image_Filter extends OTGS_TestCase {

	public function get_subject( $translation_element_factory = null, $wpml_cache = null ) {
		if ( null === $translation_element_factory ) {
			$translation_element_factory = $this->get_translation_element_factory();
		}

		if ( null === $wpml_cache ) {
			$wpml_cache = $this->get_wpml_cache_mock();
		}

		return new WCML_Product_Image_Filter( $translation_element_factory, $wpml_cache );
	}

	private function get_wpml_cache_mock() {
		return $this->getMockBuilder( 'WPML_WP_Cache' )
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'get', 'set', 'flush_group_cache' ) )
		            ->getMock();
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
		                ->setMethods( [ 'get_source_element', 'get_id' ] )
		                ->getMock();
		$element->method( 'get_id' )->willReturn( $id );

		return $element;
	}

	/**
	 * @test
	 */
	public function it_should_add_hooks() {
		$subject = $this->get_subject();

		$this->expectFilterAdded( 'get_post_metadata', array( $subject, 'localize_image_id' ), 11, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_should_only_filter_thumbnail_id_meta() {
		$subject = $this->get_subject();

		$meta_key  = 'NOT_thumbnail_id';
		$object_id = 123;
		$value     = 88;

		\WP_Mock::userFunction( 'get_post_type', [ 'times' => 0 ] );
		\WP_Mock::userFunction( 'get_post_meta', [ 'times' => 0 ] );

		$this->assertSame( $value, $subject->localize_image_id( $value, $object_id, $meta_key ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_non_products() {
		$subject = $this->get_subject();

		$meta_key  = '_thumbnail_id';
		$object_id = 123;
		$value     = null;

		\WP_Mock::userFunction( 'get_post_type', [
			'times'  => 1,
			'args'   => [ $object_id ],
			'return' => 'NOT-product-type'
		] );
		\WP_Mock::userFunction( 'get_post_meta', [ 'times' => 0 ] );

		$this->assertSame( $value, $subject->localize_image_id( $value, $object_id, $meta_key ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_when_translated_image_passed_to_function() {
		$subject = $this->get_subject();

		$meta_key  = '_thumbnail_id';
		$object_id = 123;
		$value     = 88;

		$this->assertSame( $value, $subject->localize_image_id( $value, $object_id, $meta_key ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_when_a_translated_image_exists() {
		$subject = $this->get_subject();

		$meta_key     = '_thumbnail_id';
		$object_id    = 123;
		$thumbnail_id = 88;

		\WP_Mock::userFunction( 'get_post_type', [
			'times'  => 1,
			'args'   => [ $object_id ],
			'return' => 'product'
		] );

		\WP_Mock::userFunction( 'remove_filter', [
			'times' => 1,
			'args'  => [ 'get_post_metadata', array( $subject, 'localize_image_id' ), 11, 3 ]
		] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'times'  => 1,
			'args'   => [ $object_id, '_thumbnail_id', true ],
			'return' => $thumbnail_id
		] );

		$this->expectFilterAdded( 'get_post_metadata', array( $subject, 'localize_image_id' ), 11, 3 );

		$this->assertNull( $subject->localize_image_id( null, $object_id, $meta_key ) );
	}

	/**
	 * @test
	 */
	public function it_should_return_original_image_id_when_translation_doesnt_exist() {
		$translation_element_factory = $this->get_translation_element_factory();

		$subject = $this->get_subject( $translation_element_factory );

		$meta_key            = '_thumbnail_id';
		$object_id           = 123;
		$thumbnail_id        = 88;
		$source_object_id    = 120;
		$source_thumbnail_id = 77;

		\WP_Mock::userFunction( 'get_post_type', [
			'times'  => 1,
			'args'   => [ $object_id ],
			'return' => 'product'
		] );

		\WP_Mock::userFunction( 'remove_filter', [
			'times' => 1,
			'args'  => [ 'get_post_metadata', array( $subject, 'localize_image_id' ), 11, 3 ]
		] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'times'  => 1,
			'args'   => [ $object_id, '_thumbnail_id', true ],
			'return' => null
		] );

		$this->expectFilterAdded( 'get_post_metadata', array( $subject, 'localize_image_id' ), 11, 3 );

		$post_element = $this->get_wpml_post_element( $object_id );

		$translation_element_factory->expects( $this->once() )
		                            ->method( 'create' )
		                            ->with( $object_id, 'post' )
		                            ->willReturn( $post_element );

		$source_element = $this->get_wpml_post_element( $source_object_id );
		$post_element->expects( $this->once() )->method( 'get_source_element' )->willReturn( $source_element );

		\WP_Mock::userFunction( 'get_post_meta', [
			'times'  => 1,
			'args'   => [ $source_object_id, '_thumbnail_id', true ],
			'return' => $source_thumbnail_id
		] );

		$this->assertSame( [ $source_thumbnail_id ], $subject->localize_image_id( null, $object_id, $meta_key ) );
	}


	/**
	 * @test
	 */
	public function it_should_return_cache_value_if_exists() {

		$meta_key  = '_thumbnail_id';
		$object_id = 123;
		$cache_value = 88;

		\WP_Mock::userFunction( 'get_post_type', [
			'times'  => 1,
			'args'   => [ $object_id ],
			'return' => 'product'
		] );

		$wpml_cache = $this->get_wpml_cache_mock();

		$wpml_cache->method( 'get' )->with( $object_id.'_thumbnail_id', false )->willReturn( $cache_value );

		$subject = $this->get_subject( null, $wpml_cache );

		$this->assertSame( [ $cache_value ], $subject->localize_image_id( null, $object_id, $meta_key ) );
	}


}