<?php

class Test_WooCommerce_WPML extends OTGS_TestCase {

	private $options = [];
	private $is_admin = false;

	public function setUp() {
		parent::setUp();

		include WCML_PATH . '/inc/constants.php';

		\WP_Mock::wpFunction( 'get_option', array(
			'return' => function ( $option_name ) {
				return isset( $this->options[ $option_name ] ) ? $this->options[ $option_name ] : null;
			},
		) );

		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => function () {
				return $this->is_admin;
			},
		) );

		\WP_Mock::wpFunction( 'trailingslashit', array(
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		) );
		\WP_Mock::wpFunction( 'rest_get_url_prefix', array(
			'return' => function ( ) {
				return 'wp-json';
			},
		) );


	}

	private function get_subject(){
		return new woocommerce_wpml();
	}

	/**
	 * @test
	 */
	public function is_rest_api_request(){

		$subject = $this->get_subject();

		// Part 1
		// backup
		if( isset( $_SERVER['REQUEST_URI'] ) ){
			$req_uri = $_SERVER['REQUEST_URI'];
			unset($_SERVER['REQUEST_URI']);
		}

		// test
		$this->assertFalse( $subject->is_rest_api_request() );

		//restore
		if( isset( $req_uri ) ) {
			$_SERVER['REQUEST_URI'] = $req_uri;
		}


		// Part 2
		// backup
		if( isset( $_SERVER['REQUEST_URI'] ) ){ // backup
			$req_uri = $_SERVER['REQUEST_URI'];
		}
		$_SERVER['REQUEST_URI'] = 'wp-json/wc/';

		// test
		$this->assertTrue( $subject->is_rest_api_request() );

		//restore
		if( isset( $req_uri ) ) {
			$_SERVER['REQUEST_URI'] = $req_uri;
		}


	}


}












