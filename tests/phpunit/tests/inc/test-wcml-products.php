<?php

class Test_WCML_Products extends OTGS_TestCase {

	/** @var \woocommerce_wpml|\PHPUnit_Framework_MockObject_MockObject */
	private $woocommerce_wpml;
	/** @var \WPML_Post_Translation|\PHPUnit_Framework_MockObject_MockObject */
	private $wpml_post_translations;
	/** @var \Sitepress|\PHPUnit_Framework_MockObject_MockObject */
	private $sitepress;
	/** @var \wpdb|\PHPUnit_Framework_MockObject_MockObject */
	private $wpdb;
	/** @var \WPML_WP_Cache|\PHPUnit_Framework_MockObject_MockObject */
	private $wpml_cache;
	/** @var \WPML_WP_API|\PHPUnit_Framework_MockObject_MockObject */
	private $wp_api;

	private $default_language = 'en';
	private $cached_data = array();

	public function setUp() {
		parent::setUp();

		$that = $this;

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array(
			                        'get_current_language', 'get_wp_api', 'get_setting', 'convert_url'
		                        ) )
		                        ->getMock();

		$this->wp_api = $this->getMockBuilder( 'WPML_WP_API' )
		                     ->disableOriginalConstructor()
		                     ->setMethods( array( 'constant', 'version_compare' ) )
		                     ->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );


		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->wpml_post_translations = $this->getMockBuilder( 'WPML_Post_Translation' )
		                                     ->disableOriginalConstructor()
		                                     ->setMethods( array( 'get_original_element', 'get_source_lang_code' ) )
		                                     ->getMock();

		$this->wpdb = $this->get_wpdb();

		$this->wpml_cache = $this->getMockBuilder( 'WPML_WP_Cache' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'get', 'set' ) )
		                         ->getMock();

		$this->wpml_cache->method( 'get' )->willReturnCallback( function ( $key, $found ) use ( $that ) {
			if ( isset( $that->cached_data[ $key ] ) ) {
				$found = true;

				return $that->cached_data[ $key ];
			} else {
				$found = false;
				return false;
			}
		} );

		$this->wpml_cache->method( 'set' )->willReturnCallback( function ( $key, $value ) use ( $that ) {
			$that->cached_data[ $key ] = $value;
		} );

	}

	/**
	 * @return WCML_Products
	 */
	private function get_subject(){
		$subject = new WCML_Products( $this->woocommerce_wpml, $this->sitepress, $this->wpml_post_translations, $this->wpdb, $this->wpml_cache );

		return $subject;
	}

	/**
	 * @test
	 * @dataProvider wc_versions_provider
	 *
	 * @param string $wc_version
	 * @param bool   $version_compare_result
	 */
	public function it_adds_frontend_hooks( $wc_version, $version_compare_result ){
		\WP_Mock::userFunction( 'is_admin', array(
			'return' => false,
			'times'  => 1
		) );

		$this->wp_api->method( 'constant' )
		             ->with( 'WC_VERSION' )
		             ->willReturn( $wc_version );

		$this->wp_api->method( 'version_compare' )
		             ->with( $wc_version, '3.6.0', '>=' )
		             ->willReturn( $version_compare_result );

		$subject = $this->get_subject();

		if( $version_compare_result ){
			\WP_Mock::expectFilterAdded( 'get_post_metadata', array( $subject, 'filter_product_data' ), 10, 3 );
		}else{
			\WP_Mock::expectFilterNotAdded( 'get_post_metadata', array( $subject, 'filter_product_data' ) );
		}

		\WP_Mock::expectFilterAdded( 'woocommerce_shortcode_products_query', array( $subject, 'add_lang_to_shortcode_products_query' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_product_file_download_path', array( $subject, 'filter_file_download_path' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_product_related_posts_query', array( $subject, 'filter_related_products_query' ) );

		$subject->add_hooks();
	}

	public function wc_versions_provider(){
		return array(
			array( '3.5.0', false ),
			array( '3.6.0', true )
		);
	}

	/**
	 * @test
	 */
	public function add_lang_to_shortcode_products_query(){

		$this->sitepress->method( 'get_current_language' )
			->wilLReturn( $this->default_language );

		$subject = $this->get_subject();

		$query_args = array();

		$product_query_args = $subject->add_lang_to_shortcode_products_query( $query_args );

		$this->assertEquals( $this->default_language, $product_query_args[ 'lang' ] );

	}

	/**
	 * @test
	 */
	public function get_original_product_id(){

		$product_id = rand( 1, 100 );
		$original_product_id = rand( 1, 100 );

		$this->wpml_post_translations->method( 'get_original_element' )->with( $product_id )->wilLReturn( $original_product_id );

		$subject = $this->get_subject();
		$subject_original_product_id = $subject->get_original_product_id( $product_id );

		$this->assertEquals( $original_product_id, $subject_original_product_id );

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

		/** @var WC_Product|\PHPUnit_Framework_MockObject_MockObject $product */
		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array(
			                'get_id', 'is_downloadable', 'get_available_variations'
		                ) )
		                ->getMock();

		$product->method( 'get_id' )->willReturn( $product_id );
		$product->method( 'is_downloadable' )->willReturn( false );


		\WP_Mock::userFunction( 'wp_cache_get', array(
			'args'   => array( $product_id, 'is_variable_product' ),
			'return' => true
		) );

		$available_variations = array(
			array(
				'variation_id' => rand_str(),
				'is_downloadable' => true
			)
		);

		$product->method( 'get_available_variations' )->willReturn( $available_variations );

		$subject = $this->get_subject();

		$this->assertTrue( $subject->is_downloadable_product( $product ) );

	}

	/**
	 * @test
	 */
	public function filter_file_download_path_default() {

		$file_path              = rand_str();

		$subject            = $this->get_subject();
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

		$this->wp_api->method( 'constant' )
		             ->with( 'WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN' )
		             ->willReturn( $negotation_type_domain );

		$this->sitepress->expects( $this->once() )
		                ->method( 'get_setting' )
		                ->with( 'language_negotiation_type' )
		                ->willReturn( $negotation_type_domain );

		$this->sitepress->method( 'convert_url' )
		                ->with( $file_path )
		                ->willReturn( $converted_file_path );

		$url_helper_mock = \Mockery::mock( 'overload:WPML_URL_Converter_Url_Helper' );
		$url_helper_mock->shouldReceive( 'get_abs_home' )->andReturn( $home_url );

		$subject = $this->get_subject();
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

		$this->wp_api->method( 'constant' )
		             ->with( 'WPML_LANGUAGE_NEGOTIATION_TYPE_DOMAIN' )
		             ->willReturn( $negotation_type_domain );

		$this->sitepress->expects( $this->once() )
		                ->method( 'get_setting' )
		                ->with( 'language_negotiation_type' )
		                ->willReturn( $negotation_type_domain );

		$url_helper_mock = \Mockery::mock( 'overload:WPML_URL_Converter_Url_Helper' );
		$url_helper_mock->shouldReceive( 'get_abs_home' )->andReturn( $home_url );

		$subject = $this->get_subject();
		$filtered_file_path = $subject->filter_file_download_path( $file_path );

		$this->assertEquals( $file_path, $filtered_file_path );
	}


	/**
	 * @test
	 */
	public function is_customer_bought_product_in_original(){

		\WP_Mock::passthruFunction( 'remove_filter' );
		\WP_Mock::passthruFunction( 'wp_cache_set' );

		$user_email = rand_str();
		$user_id = mt_rand( 1, 10 );
		$product_id = mt_rand( 11, 20 );
		$original_product_id = mt_rand( 21, 30 );
		$original_language = NULL;

		WP_Mock::userFunction( 'wc_customer_bought_product', array(
			'args'  => array( $user_email, $user_id, $original_product_id ),
			'return' => true
		));

		$this->wpml_post_translations->method( 'get_source_lang_code' )->with( $product_id )->wilLReturn( 'en' );
		$this->wpml_post_translations->method( 'get_original_element' )->with( $product_id )->wilLReturn( $original_product_id );

		$subject = $this->get_subject();
		$is_customer_bought_product = $subject->is_customer_bought_product( false, $user_email, $user_id, $product_id );

		$this->assertTrue( $is_customer_bought_product );
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @group wcml_price_custom_fields
	 */
	public function it_filter_product_data(){

		\WP_Mock::passthruFunction( 'remove_filter' );
		$product_id = 111;
		$post_meta = array(
			'_price' => array( 10 ),
			'_wc_review_count' => array( 1 ),
			'_product_image_gallery' => array( '1, 2' ),
			'_thumbnail_id' => array( 5 ),
		);

		$expected_data = array(
			'_price' => array( 20 ),
			'_wc_review_count' => array( 2 ),
			'_product_image_gallery' => array( '3, 4' ),
			'_thumbnail_id' => array( 6 ),
		);

		WP_Mock::userFunction( 'get_post_meta', array(
			'args'  => array( $product_id ),
			'return' => $post_meta
		));

		WP_Mock::userFunction( 'get_post_type', array(
			'args'  => array( $product_id ),
			'return' => 'product'
		));

		WP_Mock::userFunction( 'wcml_price_custom_fields', array(
			'return' => function ( $object_id ) {
				$default_keys = array(
					'_max_variation_price',
					'_max_variation_regular_price',
					'_max_variation_sale_price',
					'_min_variation_price',
					'_min_variation_regular_price',
					'_min_variation_sale_price',
					'_price',
					'_regular_price',
					'_sale_price',
				);

				return apply_filters( 'wcml_price_custom_fields', $default_keys, $object_id );
			}
		) );

		$enable_multi_currency = 1;
		$this->wp_api->method( 'constant' )->with( 'WCML_MULTI_CURRENCIES_INDEPENDENT' )->willReturn( $enable_multi_currency );
		$this->woocommerce_wpml->settings[ 'enable_multi_currency' ] = $enable_multi_currency;

		$this->woocommerce_wpml->multi_currency = $this->getMockBuilder( 'WCML_Multi_Currency' )
		                                              ->disableOriginalConstructor()
		                                              ->getMock();

		/** @var \WCML_Multi_Currency_Prices|\PHPUnit_Framework_MockObject_MockObject $comments */
		$prices     = $this->getMockBuilder( 'WCML_Multi_Currency_Prices' )
															   ->disableOriginalConstructor()
															   ->setMethods( array( 'product_price_filter' ) )
															   ->getMock();
		$prices->method( 'product_price_filter' )->with( $post_meta['_price'][0], $product_id, '_price' , true )->willReturn( $expected_data['_price'][0]);

		$this->woocommerce_wpml->multi_currency->prices = $prices;


		/** @var \WCML_Comments|\PHPUnit_Framework_MockObject_MockObject $comments */
		$comments = $this->getMockBuilder( 'WCML_Comments' )
						 ->disableOriginalConstructor()
						 ->setMethods( array( 'filter_average_rating' ) )
						 ->getMock();
		$comments->method( 'filter_average_rating' )->with( $post_meta['_wc_review_count'][0], $product_id, '_wc_review_count' , true )->willReturn( $expected_data['_wc_review_count'][0]);

		$this->woocommerce_wpml->comments = $comments;

		/** @var \WCML_Products|\PHPUnit_Framework_MockObject_MockObject $products */
		$products = $this->getMockBuilder( 'WCML_Products' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'is_original_product' ) )
		                 ->getMock();
		$products->method( 'is_original_product' )->with( $product_id )->willReturn( false );

		$this->woocommerce_wpml->products = $products;

		$gallery_filter_factory = \Mockery::mock( 'overload:WCML_Product_Gallery_Filter_Factory' );
		$gallery_filter         = $this->getMockBuilder( 'WCML_Product_Gallery_Filter' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'localize_image_ids' ) )
		                               ->getMock();
		$gallery_filter->method( 'localize_image_ids' )->with( null, $product_id, '_product_image_gallery' )->willReturn( $expected_data['_product_image_gallery'][0] );
		$gallery_filter_factory->shouldReceive( 'create' )->andReturn( $gallery_filter );

		$image_filter_factory = \Mockery::mock( 'overload:WCML_Product_Image_Filter_Factory' );
		$image_filter         = $this->getMockBuilder( 'WCML_Product_Image_Filter' )
		                               ->disableOriginalConstructor()
		                               ->setMethods( array( 'localize_image_id' ) )
		                               ->getMock();
		$image_filter->method( 'localize_image_id' )->with( $post_meta['_thumbnail_id'][0], $product_id, '_thumbnail_id' )->willReturn( $expected_data['_thumbnail_id'][0] );
		$image_filter_factory->shouldReceive( 'create' )->andReturn( $image_filter );

		WP_Mock::userFunction( 'is_product', array(
			'times'  => 1,
			'return' => true
		) );

		$subject = $this->get_subject();
		$filtered_data = $subject->filter_product_data( false, $product_id, false );

		$this->assertEquals( $expected_data, $filtered_data );
	}

	/**
	 * @test
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 * @group wcml_price_custom_fields
	 */
	public function it_should_not_filter_product_data_images_for_original_product(){

		\WP_Mock::passthruFunction( 'remove_filter' );
		$product_id = 111;
		$post_meta = array(
			'_product_image_gallery' => array( '1, 2' ),
			'_thumbnail_id' => array( 5 ),
		);

		WP_Mock::userFunction( 'get_post_meta', array(
			'args'  => array( $product_id ),
			'return' => $post_meta
		));

		WP_Mock::userFunction( 'get_post_type', array(
			'args'  => array( $product_id ),
			'return' => 'product'
		));

		WP_Mock::userFunction( 'is_product', array(
			'times'  => 1,
			'return' => true
		) );

		/** @var \WCML_Products|\PHPUnit_Framework_MockObject_MockObject $products */
		$products = $this->getMockBuilder( 'WCML_Products' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'is_original_product' ) )
		                 ->getMock();
		$products->method( 'is_original_product' )->with( $product_id )->willReturn( true );

		$this->woocommerce_wpml->products = $products;

		WP_Mock::userFunction( 'wcml_price_custom_fields', array(
			'times' => 1,
			'args'  => array( $product_id ),
			'return' => array()
		) );

		$subject = $this->get_subject();
		$filtered_data = $subject->filter_product_data( false, $product_id, false );

		$this->assertEquals( $post_meta, $filtered_data );
	}

	/**
	 * @test
	 */
	public function it_should_filter_related_products_query() {

		$query = array( 'join' => 'test', 'where' => 'test' );

		$this->sitepress->method( 'get_current_language' )
		                ->wilLReturn( $this->default_language );

		$this->wpdb->method( 'prepare' )
		           ->with( ' AND icl.language_code = %s ', $this->default_language )
		           ->willReturn( ' AND icl.language_code = ' . $this->default_language . ' ' );

		$subject        = $this->get_subject();
		$filtered_query = $subject->filter_related_products_query( $query );

		$this->assertSame( 'test LEFT JOIN wp_icl_translations AS icl ON icl.element_id = p.ID ', $filtered_query['join'] );
		$this->assertSame( 'test AND icl.language_code = ' . $this->default_language . ' ', $filtered_query['where'] );
	}

	/**
	 * @test
	 * @group wcml_price_custom_fields
	 */
	public function it_should_call_wcml_price_custom_fields() {
		$product_id = 123;
		$post_type = 'product';
		$multi_currency_setting = 2;
		$meta_key = null;

		$this->wp_api->method( 'constant' )->with( 'WCML_MULTI_CURRENCIES_INDEPENDENT' )->willReturn( 2 );
		$this->woocommerce_wpml->settings['enable_multi_currency'] = $multi_currency_setting;


		/** @var \WCML_Products|\PHPUnit_Framework_MockObject_MockObject $products */
		$products = $this->getMockBuilder( 'WCML_Products' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'is_original_product' ) )
		                 ->getMock();
		$products->method( 'is_original_product' )->with( $product_id )->willReturn( false );

		$this->woocommerce_wpml->products = $products;

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'get_post_type', array(
			'times'  => 1,
			'args'   => array( $product_id ),
			'return' => $post_type,
		) );
		WP_Mock::userFunction( 'remove_filter', array(
			'times' => 1,
			'args'  => array(
				'get_post_metadata',
				array( $subject, 'filter_product_data' ),
				10,
			)
		) );
		WP_Mock::userFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( $product_id ),
			'return' => array(),
		) );
		WP_Mock::userFunction( 'wcml_price_custom_fields', array(
			'times' => 1,
			'args'  => array( $product_id ),
		) );
		WP_Mock::userFunction( 'is_product', array(
			'times'  => 1,
			'return' => false
		) );

		$subject->filter_product_data( array(), $product_id, $meta_key );
	}

	/**
	 * @test
	 * @group wcml_price_custom_fields
	 */
	public function it_should_NOT_call_wcml_price_custom_fields_if_multicurrency_is_disabled() {
		$product_id = 123;
		$post_type = 'product';
		$multi_currency_setting = 1;
		$meta_key = null;

		$this->wp_api->method( 'constant' )->with( 'WCML_MULTI_CURRENCIES_INDEPENDENT' )->willReturn( 2 );
		$this->woocommerce_wpml->settings['enable_multi_currency'] = $multi_currency_setting;

		/** @var \WCML_Products|\PHPUnit_Framework_MockObject_MockObject $products */
		$products = $this->getMockBuilder( 'WCML_Products' )
		                 ->disableOriginalConstructor()
		                 ->setMethods( array( 'is_original_product' ) )
		                 ->getMock();
		$products->method( 'is_original_product' )->with( $product_id )->willReturn( false );

		$this->woocommerce_wpml->products = $products;

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'get_post_type', array(
			'times'  => 1,
			'args'   => array( $product_id ),
			'return' => $post_type,
		) );
		WP_Mock::userFunction( 'remove_filter', array(
			'times' => 1,
			'args'  => array(
				'get_post_metadata',
				array( $subject, 'filter_product_data' ),
				10,
			)
		) );
		WP_Mock::userFunction( 'get_post_meta', array(
			'times'  => 1,
			'args'   => array( $product_id ),
			'return' => array(),
		) );

		WP_Mock::userFunction( 'is_product', array(
			'times'  => 1,
			'return' => false
		) );
		WP_Mock::userFunction( 'wcml_price_custom_fields', array( 'times' => 0 ) );

		$subject->filter_product_data( array(), $product_id, $meta_key );
	}

	/**
	 * @test
	 * @group wcml_price_custom_fields
	 */
	public function it_should_NOT_call_wcml_price_custom_fields_if_the_post_type_is_not_a_product_or_a_product_variation() {
		$product_id = 123;
		$post_type = 'post';
		$meta_key = null;

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'get_post_type', array(
			'times'  => 1,
			'args'   => array( $product_id ),
			'return' => $post_type,
		) );
		WP_Mock::userFunction( 'remove_filter', array( 'times' => 0 ) );
		WP_Mock::userFunction( 'get_post_meta', array( 'times' => 0 ) );
		WP_Mock::userFunction( 'wcml_price_custom_fields', array( 'times' => 0 ) );

		$subject->filter_product_data( array(), $product_id, $meta_key );
	}

	/**
	 * @test
	 * @group wcml_price_custom_fields
	 */
	public function it_should_NOT_call_wcml_price_custom_fields_if_the_a_meta_key_is_provided() {
		$product_id = 123;
		$meta_key = null;

		$subject = $this->get_subject();

		WP_Mock::userFunction( 'remove_filter', array( 'times' => 0 ) );
		WP_Mock::userFunction( 'get_post_meta', array( 'times' => 0 ) );
		WP_Mock::userFunction( 'wcml_price_custom_fields', array( 'times' => 0 ) );

		$subject->filter_product_data( array(), $product_id, $meta_key );
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
