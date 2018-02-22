<?php

class WCML_Product_Image_Filter implements IWPML_Action {

	/**
	 * @var WPML_Translation_Element_Factory
	 */
	private $translation_element_factory;

	public function __construct( WPML_Translation_Element_Factory $translation_element_factory ) {
		$this->translation_element_factory = $translation_element_factory;
	}

	public function add_hooks() {
		add_filter( 'get_post_metadata', array( $this, 'localize_image_id' ), 10, 3 );
	}

	public function localize_image_id( $value, $object_id, $meta_key ) {
		if ( '_thumbnail_id' === $meta_key &&
		     in_array( get_post_type( $object_id ), array( 'product', 'product_variation' ) ) ) {

			remove_filter( 'get_post_metadata', array( $this, 'localize_image_id' ), 10, 3 );
			$meta_value = get_post_meta( $object_id, '_thumbnail_id', true );
			if ( empty( $meta_value ) ) {
				$post_element   = $this->translation_element_factory->create( $object_id, 'post' );
				$source_element = $post_element->get_source_element();
				if ( null !== $source_element ) {
					$value = get_post_meta( $source_element->get_id(), '_thumbnail_id', true );
				}

			}
			add_filter( 'get_post_metadata', array( $this, 'localize_image_id' ), 10, 3 );

		}

		return $value;
	}

}