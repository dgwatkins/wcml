<?php
/**
 * Class Test_WCML_REST_API
 * @group wcml-1979
 */
class Test_WCML_REST_API extends OTGS_TestCase {

	/** @var woocommerce_wpml */
	private $woocommerce_wpml;
	/** @var Sitepress */
	private $sitepress;
	/** @var wpdb */
	private $wpdb;

	private $test_data = [
		'post_meta' => [ ]
	];

	public function setUp() {
		parent::setUp();


		$this->sitepress = $this->getMockBuilder( 'SitePress' )
		                        ->disableOriginalConstructor()
		                        ->setMethods( array(
			                        'get_settings',
			                        'save_settings',
			                        'get_element_trid',
			                        'get_element_translations',
		                        ) )
		                        ->getMock();

		$this->woocommerce_wpml = $this->getMockBuilder( 'woocommerce_wpml' )
		                               ->disableOriginalConstructor()
		                               ->getMock();

		\WP_Mock::wpFunction( 'remove_filter', array(
			'return' => function ( $tag, $function_to_remove, $priority ) {
				unset( $GLOBALS['wp_filter'][ $tag ][ $priority ][ 'hardcoded_callback' ] );
			},
		) );

		\WP_Mock::wpFunction( 'get_post_meta', array(
			'return' => function ( $id, $meta, $single ) {
				$value = null;
				if( $meta === '_wcml_custom_prices_status' ){
					if( $id <= 100 ){
						$value = false;
					}else{
						$value = true;
					}
				} elseif( isset( $this->test_data['post_meta'][ $meta ] )) {
					$value = $this->test_data['post_meta'][ $meta ];
				}
				return $value;
			},
		) );

		\WP_Mock::wpFunction( 'update_post_meta', array(
			'return' => function ( $id, $meta, $value ) {
				$this->test_data['post_meta'][ $meta ] = $value;
			},
		) );

		\WP_Mock::wpFunction( 'get_option', array(
			'return' => function ( $option_name ) {
				$value = null;
				if( $option_name === 'woocommerce_currency' ){
					$value = 'EUR';
				}
				return $value;
			},
		) );

		\WP_Mock::wpPassthruFunction('__');

		\WP_Mock::wpFunction( 'get_query_var', array(
			'return' => function ( $var ) {
				return isset( $this->test_data['query_var'][$var] ) ?
					$this->test_data['query_var'][$var] : null;
			},
		) );

		\WP_Mock::wpFunction( 'get_post', array(
			'return' => function ( $id ) {
				if( isset( $this->test_data['posts'][ $id ] )){
					$post = $this->test_data['posts'][ $id ];
					// exception
					if( $id == 4444){
						$post_parent = get_post( $post->post_parent );
						$post->post_title = $post_parent->post_title;
					}
					return $post;
				}
			},
		) );

		\WP_Mock::wpFunction( 'rest_get_url_prefix', array(
			'return' => 'wp-json',
		) );

		$_SERVER['REQUEST_URI'] = rest_get_url_prefix() . '/wc/v1/';

		$this->wpdb = $this->stubs->wpdb();
	}

	public function tearDown() {
		unset( $this->sitepress, $this->woocommerce );
		parent::tearDown();
	}

	/**
	 * @return WCML_REST_API
	 */
	private function get_subject(){
		return new WCML_REST_API();
	}

	/**
	 * @test
	 */
	public function is_rest_api_request(){

		\WP_Mock::wpFunction( 'trailingslashit', array(
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		) );
		\WP_Mock::wpFunction( 'rest_get_url_prefix', array(
			'return' => 'wp-json',
		) );

		$subject = $this->get_subject();

		// Part 1
		if( isset( $_SERVER['REQUEST_URI'] ) ){
			unset($_SERVER['REQUEST_URI']);
		}

		// test
		$this->assertFalse( $subject->is_rest_api_request() );


		// Part 2
		$_SERVER['REQUEST_URI'] = 'wp-json/wc/';
		// test
		$this->assertTrue( $subject->is_rest_api_request() );

	}

	/**
	 * @test
	 * not very useful
	 */
	public function remove_wpml_global_url_filters(){
		$globals_bk = $GLOBALS;

		$tag = 'home_url';
		$priority = '-10';
		$GLOBALS['wp_filter'][$tag][$priority] = ['hardcoded_callback' => 1 ];

		$subject = $this->get_subject();
		$subject->remove_wpml_global_url_filters();

		$this->assertTrue( !isset( $GLOBALS['wp_filter'][ $tag ][ $priority ][ 'hardcoded_callback' ] ) );

		$GLOBALS = $globals_bk;
	}

	/**
	 * @test
	 */
	function test_get_api_request_version() {

		$version = rand( 1, 1000 );

		\WP_Mock::wpFunction( 'trailingslashit', array(
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		) );
		\WP_Mock::wpFunction( 'rest_get_url_prefix', array( 'return' => 'wp-json' ) );

		$_SERVER['REQUEST_URI'] = sprintf( '/wp-json/wc/v%d/', $version );
		$this->assertEquals( $version, WCML_REST_API::get_api_request_version() );

		$_SERVER['REQUEST_URI'] = sprintf( rand_str( 8 ), $version );
		$this->assertEquals( 0, WCML_REST_API::get_api_request_version() );

	}


}












