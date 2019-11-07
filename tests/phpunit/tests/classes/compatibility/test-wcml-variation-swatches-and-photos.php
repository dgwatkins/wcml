<?php

class Test_WCML_Variation_Swatches_And_Photos extends OTGS_TestCase {

	private function get_woocommerce_wpml_mock() {

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->attributes = $this->getMockBuilder( 'WCML_Attributes' )
		                                     ->disableOriginalConstructor()
		                                     ->setMethods( [
			                                     'get_product_attributes',
			                                     'is_a_taxonomy',
			                                     'get_custom_attr_translation'
		                                     ] )
		                                     ->getMock();

		$woocommerce_wpml->terms = $this->getMockBuilder( 'WCML_Terms' )
		                                     ->disableOriginalConstructor()
		                                     ->setMethods( [
			                                     'wcml_get_translated_term'
		                                     ] )
		                                     ->getMock();


		return $woocommerce_wpml;
	}

	private function get_post_translations_mock() {
		return $this->getMockBuilder( 'WPML_Post_Translation' )
			->disableOriginalConstructor()
			->setMethods( [ 'get_element_lang_code' ] )
			->getMock();
	}

	private function get_subject( $post_translations = null, $woocommerce_wpml = null ){

		if ( null === $post_translations ) {
			$post_translations = $this->get_post_translations_mock();
		}

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		}

		return new WCML_Variation_Swatches_And_Photos( $post_translations, $woocommerce_wpml );
	}

	/**
	 * @test
	 */
	public function add_hooks() {
		$subject = $this->get_subject();
		\WP_Mock::expectActionAdded( 'wcml_after_duplicate_product_post_meta', [
			$subject,
			'sync_variation_swatches_and_photos'
		], 10, 2 );
		$subject->add_hooks();
	}


	/**
	 * @test
	 */
	public function it_should_sync_variation_swatches_and_photos() {

		\WP_Mock::passthruFunction( 'sanitize_title' );
		\WP_Mock::passthruFunction( 'maybe_unserialize' );

		$original_product_id    = 1;
		$translated_product_id  = 2;
		$language               = 'es';
		$taxonomy_attribute_key = 'pa_color';

		$product_attributes = [
			$taxonomy_attribute_key => [
				'name'        => 'color',
				'is_taxonomy' => true
			],
			'custom'                => [
				'name'        => 'custom',
				'value'       => 'val 1',
				'is_taxonomy' => false
			]
		];

		$translated_custom_attribute_value = 'val 1 es';

		$term          = new stdClass();
		$term->slug    = 'white';
		$term->term_id = 10;
		$color_terms   = [ $term ];

		$translated_term       = new stdClass();
		$translated_term->slug = 'red-es';

		$swatch_options = [
			md5( $product_attributes[ $taxonomy_attribute_key ]['name'] ) => [
				'attributes' => [
					md5( $term->slug ) => [
						'color' => '#FFFFFF'
					]
				]
			],
			md5( $product_attributes['custom']['name'] )                  => [
				'attributes' => [
					md5( $product_attributes['custom']['value'] ) => [
						'color' => '#F0F0F0'
					]
				]
			]
		];

		$expected_swatch_options = [
			md5( $product_attributes[ $taxonomy_attribute_key ]['name'] ) => [
				'attributes' => [
					md5( $translated_term->slug ) => [
						'color' => '#FFFFFF'
					]
				]
			],
			md5( $product_attributes['custom']['name'] )                  => [
				'attributes' => [
					md5( $translated_custom_attribute_value ) => [
						'color' => '#F0F0F0'
					]
				]
			]
		];

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $original_product_id, '_swatch_type_options', true ],
			'return' => $swatch_options
		] );

		$post_translations = $this->get_post_translations_mock();
		$post_translations->method( 'get_element_lang_code' )->with( $translated_product_id )->willReturn( $language );

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock();
		$woocommerce_wpml->attributes->method( 'get_product_attributes' )->with( $original_product_id )->willReturn( $product_attributes );

		$woocommerce_wpml->attributes->method( 'is_a_taxonomy' )->will( $this->returnCallback(
			function ( $attribute ) {
				return $attribute['is_taxonomy'];
			}
		) );

		\WP_Mock::userFunction( 'get_terms', [
			'args'   => [ [ 'taxonomy' => $taxonomy_attribute_key ] ],
			'return' => $color_terms
		] );

		$woocommerce_wpml->terms->method( 'wcml_get_translated_term' )->with( $term->term_id, $taxonomy_attribute_key, $language )->willReturn( $translated_term );

		$woocommerce_wpml->attributes->method( 'get_custom_attr_translation' )->with( $original_product_id, $translated_product_id, $product_attributes['custom']['name'], $product_attributes['custom']['value'] )->willReturn( $translated_custom_attribute_value );

		$subject = $this->get_subject( $post_translations, $woocommerce_wpml );

		\WP_Mock::userFunction( 'update_post_meta', [
			'args'   => [ $translated_product_id, '_swatch_type_options', $expected_swatch_options ],
			'times'  => 1,
			'return' => true
		] );

		$filtered_swatch_options = $subject->sync_variation_swatches_and_photos( $original_product_id, $translated_product_id );
	}

}