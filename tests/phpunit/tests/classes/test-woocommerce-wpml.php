<?php

class Test_WooCommerce_WPML extends OTGS_TestCase {

	public function setUp() {
		parent::setUp();

		include WCML_PATH . '/inc/constants.php';

		\WP_Mock::wpFunction( 'get_option', array(
			'return' => '',
		) );

		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => false,
		) );

		\WP_Mock::wpFunction( 'trailingslashit', array(
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		) );
		\WP_Mock::wpFunction( 'rest_get_url_prefix', array(
			'return' => 'wp-json',
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


}












