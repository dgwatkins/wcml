<?php

use tad\FunctionMocker\FunctionMocker;

class Test_WCML_TP_Support extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_Element_Translation_Package */
	private $tp;
	/** @var array */
	private $tm_settings;

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
			->setMethods( array( 'get_original_product_id' ) )
			->getMock();

		$this->tp = $this->getMockBuilder( 'WPML_Element_Translation_Package' )
			->disableOriginalConstructor()
			->setMethods( array( 'encode_field_data' ) )
			->getMock();

		$base = 'base64';
		$this->tp->method( 'encode_field_data' )->willReturnCallback( function ( $value ) {
			return $value;
		});

		$this->wpdb = $this->stubs->wpdb();

		$this->tm_settings['custom_fields_translation'] = [];
	}

	/**
	 * @return WCML_TP_Support
	 */
	private function get_subject(){
		$subject = new WCML_TP_Support( $this->woocommerce_wpml, $this->wpdb, $this->tp, $this->tm_settings );

		return $subject;
	}

	/**
	 * @test
	 */
	public function add_hooks(){

		FunctionMocker::replace( 'defined', function( $name ) {
			return 'WPML_MEDIA_VERSION' === $name ? false : null;
		});

		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'wpml_tm_translation_job_data', [ $subject, 'append_custom_attributes_to_translation_package' ], 10, 2 );
		\WP_Mock::expectActionAdded( 'wpml_translation_job_saved',   [ $subject, 'save_custom_attribute_translations' ], 10, 3 );

		\WP_Mock::expectFilterAdded( 'wpml_tm_translation_job_data', [ $subject, 'append_variation_custom_fields_to_translation_package' ], 10, 2 );
		\WP_Mock::expectActionAdded( 'wpml_pro_translation_completed',   [ $subject, 'save_variation_custom_fields_translations' ], 20, 3 );

		\WP_Mock::expectFilterAdded( 'wpml_tm_translation_job_data', [ $subject, 'append_images_to_translation_package' ], 10, 2 );
		\WP_Mock::expectActionAdded( 'wpml_translation_job_saved',   [ $subject, 'save_images_translations' ], 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function it_should_not_add_media_hooks_when_Media_plugin_enabled(){

		FunctionMocker::replace( 'defined', function( $name ) {
			return 'WPML_MEDIA_VERSION' === $name ? true : null;
		});

		$subject = $this->get_subject();

		\WP_Mock::expectFilterNotAdded( 'wpml_tm_translation_job_data', [ $subject, 'append_images_to_translation_package' ], 10, 2 );
		\WP_Mock::expectActionNotAdded( 'wpml_translation_job_saved',   [ $subject, 'save_images_translations' ], 10, 3 );

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
		$data = [
			'wc_attribute_name:custom'    =>
				[
					'data'       => 'custom es',
					'finished'   => 1,
					'tid'        => '65',
					'field_type' => 'wc_attribute_name:custom',
					'format'     => 'base64',
				],
			'wc_attribute_value:0:custom' =>
				[
					'data'       => 'val es',
					'finished'   => 1,
					'tid'        => '66',
					'field_type' => 'wc_attribute_value:0:custom',
					'format'     => 'base64',
				],
			'wc_attribute_value:1:custom' =>
				[
					'data'       => 'val2 es',
					'finished'   => 1,
					'tid'        => '67',
					'field_type' => 'wc_attribute_value:1:custom',
					'format'     => 'base64',
				],
			'wc_attribute_name:attr-es'    =>
				[
					'data'       => 'attr es',
					'finished'   => 1,
					'tid'        => '68',
					'field_type' => 'wc_attribute_name:attr-es',
					'format'     => 'base64',
				],
			'wc_attribute_value:0:attr-es' =>
				[
					'data'       => 'val es',
					'finished'   => 1,
					'tid'        => '69',
					'field_type' => 'wc_attribute_value:0:attr-es',
					'format'     => 'base64',
				],
			'wc_attribute_value:1:attr-es' =>
				[
					'data'       => 'val2 es',
					'finished'   => 1,
					'tid'        => '70',
					'field_type' => 'wc_attribute_value:1:attr-es',
					'format'     => 'base64',
				],
		];
		$job = new stdClass();
		$job->language_code = 'es';
		$original_product_id = 2;

		\WP_Mock::wpFunction( 'get_post_meta', [
			'args' => [ $product_id, 'attr_label_translations', true ],
			'return' => false
		] );


		\WP_Mock::wpFunction( 'get_post_meta', [
			'args' => [ $product_id, '_product_attributes', true ],
			'return' => []
		] );

		$this->woocommerce_wpml->products->method( 'get_original_product_id' )->willReturn( $original_product_id );

		$original_product_attributes = [
			'custom' =>
				[
					'name'        => 'custom',
					'value'       => 'val | val2',
					'position'    => 0,
					'is_visible'  => 1,
					'is_taxonomy' => 0,
				],
		];

		\WP_Mock::wpFunction( 'get_post_meta', [
			'args' => [ $original_product_id, '_product_attributes', true ],
			'return' => $original_product_attributes
		] );

		$expected_product_attributes = $original_product_attributes;
		$expected_product_attributes['custom']['name'] = 'custom es';
		$expected_product_attributes['custom']['value'] = 'val es | val2 es';

		\WP_Mock::wpFunction( 'update_post_meta', [
			'args' => [ $product_id, '_product_attributes', $expected_product_attributes ],
			'times' => 1,
			'return' => true
		] );

		$expected_product_attributes_labels = [
			'es' =>
				[
					'custom' => 'custom es'
				],
		];

		\WP_Mock::wpFunction( 'update_post_meta', [
			'args' => [ $product_id, 'attr_label_translations', $expected_product_attributes_labels ],
			'times' => 1,
			'return' => true
		] );

		$subject = $this->get_subject();
		$subject->save_custom_attribute_translations( $product_id, $data, $job );
	}

	/**
	 * @test
	 */
	public function it_should_append_variation_custom_fields_to_translation_package() {

		$package               = [];
		$post                  = new stdClass();
		$post->ID              = 1;
		$post->post_type       = 'product';
		$variation_description = rand_str();
		$custom_key_value      = rand_str();
		$meta_keys             = [ '_variation_description', '_custom_key', '_custom_key_ignore', '_custom_key_copy', '_custom_key_copy_once' ];

		$this->tm_settings['custom_fields_translation']['_variation_description'] = WPML_TRANSLATE_CUSTOM_FIELD;
		$this->tm_settings['custom_fields_translation']['_custom_key'] = WPML_TRANSLATE_CUSTOM_FIELD;
		$this->tm_settings['custom_fields_translation']['_custom_key_ignore'] = WPML_IGNORE_CUSTOM_FIELD;
		$this->tm_settings['custom_fields_translation']['_custom_key_copy'] = WPML_COPY_CUSTOM_FIELD;
		$this->tm_settings['custom_fields_translation']['_custom_key_copy_once'] = WPML_COPY_ONCE_CUSTOM_FIELD;

		$product_object = $this->getMockBuilder( 'WC_Product' )
		                       ->disableOriginalConstructor()
		                       ->setMethods( [ 'get_type' ] )
		                       ->getMock();

		$product_object->method( 'get_type' )->willReturn( 'variable' );

		\WP_Mock::wpFunction( 'wc_get_product', [
			'args'   => [ $post->ID ],
			'return' => $product_object
		] );

		$variation     = new stdClass();
		$variation->ID = 101;

		$available_variations = [
			$variation
		];

		\WP_Mock::userFunction( 'get_post_custom_keys', [
			'args'   => [ $variation->ID ],
			'return' => $meta_keys
		] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $variation->ID, '_variation_description', true ],
			'return' => $variation_description
		] );

		\WP_Mock::userFunction( 'get_post_meta', [
			'args'   => [ $variation->ID, '_custom_key', true ],
			'return' => $custom_key_value
		] );

		$sync_variations_data = $this->getMockBuilder( 'WCML_Synchronize_Variations_Data' )
		                             ->disableOriginalConstructor()
		                             ->setMethods( [ 'get_product_variations' ] )
		                             ->getMock();
		$sync_variations_data->method( 'get_product_variations' )->with( $post->ID )->willReturn( $available_variations );

		$this->woocommerce_wpml->sync_variations_data = $sync_variations_data;

		$expected_package = [
			'contents' => [
				'wc_variation_field:_variation_description:' . $variation->ID =>
					[
						'translate' => 1,
						'data'      => $variation_description,
						'format'    => 'base64'
					],
				'wc_variation_field:_custom_key:' . $variation->ID            =>
					[
						'translate' => 1,
						'data'      => $custom_key_value,
						'format'    => 'base64'
					],
			]
		];

		$subject          = $this->get_subject();
		$filtered_package = $subject->append_variation_custom_fields_to_translation_package( $package, $post );

		$this->assertEquals( $expected_package, $filtered_package );
	}

	/**
	 * @test
	 */
	public function it_should_save_variation_custom_fields_translations() {

		$variation_id            = 2;
		$translated_variation_id = 3;
		$translated_description  = 'description es';
		$translated_custom_key   = 'custom es';

		$data                = [
			'wc_variation_field:_variation_description:' . $variation_id =>
				[
					'data'       => $translated_description,
					'finished'   => 1,
					'tid'        => '65',
					'field_type' => 'wc_variation_field:_variation_description:' . $variation_id,
					'format'     => 'base64',
				],
			'wc_variation_field:_custom_key:' . $variation_id            =>
				[
					'data'       => $translated_custom_key,
					'finished'   => 1,
					'tid'        => '66',
					'field_type' => 'wc_variation_field:_custom_key:' . $variation_id,
					'format'     => 'base64',
				],
		];
		$job                 = new stdClass();
		$job->language_code  = 'es';
		$original_product_id = 1;

		\WP_Mock::onFilter( 'translate_object_id' )->with( $variation_id, 'product_variation', false, $job->language_code )->reply( $translated_variation_id );

		\WP_Mock::wpFunction( 'is_post_type_translated', [
			'args'   => [ 'product_variation' ],
			'times'  => 2,
			'return' => true
		] );

		\WP_Mock::wpFunction( 'update_post_meta', [
			'args'   => [ $translated_variation_id, '_variation_description', $translated_description ],
			'times'  => 1,
			'return' => true
		] );

		\WP_Mock::wpFunction( 'update_post_meta', [
			'args'   => [ $translated_variation_id, '_custom_key', $translated_custom_key ],
			'times'  => 1,
			'return' => true
		] );

		$subject = $this->get_subject();
		$subject->save_variation_custom_fields_translations( $original_product_id, $data, $job );
	}

}
