<?php

class Test_WCML_Taxonomy_Translation_Link_Filters extends OTGS_TestCase {

	/**
	 * @test
	 */

	public function add_filters(){

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$subject = new WCML_Taxonomy_Translation_Link_Filters( $woocommerce_wpml );

		\WP_Mock::expectFilterAdded( 'wpml_taxonomy_term_translation_url', array( $subject, 'get_filtered_url' ), 10, 2 );

		$subject->add_filters();


	}

	/**
	 * @test
	 */
	public function get_filtered_url(){

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->attributes = $this->getMockBuilder( 'WCML_Attributes' )
		                                     ->disableOriginalConstructor()
											 ->setMethods( array( 'get_translatable_attributes' ) )
		                                     ->getMock();

		$attribute_1 = new stdClass();
		$attribute_1->attribute_name = rand_str();
		$attribute_2 = new stdClass();
		$attribute_2->attribute_name = rand_str();
		$translatable_attributes = [ $attribute_1, $attribute_2 ];
		$woocommerce_wpml->attributes->method( 'get_translatable_attributes' )->willReturn( $translatable_attributes );

		$translatable_custom_taxonomies = [ rand_str() => new stdClass(), rand_str() => new stdClass() ];
		\WP_Mock::wpFunction( 'get_object_taxonomies', array( 'return' => $translatable_custom_taxonomies ) );

		\WP_Mock::wpFunction( 'is_taxonomy_translated', array(
			'return' => function ( $taxonomy ) use( $translatable_custom_taxonomies ) {
				return isset( $translatable_custom_taxonomies[ $taxonomy ] );
			}
		) );

		\WP_Mock::wpFunction( 'add_query_arg', array(
			'return' => function ( $args, $url ) {
				$glue = strpos( $url , '?' ) ? '&' : '?';
				return $url . $glue . http_build_query( $args );
			}
		) );

		\WP_Mock::wpPassthruFunction('admin_url');

		$subject = new WCML_Taxonomy_Translation_Link_Filters( $woocommerce_wpml );

		$original_url = rand_str();

		// Not translated taxonomy, return original url
		$taxonomy = rand_str();
		$url = $subject->get_filtered_url( $original_url, $taxonomy );
		$this->assertEquals( $original_url, $url );

		// Built in product_tag
		$taxonomy = 'product_tag';
		$url = $subject->get_filtered_url( $original_url, $taxonomy );
		$this->assertEquals( 'admin.php?page=wpml-wcml&tab=' . $taxonomy, $url );

		// Built in product_shipping_class
		$taxonomy = 'product_shipping_class';
		$url = $subject->get_filtered_url( $original_url, $taxonomy );
		$this->assertEquals( 'admin.php?page=wpml-wcml&tab=' . $taxonomy, $url );

		// Attribute
		$taxonomy = 'pa_' . $attribute_1->attribute_name;
		$url = $subject->get_filtered_url( $original_url, $taxonomy );
		$this->assertEquals( 'admin.php?page=wpml-wcml&tab=product-attributs&taxonomy=' . $taxonomy, $url );

		// Custom taxonomy
		$taxonomy = key( $translatable_custom_taxonomies );
		$url = $subject->get_filtered_url( $original_url, $taxonomy );
		$this->assertEquals( 'admin.php?page=wpml-wcml&tab=custom-taxonomies&taxonomy=' . $taxonomy, $url );


	}

}
