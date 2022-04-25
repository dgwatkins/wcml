<?php

/**
 * @group compatibility
 * @group wc-product-addons
 */
class Test_WCML_Product_Addons extends OTGS_TestCase {

	/** @var Sitepress */
	private $sitepress;
	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** var int */
	private $independent_currencies;

	public function setUp() {
		parent::setUp();

		$this->independent_currencies = 2;

		$this->sitepress = $this->getMockBuilder( 'Sitepress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array( 'get_wp_api' ) )
		                        ->getMock();

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'constant' ) )
		               ->getMock();

		$that = $this;
		$wp_api->method( 'constant' )->willReturnCallback( function ( $const ) use ( $that ) {
			if ( 'WCML_MULTI_CURRENCIES_INDEPENDENT' == $const ) {
				return $that->independent_currencies;
			} else if ( 'WCML_PLUGIN_PATH' == $const ) {
				return rand_str();
			}
		} );

		$this->sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->woocommerce_wpml->settings['enable_multi_currency'] = $this->independent_currencies;

	}

	public function get_subject() {

		return new WCML_Product_Addons( $this->sitepress, $this->woocommerce_wpml );
	}

	/**
	 * @test
	 */
	public function add_admin_hooks() {
		WP_Mock::userFunction( 'is_admin' )->andReturn( true );

		$subject = $this->get_subject();

		WP_Mock::expectFilterAdded( 'wcml_do_not_display_custom_fields_for_product', [
			$subject,
			'replace_tm_editor_custom_fields_with_own_sections'
		] );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_front_hooks() {
		WP_Mock::userFunction( 'is_admin' )->andReturn( false );

		$subject = $this->get_subject();
		WP_Mock::expectFilterAdded( 'get_post_metadata', [ $subject, 'translate_addons_strings' ], 10, 4 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function replace_tm_editor_custom_fields_with_own_sections() {

		$subject        = $this->get_subject();
		$fields_to_hide = $subject->replace_tm_editor_custom_fields_with_own_sections( array() );
		$this->assertEquals( array( '_product_addons' ), $fields_to_hide );

	}

	/**
	 * @test
	 * @dataProvider dp_sets_global_ids_without_adjusting_term_ids_in_query_args
	 *
	 * @param \WP_Post[]|int[] $global_addons
	 * @param int[] $expected_ids
	 */
	public function it_sets_global_ids_without_adjusting_term_ids_in_query_args( $global_addons, $expected_ids ) {
		$args = [
			'tax_query' => [ 'some query' ],
		];

		WP_Mock::userFunction( 'is_archive', [
			'return' => false
		] );

		WP_Mock::userFunction( 'get_posts', [
			'args'   => [ $args ],
			'return' => $global_addons
		] );

		WP_Mock::userFunction( 'wp_list_pluck', [
			'args'   => [ $global_addons, 'ID' ],
			'return' => function( $array, $property ) {
				return array_map( function( $addon ) use ( $property ) {
					return $addon->{$property};
				}, $array );
			},
		] );

		WP_Mock::userFunction( 'remove_filter', [
			'return' => true,
			'times'  => 3
		] );

		WP_Mock::expectFilterAdded( 'get_terms_args', [ $this->sitepress, 'get_terms_args_filter' ], 10, 2 );
		WP_Mock::expectFilterAdded( 'get_term', [ $this->sitepress, 'get_term_adjust_id' ], 1 );
		WP_Mock::expectFilterAdded( 'terms_clauses', [ $this->sitepress, 'terms_clauses' ], 10, 3 );

		$subject             = $this->get_subject();
		$filtered_query_args = $subject->set_global_ids_in_query_args( $args );

		$this->assertEquals( [ 'include' => $expected_ids ], $filtered_query_args );
	}

	public function dp_sets_global_ids_without_adjusting_term_ids_in_query_args() {
		$global_addon     = Mockery::mock( WP_Post::class );
		$global_addon->ID = 123;

		return [
			'get_posts returns objects' => [
				[ $global_addon ],
				[ $global_addon->ID ],
			],
			'get_posts returns ids'     => [
				[ $global_addon->ID ],
				[ $global_addon->ID ],
			],
		];
	}

	/**
	 * @test
	 * @group wcml-3804
	 */
	public function it_should_append_addons_to_translation_package() {
		$package = [
			'contents' => [
				'foo' => [],
			],
		];

		/** @var \WP_Post $post */
		$post            = \Mockery::mock( \WP_Post::class );
		$post->ID        = 123;
		$post->post_type = 'product';

		$addons = [
			[
				'name'        => 'The name',
				'description' => 'The description',
				'options'     => [
					[
						'label' => 'The first option label',
					],
					[
						// another option with no label
					],
				],
			],
			[
				// addon with no option
			],
		];

		WP_Mock::userFunction( 'get_post_meta' )
		        ->with( $post->ID, WCML_Product_Addons::ADDONS_OPTION_KEY, true )
				->andReturn( $addons );

		WP_Mock::passthruFunction( 'maybe_unserialize' );

		$expected_package = $package;
		$expected_package['contents'][ 'addon_0_name' ] = [
			'translate' => 1,
			'data'      => base64_encode( 'The name' ),
			'format'    => 'base64',
		];
		$expected_package['contents'][ 'addon_0_description' ] = [
			'translate' => 1,
			'data'      => base64_encode( 'The description' ),
			'format'    => 'base64',
		];
		$expected_package['contents'][ 'addon_0_option_0_label' ] = [
			'translate' => 1,
			'data'      => base64_encode( 'The first option label' ),
			'format'    => 'base64',
		];

		$this->assertEquals(
			$expected_package,
			$this->get_subject()->append_addons_to_translation_package( $package, $post )
		);
	}

	/**
	 * @test
	 * @group wcml-3804
	 */
	public function it_should_NOT_append_addons_to_translation_package_if_not_a_product() {
		$package = [
			'contents' => [
				'foo' => [],
			],
		];

		/** @var \WP_Post $post */
		$post            = \Mockery::mock( \WP_Post::class );
		$post->ID        = 123;
		$post->post_type = 'page';

		$this->assertEquals(
			$package,
			$this->get_subject()->append_addons_to_translation_package( $package, $post )
		);
	}

	/**
	 * @test
	 * @group wcml-3804
	 */
	public function it_should_save_addons_to_translation() {
		$post_id = 123;

		$fields = [
			'foo'                    => [ 'data' => 'FR bar' ],
			'addon_0_name'           => [ 'data' => 'FR The name' ],
			'addon_0_description'    => [ 'data' => 'FR The description' ],
			'addon_0_option_0_label' => [ 'data' => 'FR The first option label' ],
		];

		$job = (object) [
			'original_post_type' => 'post_product',
		];

		$addons = [
			[
				'name'        => 'The name',
				'description' => 'The description',
				'options'     => [
					[
						'label' => 'The first option label',
					],
					[
						// another option with no label
					],
				],
			],
			[
				// addon with no option
			],
		];

		$expected_addons = [
			[
				'name'        => 'FR The name',
				'description' => 'FR The description',
				'options'     => [
					[
						'label' => 'FR The first option label',
					],
					[
						// another option with no label
					],
				],
			],
			[
				// addon with no option
			],
		];

		WP_Mock::userFunction( 'get_post_type' )
			->with( $post_id )
			->andReturn( 'product' );

		WP_Mock::userFunction( 'get_post_meta' )
		        ->with( $post_id, WCML_Product_Addons::ADDONS_OPTION_KEY, true )
				->andReturn( $addons );

		WP_Mock::passthruFunction( 'maybe_unserialize' );

		WP_Mock::userFunction( 'update_post_meta', [
			'times' => 1,
			'args'  => [ $post_id, WCML_Product_Addons::ADDONS_OPTION_KEY, $expected_addons ],
		] );

		$this->get_subject()->save_addons_to_translation( $post_id, $fields, $job );
	}

	/**
	 * @test
	 * @group wcml-3804
	 */
	public function it_should_save_addons_to_translation_with_empty_addons_data() {
		$post_id = 123;

		$fields = [];

		$job = (object) [
			'original_post_type' => 'post_product',
		];

		$addons = '';

		WP_Mock::userFunction( 'get_post_type' )
		        ->with( $post_id )
		        ->andReturn( 'product' );

		WP_Mock::userFunction( 'get_post_meta' )
		        ->with( $post_id, WCML_Product_Addons::ADDONS_OPTION_KEY, true )
		        ->andReturn( $addons );

		WP_Mock::userFunction( 'maybe_unserialize' )->times( 0 );

		WP_Mock::userFunction( 'update_post_meta', [
			'times' => 1,
			'args'  => [ $post_id, WCML_Product_Addons::ADDONS_OPTION_KEY, [] ],
		] );

		$this->get_subject()->save_addons_to_translation( $post_id, $fields, $job );
	}

	/**
	 * @test
	 * @group wcml-3804
	 */
	public function it_should_NOT_save_addons_to_translation_if_not_a_product() {
		$post_id = 123;

		$fields = [
			'foo'                    => [ 'data' => 'FR bar' ],
			'addon_0_name'           => [ 'data' => 'FR The name' ],
			'addon_0_description'    => [ 'data' => 'FR The description' ],
			'addon_0_option_0_label' => [ 'data' => 'FR The first option label' ],
		];

		$job = (object) [
			'original_post_type' => 'tax_something',
		];

		WP_Mock::userFunction( 'get_post_type' )
			->with( $post_id )
			->andReturn( 'product' );

		WP_Mock::userFunction( 'update_post_meta', [
			'times' => 0,
		] );

		$this->get_subject()->save_addons_to_translation( $post_id, $fields, $job );
	}
}
