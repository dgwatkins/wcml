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
			->setMethods( array( 'get_wp_api' ) )
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

}
