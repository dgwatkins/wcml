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

		$this->woocommerce_wpml->products = $this->getMockBuilder( 'WCML_Products' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_original_product_language' ) )
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
		\WP_Mock::expectActionAdded( 'wpml_translation_job_saved',   array( $subject, 'save_custom_attribute_translations' ), 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @dataProvider product_types
	 */
	public function append_custom_attributes_to_translation_package( $product_type ){

		$package = array();

		$post = new stdClass();
		$post->ID = rand( 1, 100 );
		$post->post_type = 'product';

		$product = $this->getMockBuilder( 'WC_Product' )
			->disableOriginalConstructor()
			->setMethods( array( 'get_attributes','get_type' ) )
			->getMock();

		$product->method( 'get_type' )->willReturn( $product_type );

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

	function product_types(){
		return array(
			array( 'simple' ),
			array( 'variable' ),
			array( 'external' ),
			array( 'grouped' )
		);
	}

	/**
	 * @test
	 */
	public function it_should_save_custom_attribute_translations(){

		$product_id = 1;
		$data = array(
			'wc_attribute_name:custom'    =>
				array(
					'data'       => 'custom es',
					'finished'   => 1,
					'tid'        => '65',
					'field_type' => 'wc_attribute_name:custom',
					'format'     => 'base64',
				),
			'wc_attribute_value:0:custom' =>
				array(
					'data'       => 'val es',
					'finished'   => 1,
					'tid'        => '66',
					'field_type' => 'wc_attribute_value:0:custom',
					'format'     => 'base64',
				),
			'wc_attribute_value:1:custom' =>
				array(
					'data'       => 'val2 es',
					'finished'   => 1,
					'tid'        => '67',
					'field_type' => 'wc_attribute_value:1:custom',
					'format'     => 'base64',
				),
		);
		$job = new stdClass();
		$job->language_code = 'es';
		$original_post_language = 'en';
		$original_product_id = 2;

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $product_id, 'attr_label_translations', true ),
			'return' => false
		) );

		$product_attributes = array();

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $product_id, '_product_attributes', true ),
			'return' => $product_attributes
		) );

		$this->woocommerce_wpml->products->method( 'get_original_product_language' )->willReturn( $original_post_language );

		\WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', false, $original_post_language  )->reply( $original_product_id );

		$original_product_attributes = array (
			'custom' =>
				array (
					'name' => 'custom',
					'value' => 'val | val2',
					'position' => 0,
					'is_visible' => 1,
					'is_taxonomy' => 0,
				),
		);

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'args' => array( $original_product_id, '_product_attributes', true ),
			'return' => $original_product_attributes
		) );

		$expected_product_attributes = $original_product_attributes;
		$expected_product_attributes['custom']['name'] = 'custom es';
		$expected_product_attributes['custom']['value'] = 'val es | val2 es';

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args' => array( $product_id, '_product_attributes', $expected_product_attributes ),
			'times' => 1,
			'return' => true
		) );

		$expected_product_attributes_labels = array(
			'es' =>
				array(
					'custom' => 'custom es'
				),
		);

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'args' => array( $product_id, 'attr_label_translations', $expected_product_attributes_labels ),
			'times' => 1,
			'return' => true
		) );

		$subject = $this->get_subject();
		$subject->save_custom_attribute_translations( $product_id, $data, $job );
	}

}
