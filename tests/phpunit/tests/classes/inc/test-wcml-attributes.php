<?php

class Test_WCML_Attributes extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_WP_API $wp_api */
	private $wp_api;

	public function setUp() {
		parent::setUp();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
			->disableOriginalConstructor()
		    ->getMock();

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'is_product_display_as_translated_post_type' ) )
		                                         ->getMock();

		$this->sitepress = $this->getMockBuilder( 'Sitepress' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_wp_api', 'get_current_language' ) )
			->getMock();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )
			->disableOriginalConstructor()
			->setMethods( array( 'constant', 'version_compare' ) )
			->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );

		$this->wpdb = $this->stubs->wpdb();
	}


	/**
	 * @return WCML_Attributes
	 */
	private function get_subject(){
		$subject = new WCML_Attributes( $this->woocommerce_wpml, $this->sitepress, $this->wpdb );

		return $subject;
	}

	/**
	 * @test
	 */
	public function hooks_before_wc_3_0()
	{
		$check_version = '3.0.0';
		$wc_version = '2.7.0';

		$this->wp_api->expects( $this->once() )
			->method('constant')
			->with('WC_VERSION')
			->willReturn( $wc_version );
		$this->wp_api->expects($this->once())
			->method('version_compare')
			->with($wc_version, $check_version, '<')
			->willReturn(true);

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_get_product_attributes', array( $subject, 'filter_adding_to_cart_product_attributes_names' ) );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function hooks_from_wc_3_0()
	{
		$check_version = '3.0.0';
		$wc_version = '3.0.0';
		$this->wp_api->expects( $this->once() )
			->method( 'constant' )
			->with( 'WC_VERSION' )
			->willReturn( $wc_version );
		$this->wp_api->expects( $this->once() )
			->method( 'version_compare' )
			->with( $wc_version, $check_version, '<' )
			->willReturn( false );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_product_get_attributes', array( $subject, 'filter_adding_to_cart_product_attributes_names' ) );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function hooks_for_product_display_as_translated_post_type()
	{
		$check_version = '3.0.0';
		$wc_version = '3.0.0';
		$this->wp_api->expects( $this->once() )
			->method( 'constant' )
			->with( 'WC_VERSION' )
			->willReturn( $wc_version );
		$this->wp_api->expects( $this->once() )
			->method( 'version_compare' )
			->with( $wc_version, $check_version, '<' )
			->willReturn( false );


		$this->woocommerce_wpml->products->method( 'is_product_display_as_translated_post_type' )->willReturn( true );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'woocommerce_available_variation', array( $subject, 'filter_available_variation_attribute_values_in_current_language' ) );
		\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'filter_product_variation_post_meta_attribute_values_in_current_language' ), 10 ,4 );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function is_a_taxonomy(){

		$attribute = $this->getMockBuilder( 'WC_Product_Attribute' )
			->disableOriginalConstructor()
			->setMethods( array( 'is_taxonomy' ) )
			->getMock();

		$attribute->method( 'is_taxonomy' )->willReturn( true );

		$subject = $this->get_subject();
		$attribute_is_taxonomy = $subject->is_a_taxonomy( $attribute );

		$this->assertTrue( $attribute_is_taxonomy );

		$attribute = array();
		$attribute[ 'is_taxonomy' ] = true;

		$subject = $this->get_subject();
		$attribute_is_taxonomy = $subject->is_a_taxonomy( $attribute );

		$this->assertTrue( $attribute_is_taxonomy );


		$attribute = array();
		$attribute[ 'is_taxonomy' ] = false;

		$subject = $this->get_subject();
		$attribute_is_taxonomy = $subject->is_a_taxonomy( $attribute );

		$this->assertFalse( $attribute_is_taxonomy );
	}

	/**
	 * @test
	 */
	public function sync_product_attr_test_empty_translated_attributes_array(){

		$product_id = rand( 1, 100 );
		$translated_product_id = rand( 1, 100 );

		$original_attributes = array( array(
			'name' => rand_str(),
			'value' => rand_str(),
			'is_taxonomy' => 0
		) );

		$translated_attributes = array();

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $product_id, '_product_attributes', true ),
			'return' => $original_attributes
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $translated_product_id, '_product_attributes', true ),
			'return' => $translated_attributes
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $translated_product_id, 'attr_label_translations', true ),
			'return' => false
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, 'attr_label_translations', array() ),
			'times'  => 1,
			'return' => true
		));

