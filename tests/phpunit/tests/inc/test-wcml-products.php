<?php

use tad\FunctionMocker\FunctionMocker;
use WPML\Core\ISitePress;
use WPML\FP\Fns;

/**
 * Class Test_WCML_Products
 * @group wcml-2905
 */
class Test_WCML_Products extends OTGS_TestCase {

	private $default_language = 'en';
	private $cached_data = array();

	private function get_woocommerce_wpml(){
		return $this->getMockBuilder( 'woocommerce_wpml' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_sitepress() {
		return $this->getMockBuilder( ISitePress::class )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	private function get_wpml_post_translations() {
		return $this->getMockBuilder( 'WPML_Post_Translation' )
		            ->disableOriginalConstructor()
		            ->getMock();
	}

	/**
	 * @return WCML_Products
	 */
	private function get_subject( $woocommerce_wpml = false, $sitepress = false, $wpml_post_translations = false, $wpdb = false ){

		if( !$woocommerce_wpml ){
			$woocommerce_wpml = $this->get_woocommerce_wpml();
		}

		if( !$sitepress ){
			$sitepress = $this->get_sitepress();
		}


		if( !$wpml_post_translations ){
			$wpml_post_translations = $this->get_wpml_post_translations();
		}

		if( !$wpdb ){
			$wpdb = $this->get_wpdb();
		}

		$wpml_cache = $this->getMockBuilder( 'WPML_WP_Cache' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'get', 'set' ) )
		                         ->getMock();

		$that = $this;
		$wpml_cache->method( 'get' )->willReturnCallback( function ( $key, $found ) use ( $that ) {
			if ( isset( $that->cached_data[ $key ] ) ) {
				$found = true;

				return $that->cached_data[ $key ];
			} else {
				$found = false;
				return false;
			}
		} );

		$wpml_cache->method( 'set' )->willReturnCallback( function ( $key, $value ) use ( $that ) {
			$that->cached_data[ $key ] = $value;
		} );

		return new WCML_Products( $woocommerce_wpml, $sitepress, $wpml_post_translations, $wpdb, $wpml_cache );
	}

	/**
	 * @test
	 * @group        wpmlcore-6794
	 *
	 * @param bool   $is_shop_manager
	 * @param string $post_type
	 * @param bool   $expected
	 *
	 * @dataProvider dp_it_overrides_translator_when_user_is_translator
	 */
	public function it_overrides_translator_when_user_is_translator( $is_shop_manager, $post_type, $expected ) {
		$post_id         = 5;
		$args['post_id'] = $post_id;
		$is_translator   = true;

		\WP_Mock::userFunction(
			'current_user_can',
			[
				'args'   => [ 'wpml_operate_woocommerce_multilingual' ],
				'times'  => 1,
				'return' => $is_shop_manager,
			]
		);

		\WP_Mock::userFunction(
			'get_post_type',
			[
				'args'   => [ $args['post_id'] ],
				'times'  => '1-',
				'return' => $post_type,
			]
		);

		$mock = \Mockery::mock( 'WCML_Products' )->makePartial();
		$this->assertSame( $expected, $mock->wcml_override_is_translator( $is_translator, 0, $args ) );
	}

	/**
	 * Data provider for it_overrides_translator
	 *
	 * @return array
	 */
	public function dp_it_overrides_translator_when_user_is_translator() {
		return [
			'not shop manager'                          => [ false, null, true ],
			'shop manager, no post_type'                => [ true, null, true ],
			'shop manager, not WC post_type'            => [ true, 'post', true ],
			'shop manager, post_type=product'           => [ true, 'product', true ],
			'shop manager, post_type=product_variation' => [ true, 'product_variation', true ],
			'shop manager, post_type=shop_coupon'       => [ true, 'shop_coupon', true ],
			'shop manager, post_type=shop_order'        => [ true, 'shop_order', true ],
			'shop manager, post_type=shop_order_refund' => [ true, 'shop_order_refund', true ],
		];
	}

	/**
	 * @test
	 * @group        wpmlcore-6794
	 *
	 * @param bool   $is_shop_manager
	 * @param string $post_type
	 * @param bool   $expected
	 *
	 * @dataProvider dp_it_overrides_translator_when_user_is_NOT_translator
	 */
	public function it_overrides_translator_when_user_is_NOT_translator( $is_shop_manager, $post_type, $expected ) {
		$post_id         = 5;
		$args['post_id'] = $post_id;
		$is_translator   = false;

		\WP_Mock::userFunction(
			'current_user_can',
			[
				'args'   => [ 'wpml_operate_woocommerce_multilingual' ],
				'times'  => 1,
				'return' => $is_shop_manager,
			]
		);

		\WP_Mock::userFunction(
			'get_post_type',
			[
				'args'   => [ $args['post_id'] ],
				'times'  => '1-',
				'return' => $post_type,
			]
		);

		$mock = \Mockery::mock( 'WCML_Products' )->makePartial();
		$this->assertSame( $expected, $mock->wcml_override_is_translator( $is_translator, 0, $args ) );
	}

	/**
	 * Data provider for it_overrides_translator
	 *
	 * @return array
	 */
	public function dp_it_overrides_translator_when_user_is_NOT_translator() {
		return [
			'not shop manager'                          => [ false, null, false ],
			'shop manager, no post_type'                => [ true, null, false ],
			'shop manager, not WC post_type'            => [ true, 'post', false ],
			'shop manager, post_type=product'           => [ true, 'product', true ],
			'shop manager, post_type=product_variation' => [ true, 'product_variation', true ],
			'shop manager, post_type=shop_coupon'       => [ true, 'shop_coupon', true ],
			'shop manager, post_type=shop_order'        => [ true, 'shop_order', true ],
			'shop manager, post_type=shop_order_refund' => [ true, 'shop_order_refund', true ],
		];
	}

	/**
	 * @test
	 * @group wpmlcore-6794
	 */
	public function it_returns_is_translator_when_post_id_is_0() {
		\WP_Mock::userFunction(
			'current_user_can',
			[
				'args'   => [ 'wpml_operate_woocommerce_multilingual' ],
				'times'  => 2,
				'return' => true,
			]
		);

		$args = [ 'post_id' => 0 ];

		$mock = \Mockery::mock( 'WCML_Products' )->makePartial();
		$this->assertTrue( $mock->wcml_override_is_translator( true, 0, $args ) );
		$this->assertTrue( $mock->wcml_override_is_translator( false, 0, $args ) );
	}

	/**
	 * @test
	 */
	public function it_filters_wcml_user_can_translate_for_shop_manager() {
		$user = \Mockery::mock( 'WP_User' );
		\WP_Mock::userFunction(
			'user_can',
			[
				'args'   => [ $user, 'wpml_operate_woocommerce_multilingual' ],
				'times'  => 2,
				'return' => true,
			]
		);

		$mock = \Mockery::mock( 'WCML_Products' )->makePartial();
		$this->assertTrue( $mock->wcml_user_can_translate( false, $user ) );
		$this->assertTrue( $mock->wcml_user_can_translate( true, $user ) );
	}

	/**
	 * @test
	 */
	public function it_filters_wcml_user_can_translate_for_NOT_shop_manager() {
		$user = \Mockery::mock( 'WP_User' );
		\WP_Mock::userFunction(
			'user_can',
			[
				'args'   => [ $user, 'wpml_operate_woocommerce_multilingual' ],
				'times'  => 2,
				'return' => false,
			]
		);

		$mock = \Mockery::mock( 'WCML_Products' )->makePartial();
		$this->assertFalse( $mock->wcml_user_can_translate( false, $user ) );
		$this->assertTrue( $mock->wcml_user_can_translate( true, $user ) );
	}

	/**
	 * @test
	 */
	public function it_adds_frontend_fullmode_hooks(){
		\WP_Mock::userFunction( 'is_admin' )->andReturn( false );
		\WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( false );

		$sitepress = Mockery::mock( ISitePress::class );
		$subject   = $this->get_subject( false, $sitepress );

		// Frontend
		\WP_Mock::expectFilterAdded( 'woocommerce_related_products_args', [ $subject, 'filter_related_products_args' ] );

		// Frontend and full mode
		\WP_Mock::expectFilterAdded( 'woocommerce_json_search_found_products', [ $subject, 'filter_wc_searched_products_on_front' ] );
		\WP_Mock::expectFilterAdded( 'woocommerce_product_related_posts_query', [ $subject, 'filter_related_products_query' ] );
		\WP_Mock::expectFilterAdded( 'woocommerce_shortcode_products_query', [ $subject, 'add_lang_to_shortcode_products_query' ] );
		\WP_Mock::expectFilterAdded( 'woocommerce_product_file_download_path', [ $subject, 'filter_file_download_path' ] );
		\WP_Mock::expectFilterAdded( 'woocommerce_product_add_to_cart_url', [ $subject, 'maybe_add_language_parameter' ] );

		// Full mode
		\WP_Mock::expectFilterAdded( 'woocommerce_upsell_crosssell_search_products', [ $subject, 'filter_woocommerce_upsell_crosssell_posts_by_language' ] );
		\WP_Mock::expectActionAdded( 'woocommerce_after_product_ordering', [ $subject, 'update_all_products_translations_ordering' ] );
		\WP_Mock::expectFilterAdded( 'wpml_copy_from_original_custom_fields', [ $subject, 'filter_excerpt_field_content_copy' ] );
		\WP_Mock::expectFilterAdded( 'wpml_override_is_translator', [ $subject, 'wcml_override_is_translator' ], 10, 3 );
		\WP_Mock::expectFilterAdded( 'wpml_user_can_translate', [ $subject, 'wcml_user_can_translate' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'wc_product_has_unique_sku', [ $subject, 'check_product_sku' ], 10, 3 );
		\WP_Mock::expectFilterAdded( 'get_product_search_form', [ $sitepress, 'get_search_form_filter' ] );
		\WP_Mock::expectFilterAdded( 'woocommerce_pre_customer_bought_product', Fns::withoutRecursion( Fns::identity(), [ $subject, 'is_customer_bought_product' ] ), 10, 4 );

		// All contexts
		\WP_Mock::expectFilterAdded( 'woocommerce_can_reduce_order_stock', [ $subject, 'remove_post_meta_data_filter_on_checkout_stock_update' ] );
		\WP_Mock::expectFilterAdded( 'get_post_metadata', [ $subject, 'filter_product_data' ], 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_adds_backend_fullmode_hooks(){
		\WP_Mock::userFunction( 'is_admin' )->andReturn( true );
		\WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( false );

		$sitepress = Mockery::mock( ISitePress::class );
		$subject   = $this->get_subject( false, $sitepress );

		// Backend
		\WP_Mock::expectFilterAdded( 'woocommerce_json_search_found_products', [ $subject, 'filter_wc_searched_products_on_admin' ] );
		\WP_Mock::expectActionAdded( 'wp_ajax_wpml_switch_post_language', [ $subject, 'switch_product_variations_language' ], 9 );
		\WP_Mock::expectFilterAdded( 'post_row_actions', [ $subject, 'filter_product_actions' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'woocommerce_product_type_query', [ $subject, 'override_product_type_query' ], 10, 2 );

		// All contexts and full mode
		\WP_Mock::expectFilterAdded( 'woocommerce_upsell_crosssell_search_products', [ $subject, 'filter_woocommerce_upsell_crosssell_posts_by_language' ] );
		\WP_Mock::expectActionAdded( 'woocommerce_after_product_ordering', [ $subject, 'update_all_products_translations_ordering' ] );
		\WP_Mock::expectFilterAdded( 'wpml_copy_from_original_custom_fields', [ $subject, 'filter_excerpt_field_content_copy' ] );
		\WP_Mock::expectFilterAdded( 'wpml_override_is_translator', [ $subject, 'wcml_override_is_translator' ], 10, 3 );
		\WP_Mock::expectFilterAdded( 'wpml_user_can_translate', [ $subject, 'wcml_user_can_translate' ], 10, 2 );
		\WP_Mock::expectFilterAdded( 'wc_product_has_unique_sku', [ $subject, 'check_product_sku' ], 10, 3 );
		\WP_Mock::expectFilterAdded( 'get_product_search_form', [ $sitepress, 'get_search_form_filter' ] );
		\WP_Mock::expectFilterAdded( 'woocommerce_pre_customer_bought_product', Fns::withoutRecursion( Fns::identity(), [ $subject, 'is_customer_bought_product' ] ), 10, 4 );

		// All contexts
		\WP_Mock::expectFilterAdded( 'woocommerce_can_reduce_order_stock', [ $subject, 'remove_post_meta_data_filter_on_checkout_stock_update' ] );
		\WP_Mock::expectFilterAdded( 'get_post_metadata', [ $subject, 'filter_product_data' ], 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_adds_frontend_standalone_hooks(){
		\WP_Mock::userFunction( 'is_admin' )->andReturn( false );
		\WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( true );

		$sitepress = Mockery::mock( ISitePress::class );
		$subject   = $this->get_subject( false, $sitepress );

		// Frontend
		\WP_Mock::expectFilterAdded( 'woocommerce_related_products_args', [ $subject, 'filter_related_products_args' ] );

		// Frontend and full mode
		\WP_Mock::expectFilterNotAdded( 'woocommerce_json_search_found_products', [ $subject, 'filter_wc_searched_products_on_front' ] );
		\WP_Mock::expectFilterNotAdded( 'woocommerce_product_related_posts_query', [ $subject, 'filter_related_products_query' ] );
		\WP_Mock::expectFilterNotAdded( 'woocommerce_shortcode_products_query', [ $subject, 'add_lang_to_shortcode_products_query' ] );
		\WP_Mock::expectFilterNotAdded( 'woocommerce_product_file_download_path', [ $subject, 'filter_file_download_path' ] );
		\WP_Mock::expectFilterNotAdded( 'woocommerce_product_add_to_cart_url', [ $subject, 'maybe_add_language_parameter' ] );

		// Full mode
		\WP_Mock::expectFilterNotAdded( 'woocommerce_upsell_crosssell_search_products', [ $subject, 'filter_woocommerce_upsell_crosssell_posts_by_language' ] );
		\WP_Mock::expectActionNotAdded( 'woocommerce_after_product_ordering', [ $subject, 'update_all_products_translations_ordering' ] );
		\WP_Mock::expectFilterNotAdded( 'wpml_copy_from_original_custom_fields', [ $subject, 'filter_excerpt_field_content_copy' ] );
		\WP_Mock::expectFilterNotAdded( 'wpml_override_is_translator', [ $subject, 'wcml_override_is_translator' ], 10, 3 );
		\WP_Mock::expectFilterNotAdded( 'wpml_user_can_translate', [ $subject, 'wcml_user_can_translate' ], 10, 2 );
		\WP_Mock::expectFilterNotAdded( 'wc_product_has_unique_sku', [ $subject, 'check_product_sku' ], 10, 3 );
		\WP_Mock::expectFilterNotAdded( 'get_product_search_form', [ $sitepress, 'get_search_form_filter' ] );
		\WP_Mock::expectFilterNotAdded( 'woocommerce_pre_customer_bought_product', Fns::withoutRecursion( Fns::identity(), [ $subject, 'is_customer_bought_product' ] ), 10, 4 );

		// All contexts
		\WP_Mock::expectFilterAdded( 'woocommerce_can_reduce_order_stock', [ $subject, 'remove_post_meta_data_filter_on_checkout_stock_update' ] );
		\WP_Mock::expectFilterAdded( 'get_post_metadata', [ $subject, 'filter_product_data' ], 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function it_adds_backend_standalone_hooks(){
		\WP_Mock::userFunction( 'is_admin' )->andReturn( true );
		\WP_Mock::userFunction( 'WCML\functions\isStandAlone' )->andReturn( true );

		$sitepress = Mockery::mock( ISitePress::class );
		$subject   = $this->get_subject( false, $sitepress );

		// Backend
		\WP_Mock::expectFilterNotAdded( 'woocommerce_json_search_found_products', [ $subject, 'filter_wc_searched_products_on_admin' ] );
		\WP_Mock::expectActionNotAdded( 'wp_ajax_wpml_switch_post_language', [ $subject, 'switch_product_variations_language' ], 9 );
		\WP_Mock::expectFilterNotAdded( 'post_row_actions', [ $subject, 'filter_product_actions' ], 10, 2 );
		\WP_Mock::expectFilterNotAdded( 'woocommerce_product_type_query', [ $subject, 'override_product_type_query' ], 10, 2 );

		// All contexts and full mode
		\WP_Mock::expectFilterNotAdded( 'woocommerce_upsell_crosssell_search_products', [ $subject, 'filter_woocommerce_upsell_crosssell_posts_by_language' ] );
		\WP_Mock::expectActionNotAdded( 'woocommerce_after_product_ordering', [ $subject, 'update_all_products_translations_ordering' ] );
		\WP_Mock::expectFilterNotAdded( 'wpml_copy_from_original_custom_fields', [ $subject, 'filter_excerpt_field_content_copy' ] );
		\WP_Mock::expectFilterNotAdded( 'wpml_override_is_translator', [ $subject, 'wcml_override_is_translator' ], 10, 3 );
		\WP_Mock::expectFilterNotAdded( 'wpml_user_can_translate', [ $subject, 'wcml_user_can_translate' ], 10, 2 );
		\WP_Mock::expectFilterNotAdded( 'wc_product_has_unique_sku', [ $subject, 'check_product_sku' ], 10, 3 );
		\WP_Mock::expectFilterNotAdded( 'get_product_search_form', [ $sitepress, 'get_search_form_filter' ] );
		\WP_Mock::expectFilterNotAdded( 'woocommerce_pre_customer_bought_product', Fns::withoutRecursion( Fns::identity(), [ $subject, 'is_customer_bought_product' ] ), 10, 4 );

		// All contexts
		\WP_Mock::expectFilterAdded( 'woocommerce_can_reduce_order_stock', [ $subject, 'remove_post_meta_data_filter_on_checkout_stock_update' ] );
		\WP_Mock::expectFilterAdded( 'get_post_metadata', [ $subject, 'filter_product_data' ], 10, 3 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function add_lang_to_shortcode_products_query(){

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language' ) )
		                  ->getMock();
		$sitepress->method( 'get_current_language' )
			->wilLReturn( $this->default_language );

		$subject = $this->get_subject( false, $sitepress );

		$query_args = array();

		$product_query_args = $subject->add_lang_to_shortcode_products_query( $query_args );

		$this->assertEquals( $this->default_language, $product_query_args[ 'lang' ] );

	}

	/**
	 * @test
	 */
	public function get_original_product_id(){
		$product_id          = 456;
		$original_product_id = 123;

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( [ 'get_original_element' ] )
		                               ->getMock();

		$wpml_post_translations->method( 'get_original_element' )->with( $product_id )->willReturn( $original_product_id );

		$subject = $this->get_subject( false, false, $wpml_post_translations );
		$subject_original_product_id = $subject->get_original_product_id( $product_id );

		$this->assertEquals( $original_product_id, $subject_original_product_id );
	}

	/**
	 * @test
	 */
	public function get_original_product_id_in_standalone_mode(){
		$product_id = 456;

		$subject = new WCML_Products( $this->get_woocommerce_wpml(), $this->get_sitepress(), null, $this->get_wpdb() );

		$this->assertEquals( $product_id, $subject->get_original_product_id( $product_id ) );
	}

	/**
	 * @test
	 */
	public function is_downloadable_simple_product() {

		$product_id = rand( 1, 100 );

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array(
			                'get_id',
			                'is_downloadable',
			                'get_available_variations'
		                ) )
		                ->getMock();

		$product->method( 'get_id' )->willReturn( $product_id );
		$product->method( 'is_downloadable' )->willReturn( true );

		$subject = $this->get_subject();

		$this->assertTrue( $subject->is_downloadable_product( $product ) );
	}

	/**
	 * @test
	 */
	public function is_downloadable_variable_product(){

		$product_id = rand( 1, 100 );
		$available_variations = array(
			array(
				'variation_id' => 12,
				'is_downloadable' => false
			),
			array(
				'variation_id' => 15,
				'is_downloadable' => true
			)
		);

		/** @var WC_Product|\PHPUnit_Framework_MockObject_MockObject $product */
		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array(
			                'get_id', 'is_downloadable', 'get_available_variations'
		                ) )
		                ->getMock();

		$product->method( 'get_id' )->willReturn( $product_id );
		$product->method( 'is_downloadable' )->willReturn( false );
		$product->method( 'get_available_variations' )->willReturn( $available_variations );


		\WP_Mock::userFunction( 'wp_cache_get', array(
			'args'   => array( $product_id, 'is_variable_product' ),
			'return' => true
		) );

		$subject = $this->get_subject( );

		$this->assertTrue( $subject->is_downloadable_product( $product ) );

	}

	/**
	 * @test
	 */
	public function filter_file_download_path_default() {

		$file_path              = rand_str();

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'constant' ) )
		               ->getMock();
		$wp_api->method( 'constant' )
		       ->with( 'WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN' )
		       ->willReturn( 1 );

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_wp_api', 'get_setting', 'convert_url' ) )
		                  ->getMock();
		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		$subject            = $this->get_subject( false, $sitepress );
		$filtered_file_path = $subject->filter_file_download_path( $file_path );

		$this->assertEquals( $file_path, $filtered_file_path );

	}

	/**
	 * @test
	 */
	public function filter_file_download_path_per_domain(){

		$negotation_type_domain = mt_rand( 1, 10);
		$home_url = rand_str( 5 );
		$file_path = $home_url.rand_str();
		$converted_file_path = rand_str();

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'constant' ) )
		               ->getMock();
		$wp_api->method( 'constant' )
		             ->with( 'WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN' )
		             ->willReturn( $negotation_type_domain );

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_wp_api', 'get_setting', 'convert_url' ) )
		                  ->getMock();
		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );


		$sitepress->expects( $this->once() )
		                ->method( 'get_setting' )
		                ->with( 'language_negotiation_type' )
		                ->willReturn( $negotation_type_domain );

		$sitepress->method( 'convert_url' )
		                ->with( $file_path )
		                ->willReturn( $converted_file_path );

		$url_helper_mock = \Mockery::mock( 'overload:WPML_URL_Converter_Url_Helper' );
		$url_helper_mock->shouldReceive( 'get_abs_home' )->andReturn( $home_url );

		$subject = $this->get_subject( false, $sitepress );
		$filtered_file_path = $subject->filter_file_download_path( $file_path );

		$this->assertEquals( $converted_file_path, $filtered_file_path );
	}

	/**
	 * @test
	 */
	public function it_does_not_filter_not_home_site_file_download_path_per_domain(){

		$negotation_type_domain = mt_rand( 1, 10);
		$home_url = rand_str( 5 );
		$file_path = rand_str();

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'constant' ) )
		               ->getMock();
		$wp_api->method( 'constant' )
		       ->with( 'WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN' )
		       ->willReturn( $negotation_type_domain );

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_wp_api', 'get_setting' ) )
		                  ->getMock();
		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		$sitepress->expects( $this->once() )
		                ->method( 'get_setting' )
		                ->with( 'language_negotiation_type' )
		                ->willReturn( $negotation_type_domain );

		$url_helper_mock = \Mockery::mock( 'overload:WPML_URL_Converter_Url_Helper' );
		$url_helper_mock->shouldReceive( 'get_abs_home' )->andReturn( $home_url );

		$subject = $this->get_subject( false, $sitepress );
		$filtered_file_path = $subject->filter_file_download_path( $file_path );

		$this->assertEquals( $file_path, $filtered_file_path );
	}

	/**
	 * @test
	 * @group wcml-3339
	 */
	public function is_customer_bought_product_already_true() {
		$this->assertTrue( $this->get_subject()->is_customer_bought_product( true, 'john@doe.com', 159, 456 ) );
	}

	/**
	 * @test
	 * @group wcml-3339
	 */
	public function is_customer_bought_product_in_original_or_translation() {
		$user_email          = 'john@doe.com';
		$user_id             = 159;
		$trid                = 99;
		$product_id          = 456;
		$original_product_id = 123;
		$original_language   = null;
		$post_type           = 'product';

		$translations = [
			(object) [ 'element_id' => $product_id ],
			(object) [ 'element_id' => $original_product_id ],
			(object) [ 'element_id' => 789 ],
		];

		WP_Mock::userFunction( 'get_post_type' )
			->with( $product_id )
			->andReturn( $post_type );

		WP_Mock::onFilter( 'wpml_element_trid' )
		       ->with( 0, $product_id, 'post_'. $post_type )
		       ->reply( $trid );

		WP_Mock::onFilter( 'wpml_get_element_translations' )
		       ->with( [], $trid, $post_type )
		       ->reply( $translations );

		WP_Mock::userFunction( 'wc_customer_bought_product', [
			'args'   => [ $user_email, $user_id, $product_id ],
			'return' => false
		] );

		WP_Mock::userFunction( 'wc_customer_bought_product', [
			'args'   => [ $user_email, $user_id, $original_product_id ],
			'return' => true
		] );

		$this->assertTrue( $this->get_subject()->is_customer_bought_product( false, $user_email, $user_id, $product_id ) );
	}

	/**
	 * @test
	 * @group wcml-3339
	 */
	public function is_customer_bought_product_returns_false_if_NOT_in_original_NOR_translation() {
		$user_email          = 'john@doe.com';
		$user_id             = 159;
		$trid                = 99;
		$product_id          = 456;
		$original_product_id = 123;
		$original_language   = null;
		$post_type           = 'product';

		$translations = [
			(object) [ 'element_id' => $product_id ],
			(object) [ 'element_id' => $original_product_id ],
		];

		WP_Mock::userFunction( 'get_post_type' )
			->with( $product_id )
			->andReturn( $post_type );

		WP_Mock::onFilter( 'wpml_element_trid' )
		       ->with( 0, $product_id, 'post_'. $post_type )
		       ->reply( $trid );

		WP_Mock::onFilter( 'wpml_get_element_translations' )
		       ->with( [], $trid, $post_type )
		       ->reply( $translations );

		WP_Mock::userFunction( 'wc_customer_bought_product', [
			'args'   => [ $user_email, $user_id, $product_id ],
			'return' => false
		] );

		WP_Mock::userFunction( 'wc_customer_bought_product', [
			'args'   => [ $user_email, $user_id, $original_product_id ],
			'return' => false
		] );

		$this->assertFalse( $this->get_subject()->is_customer_bought_product( false, $user_email, $user_id, $product_id ) );
	}

	/**
	 * @test
	 */
	public function it_filter_product_data(){

		\WP_Mock::passthruFunction( 'remove_filter' );
		$product_id = 111;

		$expected_data = array(
			'_price' => array( array( 20 ) ),
			'_wc_review_count' => array( array( 2 ) ),
			'_wc_average_rating' => array( array( 5 ) ),
			'_product_image_gallery' => array( array( '3, 4' ) ),
			'_thumbnail_id' => array( array( 6 ) ),
		);

		WP_Mock::userFunction( 'is_admin', array(
			'times' => 1,
			'return' => false
		));

		WP_Mock::userFunction( 'get_post_meta', array(
			'args'  => array( $product_id ),
			'return' => array()
		));

		WP_Mock::userFunction( 'get_post_meta', array(
			'args'  => array( $product_id, '_price', true ),
			'return' => $expected_data['_price'][0]
		));

		WP_Mock::userFunction( 'get_post_meta', array(
			'args'  => array( $product_id, '_wc_review_count', true ),
			'return' => $expected_data['_wc_review_count'][0]
		));

		WP_Mock::userFunction( 'get_post_meta', array(
			'args'  => array( $product_id, '_wc_average_rating', true ),
			'return' => $expected_data['_wc_average_rating'][0]
		));

		WP_Mock::userFunction( 'get_post_meta', array(
			'args'  => array( $product_id, '_product_image_gallery', true ),
			'return' => $expected_data['_product_image_gallery'][0]
		));

		WP_Mock::userFunction( 'get_post_meta', array(
			'args'  => array( $product_id, '_thumbnail_id', true ),
			'return' => $expected_data['_thumbnail_id'][0]
		));


		WP_Mock::userFunction( 'get_post_type', array(
			'args'  => array( $product_id ),
			'return' => 'product'
		));

		WP_Mock::userFunction( 'wcml_price_custom_fields', array(
			'return' => function ( $object_id ) {
				$default_keys = array(
					'_price'
				);

				return apply_filters( 'wcml_price_custom_fields', $default_keys, $object_id );
			}
		) );

		$enable_multi_currency = 1;

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'constant' ) )
		               ->getMock();
		$wp_api->method( 'constant' )
		       ->with( 'WCML_MULTI_CURRENCIES_INDEPENDENT' )
		       ->willReturn( $enable_multi_currency );

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_wp_api' ) )
		                  ->getMock();
		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		$woocommerce_wpml = $this->get_woocommerce_wpml();
		$woocommerce_wpml->settings[ 'enable_multi_currency' ] = $enable_multi_currency;

		/** @var \WCML_Products|\PHPUnit_Framework_MockObject_MockObject $products */
		$products = $this->getMockBuilder( 'WCML_Products' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'is_original_product' ) )
		                 ->getMock();
		$products->method( 'is_original_product' )->with( $product_id )->willReturn( false );

		$woocommerce_wpml->products = $products;

		WP_Mock::userFunction( 'is_product', array(
			'times'  => 1,
			'return' => true
		) );

		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );
		$filtered_data = $subject->filter_product_data( false, $product_id, false );

