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

	private $default_language = 'en';
	private $cached_data = array();

	public function setUp() {
		parent::setUp();

		$that = $this;

		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array(
			                        'get_current_language',
		                        ) )
		                        ->getMock();


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

}