//		 The $original_attributes should be saved to the translated product if is_taxonomy = 0 and there are no existing $translated_attributes
		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args'   => array( $translated_product_id, '_product_attributes', $original_attributes ),
			'times'  => 1,
			'return' => true
		));

		\WP_Mock::wpPassthruFunction( 'sanitize_title' );

		$subject = $this->get_subject();

		$subject->sync_product_attr( $product_id, $translated_product_id );
	}

	/**
	 * @test
	 */
	public function filter_attribute_name() {

		$attribute_name           = rand_str();
		$sanitized_attribute_name = rand_str();
		$orig_lang                = 'de';
		$product_id               = rand( 1, 100 );
		$current_language         = 'fr';

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_language' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products
			->method( 'get_original_product_language' )
			->with( $product_id )
			->willReturn( $orig_lang );

		$this->sitepress->locale_utils = $this->getMockBuilder( 'WPML_Locale' )
		                                      ->disableOriginalConstructor()
		                                      ->setMethods( array( 'filter_sanitize_title' ) )
		                                      ->getMock();

		$this->sitepress->locale_utils
			->method( 'filter_sanitize_title' )
			->with( $attribute_name, $attribute_name )
			->willReturn( $attribute_name );

		$this->sitepress
			->method( 'get_current_language' )
			->willReturn( $current_language );

		\WP_Mock::wpFunction( 'is_admin', array(
			'times'  => 1,
			'return' => false
		) );

		\WP_Mock::wpFunction( 'remove_accents', array(
			'args'   => array( $attribute_name ),
			'times'  => 1,
			'return' => $attribute_name
		) );

		\WP_Mock::wpFunction( 'sanitize_title', array(
			'args'   => array( $attribute_name ),
			'times'  => 1,
			'return' => $sanitized_attribute_name
		) );

		\WP_Mock::wpFunction( 'remove_filter', array(
			'times'  => 1,
			'return' => true
		) );

		$subject = $this->get_subject();

		$filtered_attribute_name = $subject->filter_attribute_name( $attribute_name, $product_id, true );

		$this->assertEquals( $sanitized_attribute_name, $filtered_attribute_name );
	}

	/**
	 * @test
	 */
	public function filter_attribute_name_current_is_original() {

		$attribute_name           = rand_str();
		$orig_lang                = 'de';
		$product_id               = rand( 1, 100 );

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
		                                         ->disableOriginalConstructor()
		                                         ->setMethods( array( 'get_original_product_language' ) )
		                                         ->getMock();

		$this->woocommerce_wpml->products
			->method( 'get_original_product_language' )
			->with( $product_id )
			->willReturn( $orig_lang );

		$this->sitepress
			->method( 'get_current_language' )
			->willReturn( $orig_lang );

		\WP_Mock::wpFunction( 'is_admin', array(
			'times'  => 1,
			'return' => false
		) );

		$subject = $this->get_subject();

		$filtered_attribute_name = $subject->filter_attribute_name( $attribute_name, $product_id, false );

		$this->assertEquals( $attribute_name, $filtered_attribute_name );
	}

	/**
	 * @test
	 */
	public function sync_default_product_attr_for_custom_attributes_should_sync_by_insert() {
		$subject = $this->get_subject();

		$original_product_id   = 27222;
		$translated_product_id = 1919191;
		$lang                  = 'ru';

		$original_product_attributes = [
			'size'  => [
				'name'  => 'size',
				'value' => 'small | medium | big'
			],
			'color' => [
				'name'  => 'color',
				'value' => 'white|black'
			]
		];

		$original_default_attributes = [
			'size'  => 'medium',
			'color' => 'black'
		];

		$translated_product_attributes = [
			'size'  => [
				'name'  => 'size',
				'value' => 'small-translated | medium-translated | big-translated'
			],
			'color' => [
				'name'  => 'color',
				'value' => 'white-translated|black-translated'
			]
		];
		// default attribute not set
		$translated_product_meta = [
			'_product_attributes' => $translated_product_attributes
		];

		$expected_translated_default_attributes = [
			'size'  => 'medium-translated',
			'color' => 'black-translated'
		];


		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $original_product_id, '_default_attributes', true ],
			'return' => $original_default_attributes
		] );

		\WP_Mock::passthruFunction( 'maybe_unserialize' );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $original_product_id, '_product_attributes', true ],
			'return' => $original_product_attributes
		] );

		\WP_Mock::passthruFunction( 'sanitize_title' );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $translated_product_id, '_product_attributes', true ],
			'return' => $translated_product_attributes
		] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $translated_product_id ],
			'return' => $translated_product_meta
		] );

		\WP_Mock::passthruFunction( 'maybe_serialize' );

		$insert_data = [
			'post_id'    => $translated_product_id,
			'meta_key'   => '_default_attributes',
			'meta_value' => $expected_translated_default_attributes
		];

		$this->wpdb->expects( $this->once() )
		           ->method( 'insert' )
		           ->with( $this->wpdb->postmeta, $insert_data );

		$subject->sync_default_product_attr( $original_product_id, $translated_product_id, $lang );

	}

	/**
	 * @test
	 */
	public function sync_default_product_attr_for_custom_attributes_should_sync_by_update() {
		$subject = $this->get_subject();

		$original_product_id   = 27222;
		$translated_product_id = 1919191;
		$lang                  = 'ru';

		$original_product_attributes = [
			'size'  => [
				'name'  => 'size',
				'value' => 'small | medium | big'
			],
			'color' => [
				'name'  => 'color',
				'value' => 'white|black'
			]
		];

		$original_default_attributes = [
			'size'  => 'medium',
			'color' => 'black'
		];

		$translated_product_attributes = [
			'size'  => [
				'name'  => 'size',
				'value' => 'small-translated | medium-translated | big-translated'
			],
			'color' => [
				'name'  => 'color',
				'value' => 'white-translated|black-translated'
			]
		];
		// default attribute ARE set
		$translated_product_meta = [
			'_product_attributes' => $translated_product_attributes,
			'_default_attributes' => [ 'size' => 'small-translated', 'color' => 'white-translated' ]
		];

		$expected_translated_default_attributes = [
			'size'  => 'medium-translated',
			'color' => 'black-translated'
		];


		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $original_product_id, '_default_attributes', true ],
			'return' => $original_default_attributes
		] );

		\WP_Mock::passthruFunction( 'maybe_unserialize' );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $original_product_id, '_product_attributes', true ],
			'return' => $original_product_attributes
		] );

		\WP_Mock::passthruFunction( 'sanitize_title' );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $translated_product_id, '_product_attributes', true ],
			'return' => $translated_product_attributes
		] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $translated_product_id ],
			'return' => $translated_product_meta
		] );

		\WP_Mock::passthruFunction( 'maybe_serialize' );

		$update_data = [
			'meta_value' => $expected_translated_default_attributes
		];
		$update_where = [
			'post_id'    => $translated_product_id,
			'meta_key'   => '_default_attributes'
		];

		$this->wpdb->expects( $this->once() )
		           ->method( 'update' )
		           ->with( $this->wpdb->postmeta, $update_data, $update_where );

		$subject->sync_default_product_attr( $original_product_id, $translated_product_id, $lang );

	}

	/**
	 * @test
	 */
	public function it_does_filter_available_variation_attribute_values_in_current_language() {

		$attribute_taxonomy = rand_str( 10 );
		$attribute_key = 'attribute_'.$attribute_taxonomy;
		$attribute_value = rand_str( 12 );
		$translated_attribute_value = rand_str( 15 );

		$args['attributes'] = array( $attribute_key => $attribute_value );

		$term = new stdClass();
		$term->slug = $translated_attribute_value;

		\WP_Mock::wpFunction( 'get_term_by', array(
			'args'  => array( 'slug', $attribute_value, $attribute_taxonomy ),
			'return' => $term
		) );

		$subject = $this->get_subject();

		$filter_attribute = $subject->filter_available_variation_attribute_values_in_current_language( $args );

		$this->assertEquals( $filter_attribute[ 'attributes' ][ $attribute_key ], $translated_attribute_value );
	}

	/**
	 * @test
	 */
	public function it_does_filter_product_variation_post_meta_attribute_values_in_current_language_when_post_type_is_product_variation() {

		$object_id = mt_rand( 1, 10 );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'  => array( $object_id ),
			'return' => 'product_variation'
		) );

		$attribute_taxonomy = rand_str( 10 );
		$attribute_key = 'attribute_'.$attribute_taxonomy;
		$attribute_value = rand_str( 12 );
		$translated_attribute_value = rand_str( 15 );

		$all_meta[ $attribute_key ] = array( $attribute_value );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args'  => array( $object_id ),
			'return' => $all_meta
		) );

		$term = new stdClass();
		$term->slug = $translated_attribute_value;

		\WP_Mock::wpFunction( 'get_term_by', array(
			'args'  => array( 'slug', $attribute_value, $attribute_taxonomy ),
			'return' => $term
		) );

		$subject = $this->get_subject();

		$filter_attribute = $subject->filter_product_variation_post_meta_attribute_values_in_current_language( null, $object_id, '', false );

		$this->assertEquals( $filter_attribute[ $attribute_key ][ 0 ], $translated_attribute_value );
	}

	/**
	 * @test
	 */
	public function does_not_filter_product_variation_post_meta_attribute_values_in_current_language_when_post_type_is_not_product_variation() {

		$object_id = mt_rand( 1, 10 );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'args'  => array( $object_id ),
			'return' => rand_str()
		) );

		$subject = $this->get_subject();

		$filter_attribute = $subject->filter_product_variation_post_meta_attribute_values_in_current_language( null, $object_id, '', false );

		$this->assertNull( $filter_attribute );
	}

}
