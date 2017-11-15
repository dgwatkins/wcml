<?php

class Test_WCML_Synchronize_Variations_Data extends OTGS_TestCase {

	/**
	 * @return woocommerce_wpml
	 */
	public function get_woocommerce_wpml() {

		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();

	}

	/**
	 * @return SitePress
	 */
	public function get_sitepress() {

		return $this->getMockBuilder( 'SitePress' )
		            ->disableOriginalConstructor()
		            ->getMock();

	}

	/**
	 * @return WCML_Synchronize_Variations_Data
	 */
	private function get_subject( $woocommerce_wpml = null, $sitepress = null ) {

		if ( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if ( null === $sitepress ) {
			$sitepress = $this->get_sitepress();
		}

		return new WCML_Synchronize_Variations_Data( $woocommerce_wpml, $sitepress, $this->stubs->wpdb() );
	}

	/**
	 * @test
	 */
	function sync_variations_taxonomies_no_terms_assigned() {

		$original_variation_id   = mt_rand( 1, 100 );
		$translated_variation_id = mt_rand( 101, 200 );
		$taxonomy                = new stdClass();;
		$taxonomy->name = rand_str();

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock_sync_variations_taxonomies();

		$woocommerce_wpml->terms->expects( $this->once() )->method( 'is_translatable_wc_taxonomy' )->with( $taxonomy->name )->willReturn( false );

		$subject = $this->get_subject( $woocommerce_wpml );

		\WP_Mock::wpFunction( 'remove_filter', array(
			'times'  => 1,
			'return' => true
		) );

		\WP_Mock::wpFunction( 'get_object_taxonomies', array(
			'args'   => array( 'product_variation' ),
			'times'  => 1,
			'return' => array( $taxonomy )
		) );

		$terms_for_original = array();
		\WP_Mock::wpFunction( 'get_the_terms', array(
			'args'   => array( $original_variation_id, $taxonomy->name ),
			'times'  => 1,
			'return' => $terms_for_original
		) );

		$expected_terms_for_translation = array();
		\WP_Mock::wpFunction( 'wp_set_object_terms', array(
			'args'   => array( $translated_variation_id, $expected_terms_for_translation, $taxonomy->name ),
			'times'  => 1,
			'return' => true
		) );

		$subject->sync_variations_taxonomies( $original_variation_id, $translated_variation_id, rand_str() );

	}

	/**
	 * @test
	 */
	function sync_variations_taxonomies() {

		$original_variation_id     = mt_rand( 1, 100 );
		$translated_variation_id   = mt_rand( 101, 200 );
		$not_translatable_taxonomy = rand_str();
		$translatable_taxonomy     = rand_str();

		$woocommerce_wpml = $this->get_woocommerce_wpml_mock_sync_variations_taxonomies();

		$sitepress = $this->getMockBuilder( 'SitePress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'is_translated_taxonomy' ) )
		                  ->getMock();

		$this->taxonomies[ $not_translatable_taxonomy ] = false;
		$this->taxonomies[ $translatable_taxonomy ]     = true;

		$sitepress->method( 'is_translated_taxonomy' )->willReturnCallback( function ( $tax ) {
			return $this->taxonomies[ $tax ];
		} );

		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );

		\WP_Mock::wpFunction( 'remove_filter', array(
			'times'  => 1,
			'return' => true
		) );

		\WP_Mock::wpFunction( 'get_object_taxonomies', array(
			'args'   => array( 'product_variation' ),
			'times'  => 1,
			'return' => array( $not_translatable_taxonomy, $translatable_taxonomy )
		) );

		$terms         = array();
		$term          = new stdClass();
		$term->term_id = mt_rand( 201, 300 );
		$terms[]       = $term;

		\WP_Mock::wpFunction( 'get_the_terms', array(
			'args'   => array( $original_variation_id, $not_translatable_taxonomy ),
			'times'  => 1,
			'return' => $terms
		) );

		$expected_terms = array( $term->term_id );

		\WP_Mock::wpFunction( 'wp_set_object_terms', array(
			'args'   => array( $translated_variation_id, $expected_terms, $not_translatable_taxonomy, true ),
			'times'  => 1,
			'return' => true
		) );

		$language           = rand_str();
		$terms              = array();
		$term               = new stdClass();
		$term->term_id      = mt_rand( 301, 400 );
		$terms[]            = $term;
		$translated_term_id = mt_rand( 401, 500 );

		\WP_Mock::wpFunction( 'get_the_terms', array(
			'args'   => array( $original_variation_id, $translatable_taxonomy ),
			'times'  => 1,
			'return' => $terms
		) );

		\WP_Mock::onFilter( 'translate_object_id' )->with( $term->term_id, $translatable_taxonomy, false, $language )->reply( $translated_term_id );

		$expected_terms = array( $translated_term_id );

		\WP_Mock::wpFunction( 'wp_set_object_terms', array(
			'args'   => array( $translated_variation_id, $expected_terms, $translatable_taxonomy, true ),
			'times'  => 1,
			'return' => true
		) );

		$subject->sync_variations_taxonomies( $original_variation_id, $translated_variation_id, $language );

	}

	function get_woocommerce_wpml_mock_sync_variations_taxonomies() {

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                         ->disableOriginalConstructor()
		                         ->getMock();

		$woocommerce_wpml->sync_product_data = $this->getMockBuilder( 'WCML_Synchronize_Product_Data' )
		                                            ->disableOriginalConstructor()
		                                            ->setMethods( array( 'check_if_product_fields_sync_needed' ) )
		                                            ->getMock();

		$woocommerce_wpml->sync_product_data->method( 'check_if_product_fields_sync_needed' )->willReturn( true );


		$woocommerce_wpml->terms = $this->getMockBuilder( 'WCML_Terms' )
		                                ->disableOriginalConstructor()
		                                ->setMethods( array( 'is_translatable_wc_taxonomy' ) )
		                                ->getMock();

		return $woocommerce_wpml;

	}

	/**
	 * @test
	 */
	function sync_prices_variation_ids() {

		$product_id = 1;
		$translated_product_id = 2;
		$language = 'fr';

		$original_min_price_variation_id = 10;
		$translated_min_price_variation_id = 11;

		$original_max_price_variation_id = 20;
		$translated_max_price_variation_id = 21;

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_min_price_variation_id', true ),
			'return' => $original_min_price_variation_id
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_max_price_variation_id', true ),
			'return' => $original_max_price_variation_id
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_min_regular_price_variation_id', true ),
			'return' => false
		) );
		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_min_sale_price_variation_id', true ),
			'return' => false
		) );
		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_max_regular_price_variation_id', true ),
			'return' => false
		) );
		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_max_sale_price_variation_id', true ),
			'return' => false
		) );

		\WP_Mock::onFilter( 'translate_object_id' )->with( $original_min_price_variation_id, 'product_variation', false, $language )->reply( $translated_min_price_variation_id );
		\WP_Mock::onFilter( 'translate_object_id' )->with( $original_max_price_variation_id, 'product_variation', false, $language )->reply( $translated_max_price_variation_id );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_min_price_variation_id', $translated_min_price_variation_id ),
			'return' => true,
			'times'  => 1
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_max_price_variation_id', $translated_max_price_variation_id ),
			'return' => true,
			'times'  => 1
		) );

		$subject = $this->get_subject();
		$subject->sync_prices_variation_ids( $product_id, $translated_product_id, $language );
	}

}