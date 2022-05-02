<?php

use WPML\FP\Str;

/**
 * @group compatibility
 * @group wc-composite-products
 * @group wcml-3687
 */
class Test_WCML_Composite_Products extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var WPML_Element_Translation_Package */
	private $tp;

	private function get_subject( $sitepress = null, $woocommerce_wpml = null, $tp = null ) {

		if ( null === $sitepress ) {
			$sitepress = $this->getMockBuilder( 'Sitepress' )
			                  ->disableOriginalConstructor()
			                  ->getMock();
		}

		if ( null === $woocommerce_wpml ) {
			$woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
			                         ->disableOriginalConstructor()
			                         ->getMock();
		}

		if ( null === $tp ) {
			$tp = $this->getMockBuilder( 'WPML_Element_Translation_Package' )
			           ->disableOriginalConstructor()
			           ->getMock();
		}

		return new WCML_Composite_Products( $sitepress, $woocommerce_wpml, $tp );
	}

	/**
	 * @test
	 */
	public function add_hooks() {
		\WP_Mock::wpFunction( 'is_admin', [ 'return' => true ] );

		$subject = $this->get_subject();
		\WP_Mock::expectFilterAdded( 'wcml_do_not_display_custom_fields_for_product', [
			$subject,
			'replace_tm_editor_custom_fields_with_own_sections'
		] );
		\WP_Mock::expectActionAdded( 'wcml_before_sync_product_data', [
			$subject,
			'sync_composite_data_across_translations'
		], 10, 2 );
		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function replace_tm_editor_custom_fields_with_own_sections(){

		$subject = $this->get_subject();
		$fields_to_hide = $subject->replace_tm_editor_custom_fields_with_own_sections( array() );
		$this->assertEquals( array( '_bto_data', '_bto_scenario_data' ), $fields_to_hide );

	}

	/**
	 * @test
	 */
	public function it_should_get_composite_data(){

		$product_id = 21;
		$bto_data = array( 11 => array( 'title' => 'test' ) );

		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'args' => array( $product_id, '_bto_data', true ),
				'return' => $bto_data
			)
		);

		$subject = $this->get_subject();
		$composite_data = $subject->get_composite_data( $product_id );
		$this->assertEquals( $bto_data, $composite_data );
	}

	/**
	 * @test
	 */
	public function it_should_return_empty_array_if_composite_data_not_exists(){

		$product_id = 21;

		\WP_Mock::userFunction(
			'get_post_meta',
			array(
				'args' => array( $product_id, '_bto_data', true ),
				'return' => false
			)
		);

		$subject = $this->get_subject();
		$composite_data = $subject->get_composite_data( $product_id );
		$this->assertEquals( array(), $composite_data );
	}

	/**
	 * @test
	 */
	public function it_should_sync_composite_data_across_translations(){

		\WP_Mock::passthruFunction( 'sanitize_title' );

		$original_product_id = 21;
		$current_product_id = 22;

		$product_type = new stdClass();
		$product_type->name = 'composite';
		$terms = [ $product_type ];

		\WP_Mock::userFunction(
			'wp_get_object_terms',
			[
				'args' => [ $original_product_id, 'product_type' ],
				'return' => $terms
			]
		);

		$assigned_original_product_id = 12;
		$assigned_original_category_id = 14;

		$original_bto_data[] = [
			'title'        => 'test title',
			'description'  => 'test description',
			'query_type'   => 'product_ids',
			'default_id'   => $assigned_original_product_id,
			'assigned_ids' => [ $assigned_original_product_id ],
		];

		$original_bto_data[] = [
			'title'                 => 'test title',
			'description'           => 'test description',
			'query_type'            => 'category_ids',
			'default_id'            => $assigned_original_category_id,
			'assigned_category_ids' => [ $assigned_original_category_id ],
		];

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args' => [ $original_product_id, '_bto_data', true ],
				'return' => $original_bto_data
			]
		);

		$translated_bto_data[] = [
			'title'        => 'test title FR',
			'description'  => 'test description FR',
			'query_type'   => 'product_ids',
			'default_id'   => $assigned_original_product_id,
			'assigned_ids' => [ $assigned_original_product_id ],
		];

		$translated_bto_data[] = [
			'title'                 => 'test title FR',
			'description'           => 'test description FR',
			'query_type'            => 'category_ids',
			'default_id'            => $assigned_original_category_id,
			'assigned_category_ids' => [ $assigned_original_category_id ],
		];

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args' => [ $current_product_id, '_bto_data', true ],
				'return' => $translated_bto_data
			]
		);

		$original_bto_scenario_data = [
			[
				'component_data' => [
					1451 => [ $assigned_original_product_id ],
					1452 => [ $assigned_original_category_id ],
				],
				'scenario_actions'      => [
					'conditional_options' => [
						'is_active' => "yes",
						'component_data' => [
							1451 => [ $assigned_original_product_id ],
							1452 => [ $assigned_original_category_id ],
						],
					],
				],
			],
		];

		\WP_Mock::userFunction(
			'get_post_meta',
			[
				'args' => [ $original_product_id, '_bto_scenario_data', true ],
				'return' => $original_bto_scenario_data
			]
		);

		\WP_Mock::userFunction(
			'get_post_type',
			[
				'args' => [ $assigned_original_product_id ],
				'return' => 'product'
			]
		);

		\WP_Mock::userFunction(
			'get_post_type',
			[
				'args' => [ $assigned_original_category_id ],
				'return' => 'product_cat'
			]
		);



		$sitepress = $this->getMockBuilder( 'Sitepress' )
		                  ->disableOriginalConstructor()
		                  ->setMethods( [ 'get_element_trid', 'get_element_translations' ] )
		                  ->getMock();

		$en_translation                = new stdClass();
		$en_translation->language_code = 'en';
		$en_translation->original      = true;
		$en_translation->element_id    = $original_product_id;
		$translations['en']            = $en_translation;

		$fr_translation                = new stdClass();
		$fr_translation->language_code = 'fr';
		$fr_translation->element_id    = $current_product_id;
		$translations['fr']            = $fr_translation;

		$sitepress->method( 'get_element_translations' )->willReturn( $translations );

		$translated_assigned_product_id = 15;

		WP_Mock::onFilter( 'wpml_object_id' )->with( $assigned_original_product_id, 'product', true, $fr_translation->language_code )->reply( $translated_assigned_product_id );
		WP_Mock::onFilter( 'wpml_object_id' )->with( $assigned_original_product_id, 'product', false, $fr_translation->language_code )->reply( $translated_assigned_product_id );

		$expected_bto_data = $translated_bto_data;
		$expected_bto_data[0]['default_id'] = $translated_assigned_product_id;
		$expected_bto_data[0]['assigned_ids'] = [ $translated_assigned_product_id ];

		$translated_assigned_category_id  = 16;

		WP_Mock::onFilter( 'wpml_object_id' )->with( $assigned_original_category_id, 'product_cat', false, $fr_translation->language_code )->reply( $translated_assigned_category_id );

		$expected_bto_data[1]['default_id'] = $translated_assigned_category_id;
		$expected_bto_data[1]['assigned_category_ids'] = [ $translated_assigned_category_id ];

		\WP_Mock::userFunction(
			'update_post_meta',
			[
				'args' => [ $current_product_id, '_bto_data', $expected_bto_data ],
				'times' => 1,
				'return' => true
			]
		);

		$expected_scenario_data = [
			[
				'component_data' => [
					1451 => [ $translated_assigned_product_id ],
					1452 => [ $translated_assigned_category_id ],
				],
				'scenario_actions'      => [
					'conditional_options' => [
						'is_active' => "yes",
						'component_data' => [
							1451 => [ $translated_assigned_product_id ],
							1452 => [ $translated_assigned_category_id ],
						],
					],
				],
			]
		];

		\WP_Mock::userFunction(
			'update_post_meta',
			[
				'args' => [ $current_product_id, '_bto_scenario_data', $expected_scenario_data ],
				'times' => 1,
				'return' => true
			]
		);

		$subject = $this->get_subject( $sitepress );
		$subject->sync_composite_data_across_translations( $original_product_id, $current_product_id );
	}

	/**
	 * @test
	 */
	public function it_should_not_sync_composite_data_across_translations_for_simple_product(){

		\WP_Mock::passthruFunction( 'sanitize_title' );

		$original_product_id = 10;
		$current_product_id = 11;

		$product_type = new stdClass();
		$product_type->name = 'simple';
		$terms = [ $product_type ];

		\WP_Mock::userFunction(
			'wp_get_object_terms',
			[
				'args' => [ $original_product_id, 'product_type' ],
				'return' => $terms
			]
		);

		$subject = $this->get_subject();
		$subject->sync_composite_data_across_translations( $original_product_id, $current_product_id );
	}

	/**
	 * @test
	 * @group wcml-3841
	 */
	public function it_should_append_composite_data_translation_package() {
		$get_item = function( $string ) {
			return [
				'translate' => 1,
				'data'      => base64_encode( $string ),
				'format'    => 'base64',
			];
		};

		$post            = \Mockery::mock( \WP_Post::class );
		$post->post_type = 'product';
		$post->ID        = 123;

		$component_id = 1638382248;
		$title        = 'The title';
		$description  = 'The description';

		$package = [
			'contents' => [
				'foo' => $get_item( 'bar' ),
			],
		];

		$expected_package = [
			'contents' => [
				'foo'                                             => $get_item( 'bar' ),
				"wc_composite:$component_id:title"                => $get_item( $title ),
				"wc_composite:$component_id:description"          => $get_item( $description ),
				"wc_composite:scenario:$component_id:title"       => $get_item( $title ),
				"wc_composite:scenario:$component_id:description" => $get_item( $description ),
			],
		];

		$original_cp_data = [
			$component_id => [
				'title'       => $title,
				'description' => $description,
			]
		];

		$original_cp_scenario = [
			$component_id => [
				'title'        => $title,
				'description'  => $description,
			]
		];

		\WP_Mock::userFunction( 'get_post_meta' )
		        ->withArgs( [ $post->ID, '_bto_data', true ] )
		        ->andReturn( $original_cp_data );

		\WP_Mock::userFunction( 'get_post_meta' )
		        ->withArgs( [ $post->ID, '_bto_scenario_data', true ] )
		        ->andReturn( $original_cp_scenario );

		$this->assertEquals(
			$expected_package,
			$this->get_subject()->append_composite_data_translation_package( $package, $post )
		);
	}

	/**
	 * @test
	 * @dataProvider dp_should_save_composite_data_translation
	 * @group wcml-3841
	 *
	 * @param string $query_type
	 * @param string $query_cpt
	 * @param string $ids_key
	 */
	public function it_should_save_composite_data_translation( $query_type, $query_cpt, $ids_key ) {
		$original_post_id   = 123;
		$post_id            = 456;
		$post_type          = 'product';
		$target_lang        = 'fr';
		$component_id       = 1638382248;
		$original_target_id = 1000;
		$target_id          = 1001;
		$title              = 'The title';
		$description        = 'The description';

		$translate = Str::concat( "$target_lang " );

		$data = [
			"wc_composite:$component_id:title" => [
				'data' => $translate( $title ),
			],
			"wc_composite:$component_id:description" => [
				'data' => $translate( $description ),
			],
			"wc_composite:scenario:$component_id:title" => [
				'data' => $translate( $title ),
			],
			"wc_composite:scenario:$component_id:description" => [
				'data' => $translate( $description ),
			],
		];

		$job = (object) [
			'original_post_type' => "post_$post_type",
			'original_doc_id'    => $original_post_id,
			'language_code'      => $target_lang,
		];

		$original_cp_data = [
			$component_id => [
				'query_type'  => $query_type,
				$ids_key      => [ $original_target_id ],
				'title'       => $title,
				'description' => $description,
			]
		];

		$original_cp_scenario = [
			$component_id => [
				'title'        => $title,
				'description'  => $description,
			]
		];

		$expected_cp_data   = [
			$component_id => [
				'query_type'  => $query_type,
				$ids_key      => [ $target_id ],
				'title'       => $translate( $title ),
				'description' => $translate( $description ),
			]
		];

		$expected_cp_scenario = [
			$component_id => [
				'title'        => $translate( $title ),
				'description'  => $translate( $description ),
			]
		];

		\WP_Mock::userFunction( 'get_post_type' )
			->withArgs( [ $original_post_id ] )
			->andReturn( $post_type );

		\WP_Mock::userFunction( 'get_post_meta' )
			->withArgs( [ $original_post_id, '_bto_data', true ] )
			->andReturn( $original_cp_data );

		\WP_Mock::userFunction( 'get_post_meta' )
			->withArgs( [ $original_post_id, '_bto_scenario_data', true ] )
			->andReturn( $original_cp_scenario );

		\WP_Mock::onFilter( 'wpml_object_id' )
			->with( $original_target_id, $query_cpt, true, $target_lang )
			->reply( $target_id );

		\WP_Mock::userFunction( 'update_post_meta' )
			->times( 1 )
			->withArgs( [ $post_id, '_bto_data', $expected_cp_data ] );

		\WP_Mock::userFunction( 'update_post_meta' )
			->times( 1 )
			->withArgs( [ $post_id, '_bto_scenario_data', $expected_cp_scenario ] );

		$this->get_subject()->save_composite_data_translation( $post_id, $data, $job );
	}

	public function dp_should_save_composite_data_translation() {
		return [
			'product ids' => [
				'product_ids',
				'product',
				'assigned_ids',
			],
			'category ids' => [
				'category_ids',
				'product_cat',
				'assigned_category_ids',
			],
		];
	}
}
