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

}
