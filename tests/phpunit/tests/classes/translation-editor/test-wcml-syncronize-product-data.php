<?php

class Test_WCML_Synchronize_Product_Data extends OTGS_TestCase {

	/** @var wpdb */
	private $wpdb;

	public function setUp()
	{
		parent::setUp();

		$this->wpdb = $this->stubs->wpdb();
	}


	/**
	 * @return woocommerce_wpml
	 */
	private function get_woocommerce_wpml() {
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @return SitePress
	 */
	private function get_sitepress() {
		return $this->getMockBuilder('SitePress')
		            ->disableOriginalConstructor()
		            ->setMethods( array( 'get_current_language' ) )
		            ->getMock();
	}

	/**
	 * @return WCML_Synchronize_Product_Data
	 */
	private function get_subject(  $woocommerce_wpml = null, $sitepress = null  ){

		if( null === $woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if( null === $sitepress ){
			$sitepress = $this->get_sitepress();
		}


		return new WCML_Synchronize_Product_Data( $woocommerce_wpml, $sitepress, $this->wpdb );
	}

	/**
	 * @test
	 */
	public function it_adds_admin_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => true,
			'times'  => 1
		) );

		$subject = $this->get_subject();

		\WP_Mock::expectActionAdded( 'woocommerce_product_set_visibility', array( $subject, 'sync_product_translations_visibility' ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function get_translated_custom_field_values(){

		$subject = $this->get_subject( );

		$custom_field = rand_str();
		$custom_field_index = 0;
		$translated_title = rand_str();
		$translated_id = rand_str();
		$translated_content = rand_str();

		$custom_field_value = array(
			'title'   => rand_str(),
			'id'	  => rand_str(),
			'content' => rand_str()
		);

		$custom_fields_values = array();
		$custom_fields_values[ $custom_field_index ] = $custom_field_value;

		$translation_data = array();
		$translation_data[ md5( 'field-'.$custom_field.'-0-title' ) ] = $translated_title;
		$translation_data[ md5( 'field-'.$custom_field.'-0-id' ) ] = $translated_id;
		$translation_data[ md5( 'field-'.$custom_field.'-0-content' ) ] = $translated_content;

		$translated_values = $subject->get_translated_custom_field_values( $custom_fields_values, $translation_data, $custom_field, $custom_field_value, $custom_field_index );

		$this->assertEquals( $translated_title, $translated_values[ $custom_field_index ][ 'title' ] );
		$this->assertEquals( $translated_id, $translated_values[ $custom_field_index ][ 'id' ] );
		$this->assertEquals( $translated_content, $translated_values[ $custom_field_index ][ 'content' ] );

	}

	/**
	 * @test
	 */
	public function sync_grouped_products(){

		$product_id = mt_rand( 1, 10 );
		$translated_product_id = mt_rand( 10, 20 );
		$language = rand_str();

		$original_child = mt_rand( 20, 30 );
		$translated_child = mt_rand( 30, 40 );

		\WP_Mock::wpPassthruFunction('maybe_unserialize');

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'   => array( $product_id, '_children', true ),
			'return' => array( $original_child )
		));

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'   => array( $original_child ),
			'return' => 'product'
		));

		\WP_Mock::onFilter( 'translate_object_id' )->with( $original_child, 'product', false, $language )->reply( $translated_child );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_children', array( $translated_child ) ),
			'return' => true,
			'times'  => 1
		));

		$subject = $this->get_subject();
		$subject->sync_grouped_products( $product_id, $translated_product_id, $language );
	}

	/**
	 * @test
	 */
	public function sync_product_translations_visibility(){

		$product_id = mt_rand( 1, 100 );
		$trid = mt_rand( 100, 200 );

		$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		     ->disableOriginalConstructor()
		     ->getMock();
		$woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'is_original_product' ) )
		                                         ->getMock();

		$woocommerce_wpml->products->method( 'is_original_product' )->with( $product_id )->willReturn( true );


		$sitepress = $this->getMockBuilder( 'SitePress' )
		     ->disableOriginalConstructor()
		     ->setMethods( array( 'get_element_translations', 'get_element_trid' ) )
		     ->getMock();

		$sitepress->expects( $this->once() )->method( 'get_element_trid' )->with( $product_id, 'post_product' )->willReturn( $trid );

		$en_translation = new stdClass();
		$en_translation->original = true;
		$en_translation->element_id = $product_id;
		$translations['en'] = $en_translation;

		$fr_translation = new stdClass();
		$fr_translation->original = false;
		$fr_translation->element_id = mt_rand( 200, 300 );
		$translations['fr'] = $fr_translation;

		$sitepress->expects( $this->once() )->method( 'get_element_translations' )->with( $trid, 'post_product' )->willReturn( $translations );

		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );

		$product_object = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array( 'is_featured' ) )
		                ->getMock();

		$product_object->method( 'is_featured' )->willReturn( true );

		\WP_Mock::wpFunction( 'wc_get_product', array(
			'args' => array( $product_id ),
			'return' => $product_object
		) );

		\WP_Mock::wpFunction( 'wp_set_post_terms', array(
			'args' => array( $fr_translation->element_id, array( 'featured' ), 'product_visibility', false ),
			'return' => true,
			'times' => 1
		) );

		$subject->sync_product_translations_visibility( $product_id );
	}

	/**
	 * @test
	 * @group wcml-2135
	 */
	public function sync_custom_field_value_should_sync_serialzied_cf(){
		$subject = $this->get_subject();

		$custom_field = rand_str(32);
		$translated_product_id = random_int(1, 1000);
		$post_fields = [
			'field-' . $custom_field . '-0' => [
				'data'   => rand_str( 32 ),
				'tid'    => 0,
				'format' => 'base64',
			],
			'field-' . $custom_field . '-1' => [
				'data'   => rand_str( 32 ),
				'tid'    => 0,
				'format' => 'base64',
			],
			'field-' . $custom_field . '-2' => [
				'data'   => rand_str( 32 ),
				'tid'    => 0,
				'format' => 'base64',
			]
		];
		$original_product_id = random_int(1001, 2000);
		$translation_data = [
			md5( 'field-' . $custom_field . '-0' ) => rand_str( 32 ),
			md5( 'field-' . $custom_field . '-1' ) => rand_str( 32 ),
			md5( 'field-' . $custom_field . '-2' ) => rand_str( 32 ),
		];

		$original_custom_fields = [
			'key1' => rand_str(32),
			'key2' => rand_str(32),
			'key3' => rand_str(32)
		];

		\WP_Mock::userFunction( 'get_post_meta', [
			'expected' => 1,
			'args' => [ $original_product_id, $custom_field, true ],
			'return' => $original_custom_fields
		] );

		$expected_translated_custom_field = [
			'key1' => $translation_data[ md5( 'field-' . $custom_field . '-0' ) ],
			'key2' => $translation_data[ md5( 'field-' . $custom_field . '-1' ) ],
			'key3' => $translation_data[ md5( 'field-' . $custom_field . '-2' ) ],
		];

		\WP_Mock::userFunction( 'update_post_meta', [
			'expected' => 1,
			'args' => [ $translated_product_id, $custom_field, $expected_translated_custom_field ],
		] );

		$subject->sync_custom_field_value(
			$custom_field, $translation_data, $translated_product_id, $post_fields, $original_product_id );

	}

	/**
	 * @test
	 */
	public function duplicate_product_post_meta_updating_values() {

		$subject = $this->get_subject();

		$custom_field          = rand_str();
		$original_product_id   = mt_rand( 1, 100 );
		$translated_product_id = mt_rand( 101, 200 );

		$first_mid  = mt_rand( 201, 300 );
		$second_mid = mt_rand( 301, 400 );

		$first_mid_value  = rand_str();
		$second_mid_value = rand_str();

		$translation_data = array(
			md5( $custom_field . ':' . $first_mid )  => $first_mid_value,
			md5( $custom_field . ':' . $second_mid ) => $second_mid_value,
		);

		$post_fields = array(
			$custom_field . ':' . $first_mid  => rand_str(),
			$custom_field . ':' . $second_mid => rand_str()
		);

		\WP_Mock::wpFunction( 'update_meta', array(
			'args'  => array( $first_mid, $custom_field, $first_mid_value ),
			'times' => 1
		) );

		\WP_Mock::wpFunction( 'update_meta', array(
			'args'  => array( $second_mid, $custom_field, $second_mid_value ),
			'times' => 1
		) );

		$subject->sync_custom_field_value( $custom_field, $translation_data, $translated_product_id, $post_fields );

	}

}
