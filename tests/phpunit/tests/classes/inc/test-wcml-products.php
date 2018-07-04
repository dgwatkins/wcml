<?php

class Test_WCML_Products extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;
	/** @var WPML_WP_Cache */
	private $wpml_cache;
	/** @var WPML_WP_API $wp_api */
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
		                     ->setMethods( array( 'constant' ) )
		                     ->getMock();

		$this->sitepress->method( 'get_wp_api' )->willReturn( $this->wp_api );


		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		$this->wpdb = $this->stubs->wpdb();

		$this->wpml_cache = $this->getMockBuilder( 'WPML_WP_Cache' )
		                         ->disableOriginalConstructor()
		                         ->setMethods( array( 'get', 'set' ) )
		                         ->getMock();

		$this->wpml_cache->method( 'get' )->willReturnCallback( function ( $key, $found ) use ( $that ) {
			if ( isset( $that->cached_data[ $key ] ) ) {
				$found = true;

				return $that->cached_data[ $key ];
			} else {
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
		$subject = new WCML_Products( $this->woocommerce_wpml, $this->sitepress, $this->wpdb, $this->wpml_cache );

		return $subject;
	}

	/**
	 * @test
	 */
	public function it_adds_frontend_hooks(){
		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => false,
			'times'  => 1
		) );

		$subject = $this->get_subject();

		\WP_Mock::expectFilterAdded( 'woocommerce_shortcode_products_query', array( $subject, 'add_lang_to_shortcode_products_query' ) );
		\WP_Mock::expectFilterAdded( 'woocommerce_product_file_download_path', array( $subject, 'filter_file_download_path' ) );

		$subject->add_hooks();
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
		$language = rand_str();

		$this->sitepress->method( 'get_current_language' )
			->wilLReturn( $this->default_language );

		\WP_Mock::wpFunction( 'wp_cache_get', array(
			'return' => $language
		) );

		\WP_Mock::wpFunction( 'get_post_type', array(
			'return' => 'product'
		) );

		\WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', false, $language )->reply( $original_product_id );

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

		$product = $this->getMockBuilder( 'WC_Product' )
		                ->disableOriginalConstructor()
		                ->setMethods( array(
			                'get_id', 'is_downloadable', 'get_available_variations'
		                ) )
		                ->getMock();

		$product->method( 'get_id' )->willReturn( $product_id );
		$product->method( 'is_downloadable' )->willReturn( false );


		\WP_Mock::wpFunction( 'wp_cache_get', array(
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

		WP_Mock::userFunction( 'wp_cache_get', array(
			'return' => false
		));

		WP_Mock::userFunction( 'get_post_type', array(
			'args'  => array( $product_id ),
			'return' => 'product'
		));

		\WP_Mock::onFilter( 'translate_object_id' )->with( $product_id, 'product', true, $original_language )->reply( $original_product_id );

		$subject = $this->get_subject();
		$is_customer_bought_product = $subject->is_customer_bought_product( false, $user_email, $user_id, $product_id );

		$this->assertTrue( $is_customer_bought_product );
	}

}
