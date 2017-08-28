<?php

/**
 * Class Test_WCML_XDomain_Data
 */
class Test_WCML_XDomain_Data extends OTGS_TestCase {

	public function tearDown() {

		unset(
			$_COOKIE[ 'wp_woocommerce_session_' . COOKIEHASH ],
			$_COOKIE['woocommerce_cart_hash'],
			$_COOKIE['woocommerce_items_in_cart']
		);
		parent::tearDown();
	}

	private function get_wpml_cookie() {
		return $this->getMockBuilder( 'WPML_Cookie' )
		            ->disableOriginalConstructor()
		            ->setMethods( [ 'set_cookie' ] )
		            ->getMock();
	}

	private function get_subject( $cookie_handler = null ) {
		if ( null === $cookie_handler ) {
			$cookie_handler = $this->get_wpml_cookie();
		}

		return new WCML_xDomain_Data( $cookie_handler );
	}

	/**
	 * @test
	 */
	public function hooks_are_added() {
		$subject = $this->get_subject();

		$this->expectFilterAdded( 'wpml_cross_domain_language_data', array( $subject, 'pass_data_to_domain' ), 10 );
		$this->expectActionAdded( 'before_woocommerce_init', array( $subject, 'check_request' ), 10 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 */
	public function pass_data_to_domain() {
		$subject = $this->get_subject();

		$data = [];

		$_COOKIE[ 'wp_woocommerce_session_' . COOKIEHASH ] = rand_str( 32 );
		$_COOKIE['woocommerce_cart_hash']                  = rand_str( 32 );
		$_COOKIE['woocommerce_items_in_cart']              = rand_str( 32 );

		\WP_Mock::userFunction( 'update_option', [ 'times' => 1 ] );

		$data = $subject->pass_data_to_domain( $data );

		$this->assertArrayHasKey( 'wcsid', $data );

	}

	/**
	 * @test
	 */
	public function check_request_after_wpml_3_2_7_without_wc_session() {
		$subject = $this->get_subject();

		\WP_Mock::userFunction( 'has_filter', [
			'times'  => 1,
			'args'   => 'wpml_get_cross_domain_language_data',
			'return' => true
		] );

		\WP_Mock::onFilter( 'wpml_get_cross_domain_language_data' )
		        ->with( [] )
		        ->reply( [] );

		\WP_Mock::userFunction( 'maybe_unserialize', [ 'times' => 0 ] );

		$subject->check_request();

	}

	/**
	 * @test
	 */
	public function check_request_after_wpml_3_2_7_with_wc_session() {
		$cookie_handler = $this->get_wpml_cookie();
		$subject        = $this->get_subject( $cookie_handler );

		\WP_Mock::userFunction( 'has_filter', [
			'times'  => 1,
			'args'   => 'wpml_get_cross_domain_language_data',
			'return' => true
		] );

		$wcsid = rand_str( 32 );
		$data  = [
			'session' => rand_str( 32 ),
			'items'   => rand_str( 32 ),
			'hash'    => rand_str( 32 )
		];
		\WP_Mock::onFilter( 'wpml_get_cross_domain_language_data' )
		        ->with( [] )
		        ->reply( [ 'wcsid' => $wcsid ] );

		\WP_Mock::userFunction( 'maybe_unserialize', [
			'times'  => 1,
			'return' => $data
		] );
		\WP_Mock::userFunction( 'get_option', [
			'times' => 1,
			'args'  => [ 'wcml_session_data_' . $wcsid ]
		] );

		$cookie_handler->expects( $this->exactly( 3 ) )->method( 'set_cookie' );

		\WP_Mock::userFunction( 'delete_option', [
			'times' => 1,
			'args'  => [ 'wcml_session_data_' . $wcsid ]
		] );

		$subject->check_request();

		$this->assertSame( $data['session'], $_COOKIE[ 'wp_woocommerce_session_' . COOKIEHASH ] );
		$this->assertSame( $data['hash'], $_COOKIE['woocommerce_cart_hash'] );
		$this->assertSame( $data['items'], $_COOKIE['woocommerce_items_in_cart'] );

	}

	/**
	 * @test
	 */
	public function check_request_before_wpml_3_2_7_with_wc_session() {
		$cookie_handler = $this->get_wpml_cookie();
		$subject        = $this->get_subject( $cookie_handler );

		$wcsid = rand_str( 32 );
		$_GET['xdomain_data'] = base64_encode( json_encode( [ 'wcsid' => $wcsid ] ) );

		$data  = [
			'session' => rand_str( 32 ),
			'items'   => rand_str( 32 ),
			'hash'    => rand_str( 32 )
		];

		\WP_Mock::userFunction( 'has_filter', [
			'times'  => 1,
			'args'   => 'wpml_get_cross_domain_language_data',
			'return' => false
		] );

		\WP_Mock::userFunction( 'maybe_unserialize', [
			'times'  => 1,
			'return' => $data
		] );
		\WP_Mock::userFunction( 'get_option', [
			'times' => 1,
			'args'  => [ 'wcml_session_data_' . $wcsid ]
		] );

		$cookie_handler->expects( $this->exactly( 3 ) )->method( 'set_cookie' );

		\WP_Mock::userFunction( 'delete_option', [
			'times' => 1,
			'args'  => [ 'wcml_session_data_' . $wcsid ]
		] );

		$subject->check_request();

		$this->assertSame( $data['session'], $_COOKIE[ 'wp_woocommerce_session_' . COOKIEHASH ] );
		$this->assertSame( $data['hash'], $_COOKIE['woocommerce_cart_hash'] );
		$this->assertSame( $data['items'], $_COOKIE['woocommerce_items_in_cart'] );

	}

}