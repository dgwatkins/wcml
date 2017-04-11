<?php

class Test_Top_Level_Functions extends OTGS_TestCase {

	private $options = array();

	public function setUp(){
		parent::setUp();

		\WP_Mock::wpFunction( 'untrailingslashit', array(
			'return' => function ( $string ) {
				return rtrim( $string, '/' );
			},
		) );

		\WP_Mock::wpFunction( 'plugin_dir_url', array(
			'return' => WP_PLUGIN_DIR . '/' . basename( WCML_PATH ),
		) );


		$that = $this;
		\WP_Mock::wpFunction( 'get_option', array(
			'return' => function( $option_name ) use ($that) {
				return isset( $that->options[ $option_name ] ) ? $that->options[ $option_name ] : null;
			},
		) );

	}

	/**
	 * @test
	 */
	public function main_tests(){

		\WP_Mock::wpFunction( 'is_admin', array(
			'return' => false,
		) );

		\WP_Mock::expectActionAdded( 'wpml_loaded', array( 'woocommerce_wpml', 'instance' ) );
		\WP_Mock::expectActionAdded( 'plugins_loaded', 'wpml_wcml_startup', 10000 );

		$GLOBALS['wp_version'] = '4.7.3';
		include WCML_PATH .'/wpml-woocommerce.php';

	}

	/**
	 * @test
	 */
	public function creates_woocommerce_wpml_object(){
		global $woocommerce_wpml;
		$woocommerce_wpml = new stdClass();

		\WP_Mock::wpFunction( 'did_action', array( 'return' => false ) );
		wpml_wcml_startup();
		$this->assertInstanceOf( 'woocommerce_wpml', $woocommerce_wpml );

	}

	/**
	 * @test
	 */
	public function doesnt_create_woocommerce_wpml_object(){
		global $woocommerce_wpml;
		$woocommerce_wpml = new stdClass();

		\WP_Mock::wpFunction( 'did_action', array( 'return' => true ) );
		wpml_wcml_startup();
		$this->assertNotInstanceOf( 'woocommerce_wpml', $woocommerce_wpml );

	}

	/**
	 * @test
	 */
	public function adds_rest_api_filters() {

		\WP_Mock::wpFunction( 'trailingslashit', array(
			'return' => function ( $string ) {
				return rtrim( $string, '/' ) . '/';
			},
		) );
		\WP_Mock::wpFunction( 'rest_get_url_prefix', array(
			'return' => 'wp-json',
		) );

		// can't mock \woocommerce_wpml::is_rest_api_request
		$_SERVER['REQUEST_URI'] = 'wp-json/wc/';

		$this->expectActionAdded( 'wpml_before_init', array(
			'WCML_REST_API_Support',
			'remove_wpml_global_url_filters'
		), 0 );
		wpml_wcml_startup();

		// not a rest-api request
		$_SERVER['REQUEST_URI'] = 'wp-NOT-json/NOT-wc/';
		$this->expectActionAdded( 'wpml_before_init', array(
			'WCML_REST_API_Support',
			'remove_wpml_global_url_filters'
		), 0, 1, 0 );
		wpml_wcml_startup();


	}


	function expectActionAdded( $action_name, callable $callback, $priority, $args = 1, $times = null ) {
		$intercept = \Mockery::mock( 'intercept' );

		if ( null !== $times ) {
			$intercept->shouldReceive( 'intercepted' )->times( $times );

		} else {
			$intercept->shouldReceive( 'intercepted' )->atLeast()->once();
		}
		/** @var WP_Mock\HookedCallbackResponder $responder */
		$responder = \WP_Mock::onHookAdded( $action_name, 'action' )->with( $callback, $priority, $args );
		$responder->perform( array( $intercept, 'intercepted' ) );
	}

}
