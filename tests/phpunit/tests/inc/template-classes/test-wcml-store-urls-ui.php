<?php

use tad\FunctionMocker\FunctionMocker;
use WPML\Core\Twig_Environment;

/**
 * Class Test_WCML_Store_URLs_UI
 *
 * @group wcml-3408
 * @group wcml-3760
 */
class Test_WCML_Store_URLs_UI extends OTGS_TestCase {

	public function tearDown() {
		parent::tearDown();
		WPML_Templates_Factory::$set_twig = null;
	}

	/**
	 * @dataProvider data_get_endpoint_info()
	 * @param array $query_vars
	 * @param array $expected_slugs
	 */
	public function test_get_endpoint_info_only_shows_default_language_slugs( $query_vars, $expected_slugs ) {
		foreach ( $query_vars as $slug => $_ ) {
			WP_Mock::onFilter( 'wpml_get_string_language' )
			->with( '', WCML_Endpoints::STRING_CONTEXT, $slug )
			->reply( in_array( $slug, $expected_slugs, true ) ? 'en' : '' );
		}

		WP_Mock::userFunction(
			'WC',
			[
				'return' => (object) [
					'query' => (object) [ 'query_vars' => $query_vars ],
				],
			]
		);

		WP_Mock::userFunction( 'wp_nonce_field', [ 'return' => 134 ] );

		$twig                             = $this->getMockBuilder( Twig_Environment::class )
			->disableOriginalConstructor()
			->setMethods( [ 'addFunction' ] )
			->getMock();
		WPML_Templates_Factory::$set_twig = $twig;

		$subject       = $this->get_subject();
		$endpoint_info = $subject->get_endpoint_info();
		$this->assertSame( $expected_slugs, array_keys( $endpoint_info ) );
	}

	public function data_get_endpoint_info() {
		$query_var_1 = [
			'order-pay'       => 'order-pay',
			'customer-logout' => 'customer-logout',
		];
		$query_var_2 = [
			'order-pay'          => 'order-pay',
			'customer-logout'    => 'customer-logout',
			'fr-customer-logout' => 'fr-customer-logout',
		];
		$expected    = [
			'order-pay',
			'customer-logout',
		];
		return [
			[ $query_var_1, $expected ],
			[ $query_var_2, $expected ],
		];
	}

	private function get_subject() {
		$url_translation                   = $this->getMockBuilder( WCML_Url_Translation::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_source_slug_language' ] )
			->getMock();
		$woocommerce_wpml                  = $this->getMockBuilder( woocommerce_wpml::class )
			->disableOriginalConstructor()
			->getMock();
		$woocommerce_wpml->url_translation = $url_translation;
		$sitepress                         = $this->getMockBuilder( \WPML\Core\ISitePress::class )
			->disableOriginalConstructor()
			->setMethods( [ 'get_active_languages', 'get_flag_url' ] )
			->getMock();
		$sitepress->method( 'get_active_languages' )->willReturn( [] );
		$sitepress->method( 'get_flag_url' )->willReturn( 'url' );
		return new WCML_Store_URLs_UI( $woocommerce_wpml, $sitepress );
	}
}