		$this->assertEquals( $expected_data, $filtered_data );
	}

	/**
	 * @test
	 */
	public function it_should_not_filter_product_data_images_for_original_product(){

		$product_id = 112;

		WP_Mock::passthruFunction( 'remove_filter' );

		WP_Mock::userFunction( 'is_admin', array(
			'times' => 1,
			'return' => false
		));

		WP_Mock::userFunction( 'get_post_type', array(
			'args'  => array( $product_id ),
			'return' => 'product_variation'
		));

		/** @var \WCML_Products|\PHPUnit_Framework_MockObject_MockObject $products */
		$products = $this->getMockBuilder( 'WCML_Products' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'is_original_product' ) )
		                 ->getMock();
		$products->method( 'is_original_product' )->with( $product_id )->willReturn( true );

		$woocommerce_wpml = $this->get_woocommerce_wpml();
		$woocommerce_wpml->products = $products;

		$multi_currency_setting = 1;

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'constant' ) )
		               ->getMock();
		$wp_api->method( 'constant' )
		       ->with( 'WCML_MULTI_CURRENCIES_INDEPENDENT' )
		       ->willReturn( 2 );

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_wp_api' ) )
		                  ->getMock();
		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		$woocommerce_wpml->settings['enable_multi_currency'] = $multi_currency_setting;

		WP_Mock::userFunction( 'get_post_meta', array(
			'args'  => array( $product_id ),
			'return' => array()
		));

		WP_Mock::userFunction( 'get_post_meta', array(
			'times'  => 0,
			'args'   => array( $product_id, '_thumbnail_id', true ),
		) );

		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );
		$filtered_data = $subject->filter_product_data( null, $product_id, false );
	}

	/**
	 * @test
	 */
	public function it_should_filter_related_products_query() {

		$query = array( 'join' => 'test', 'where' => 'test' );

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language' ) )
		                  ->getMock();
		$sitepress->method( 'get_current_language' )
		                ->wilLReturn( $this->default_language );

		$wpdb = $this->get_wpdb();
		$wpdb->method( 'prepare' )
		           ->with( ' AND icl.language_code = %s ', $this->default_language )
		           ->willReturn( ' AND icl.language_code = ' . $this->default_language . ' ' );

		$subject        = $this->get_subject( false, $sitepress, false, $wpdb );
		$filtered_query = $subject->filter_related_products_query( $query );

		$this->assertSame( 'test LEFT JOIN wp_icl_translations AS icl ON icl.element_id = p.ID ', $filtered_query['join'] );
		$this->assertSame( 'test AND icl.language_code = ' . $this->default_language . ' ', $filtered_query['where'] );
	}

	/**
	 * @test
	 */
	public function it_should_NOT_call_wcml_price_custom_fields_if_multicurrency_is_disabled() {
		$product_id = 123;
		$post_type = 'product';
		$multi_currency_setting = 1;
		$meta_key = null;

		WP_Mock::passthruFunction( 'remove_filter' );

		$wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		               ->disableOriginalConstructor()
		               ->setMethods( array( 'constant' ) )
		               ->getMock();
		$wp_api->method( 'constant' )
		       ->with( 'WCML_MULTI_CURRENCIES_INDEPENDENT' )
		       ->willReturn( 2 );

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_wp_api' ) )
		                  ->getMock();
		$sitepress->method( 'get_wp_api' )->willReturn( $wp_api );

		$woocommerce_wpml = $this->get_woocommerce_wpml();
		$woocommerce_wpml->settings['enable_multi_currency'] = $multi_currency_setting;

		/** @var \WCML_Products|\PHPUnit_Framework_MockObject_MockObject $products */
		$products = $this->getMockBuilder( 'WCML_Products' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'is_original_product' ) )
		                 ->getMock();
		$products->method( 'is_original_product' )->with( $product_id )->willReturn( false );

		$woocommerce_wpml->products = $products;

		$subject = $this->get_subject( $woocommerce_wpml, $sitepress );

		WP_Mock::userFunction( 'get_post_meta', array(
			'args'  => array( $product_id ),
			'return' => array()
		));

		WP_Mock::userFunction( 'get_post_type', array(
			'times'  => 1,
			'args'   => array( $product_id ),
			'return' => $post_type,
		) );

		WP_Mock::userFunction( 'get_post_meta', array(
			'times'  => 0,
			'args'   => array( $product_id, '_price', true ),
		) );

		WP_Mock::userFunction( 'is_admin', array(
			'times'  => 1,
			'return' => true
		) );
		WP_Mock::userFunction( 'wcml_price_custom_fields', array( 'times' => 0 ) );

		$subject->filter_product_data( array(), $product_id, $meta_key );
	}

	/**
	 * @test
	 */
	public function it_should_NOT_call_wcml_price_custom_fields_if_the_post_type_is_not_a_product_or_a_product_variation() {
		$product_id = 124;
		$post_type = 'post';
		$meta_key = null;

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'get_post_type', array(
			'times'  => 1,
			'args'   => array( $product_id ),
			'return' => $post_type,
		) );
		WP_Mock::userFunction( 'get_post_meta', array( 'times' => 0 ) );
		WP_Mock::userFunction( 'wcml_price_custom_fields', array( 'times' => 0 ) );

		$subject->filter_product_data( array(), $product_id, $meta_key );
	}

	/**
	 * @test
	 */
	public function it_should_NOT_call_wcml_price_custom_fields_if_the_a_meta_key_is_provided() {
		$product_id = 125;
		$meta_key = 'key';

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'get_post_meta', [ 'times' => 0 ] );
		WP_Mock::userFunction( 'wcml_price_custom_fields', [ 'times' => 0 ] );

		$subject->filter_product_data( [], $product_id, $meta_key );
	}

	/**
	 * @test
	 */
	public function it_should_override_product_type_query_for_product() {
		\WP_Mock::wpPassthruFunction( 'sanitize_title' );

		$product_id = 128;
		$product_type = false;

		$term = new stdClass();
		$term->name = 'grouped';
		$terms = array( $term );

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'get_post_type', array(
			'args' => array( $product_id ),
			'return' => 'product',
			'times' => 1
		) );

		WP_Mock::userFunction( 'get_the_terms', array(
			'args' => array( $product_id, 'product_type' ),
			'return' => $terms,
			'times' => 1
		) );

		$this->assertEquals( $term->name, $subject->override_product_type_query( $product_type, $product_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_override_product_type_query_for_variation() {
		$product_id = 129;
		$product_type = false;

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'get_post_type', array(
			'args' => array( $product_id ),
			'return' => 'product_variation',
			'times' => 1
		) );

		$this->assertFalse( $subject->override_product_type_query( $product_type, $product_id ) );
	}

	/**
	 * @test
	 */
	public function it_should_filter_wc_searched_products_on_front_in_current_language() {

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language' ) )
		                  ->getMock();
		$sitepress->method( 'get_current_language' )->wilLReturn( $this->default_language );

		$this->product_id_in_default_language = 10;
		$this->product_id_in_second_language = 20;

		$found_products = array(
			$this->product_id_in_default_language => 'test default',
			$this->product_id_in_second_language => 'test second'
		);

		$expected_products = array(
			$this->product_id_in_default_language => 'test default'
		);

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_lang_code' ) )
		                               ->getMock();

		$that = $this;
		$wpml_post_translations->method( 'get_element_lang_code' )->willReturnCallback( function ( $product_id ) use ( $that ) {
			if ( $that->product_id_in_default_language === $product_id ) {
				return $that->default_language;
			} elseif ( $that->product_id_in_second_language === $product_id ) {
				return 'fr';
			}
		} );

		$subject = $this->get_subject( false, $sitepress, $wpml_post_translations );

		$this->assertEquals( $expected_products, $subject->filter_wc_searched_products_on_front( $found_products ) );
	}

	/**
	 * @test
	 */
	public function it_should_filter_wc_searched_products_on_admin_in_current_language() {

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( array( 'get_current_language' ) )
		                  ->getMock();
		$sitepress->method( 'get_current_language' )->wilLReturn( $this->default_language );

		$this->product_id_in_default_language = 10;
		$this->product_id_in_second_language = 20;

		$found_products = array(
			$this->product_id_in_default_language => 'test default',
			$this->product_id_in_second_language => 'test second'
		);

		$expected_products = array(
			$this->product_id_in_default_language => 'test default'
		);

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_lang_code' ) )
		                               ->getMock();

		$that = $this;
		$wpml_post_translations->method( 'get_element_lang_code' )->willReturnCallback( function ( $product_id ) use ( $that ) {
			if ( $that->product_id_in_default_language === $product_id ) {
				return $that->default_language;
			} elseif ( $that->product_id_in_second_language === $product_id ) {
				return 'fr';
			}
		} );

		$subject = $this->get_subject( false, $sitepress, $wpml_post_translations );

		$this->assertEquals( $expected_products, $subject->filter_wc_searched_products_on_admin( $found_products ) );
	}

	/**
	 * @test
	 */
	public function it_should_filter_wc_searched_products_on_admin_in_dashboard_order_language() {

		$this->cookie_lang = 'fr';
		$_COOKIE['_wcml_dashboard_order_language'] = $this->cookie_lang;

		$this->product_id_in_dashboard_order_language = 10;
		$this->product_id_in_default_language = 20;

		$found_products = array(
			$this->product_id_in_dashboard_order_language => 'test default',
			$this->product_id_in_default_language => 'test second'
		);

		$expected_products = array(
			$this->product_id_in_dashboard_order_language => 'test default'
		);

		$wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'get_element_lang_code' ) )
		                               ->getMock();

		$that = $this;
		$wpml_post_translations->method( 'get_element_lang_code' )->willReturnCallback( function ( $product_id ) use ( $that ) {
			if ( $that->product_id_in_dashboard_order_language === $product_id ) {
				return $that->cookie_lang;
			} elseif ( $that->product_id_in_default_language === $product_id ) {
				return $that->default_language;
			}
		} );

		$subject = $this->get_subject( false, false, $wpml_post_translations );

		$this->assertEquals( $expected_products, $subject->filter_wc_searched_products_on_admin( $found_products ) );
		unset( $_COOKIE ['_wcml_dashboard_order_language'] );
	}

	/**
	 * @test
	 */
	public function it_should_remove_post_meta_data_filter_on_checkout_stock_update() {

		$_GET['wc-ajax'] = 'checkout';

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'remove_filter',
			array(
				'args' => array( 'get_post_metadata', array( $subject, 'filter_product_data' ), 10 ),
				'times' => 1,
				'return' => true
			)
		);

		$this->assertTrue( $subject->remove_post_meta_data_filter_on_checkout_stock_update( true ) );

		unset( $_GET['wc-ajax'] );
	}

	/**
	 * @test
	 */
	public function it_should_not_remove_post_meta_data_filter_on_non_checkout_stock_update() {

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'remove_filter',
			array(
				'times' => 0,
				'return' => true
			)
		);

		$this->assertTrue( $subject->remove_post_meta_data_filter_on_checkout_stock_update( true ) );
	}

	/**
	 * @test
	 */
	public function it_should_add_language_parameter_to_add_to_cart_url() {

		$lang_as_parameter = 3;
		$current_language  = 'es';
		$url               = rand_str();
		$expected_url      = $url . '&lang=' . $current_language;

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( [ 'get_current_language', 'get_setting', 'get_default_language' ] )
		                  ->getMock();

		$sitepress->method( 'get_current_language' )->willReturn( $current_language );
		$sitepress->method( 'get_default_language' )->willReturn( 'en' );
		$sitepress->method( 'get_setting' )->with( 'language_negotiation_type' )->willReturn( $lang_as_parameter );

		FunctionMocker::replace( 'constant', function( $name ) {
			return 'WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER' === $name ? 3 : null;
		});

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::userFunction( 'get_option',
			[
				'args'   => [ 'woocommerce_enable_ajax_add_to_cart' ],
				'return' => 'no'
			]
		);

		$this->assertEquals( $expected_url, $subject->maybe_add_language_parameter( $url ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_add_language_parameter_to_add_to_cart_url_for_default_language() {

		$lang_as_parameter = 3;
		$current_language  = 'en';
		$url               = rand_str();

		FunctionMocker::replace( 'constant', function( $name ) {
			return 'WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER' === $name ? 3 : null;
		});

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( [ 'get_current_language', 'get_setting', 'get_default_language' ] )
		                  ->getMock();
		$sitepress->method( 'get_current_language' )->willReturn( $current_language );
		$sitepress->method( 'get_default_language' )->willReturn( $current_language );
		$sitepress->method( 'get_setting' )->with( 'language_negotiation_type' )->willReturn( $lang_as_parameter );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::userFunction( 'get_option',
			[
				'args'   => [ 'woocommerce_enable_ajax_add_to_cart' ],
				'return' => 'no'
			]
		);

		$this->assertEquals( $url, $subject->maybe_add_language_parameter( $url ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_add_language_parameter_to_add_to_cart_url_if_lang_not_as_parameter() {

		$url               = rand_str();

		FunctionMocker::replace( 'constant', function( $name ) {
			return 'WPML_LANGUAGE_NEGOTIATION_TYPE_PARAMETER' === $name ? 3 : null;
		});

		$sitepress = $this->getMockBuilder( ISitePress::class )
		                  ->disableOriginalConstructor()
		                  ->setMethods( [ 'get_setting' ] )
		                  ->getMock();
		$sitepress->method( 'get_setting' )->with( 'language_negotiation_type' )->willReturn( 4 );

		$subject = $this->get_subject( false, $sitepress );

		WP_Mock::userFunction( 'get_option',
			[
				'args'   => [ 'woocommerce_enable_ajax_add_to_cart' ],
				'return' => 'no'
			]
		);

		$this->assertEquals( $url, $subject->maybe_add_language_parameter( $url ) );
	}

	/**
	 * @test
	 */
	public function it_should_not_add_language_parameter_to_add_to_cart_url_if_ajax_cart_enabled() {

		$url = rand_str();

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'get_option',
			[
				'args'   => [ 'woocommerce_enable_ajax_add_to_cart' ],
				'return' => 'yes'
			]
		);

		$this->assertEquals( $url, $subject->maybe_add_language_parameter( $url ) );
	}

	/**
	 * @test
	 */
	public function it_should_update_stock_status() {

		$product_id = 1;
		$status     = 'instock';

		WP_Mock::userFunction( 'update_post_meta',
			[
				'args'   => [ $product_id, '_stock_status', $status ],
				'return' => true,
				'times'  => 1
			]
		);

		$wpdb = $this->get_wpdb();
		$wpdb->method( 'prepare' )
		     ->with( 'UPDATE wc_product_meta_lookup SET stock_status = %s WHERE product_id = %d', $status, $product_id )
		     ->willReturn( true );
		$wpdb->method( 'query' )->willReturn( true );

		$subject = $this->get_subject( false, false, false, $wpdb );

		$subject->update_stock_status( $product_id, $status );
	}


	/**
	 * @return wpdb|PHPUnit_Framework_MockObject_MockObject
	 */
	private function get_wpdb() {
		$methods = array(
			'prepare',
			'query',
			'get_results',
			'get_col',
			'get_var',
			'get_row',
			'delete',
			'update',
			'insert',
		);

		$wpdb = $this->getMockBuilder( 'wpdb' )->disableOriginalConstructor()->setMethods( $methods )->getMock();

		/** @noinspection PhpUndefinedFieldInspection */
		$wpdb->blogid             = 1;
		/** @noinspection PhpUndefinedFieldInspection */
		$wpdb->prefix             = 'wp_';
		/** @noinspection PhpUndefinedFieldInspection */
		$wpdb->posts              = 'posts';
		/** @noinspection PhpUndefinedFieldInspection */
		$wpdb->postmeta           = 'post_meta';
		/** @noinspection PhpUndefinedFieldInspection */
		$wpdb->comments           = 'comments';
		/** @noinspection PhpUndefinedFieldInspection */
		$wpdb->commentmeta        = 'comment_meta';
		/** @noinspection PhpUndefinedFieldInspection */
		$wpdb->terms              = 'terms';
		/** @noinspection PhpUndefinedFieldInspection */
		$wpdb->term_taxonomy      = 'term_taxonomy';
		/** @noinspection PhpUndefinedFieldInspection */
		$wpdb->term_relationships = 'term_relationships';
		/** @noinspection PhpUndefinedFieldInspection */
		$wpdb->wc_product_meta_lookup = 'wc_product_meta_lookup';

		return $wpdb;
	}
}

if ( ! class_exists( 'WP_Widget' ) ) {
	/**
	 * Class WP_Widget
	 * Stub for Test_WCML_Products
	 */
	abstract class WP_Widget {

		public function __construct() { /*silence is golden*/
		}

	}
}
