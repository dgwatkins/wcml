<?php

namespace WCML\Rest\Store;

use WPML\FP\Fns;

/**
 * @group rest/store
 */
class TestHooks extends \OTGS_TestCase {

	public function tearDown() {
		unset( $_SERVER['REQUEST_URI'] );

		parent::tearDown();
	}

	/**
	 * @test
	 *
	 * @return void 
	 */
	public function itAddsHooks() {
		\WP_Mock::userFunction( 'rest_get_url_prefix', [
			'return' => 'wp-json/',
		] );

		\WP_Mock::userFunction( 'trailingslashit', [
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		] );

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => true,
		] );

		\WP_Mock::userFunction( 'WCML\functions\getSetting', [
			'args'   => [ 'reviews_in_all_languages', false ],
			'return' => false,
		] );

		$_SERVER['REQUEST_URI'] = 'wp-json/wc/store/cart';

		$subject = $this->getSubject();

		\WP_Mock::expectActionAdded( 'init', [ $subject, 'initializeSession' ], 0 );
		\WP_Mock::expectActionNotAdded( 'wpml_is_comment_query_filtered', Fns::always( false ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 *
	 * @return void 
	 */
	public function itDoesNotAddHooksWhenNoMulticurrency() {
		\WP_Mock::userFunction( 'rest_get_url_prefix', [
			'return' => 'wp-json/',
		] );

		\WP_Mock::userFunction( 'trailingslashit', [
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		] );

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => false,
		] );

		$_SERVER['REQUEST_URI'] = 'wp-json/wc/store/cart';

		$subject = $this->getSubject();

		\WP_Mock::expectActionNotAdded( 'init', [ $subject, 'initializeSession' ], 0 );

		$subject->add_hooks();
	}

	/**
	 * @test
	 *
	 * @return void 
	 */
	public function itAddsReviewsHookWhenConfigured() {
		\WP_Mock::userFunction( 'rest_get_url_prefix', [
			'return' => 'wp-json/',
		] );

		\WP_Mock::userFunction( 'trailingslashit', [
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		] );

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => false,
		] );

		\WP_Mock::userFunction( 'WCML\functions\getSetting', [
			'args'   => [ 'reviews_in_all_languages', false ],
			'return' => true,
		] );

		$_SERVER['REQUEST_URI'] = 'wp-json/wc/store/cart';

		$subject = $this->getSubject();

		\WP_Mock::expectActionAdded( 'wpml_is_comment_query_filtered', Fns::always( false ) );

		$subject->add_hooks();
	}

	/**
	 * @test
	 *
	 * @return void 
	 */
	public function itDoesNotAddHooksOutsideStoreAPI() {
		\WP_Mock::userFunction( 'rest_get_url_prefix', [
			'return' => 'wp-json/',
		] );

		\WP_Mock::userFunction( 'trailingslashit', [
			'return' => function ( $url ) {
				return rtrim( $url, '/' ) . '/';
			},
		] );

		\WP_Mock::userFunction( 'wcml_is_multi_currency_on', [
			'return' => true,
		] );

		$_SERVER['REQUEST_URI'] = 'wp-json/wc/v3/products';

		$subject = $this->getSubject();

		\WP_Mock::expectActionNotAdded( 'init', [ $subject, 'initializeSession' ], 0 );

		$subject->add_hooks();
	}

	private function getSubject() {
		return new Hooks();
	}

}
