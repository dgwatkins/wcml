<?php

class Test_WCML_TP_Support extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_Element_Translation_Package */
	private $tp;

	public function setUp() {
		parent::setUp();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->woocommerce_wpml->attributes = $this->getMockBuilder( 'WCML_Attributes' )
			->disableOriginalConstructor()
			->setMethods( array( 'is_a_taxonomy' ) )
			->getMock();

		$this->tp = $this->getMockBuilder( 'WPML_Element_Translation_Package' )
			->disableOriginalConstructor()
			->setMethods( array( 'encode_field_data' ) )
			->getMock();


		$this->wpdb = $this->stubs->wpdb();
	}

	/**
	 * @return WCML_TP_Support
	 */
	private function get_subject(){
		$subject = new WCML_TP_Support( $this->woocommerce_wpml, $this->wpdb, $this->tp );

		return $subject;
	}

	/**
	 * @test
	 */
	public function add_hooks(){

		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'wpml_tm_translation_job_data', array( $subject, 'append_custom_attributes_to_translation_package' ), 10, 2 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function append_custom_attributes_to_translation_package(){

		$package = array();

		$post = new stdClass();
		$post->ID = rand( 1, 100 );
		$post->post_type = 'product';

		$product = $this->getMockBuilder( 'WC_Product' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_attributes' ) )
			->getMock();

		$wc_functions_wrapper_mock             = \Mockery::mock( 'alias:WooCommerce_Functions_Wrapper' );
		$wc_functions_wrapper_mock->shouldReceive( 'get_product_type' )->andReturn( 'variable' );

		\WP_Mock::wpFunction( 'wc_get_product', array(
			'args' => array( $post->ID ),
			'return' => $product
		) );

		$first_attribute_value = rand_str();
		$second_attribute_value = rand_str();
		$attribute_key = rand_str();
		$attribute_name = rand_str();

		$attributes = array(
			$attribute_key => array(
				'name' => $attribute_name,
				'value' => $first_attribute_value. '|'. $second_attribute_value
			)
		);

		$product->method( 'get_attributes' )->willReturn( $attributes );

		$this->woocommerce_wpml->attributes->method( 'is_a_taxonomy' )->willReturn( false );

		$base = 'base64';
		$this->tp->method( 'encode_field_data' )->willReturnCallback( function ( $value, $base ) {
			return $value;
		});

		$subject = $this->get_subject();
		$translation_package = $subject->append_custom_attributes_to_translation_package( $package, $post );

		$this->assertEquals( $attribute_name, $translation_package[ 'contents' ][ 'wc_attribute_name:' . $attribute_key ][ 'data' ] );
		$this->assertEquals( $first_attribute_value, $translation_package[ 'contents' ][ 'wc_attribute_value:0:' . $attribute_key ][ 'data' ] );
		$this->assertEquals( $second_attribute_value, $translation_package[ 'contents' ][ 'wc_attribute_value:1:' . $attribute_key ][ 'data' ] );

	}

}
